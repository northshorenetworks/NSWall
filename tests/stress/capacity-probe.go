// capacity-probe.go - Quick capacity probe for NATS controller
//
// Finds the actual connection limit for your controller deployment.
// Reports the result in a format suitable for the metrics database.
//
// Usage:
//   go run capacity-probe.go -url nats://controller:4222
//   go run capacity-probe.go -url nats://controller:4222 -json
//
package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"os"
	"runtime"
	"sync"
	"syscall"
	"time"

	"github.com/nats-io/nats.go"
)

var (
	natsURL     = flag.String("url", "nats://localhost:4222", "NATS server URL")
	maxTest     = flag.Int("max", 10000, "Maximum connections to attempt")
	batchSize   = flag.Int("batch", 100, "Connections per batch")
	timeout     = flag.Duration("timeout", 5*time.Second, "Connection timeout")
	jsonOutput  = flag.Bool("json", false, "Output as JSON")
	holdTime    = flag.Duration("hold", 10*time.Second, "Time to hold max connections")
	verbose     = flag.Bool("v", false, "Verbose output")
)

type ProbeResult struct {
	Timestamp         string  `json:"timestamp"`
	ServerURL         string  `json:"server_url"`
	MaxConnections    int     `json:"max_connections"`
	HealthyAtMax      int     `json:"healthy_at_max"`
	FailurePoint      int     `json:"failure_point"`
	FailureReason     string  `json:"failure_reason,omitempty"`
	MemoryUsedMB      float64 `json:"memory_used_mb"`
	TestDurationSecs  float64 `json:"test_duration_secs"`
	ConnectionTimeP50 float64 `json:"connection_time_p50_ms"`
	ConnectionTimeP99 float64 `json:"connection_time_p99_ms"`
	Recommendation    string  `json:"recommendation"`
}

func main() {
	flag.Parse()

	// Try to increase file descriptor limit
	increaseFileLimit()

	result := runProbe()

	if *jsonOutput {
		enc := json.NewEncoder(os.Stdout)
		enc.SetIndent("", "  ")
		enc.Encode(result)
	} else {
		printResult(result)
	}

	// Exit with error if we couldn't connect at all
	if result.MaxConnections == 0 {
		os.Exit(1)
	}
}

func increaseFileLimit() {
	var rLimit syscall.Rlimit
	if err := syscall.Getrlimit(syscall.RLIMIT_NOFILE, &rLimit); err == nil {
		if *verbose {
			log.Printf("Current file limit: %d (max: %d)", rLimit.Cur, rLimit.Max)
		}
		if rLimit.Cur < rLimit.Max {
			rLimit.Cur = rLimit.Max
			syscall.Setrlimit(syscall.RLIMIT_NOFILE, &rLimit)
			if *verbose {
				log.Printf("Increased to: %d", rLimit.Cur)
			}
		}
	}
}

func runProbe() ProbeResult {
	result := ProbeResult{
		Timestamp: time.Now().Format(time.RFC3339),
		ServerURL: *natsURL,
	}

	startTime := time.Now()

	// Test basic connectivity first
	nc, err := nats.Connect(*natsURL, nats.Timeout(*timeout))
	if err != nil {
		result.FailureReason = fmt.Sprintf("Cannot connect to server: %v", err)
		return result
	}
	nc.Close()

	if !*jsonOutput {
		fmt.Printf("Probing NATS controller capacity at %s...\n\n", *natsURL)
	}

	connections := make([]*nats.Conn, 0, *maxTest)
	var connMu sync.Mutex
	var connTimes []time.Duration
	var timeMu sync.Mutex

	lastSuccess := 0
	failurePoint := 0

	for total := *batchSize; total <= *maxTest; total += *batchSize {
		if *verbose || !*jsonOutput {
			fmt.Printf("\rTesting %d connections...", total)
		}

		// Add batch of connections in parallel
		var wg sync.WaitGroup
		batchFailed := 0
		var batchMu sync.Mutex

		for i := len(connections); i < total; i++ {
			wg.Add(1)
			go func(idx int) {
				defer wg.Done()

				start := time.Now()
				nc, err := nats.Connect(*natsURL,
					nats.Name(fmt.Sprintf("probe-%d", idx)),
					nats.Timeout(*timeout),
					nats.MaxReconnects(0), // Don't reconnect during probe
				)
				elapsed := time.Since(start)

				if err != nil {
					batchMu.Lock()
					batchFailed++
					batchMu.Unlock()
					return
				}

				connMu.Lock()
				connections = append(connections, nc)
				connMu.Unlock()

				timeMu.Lock()
				connTimes = append(connTimes, elapsed)
				timeMu.Unlock()
			}(i)
		}

		wg.Wait()

		// Check if too many failed
		if batchFailed > *batchSize/2 {
			failurePoint = total
			result.FailureReason = fmt.Sprintf("Connection failures at %d", total)
			break
		}

		// Verify connections are healthy
		healthy := 0
		connMu.Lock()
		for _, nc := range connections {
			if nc.IsConnected() {
				healthy++
			}
		}
		connMu.Unlock()

		if healthy < len(connections)*90/100 { // Less than 90% healthy
			failurePoint = total
			result.FailureReason = fmt.Sprintf("Health degradation at %d (only %d%% healthy)", total, healthy*100/len(connections))
			break
		}

		lastSuccess = len(connections)

		// Brief pause between batches
		time.Sleep(100 * time.Millisecond)
	}

	if !*jsonOutput {
		fmt.Printf("\r                              \r")
	}

	// Record max connections achieved
	result.MaxConnections = lastSuccess
	result.FailurePoint = failurePoint

	// Count healthy connections at max
	healthy := 0
	for _, nc := range connections {
		if nc.IsConnected() {
			healthy++
		}
	}
	result.HealthyAtMax = healthy

	// Calculate connection time percentiles
	if len(connTimes) > 0 {
		// Simple sorting for percentiles
		for i := 0; i < len(connTimes); i++ {
			for j := i + 1; j < len(connTimes); j++ {
				if connTimes[j] < connTimes[i] {
					connTimes[i], connTimes[j] = connTimes[j], connTimes[i]
				}
			}
		}
		p50 := connTimes[len(connTimes)/2]
		p99 := connTimes[len(connTimes)*99/100]
		result.ConnectionTimeP50 = float64(p50.Microseconds()) / 1000
		result.ConnectionTimeP99 = float64(p99.Microseconds()) / 1000
	}

	// Hold connections briefly to measure stability
	if *holdTime > 0 && len(connections) > 0 {
		if !*jsonOutput {
			fmt.Printf("Holding %d connections for %s to verify stability...\n", len(connections), *holdTime)
		}
		time.Sleep(*holdTime)

		// Recount healthy after hold
		healthy = 0
		for _, nc := range connections {
			if nc.IsConnected() {
				healthy++
			}
		}
		result.HealthyAtMax = healthy
	}

	// Memory usage
	var m runtime.MemStats
	runtime.ReadMemStats(&m)
	result.MemoryUsedMB = float64(m.HeapAlloc) / 1024 / 1024

	result.TestDurationSecs = time.Since(startTime).Seconds()

	// Generate recommendation
	result.Recommendation = generateRecommendation(result)

	// Cleanup
	if !*jsonOutput && *verbose {
		fmt.Printf("Closing %d connections...\n", len(connections))
	}
	for _, nc := range connections {
		nc.Close()
	}

	return result
}

func generateRecommendation(r ProbeResult) string {
	if r.MaxConnections == 0 {
		return "Unable to connect. Check server availability and network."
	}

	// Use 80% of max as safe operating capacity
	safeCapacity := r.MaxConnections * 80 / 100

	if r.MaxConnections >= 5000 {
		return fmt.Sprintf("Excellent! Safe capacity: %d agents. Enterprise-ready.", safeCapacity)
	} else if r.MaxConnections >= 2000 {
		return fmt.Sprintf("Good. Safe capacity: %d agents. Suitable for large deployments.", safeCapacity)
	} else if r.MaxConnections >= 1000 {
		return fmt.Sprintf("Moderate. Safe capacity: %d agents. Consider tuning for larger fleets.", safeCapacity)
	} else if r.MaxConnections >= 500 {
		return fmt.Sprintf("Limited. Safe capacity: %d agents. Increase file descriptors and memory.", safeCapacity)
	} else {
		return fmt.Sprintf("Low capacity: %d agents. Check ulimit -n and NATS server config.", safeCapacity)
	}
}

func printResult(r ProbeResult) {
	fmt.Println()
	fmt.Println("═══════════════════════════════════════════════════════════════")
	fmt.Println("              NATS Controller Capacity Probe Results            ")
	fmt.Println("═══════════════════════════════════════════════════════════════")
	fmt.Println()
	fmt.Printf("  Server:              %s\n", r.ServerURL)
	fmt.Printf("  Timestamp:           %s\n", r.Timestamp)
	fmt.Printf("  Test Duration:       %.1fs\n", r.TestDurationSecs)
	fmt.Println()
	fmt.Println("  ┌─────────────────────────────────────────────────────────┐")
	fmt.Printf("  │  MAXIMUM CONNECTIONS: %-6d                            │\n", r.MaxConnections)
	fmt.Println("  └─────────────────────────────────────────────────────────┘")
	fmt.Println()
	fmt.Printf("  Healthy at max:      %d (%.1f%%)\n", r.HealthyAtMax, float64(r.HealthyAtMax)/float64(r.MaxConnections)*100)
	if r.FailurePoint > 0 {
		fmt.Printf("  Failure point:       %d\n", r.FailurePoint)
		fmt.Printf("  Failure reason:      %s\n", r.FailureReason)
	}
	fmt.Printf("  Connection P50:      %.2fms\n", r.ConnectionTimeP50)
	fmt.Printf("  Connection P99:      %.2fms\n", r.ConnectionTimeP99)
	fmt.Printf("  Client memory:       %.1fMB\n", r.MemoryUsedMB)
	fmt.Println()
	fmt.Println("  Recommendation:")
	fmt.Printf("  → %s\n", r.Recommendation)
	fmt.Println()
	fmt.Println("═══════════════════════════════════════════════════════════════")
	fmt.Println()

	// Output for metrics recording
	fmt.Printf("METRIC: nats.capacity.max=%d\n", r.MaxConnections)
}
