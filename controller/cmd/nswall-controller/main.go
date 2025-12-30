// NSWall Controller
// Central management server for NSWall fleet using NATS messaging
package main

import (
	"context"
	"embed"
	"flag"
	"io/fs"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"

	"github.com/northshorenetworks/nswall/controller/internal/handlers"
	"github.com/northshorenetworks/nswall/controller/internal/nats"
	"github.com/northshorenetworks/nswall/controller/internal/store"
)

//go:embed static/*
var staticFiles embed.FS

var (
	httpAddr    = flag.String("http", ":8080", "HTTP listen address for WebUI/API")
	natsAddr    = flag.String("nats", "0.0.0.0:4222", "NATS server listen address")
	natsCluster = flag.String("cluster", "", "NATS cluster URL for HA (optional)")
	dataDir     = flag.String("data", "/var/db/nswall-controller", "Data directory")
	version     = "2.0.0"
)

func main() {
	flag.Parse()

	log.Printf("NSWall Controller v%s starting...", version)

	// Create data directory
	if err := os.MkdirAll(*dataDir, 0700); err != nil {
		log.Fatalf("Failed to create data directory: %v", err)
	}

	// Initialize store
	agentStore := store.NewAgentStore(*dataDir)

	// Start embedded NATS server
	log.Printf("Starting NATS server on %s", *natsAddr)
	natsServer, err := nats.StartServer(*natsAddr, *natsCluster, *dataDir)
	if err != nil {
		log.Fatalf("Failed to start NATS server: %v", err)
	}
	defer natsServer.Shutdown()

	// Wait for NATS to be ready
	time.Sleep(100 * time.Millisecond)

	// Connect to NATS as client
	natsClient, err := nats.NewClient(natsServer.ClientURL(), agentStore)
	if err != nil {
		log.Fatalf("Failed to connect to NATS: %v", err)
	}
	defer natsClient.Close()

	// Subscribe to agent messages
	if err := natsClient.SubscribeToAgents(); err != nil {
		log.Fatalf("Failed to subscribe to agents: %v", err)
	}

	// Create HTTP router
	r := chi.NewRouter()
	r.Use(middleware.RequestID)
	r.Use(middleware.RealIP)
	r.Use(middleware.Logger)
	r.Use(middleware.Recoverer)
	r.Use(middleware.Timeout(60 * time.Second))

	// API handlers
	h := handlers.NewHandler(agentStore, natsClient, version)

	// API routes
	r.Route("/api/v1", func(r chi.Router) {
		// Health
		r.Get("/health", h.Health)

		// Agents
		r.Route("/agents", func(r chi.Router) {
			r.Get("/", h.ListAgents)
			r.Get("/summary", h.GetSummary)
			r.Get("/{id}", h.GetAgent)
			r.Delete("/{id}", h.DeleteAgent)
			r.Post("/{id}/command", h.SendCommand)
			r.Post("/{id}/config", h.PushConfig)
			r.Get("/{id}/status", h.GetAgentStatus)
		})

		// Broadcast
		r.Post("/broadcast/config", h.BroadcastConfig)
		r.Post("/broadcast/command", h.BroadcastCommand)

		// Events
		r.Get("/events", h.GetEvents)
		r.Get("/events/stream", h.StreamEvents) // SSE

		// Config templates
		r.Route("/templates", func(r chi.Router) {
			r.Get("/", h.ListTemplates)
			r.Post("/", h.CreateTemplate)
			r.Get("/{name}", h.GetTemplate)
			r.Put("/{name}", h.UpdateTemplate)
			r.Delete("/{name}", h.DeleteTemplate)
		})

		// Groups
		r.Route("/groups", func(r chi.Router) {
			r.Get("/", h.ListGroups)
			r.Post("/", h.CreateGroup)
			r.Get("/{name}", h.GetGroup)
			r.Put("/{name}", h.UpdateGroup)
			r.Delete("/{name}", h.DeleteGroup)
			r.Post("/{name}/config", h.PushGroupConfig)
		})
	})

	// WebSocket for real-time updates
	r.Get("/ws", h.WebSocket)

	// Serve static files (WebUI)
	staticFS, _ := fs.Sub(staticFiles, "static")
	fileServer := http.FileServer(http.FS(staticFS))
	r.Handle("/*", fileServer)

	// Start HTTP server
	srv := &http.Server{
		Addr:         *httpAddr,
		Handler:      r,
		ReadTimeout:  10 * time.Second,
		WriteTimeout: 30 * time.Second,
		IdleTimeout:  60 * time.Second,
	}

	go func() {
		log.Printf("HTTP server listening on %s", *httpAddr)
		if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("HTTP server error: %v", err)
		}
	}()

	// Wait for shutdown signal
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down...")
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	srv.Shutdown(ctx)
	log.Println("Controller stopped")
}
