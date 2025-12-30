// NSWall API Server
// Comprehensive REST API for NSWall/NSH network appliance automation
package main

import (
	"context"
	"embed"
	"flag"
	"fmt"
	"io/fs"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/go-chi/chi/v5"
	chimw "github.com/go-chi/chi/v5/middleware"

	"github.com/northshorenetworks/nswall/api/internal/handlers"
	"github.com/northshorenetworks/nswall/api/internal/middleware"
	"github.com/northshorenetworks/nswall/api/internal/services"
)

//go:embed static/*
var staticFiles embed.FS

var (
	listenAddr   = flag.String("listen", "127.0.0.1:8080", "API listen address")
	nshPath      = flag.String("nsh", "/usr/local/bin/nsh", "Path to nsh binary")
	dataDir      = flag.String("data", "/var/db/nswall-api", "Data directory for API state")
	apiKey       = flag.String("apikey", "", "Static API key for authentication (optional)")
	enableAuth   = flag.Bool("auth", false, "Enable authentication (default: false)")
	corsOrigins  = flag.String("cors", "", "Allowed CORS origins (comma-separated)")
	rateLimit    = flag.Int("rate-limit", 100, "Rate limit per minute (0 to disable)")
	readTimeout  = flag.Duration("read-timeout", 10*time.Second, "HTTP read timeout")
	writeTimeout = flag.Duration("write-timeout", 30*time.Second, "HTTP write timeout")

	// Fleet/Controller settings
	controllerURL = flag.String("controller", "", "Controller NATS URL (e.g., nats://controller:4222)")
	agentID       = flag.String("agent-id", "", "Agent ID (auto-generated if not specified)")

	// CI/Testing mode
	mockMode    = flag.Bool("mock", false, "Run in mock mode for CI testing (no OpenBSD dependencies)")
	showVersion = flag.Bool("version", false, "Show version and exit")

	version = "2.0.0"
)

func main() {
	flag.Parse()

	if *showVersion {
		fmt.Printf("NSWall API Server v%s\n", version)
		os.Exit(0)
	}

	log.Printf("NSWall API Server v%s starting...", version)
	if *mockMode {
		log.Printf("Running in MOCK MODE - no OpenBSD dependencies required")
	}
	log.Printf("Listening on %s", *listenAddr)

	// Create data directory
	if err := os.MkdirAll(*dataDir, 0700); err != nil {
		log.Printf("Warning: failed to create data directory: %v", err)
	}

	// Start NATS agent if controller is configured
	var natsAgent *services.NATSAgent
	if *controllerURL != "" {
		aid := *agentID
		if aid == "" {
			aid = getOrCreateAgentID(*dataDir)
		}
		log.Printf("Connecting to controller at %s (agent ID: %s)", *controllerURL, aid)

		var err error
		natsAgent, err = services.NewNATSAgent(*controllerURL, aid, *nshPath)
		if err != nil {
			log.Printf("Warning: failed to connect to controller: %v", err)
		} else {
			if err := natsAgent.Start(); err != nil {
				log.Printf("Warning: failed to start NATS agent: %v", err)
			} else {
				log.Printf("Connected to controller, agent mode active")
			}
		}
	}

	// Create handler with all services
	h := handlers.NewHandler(*nshPath, *dataDir, version, *mockMode)

	// Create router
	r := chi.NewRouter()

	// Base middleware
	r.Use(chimw.RequestID)
	r.Use(chimw.RealIP)
	r.Use(chimw.Logger)
	r.Use(chimw.Recoverer)
	r.Use(chimw.Timeout(60 * time.Second))
	r.Use(middleware.JSONContentType)

	// CORS if configured
	if *corsOrigins != "" {
		origins := splitAndTrim(*corsOrigins, ",")
		r.Use(middleware.CORS(origins))
	}

	// Rate limiting if enabled
	if *rateLimit > 0 {
		rl := middleware.NewRateLimiter(*rateLimit, time.Minute)
		r.Use(rl.Middleware)
	}

	// Metrics handler for Prometheus
	metricsHandler := handlers.NewMetricsHandler(*nshPath)

	// Public routes (no auth required)
	r.Get("/", h.Root)
	r.Get("/health", h.Health)
	r.Get("/metrics", metricsHandler.GetMetrics)
	r.Get("/api/v1", h.Root)

	// Auth routes (login doesn't require auth)
	r.Post("/api/v1/auth/login", h.Login)

	// API v1 routes
	r.Route("/api/v1", func(r chi.Router) {
		// Apply authentication if enabled
		if *enableAuth {
			r.Use(middleware.APIKeyAuth(h.Auth, *apiKey))
		} else if *apiKey != "" {
			// Simple API key auth only
			r.Use(simpleAPIKeyAuth(*apiKey))
		}

		// System endpoints
		r.Route("/system", func(r chi.Router) {
			r.Get("/", h.GetSystemInfo)
			r.Get("/info", h.GetSystemInfo)
			r.Get("/version", h.GetVersion)
			r.Get("/memory", h.GetMemory)
			r.Get("/disks", h.GetDisks)
			r.Get("/logs", h.GetLogs)
		})

		// Command execution
		r.Post("/command", h.RunCommand)

		// Interface endpoints
		r.Route("/interfaces", func(r chi.Router) {
			r.Get("/", h.GetInterfaces)
			r.Get("/{name}", h.GetInterface)
			r.Put("/{name}", h.ConfigureInterface)
			r.Patch("/{name}", h.ConfigureInterface)
		})

		// Routing endpoints
		r.Route("/routes", func(r chi.Router) {
			r.Get("/", h.GetRoutes)
			r.Post("/", h.AddRoute)
			r.Delete("/{destination}", h.DeleteRoute)
		})

		// ARP endpoints
		r.Route("/arp", func(r chi.Router) {
			r.Get("/", h.GetArpTable)
			r.Delete("/{ip}", h.DeleteArpEntry)
			r.Post("/flush", h.FlushArpTable)
		})

		// Firewall (PF) endpoints
		r.Route("/pf", func(r chi.Router) {
			r.Get("/", h.GetPFInfo)
			r.Get("/info", h.GetPFInfo)
			r.Get("/stats", h.GetPFStats)
			r.Get("/rules", h.GetPFRules)
			r.Get("/states", h.GetPFStates)
			r.Delete("/states", h.KillPFStates)
			r.Post("/enable", h.EnablePF)
			r.Post("/disable", h.DisablePF)
			r.Post("/reload", h.ReloadPF)

			// Tables
			r.Route("/tables", func(r chi.Router) {
				r.Get("/", h.GetPFTables)
				r.Get("/{name}", h.GetPFTable)
				r.Post("/{name}", h.AddPFTableAddress)
				r.Delete("/{name}/{address}", h.DeletePFTableAddress)
				r.Post("/{name}/flush", h.FlushPFTable)
			})
		})

		// VPN endpoints
		r.Route("/vpn", func(r chi.Router) {
			// WireGuard
			r.Route("/wireguard", func(r chi.Router) {
				r.Get("/", h.GetWireGuardInterfaces)
				r.Post("/", h.CreateWireGuardInterface)
				r.Get("/{name}", h.GetWireGuardInterface)
				r.Put("/{name}", h.ConfigureWireGuard)
				r.Post("/{name}/peers", h.AddWireGuardPeer)
				r.Delete("/{name}/peers/{publicKey}", h.DeleteWireGuardPeer)
				r.Post("/keygen", h.GenerateWireGuardKey)
			})

			// IKE/IPsec
			r.Get("/ike/sas", h.GetIKESAs)
			r.Get("/ipsec/sas", h.GetIPSecSAs)
		})

		// High Availability endpoints
		r.Route("/ha", func(r chi.Router) {
			// CARP
			r.Route("/carp", func(r chi.Router) {
				r.Get("/", h.GetCARPStatus)
				r.Get("/{name}", h.GetCARPInterface)
				r.Put("/{name}", h.ConfigureCARPInterface)
			})

			// pfsync
			r.Get("/pfsync", h.GetPFSync)
		})

		// DHCP endpoints
		r.Route("/dhcp", func(r chi.Router) {
			r.Get("/leases", h.GetDHCPLeases)
			r.Get("/subnets", h.GetDHCPSubnets)
		})

		// DNS endpoints
		r.Route("/dns", func(r chi.Router) {
			r.Get("/", h.GetDNSConfig)
			r.Get("/config", h.GetDNSConfig)
			r.Post("/flush", h.FlushDNSCache)
		})

		// Service management endpoints
		r.Route("/services", func(r chi.Router) {
			r.Get("/", h.GetServices)
			r.Get("/{name}", h.GetService)
			r.Post("/{name}", h.ServiceAction)
		})

		// Configuration endpoints
		r.Route("/config", func(r chi.Router) {
			r.Get("/", h.GetRunningConfig)
			r.Get("/running", h.GetRunningConfig)
			r.Get("/startup", h.GetStartupConfig)
			r.Get("/diff", h.GetConfigDiff)
			r.Post("/save", h.SaveConfig)
			r.Post("/validate", h.ValidateConfig)
			r.Post("/apply", h.ApplyConfig)

			// Backups
			r.Route("/backups", func(r chi.Router) {
				r.Get("/", h.ListBackups)
				r.Post("/", h.BackupConfig)
				r.Post("/{name}/restore", h.RestoreBackup)
				r.Delete("/{name}", h.DeleteBackup)
			})
		})

		// Auth management endpoints (only when auth is enabled)
		if *enableAuth {
			r.Route("/auth", func(r chi.Router) {
				r.Post("/logout", h.Logout)

				// User management (admin only)
				r.Route("/users", func(r chi.Router) {
					r.Use(middleware.RequireRole(h.Auth, "users:read"))
					r.Get("/", h.GetUsers)

					r.Group(func(r chi.Router) {
						r.Use(middleware.RequireRole(h.Auth, "users:write"))
						r.Post("/", h.CreateUser)
						r.Delete("/{username}", h.DeleteUser)
						r.Post("/{username}/apikey", h.CreateAPIKey)
					})
				})
			})
		}
	})

	// Legacy routes for backwards compatibility
	r.Get("/api/v1/status", h.GetSystemInfo)
	r.Get("/api/v1/version", h.GetVersion)

	// SSE streaming endpoints for live visualization
	// These don't require auth - they stream real-time data
	r.Get("/api/v1/stream/interfaces", h.StreamInterfaces)
	r.Get("/api/v1/stream/pf", h.StreamPFStatus)
	r.Get("/api/v1/stream/pf/states", h.StreamPFStates)
	r.Get("/api/v1/stream/pf/rules", h.StreamPFRules)
	r.Get("/api/v1/stream/system", h.StreamSystem)
	r.Get("/api/v1/stream/throughput", h.StreamInterfaceThroughput)
	r.Get("/api/v1/stream/all", h.StreamAll)

	// Dashboard - serve static files
	staticFS, _ := fs.Sub(staticFiles, "static")
	r.Handle("/static/*", http.StripPrefix("/static/", http.FileServer(http.FS(staticFS))))
	r.Get("/dashboard", func(w http.ResponseWriter, r *http.Request) {
		data, err := staticFiles.ReadFile("static/dashboard.html")
		if err != nil {
			http.Error(w, "Dashboard not found", http.StatusNotFound)
			return
		}
		w.Header().Set("Content-Type", "text/html; charset=utf-8")
		w.Write(data)
	})

	// Server with graceful shutdown
	srv := &http.Server{
		Addr:         *listenAddr,
		Handler:      r,
		ReadTimeout:  *readTimeout,
		WriteTimeout: *writeTimeout,
		IdleTimeout:  60 * time.Second,
	}

	// Start server in goroutine
	go func() {
		log.Printf("API server ready")
		if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("Server error: %v", err)
		}
	}()

	// Wait for interrupt
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down server...")

	// Stop NATS agent if running
	if natsAgent != nil {
		natsAgent.Stop()
	}

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	if err := srv.Shutdown(ctx); err != nil {
		log.Fatalf("Server shutdown error: %v", err)
	}
	log.Println("Server stopped")
}

// getOrCreateAgentID generates or retrieves a persistent agent ID
func getOrCreateAgentID(dataDir string) string {
	idFile := dataDir + "/agent_id"

	// Try to read existing ID
	if data, err := os.ReadFile(idFile); err == nil {
		id := trimString(string(data))
		if id != "" {
			return id
		}
	}

	// Generate new ID based on hostname and random suffix
	hostname, _ := os.Hostname()
	if hostname == "" {
		hostname = "nswall"
	}

	// Generate random suffix
	b := make([]byte, 4)
	if f, err := os.Open("/dev/urandom"); err == nil {
		f.Read(b)
		f.Close()
	}

	id := hostname + "-" + fmt.Sprintf("%x", b)

	// Save for future use
	os.WriteFile(idFile, []byte(id), 0600)

	return id
}

// simpleAPIKeyAuth is a simple API key middleware for when full auth is disabled
func simpleAPIKeyAuth(key string) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			apiKey := r.Header.Get("X-API-Key")
			if apiKey == "" {
				apiKey = r.URL.Query().Get("api_key")
			}

			if apiKey != key {
				w.Header().Set("Content-Type", "application/json")
				w.WriteHeader(http.StatusUnauthorized)
				w.Write([]byte(`{"success":false,"error":"Invalid or missing API key"}`))
				return
			}

			next.ServeHTTP(w, r)
		})
	}
}

func splitAndTrim(s, sep string) []string {
	if s == "" {
		return nil
	}
	parts := make([]string, 0)
	for _, p := range splitString(s, sep) {
		p = trimString(p)
		if p != "" {
			parts = append(parts, p)
		}
	}
	return parts
}

func splitString(s, sep string) []string {
	var result []string
	for len(s) > 0 {
		idx := indexString(s, sep)
		if idx < 0 {
			result = append(result, s)
			break
		}
		result = append(result, s[:idx])
		s = s[idx+len(sep):]
	}
	return result
}

func indexString(s, sep string) int {
	for i := 0; i <= len(s)-len(sep); i++ {
		if s[i:i+len(sep)] == sep {
			return i
		}
	}
	return -1
}

func trimString(s string) string {
	start := 0
	end := len(s)
	for start < end && (s[start] == ' ' || s[start] == '\t') {
		start++
	}
	for end > start && (s[end-1] == ' ' || s[end-1] == '\t') {
		end--
	}
	return s[start:end]
}
