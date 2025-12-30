// Package main - Aggressive NATS client flood test
//
// Spawns as many simulated NSWall agents as possible until the controller
// fails. Can run on Linux to stress test the OpenBSD controller.
//
// Usage:
//   go run nats-client-flood.go -url nats://controller:4222 -max 10000
//
package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"math/rand"
	"os"
	"os/signal"
	"runtime"
	"sync"
	"sync/atomic"
	"syscall"
	"time"

	"github.com/nats-io/nats.go"
)

var (
	natsURL       = flag.String("url", "nats://localhost:4222", "NATS server URL")
	maxClients    = flag.Int("max", 10000, "Maximum clients to spawn")
	batchSize     = flag.Int("batch", 100, "Clients per batch")
	batchDelay    = flag.Duration("batch-delay", 1*time.Second, "Delay between batches")
	statusRate    = flag.Duration("status-rate", 10*time.Second, "Status update interval")
	findLimit     = flag.Bool("find-limit", true, "Keep adding until failure")
	holdTime      = flag.Duration("hold", 5*time.Minute, "Time to hold max connections")
	verbose       = flag.Bool("v", false, "Verbose output")
)

type Stats struct {
	Connected    int64
	Failed       int64
	Disconnected int64
	MessagesSent int64
	Errors       int64
}

var stats Stats
var clients []*nats.Conn
var clientsMu sync.Mutex
var stopping int32

func main() {
	flag.Parse()
	rand.Seed(time.Now().UnixNano())

	log.Printf("╔══════════════════════════════════════════════════════════════╗")
	log.Printf("║           NSWall NATS Client Flood Test                      ║")
	log.Printf("╠══════════════════════════════════════════════════════════════╣")
	log.Printf("║  Target: %-50s  ║", *natsURL)
	log.Printf("║  Max Clients: %-46d  ║", *maxClients)
	log.Printf("║  Batch Size: %-47d  ║", *batchSize)
	log.Printf("║  Status Rate: %-46s  ║", *statusRate)
	log.Printf("╚══════════════════════════════════════════════════════════════╝")
	log.Println()

	// Handle shutdown
	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, syscall.SIGINT, syscall.SIGTERM)
	go func() {
		<-sigCh
		log.Println("\n[!] Shutting down...")
		atomic.StoreInt32(&stopping, 1)
		cleanup()
		os.Exit(0)
	}()

	// Increase file descriptor limits if possible
	var rLimit syscall.Rlimit
	if err := syscall.Getrlimit(syscall.RLIMIT_NOFILE, &rLimit); err == nil {
		log.Printf("[*] Current file limit: %d (max: %d)", rLimit.Cur, rLimit.Max)
		if rLimit.Cur < rLimit.Max {
			rLimit.Cur = rLimit.Max
			syscall.Setrlimit(syscall.RLIMIT_NOFILE, &rLimit)
			log.Printf("[*] Increased to: %d", rLimit.Cur)
		}
	}

	// Start stats reporter
	go statsReporter()

	// Start flooding
	if *findLimit {
		findConnectionLimit()
	} else {
		spawnClients(*maxClients)
	}

	// Hold connections
	if len(clients) > 0 {
		log.Printf("\n[+] Holding %d connections for %s...", len(clients), *holdTime)
		time.Sleep(*holdTime)
	}

	cleanup()
}

func findConnectionLimit() {
	log.Println("[*] Finding connection limit...")
	log.Println()

	consecutiveFailures := 0
	maxConsecutiveFailures := 3
	currentBatch := 0

	for atomic.LoadInt32(&stopping) == 0 {
		currentBatch++
		targetCount := currentBatch * *batchSize

		if targetCount > *maxClients {
			log.Printf("[!] Reached max client limit: %d", *maxClients)
			break
		}

		log.Printf("[*] Batch %d: Adding %d clients (target: %d)...",
			currentBatch, *batchSize, targetCount)

		successCount := spawnBatch(*batchSize)

		if successCount < *batchSize/2 {
			consecutiveFailures++
			log.Printf("[!] Batch mostly failed (%d/%d). Consecutive failures: %d",
				successCount, *batchSize, consecutiveFailures)

			if consecutiveFailures >= maxConsecutiveFailures {
				log.Printf("[!] Hit connection limit at approximately %d clients",
					atomic.LoadInt64(&stats.Connected))
				break
			}
		} else {
			consecutiveFailures = 0
		}

		// Brief pause
		time.Sleep(*batchDelay)

		// Memory check
		var m runtime.MemStats
		runtime.ReadMemStats(&m)
		if m.HeapAlloc > 2*1024*1024*1024 { // 2GB limit
			log.Printf("[!] Memory limit reached: %.2f GB", float64(m.HeapAlloc)/1024/1024/1024)
			break
		}
	}

	log.Printf("\n[+] Final connection count: %d", atomic.LoadInt64(&stats.Connected))
}

func spawnClients(count int) {
	log.Printf("[*] Spawning %d clients...", count)

	batches := count / *batchSize
	for i := 0; i < batches && atomic.LoadInt32(&stopping) == 0; i++ {
		log.Printf("[*] Batch %d/%d...", i+1, batches)
		spawnBatch(*batchSize)
		time.Sleep(*batchDelay)
	}

	// Remaining
	remaining := count % *batchSize
	if remaining > 0 {
		spawnBatch(remaining)
	}
}

func spawnBatch(count int) int {
	var wg sync.WaitGroup
	var successCount int64

	for i := 0; i < count; i++ {
		wg.Add(1)
		go func(clientNum int) {
			defer wg.Done()

			agentID := fmt.Sprintf("agent-%08d-%04x",
				len(clients)+clientNum, rand.Intn(0xFFFF))

			nc, err := nats.Connect(*natsURL,
				nats.Name(agentID),
				nats.Timeout(10*time.Second),
				nats.ReconnectWait(2*time.Second),
				nats.MaxReconnects(3),
				nats.DisconnectErrHandler(func(nc *nats.Conn, err error) {
					atomic.AddInt64(&stats.Disconnected, 1)
					if *verbose && err != nil {
						log.Printf("[-] %s disconnected: %v", agentID, err)
					}
				}),
				nats.ReconnectHandler(func(nc *nats.Conn) {
					if *verbose {
						log.Printf("[+] %s reconnected", agentID)
					}
				}),
				nats.ErrorHandler(func(nc *nats.Conn, sub *nats.Subscription, err error) {
					atomic.AddInt64(&stats.Errors, 1)
				}),
			)

			if err != nil {
				atomic.AddInt64(&stats.Failed, 1)
				if *verbose {
					log.Printf("[-] Failed to connect %s: %v", agentID, err)
				}
				return
			}

			atomic.AddInt64(&stats.Connected, 1)
			atomic.AddInt64(&successCount, 1)

			clientsMu.Lock()
			clients = append(clients, nc)
			clientsMu.Unlock()

			// Start sending status updates
			go sendStatusUpdates(nc, agentID)

		}(i)
	}

	wg.Wait()
	return int(successCount)
}

func sendStatusUpdates(nc *nats.Conn, agentID string) {
	ticker := time.NewTicker(*statusRate + time.Duration(rand.Intn(1000))*time.Millisecond)
	defer ticker.Stop()

	for atomic.LoadInt32(&stopping) == 0 {
		select {
		case <-ticker.C:
			if !nc.IsConnected() {
				return
			}

			status := map[string]interface{}{
				"id":       agentID,
				"hostname": fmt.Sprintf("fw-%s", agentID),
				"version":  "1.0.0",
				"state":    "active",
				"uptime":   rand.Int63n(86400 * 30),
				"last_seen": time.Now().Unix(),
				"load_avg": []float64{
					rand.Float64() * 4,
					rand.Float64() * 4,
					rand.Float64() * 4,
				},
				"memory": map[string]uint64{
					"total": 16 * 1024 * 1024 * 1024,
					"used":  uint64(rand.Intn(12)) * 1024 * 1024 * 1024,
				},
				"pf": map[string]interface{}{
					"enabled": true,
					"states":  rand.Intn(100000),
					"rules":   rand.Intn(1000),
				},
			}

			data, _ := json.Marshal(status)
			subject := fmt.Sprintf("nswall.agent.%s.status", agentID)

			err := nc.Publish(subject, data)
			if err != nil {
				atomic.AddInt64(&stats.Errors, 1)
			} else {
				atomic.AddInt64(&stats.MessagesSent, 1)
			}
		}
	}
}

func statsReporter() {
	ticker := time.NewTicker(5 * time.Second)
	startTime := time.Now()

	for range ticker.C {
		elapsed := time.Since(startTime)
		connected := atomic.LoadInt64(&stats.Connected)
		failed := atomic.LoadInt64(&stats.Failed)
		disconnected := atomic.LoadInt64(&stats.Disconnected)
		messages := atomic.LoadInt64(&stats.MessagesSent)
		errors := atomic.LoadInt64(&stats.Errors)

		msgRate := float64(messages) / elapsed.Seconds()

		var m runtime.MemStats
		runtime.ReadMemStats(&m)

		log.Printf("┌─────────────────────────────────────────────────────────────┐")
		log.Printf("│ Stats @ %s                                      │", elapsed.Round(time.Second))
		log.Printf("├─────────────────────────────────────────────────────────────┤")
		log.Printf("│ Connected: %-8d │ Failed: %-8d │ Disconn: %-8d │", connected, failed, disconnected)
		log.Printf("│ Messages: %-9d │ Errors: %-8d │ Rate: %-7.0f/s │", messages, errors, msgRate)
		log.Printf("│ Memory: %-10.2f MB │ Goroutines: %-6d │                  │",
			float64(m.HeapAlloc)/1024/1024, runtime.NumGoroutine())
		log.Printf("└─────────────────────────────────────────────────────────────┘")
	}
}

func cleanup() {
	log.Printf("[*] Cleaning up %d connections...", len(clients))

	clientsMu.Lock()
	defer clientsMu.Unlock()

	for _, nc := range clients {
		nc.Close()
	}
	clients = nil
}
