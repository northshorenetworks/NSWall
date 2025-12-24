// NSWall API Server
// REST API for NSWall/NSH network appliance automation
package main

import (
	"context"
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/exec"
	"os/signal"
	"strings"
	"syscall"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
)

var (
	listenAddr = flag.String("listen", "127.0.0.1:8080", "API listen address")
	nshPath    = flag.String("nsh", "/usr/local/bin/nsh", "Path to nsh binary")
	apiKey     = flag.String("apikey", "", "API key for authentication (optional)")
	version    = "2.0.0"
)

// Response is a standard API response
type Response struct {
	Success bool        `json:"success"`
	Data    interface{} `json:"data,omitempty"`
	Error   string      `json:"error,omitempty"`
}

// CommandRequest is a request to execute an nsh command
type CommandRequest struct {
	Command string `json:"command"`
}

// CommandResponse is the response from an nsh command
type CommandResponse struct {
	Command string `json:"command"`
	Output  string `json:"output"`
	Exit    int    `json:"exit_code"`
}

func main() {
	flag.Parse()

	log.Printf("NSWall API Server v%s starting...", version)
	log.Printf("Listening on %s", *listenAddr)

	r := chi.NewRouter()

	// Middleware
	r.Use(middleware.Logger)
	r.Use(middleware.Recoverer)
	r.Use(middleware.Timeout(30 * time.Second))
	r.Use(jsonContentType)

	// API key authentication if configured
	if *apiKey != "" {
		r.Use(apiKeyAuth)
	}

	// Routes
	r.Get("/", handleRoot)
	r.Get("/health", handleHealth)

	r.Route("/api/v1", func(r chi.Router) {
		r.Get("/status", handleStatus)
		r.Get("/version", handleVersion)

		// Network information
		r.Get("/interfaces", handleInterfaces)
		r.Get("/interfaces/{name}", handleInterface)
		r.Get("/routes", handleRoutes)
		r.Get("/arp", handleArp)

		// Configuration
		r.Get("/config", handleGetConfig)
		r.Get("/config/running", handleGetConfig)

		// Command execution
		r.Post("/command", handleCommand)

		// Firewall
		r.Get("/pf/rules", handlePfRules)
		r.Get("/pf/states", handlePfStates)
		r.Get("/pf/info", handlePfInfo)
	})

	// Server with graceful shutdown
	srv := &http.Server{
		Addr:         *listenAddr,
		Handler:      r,
		ReadTimeout:  10 * time.Second,
		WriteTimeout: 30 * time.Second,
		IdleTimeout:  60 * time.Second,
	}

	// Start server in goroutine
	go func() {
		if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("Server error: %v", err)
		}
	}()

	// Wait for interrupt
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down server...")
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := srv.Shutdown(ctx); err != nil {
		log.Fatalf("Server shutdown error: %v", err)
	}
	log.Println("Server stopped")
}

// Middleware

func jsonContentType(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		next.ServeHTTP(w, r)
	})
}

func apiKeyAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		key := r.Header.Get("X-API-Key")
		if key == "" {
			key = r.URL.Query().Get("api_key")
		}
		if key != *apiKey {
			writeError(w, http.StatusUnauthorized, "Invalid or missing API key")
			return
		}
		next.ServeHTTP(w, r)
	})
}

// Handlers

func handleRoot(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, map[string]interface{}{
		"name":    "NSWall API",
		"version": version,
		"docs":    "/api/v1",
	})
}

func handleHealth(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, map[string]string{"status": "ok"})
}

func handleVersion(w http.ResponseWriter, r *http.Request) {
	// Get nsh version
	output, _ := runNshCommand("show version")
	writeJSON(w, map[string]interface{}{
		"api_version": version,
		"nsh_version": strings.TrimSpace(output),
	})
}

func handleStatus(w http.ResponseWriter, r *http.Request) {
	hostname, _ := os.Hostname()

	// Get uptime via sysctl
	uptime, _ := exec.Command("sysctl", "-n", "kern.boottime").Output()

	writeJSON(w, map[string]interface{}{
		"hostname": hostname,
		"uptime":   strings.TrimSpace(string(uptime)),
		"time":     time.Now().UTC().Format(time.RFC3339),
	})
}

func handleInterfaces(w http.ResponseWriter, r *http.Request) {
	output, err := runNshCommand("show interface")
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, CommandResponse{
		Command: "show interface",
		Output:  output,
		Exit:    0,
	})
}

func handleInterface(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	output, err := runNshCommand(fmt.Sprintf("show interface %s", name))
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, CommandResponse{
		Command: fmt.Sprintf("show interface %s", name),
		Output:  output,
		Exit:    0,
	})
}

func handleRoutes(w http.ResponseWriter, r *http.Request) {
	output, err := runNshCommand("show route")
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, CommandResponse{
		Command: "show route",
		Output:  output,
		Exit:    0,
	})
}

func handleArp(w http.ResponseWriter, r *http.Request) {
	output, err := runNshCommand("show arp")
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, CommandResponse{
		Command: "show arp",
		Output:  output,
		Exit:    0,
	})
}

func handleGetConfig(w http.ResponseWriter, r *http.Request) {
	output, err := runNshCommand("show running-config")
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, CommandResponse{
		Command: "show running-config",
		Output:  output,
		Exit:    0,
	})
}

func handleCommand(w http.ResponseWriter, r *http.Request) {
	var req CommandRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if req.Command == "" {
		writeError(w, http.StatusBadRequest, "Command is required")
		return
	}

	// Security: Block dangerous commands
	dangerous := []string{"reload", "halt", "!", "shell"}
	cmdLower := strings.ToLower(req.Command)
	for _, d := range dangerous {
		if strings.HasPrefix(cmdLower, d) {
			writeError(w, http.StatusForbidden, "Command not allowed via API")
			return
		}
	}

	output, err := runNshCommand(req.Command)
	exitCode := 0
	if err != nil {
		exitCode = 1
	}

	writeJSON(w, CommandResponse{
		Command: req.Command,
		Output:  output,
		Exit:    exitCode,
	})
}

func handlePfRules(w http.ResponseWriter, r *http.Request) {
	output, _ := exec.Command("pfctl", "-sr").Output()
	writeJSON(w, map[string]string{
		"rules": string(output),
	})
}

func handlePfStates(w http.ResponseWriter, r *http.Request) {
	output, _ := exec.Command("pfctl", "-ss").Output()
	writeJSON(w, map[string]string{
		"states": string(output),
	})
}

func handlePfInfo(w http.ResponseWriter, r *http.Request) {
	output, _ := exec.Command("pfctl", "-si").Output()
	writeJSON(w, map[string]string{
		"info": string(output),
	})
}

// Helpers

func runNshCommand(command string) (string, error) {
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	cmd := exec.CommandContext(ctx, *nshPath, "-c", command)
	output, err := cmd.CombinedOutput()
	return string(output), err
}

func writeJSON(w http.ResponseWriter, data interface{}) {
	resp := Response{Success: true, Data: data}
	json.NewEncoder(w).Encode(resp)
}

func writeError(w http.ResponseWriter, status int, message string) {
	w.WriteHeader(status)
	resp := Response{Success: false, Error: message}
	json.NewEncoder(w).Encode(resp)
}
