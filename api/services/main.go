// NSWall Services API Server
// Copyright (c) 2024 North Shore Networks
//
// This package provides a REST API for managing OpenBSD system services
// on NSWall systems including routing protocols, VPN, DNS, mail, and more.

package main

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"sync"
)

const (
	defaultPort = ":8081"
	configDir   = "/var/run"
	etcDir      = "/etc"
)

// Service configuration paths
var serviceConfigs = map[string]ServiceConfig{
	"unbound": {
		Name:       "unbound",
		Daemon:     "/usr/sbin/unbound",
		Control:    "/usr/sbin/unbound-control",
		ConfigFile: "/var/run/unbound.conf",
		PidFile:    "/var/run/unbound.pid",
		ConfigTemplate: `server:
	interface: 127.0.0.1
	access-control: 127.0.0.0/8 allow
	do-ip6: no
	hide-identity: yes
	hide-version: yes
`,
	},
	"httpd": {
		Name:       "httpd",
		Daemon:     "/usr/sbin/httpd",
		ConfigFile: "/var/run/httpd.conf",
		ConfigTemplate: `server "default" {
	listen on * port 80
	root "/htdocs"
}
`,
	},
	"iked": {
		Name:       "iked",
		Daemon:     "/sbin/iked",
		Control:    "/usr/sbin/ikectl",
		ConfigFile: "/var/run/iked.conf",
		ConfigTemplate: `# IKEv2 configuration
`,
	},
	"rad": {
		Name:       "rad",
		Daemon:     "/usr/sbin/rad",
		ConfigFile: "/var/run/rad.conf",
		ConfigTemplate: `# Router Advertisement Daemon
`,
	},
	"smtpd": {
		Name:       "smtpd",
		Daemon:     "/usr/sbin/smtpd",
		Control:    "/usr/sbin/smtpctl",
		ConfigFile: "/var/run/smtpd.conf",
		ConfigTemplate: `table aliases file:/etc/mail/aliases

listen on lo0

action "local" mbox alias <aliases>
action "relay" relay

match from local for local action "local"
match from local for any action "relay"
`,
	},
	"acme": {
		Name:       "acme",
		Daemon:     "/usr/sbin/acme-client",
		ConfigFile: "/var/run/acme-client.conf",
		ConfigTemplate: `authority letsencrypt {
	api url "https://acme-v02.api.letsencrypt.org/directory"
	account key "/etc/acme/letsencrypt-privkey.pem"
}

authority letsencrypt-staging {
	api url "https://acme-staging-v02.api.letsencrypt.org/directory"
	account key "/etc/acme/letsencrypt-staging-privkey.pem"
}
`,
	},
	"ldpd": {
		Name:       "ldpd",
		Daemon:     "/usr/sbin/ldpd",
		Control:    "/usr/sbin/ldpctl",
		ConfigFile: "/var/run/ldpd.conf",
		ConfigTemplate: `# MPLS LDP Configuration
router-id 0.0.0.0
`,
	},
	"pflogd": {
		Name:       "pflogd",
		Daemon:     "/usr/sbin/pflogd",
		ConfigFile: "/var/log/pflog",
	},
	"eigrpd": {
		Name:       "eigrpd",
		Daemon:     "/usr/sbin/eigrpd",
		Control:    "/usr/sbin/eigrpctl",
		ConfigFile: "/var/run/eigrpd.conf",
		ConfigTemplate: `# EIGRP Configuration
router-id 0.0.0.0
`,
	},
	"ospfd": {
		Name:       "ospfd",
		Daemon:     "/usr/sbin/ospfd",
		Control:    "/usr/sbin/ospfctl",
		ConfigFile: "/var/run/ospfd.conf",
		ConfigTemplate: `# OSPF Configuration
router-id 0.0.0.0
`,
	},
	"bgpd": {
		Name:       "bgpd",
		Daemon:     "/usr/sbin/bgpd",
		Control:    "/usr/sbin/bgpctl",
		ConfigFile: "/var/run/bgpd.conf",
		ConfigTemplate: `# BGP Configuration
AS 65000
router-id 0.0.0.0
`,
	},
	"ripd": {
		Name:       "ripd",
		Daemon:     "/usr/sbin/ripd",
		Control:    "/usr/sbin/ripctl",
		ConfigFile: "/var/run/ripd.conf",
		ConfigTemplate: `# RIP Configuration
`,
	},
	"dhcpd": {
		Name:       "dhcpd",
		Daemon:     "/usr/sbin/dhcpd",
		ConfigFile: "/var/run/dhcpd.conf",
		ConfigTemplate: `# DHCP Server Configuration
option domain-name-servers 8.8.8.8, 8.8.4.4;
default-lease-time 600;
max-lease-time 7200;
`,
	},
	"ntpd": {
		Name:       "ntpd",
		Daemon:     "/usr/sbin/ntpd",
		ConfigFile: "/var/run/ntpd.conf",
		ConfigTemplate: `servers pool.ntp.org
sensor *
`,
	},
	"sshd": {
		Name:       "sshd",
		Daemon:     "/usr/sbin/sshd",
		ConfigFile: "/var/run/sshd.conf",
		ConfigTemplate: `Port 22
PermitRootLogin no
PasswordAuthentication yes
`,
	},
	"relayd": {
		Name:       "relayd",
		Daemon:     "/usr/sbin/relayd",
		Control:    "/usr/sbin/relayctl",
		ConfigFile: "/var/run/relayd.conf",
		ConfigTemplate: `# Relay Configuration
`,
	},
	"snmpd": {
		Name:       "snmpd",
		Daemon:     "/usr/sbin/snmpd",
		Control:    "/usr/sbin/snmpctl",
		ConfigFile: "/var/run/snmpd.conf",
		ConfigTemplate: `listen on 127.0.0.1
`,
	},
}

// ServiceConfig holds service metadata
type ServiceConfig struct {
	Name           string `json:"name"`
	Daemon         string `json:"daemon"`
	Control        string `json:"control,omitempty"`
	ConfigFile     string `json:"config_file"`
	PidFile        string `json:"pid_file,omitempty"`
	ConfigTemplate string `json:"-"`
}

// APIResponse is the standard response format
type APIResponse struct {
	Status  string      `json:"status"`
	Message string      `json:"message,omitempty"`
	Data    interface{} `json:"data,omitempty"`
	Error   string      `json:"error,omitempty"`
}

// ServiceStatus represents the current state of a service
type ServiceStatus struct {
	Name      string `json:"name"`
	Running   bool   `json:"running"`
	Enabled   bool   `json:"enabled"`
	PID       int    `json:"pid,omitempty"`
	ConfigOK  bool   `json:"config_ok"`
	LastError string `json:"last_error,omitempty"`
}

// ServiceInfo provides detailed service information
type ServiceInfo struct {
	Config   ServiceConfig `json:"config"`
	Status   ServiceStatus `json:"status"`
	Content  string        `json:"content,omitempty"`
	Stats    interface{}   `json:"stats,omitempty"`
}

// ConfigUpdateRequest for updating service configuration
type ConfigUpdateRequest struct {
	Content string `json:"content"`
	Reload  bool   `json:"reload"`
}

// ServiceActionRequest for service control actions
type ServiceActionRequest struct {
	Action string `json:"action"` // enable, disable, reload, restart
}

var configMutex sync.RWMutex

func main() {
	port := os.Getenv("NSWALL_API_PORT")
	if port == "" {
		port = defaultPort
	}

	// Set up routes
	http.HandleFunc("/api/v1/services", handleServices)
	http.HandleFunc("/api/v1/services/", handleServiceByName)
	http.HandleFunc("/api/v1/health", handleHealth)

	log.Printf("NSWall Services API starting on %s", port)
	log.Fatal(http.ListenAndServe(port, corsMiddleware(http.DefaultServeMux)))
}

// CORS middleware
func corsMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
		w.Header().Set("Content-Type", "application/json")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusOK)
			return
		}

		next.ServeHTTP(w, r)
	})
}

// Helper functions

func writeJSON(w http.ResponseWriter, status int, data interface{}) {
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(data)
}

func runCommand(name string, args ...string) (string, error) {
	cmd := exec.Command(name, args...)
	output, err := cmd.CombinedOutput()
	return string(output), err
}

func isServiceRunning(service string) (bool, int) {
	output, err := runCommand("/usr/bin/pgrep", "-x", service)
	if err != nil {
		return false, 0
	}
	var pid int
	fmt.Sscanf(strings.TrimSpace(output), "%d", &pid)
	return pid > 0, pid
}

func isServiceEnabled(service string) bool {
	enabledFile := fmt.Sprintf("/var/run/%s.conf.enabled", service)
	_, err := os.Stat(enabledFile)
	return err == nil
}

func validateConfig(svc ServiceConfig) bool {
	if svc.Daemon == "" {
		return true
	}
	// Try config check if daemon supports -n flag
	_, err := runCommand(svc.Daemon, "-n", "-f", svc.ConfigFile)
	return err == nil
}

// Health check handler
func handleHealth(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, http.StatusOK, APIResponse{
		Status:  "ok",
		Message: "NSWall Services API is running",
	})
}

// GET /api/v1/services - List all services
func handleServices(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
		return
	}

	var services []ServiceInfo
	for name, cfg := range serviceConfigs {
		running, pid := isServiceRunning(name)
		status := ServiceStatus{
			Name:     name,
			Running:  running,
			Enabled:  isServiceEnabled(name),
			PID:      pid,
			ConfigOK: validateConfig(cfg),
		}
		services = append(services, ServiceInfo{
			Config: cfg,
			Status: status,
		})
	}

	writeJSON(w, http.StatusOK, APIResponse{
		Status: "success",
		Data:   services,
	})
}

// ControlCommandRequest for executing control commands
type ControlCommandRequest struct {
	Command string   `json:"command"`
	Args    []string `json:"args,omitempty"`
}

// Service-specific handlers
func handleServiceByName(w http.ResponseWriter, r *http.Request) {
	// Extract service name from path
	path := strings.TrimPrefix(r.URL.Path, "/api/v1/services/")
	parts := strings.Split(path, "/")
	serviceName := parts[0]

	cfg, exists := serviceConfigs[serviceName]
	if !exists {
		writeJSON(w, http.StatusNotFound, APIResponse{
			Status: "error",
			Error:  fmt.Sprintf("Service '%s' not found", serviceName),
		})
		return
	}

	// Determine sub-resource if any
	subResource := ""
	if len(parts) > 1 {
		subResource = parts[1]
	}

	switch subResource {
	case "":
		handleServiceCRUD(w, r, cfg)
	case "config":
		handleServiceConfig(w, r, cfg)
	case "status":
		handleServiceStatus(w, r, cfg)
	case "action":
		handleServiceAction(w, r, cfg)
	case "stats":
		handleServiceStats(w, r, cfg)
	case "control":
		handleServiceControl(w, r, cfg)
	case "commands":
		handleServiceCommands(w, r, cfg)
	default:
		writeJSON(w, http.StatusNotFound, APIResponse{
			Status: "error",
			Error:  fmt.Sprintf("Unknown resource '%s'", subResource),
		})
	}
}

// GET/DELETE /api/v1/services/{name}
func handleServiceCRUD(w http.ResponseWriter, r *http.Request, cfg ServiceConfig) {
	switch r.Method {
	case http.MethodGet:
		// Read service details
		running, pid := isServiceRunning(cfg.Name)
		status := ServiceStatus{
			Name:     cfg.Name,
			Running:  running,
			Enabled:  isServiceEnabled(cfg.Name),
			PID:      pid,
			ConfigOK: validateConfig(cfg),
		}

		// Read config content
		content, _ := ioutil.ReadFile(cfg.ConfigFile)

		info := ServiceInfo{
			Config:  cfg,
			Status:  status,
			Content: string(content),
		}

		writeJSON(w, http.StatusOK, APIResponse{
			Status: "success",
			Data:   info,
		})

	case http.MethodDelete:
		// Disable and stop service
		configMutex.Lock()
		defer configMutex.Unlock()

		// Stop the service
		runCommand("/usr/bin/pkill", "-x", cfg.Name)

		// Remove enabled flag
		os.Remove(cfg.ConfigFile + ".enabled")

		writeJSON(w, http.StatusOK, APIResponse{
			Status:  "success",
			Message: fmt.Sprintf("Service %s disabled and stopped", cfg.Name),
		})

	default:
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
	}
}

// GET/PUT /api/v1/services/{name}/config
func handleServiceConfig(w http.ResponseWriter, r *http.Request, cfg ServiceConfig) {
	switch r.Method {
	case http.MethodGet:
		content, err := ioutil.ReadFile(cfg.ConfigFile)
		if err != nil {
			// Return template if no config exists
			if os.IsNotExist(err) && cfg.ConfigTemplate != "" {
				writeJSON(w, http.StatusOK, APIResponse{
					Status: "success",
					Data: map[string]interface{}{
						"content":  cfg.ConfigTemplate,
						"template": true,
					},
				})
				return
			}
			writeJSON(w, http.StatusInternalServerError, APIResponse{
				Status: "error",
				Error:  err.Error(),
			})
			return
		}

		writeJSON(w, http.StatusOK, APIResponse{
			Status: "success",
			Data: map[string]interface{}{
				"content":  string(content),
				"template": false,
			},
		})

	case http.MethodPut:
		var req ConfigUpdateRequest
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			writeJSON(w, http.StatusBadRequest, APIResponse{
				Status: "error",
				Error:  "Invalid request body",
			})
			return
		}

		configMutex.Lock()
		defer configMutex.Unlock()

		// Ensure directory exists
		dir := filepath.Dir(cfg.ConfigFile)
		os.MkdirAll(dir, 0755)

		// Write config
		err := ioutil.WriteFile(cfg.ConfigFile, []byte(req.Content), 0600)
		if err != nil {
			writeJSON(w, http.StatusInternalServerError, APIResponse{
				Status: "error",
				Error:  err.Error(),
			})
			return
		}

		// Validate config
		if !validateConfig(cfg) {
			writeJSON(w, http.StatusBadRequest, APIResponse{
				Status: "error",
				Error:  "Configuration validation failed",
			})
			return
		}

		// Reload if requested
		if req.Reload && cfg.Control != "" {
			runCommand(cfg.Control, "reload")
		}

		writeJSON(w, http.StatusOK, APIResponse{
			Status:  "success",
			Message: "Configuration updated",
		})

	case http.MethodPost:
		// Create default config from template
		if cfg.ConfigTemplate == "" {
			writeJSON(w, http.StatusBadRequest, APIResponse{
				Status: "error",
				Error:  "No configuration template available",
			})
			return
		}

		configMutex.Lock()
		defer configMutex.Unlock()

		err := ioutil.WriteFile(cfg.ConfigFile, []byte(cfg.ConfigTemplate), 0600)
		if err != nil {
			writeJSON(w, http.StatusInternalServerError, APIResponse{
				Status: "error",
				Error:  err.Error(),
			})
			return
		}

		writeJSON(w, http.StatusCreated, APIResponse{
			Status:  "success",
			Message: "Default configuration created",
			Data:    cfg.ConfigTemplate,
		})

	default:
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
	}
}

// GET /api/v1/services/{name}/status
func handleServiceStatus(w http.ResponseWriter, r *http.Request, cfg ServiceConfig) {
	if r.Method != http.MethodGet {
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
		return
	}

	running, pid := isServiceRunning(cfg.Name)
	status := ServiceStatus{
		Name:     cfg.Name,
		Running:  running,
		Enabled:  isServiceEnabled(cfg.Name),
		PID:      pid,
		ConfigOK: validateConfig(cfg),
	}

	writeJSON(w, http.StatusOK, APIResponse{
		Status: "success",
		Data:   status,
	})
}

// POST /api/v1/services/{name}/action
func handleServiceAction(w http.ResponseWriter, r *http.Request, cfg ServiceConfig) {
	if r.Method != http.MethodPost {
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
		return
	}

	var req ServiceActionRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeJSON(w, http.StatusBadRequest, APIResponse{
			Status: "error",
			Error:  "Invalid request body",
		})
		return
	}

	configMutex.Lock()
	defer configMutex.Unlock()

	var output string
	var err error

	switch req.Action {
	case "enable", "start":
		// Create enabled flag
		ioutil.WriteFile(cfg.ConfigFile+".enabled", []byte{}, 0600)

		// Start the daemon
		args := []string{"-f", cfg.ConfigFile}
		output, err = runCommand(cfg.Daemon, args...)

	case "disable", "stop":
		// Remove enabled flag
		os.Remove(cfg.ConfigFile + ".enabled")

		// Stop the daemon
		output, err = runCommand("/usr/bin/pkill", "-x", cfg.Name)

	case "reload":
		if cfg.Control != "" {
			output, err = runCommand(cfg.Control, "reload")
		} else {
			// Send HUP signal
			output, err = runCommand("/usr/bin/pkill", "-HUP", cfg.Name)
		}

	case "restart":
		// Stop then start
		runCommand("/usr/bin/pkill", "-x", cfg.Name)
		args := []string{"-f", cfg.ConfigFile}
		output, err = runCommand(cfg.Daemon, args...)

	default:
		writeJSON(w, http.StatusBadRequest, APIResponse{
			Status: "error",
			Error:  fmt.Sprintf("Unknown action '%s'", req.Action),
		})
		return
	}

	if err != nil {
		writeJSON(w, http.StatusInternalServerError, APIResponse{
			Status:  "error",
			Error:   err.Error(),
			Message: output,
		})
		return
	}

	writeJSON(w, http.StatusOK, APIResponse{
		Status:  "success",
		Message: fmt.Sprintf("Action '%s' executed for %s", req.Action, cfg.Name),
		Data:    output,
	})
}

// GET /api/v1/services/{name}/stats
func handleServiceStats(w http.ResponseWriter, r *http.Request, cfg ServiceConfig) {
	if r.Method != http.MethodGet {
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
		return
	}

	var stats interface{}
	var err error

	switch cfg.Name {
	case "unbound":
		output, _ := runCommand(cfg.Control, "stats_noreset")
		stats = parseKeyValueStats(output)

	case "ospfd", "bgpd", "ripd", "eigrpd", "ldpd":
		summary, _ := runCommand(cfg.Control, "show", "summary")
		neighbors, _ := runCommand(cfg.Control, "show", "neighbor")
		stats = map[string]string{
			"summary":   summary,
			"neighbors": neighbors,
		}

	case "smtpd":
		statsOut, _ := runCommand(cfg.Control, "show", "stats")
		queue, _ := runCommand(cfg.Control, "show", "queue")
		stats = map[string]interface{}{
			"stats": parseKeyValueStats(statsOut),
			"queue": queue,
		}

	case "relayd":
		summary, _ := runCommand(cfg.Control, "show", "summary")
		hosts, _ := runCommand(cfg.Control, "show", "hosts")
		stats = map[string]string{
			"summary": summary,
			"hosts":   hosts,
		}

	case "pflogd":
		output, _ := runCommand("/usr/sbin/tcpdump", "-n", "-e", "-ttt", "-r", cfg.ConfigFile, "-c", "20")
		stats = map[string]string{"recent_logs": output}

	case "ntpd":
		peers, _ := runCommand("/usr/sbin/ntpctl", "-s", "peers")
		status, _ := runCommand("/usr/sbin/ntpctl", "-s", "status")
		stats = map[string]string{
			"peers":  peers,
			"status": status,
		}

	case "iked":
		sa, _ := runCommand("/usr/sbin/ikectl", "show", "sa")
		stats = map[string]string{"security_associations": sa}

	case "sshd":
		// Count active SSH connections
		output, _ := runCommand("/bin/ps", "aux")
		stats = map[string]string{"processes": output}

	default:
		stats = map[string]string{"message": "No stats available for this service"}
	}

	if err != nil {
		writeJSON(w, http.StatusInternalServerError, APIResponse{
			Status: "error",
			Error:  err.Error(),
		})
		return
	}

	writeJSON(w, http.StatusOK, APIResponse{
		Status: "success",
		Data:   stats,
	})
}

// Parse key=value style stats output
func parseKeyValueStats(output string) map[string]string {
	stats := make(map[string]string)
	lines := strings.Split(output, "\n")
	for _, line := range lines {
		parts := strings.SplitN(line, "=", 2)
		if len(parts) == 2 {
			stats[strings.TrimSpace(parts[0])] = strings.TrimSpace(parts[1])
		}
	}
	return stats
}

// Comprehensive service control commands mapping
var serviceCommands = map[string]map[string][]string{
	"ospfd": {
		"reload":            {"reload"},
		"fib-couple":        {"fib", "couple"},
		"fib-decouple":      {"fib", "decouple"},
		"fib-reload":        {"fib", "reload"},
		"log-brief":         {"log", "brief"},
		"log-verbose":       {"log", "verbose"},
		"database":          {"show", "database"},
		"database-asbr":     {"show", "database", "asbr"},
		"database-external": {"show", "database", "external"},
		"database-network":  {"show", "database", "network"},
		"database-router":   {"show", "database", "router"},
		"database-self":     {"show", "database", "self-originated"},
		"database-summary":  {"show", "database", "summary"},
		"show-fib":          {"show", "fib"},
		"fib-connected":     {"show", "fib", "connected"},
		"fib-ospf":          {"show", "fib", "ospf"},
		"fib-static":        {"show", "fib", "static"},
		"show-interfaces":   {"show", "interfaces"},
		"show-neighbor":     {"show", "neighbor"},
		"show-rib":          {"show", "rib"},
		"show-summary":      {"show", "summary"},
	},
	"bgpd": {
		"reload":          {"reload"},
		"fib-couple":      {"fib", "couple"},
		"fib-decouple":    {"fib", "decouple"},
		"log-brief":       {"log", "brief"},
		"log-verbose":     {"log", "verbose"},
		"network-show":    {"network", "show"},
		"network-flush":   {"network", "flush"},
		"show-tables":     {"show", "tables"},
		"show-fib":        {"show", "fib"},
		"show-metrics":    {"show", "metrics"},
		"show-summary":    {"show", "summary"},
		"show-neighbor":   {"show", "neighbor"},
		"show-rib":        {"show", "rib"},
		"show-interfaces": {"show", "interfaces"},
	},
	"ripd": {
		"reload":          {"reload"},
		"fib-couple":      {"fib", "couple"},
		"fib-decouple":    {"fib", "decouple"},
		"log-brief":       {"log", "brief"},
		"log-verbose":     {"log", "verbose"},
		"show-fib":        {"show", "fib"},
		"show-interfaces": {"show", "interfaces"},
		"show-neighbor":   {"show", "neighbor"},
		"show-rib":        {"show", "rib"},
	},
	"eigrpd": {
		"reload":          {"reload"},
		"fib-couple":      {"fib", "couple"},
		"fib-decouple":    {"fib", "decouple"},
		"clear-neighbors": {"clear", "neighbors"},
		"log-brief":       {"log", "brief"},
		"log-verbose":     {"log", "verbose"},
		"show-fib":        {"show", "fib"},
		"show-interfaces": {"show", "interfaces"},
		"show-neighbor":   {"show", "neighbor"},
		"show-topology":   {"show", "topology"},
		"show-stats":      {"show", "stats"},
	},
	"ldpd": {
		"reload":          {"reload"},
		"fib-couple":      {"fib", "couple"},
		"fib-decouple":    {"fib", "decouple"},
		"clear-neighbors": {"clear", "neighbors"},
		"log-brief":       {"log", "brief"},
		"log-verbose":     {"log", "verbose"},
		"show-fib":        {"show", "fib"},
		"show-interfaces": {"show", "interfaces"},
		"show-neighbor":   {"show", "neighbor"},
		"show-lib":        {"show", "lib"},
		"show-discovery":  {"show", "discovery"},
		"l2vpn-bindings":  {"show", "l2vpn", "bindings"},
		"l2vpn-pseudowires": {"show", "l2vpn", "pseudowires"},
	},
	"unbound": {
		"reload":             {"reload"},
		"reload-keep":        {"reload_keep_cache"},
		"flush":              {"flush_zone", "."},
		"stats":              {"stats_noreset"},
		"stats-reset":        {"stats"},
		"stop":               {"stop"},
		"dump-cache":         {"dump_cache"},
		"dump-infra":         {"dump_infra"},
		"list-stubs":         {"list_stubs"},
		"list-forwards":      {"list_forwards"},
		"list-local":         {"list_local_zones"},
		"list-local-data":    {"list_local_data"},
		"list-insecure":      {"list_insecure"},
		"list-auth":          {"list_auth_zones"},
		"log-reopen":         {"log_reopen"},
	},
	"smtpd": {
		"pause-smtp":       {"pause", "smtp"},
		"pause-mda":        {"pause", "mda"},
		"pause-mta":        {"pause", "mta"},
		"resume-smtp":      {"resume", "smtp"},
		"resume-mda":       {"resume", "mda"},
		"resume-mta":       {"resume", "mta"},
		"log-brief":        {"log", "brief"},
		"log-verbose":      {"log", "verbose"},
		"show-queue":       {"show", "queue"},
		"show-stats":       {"show", "stats"},
		"show-hosts":       {"show", "hosts"},
		"show-routes":      {"show", "routes"},
		"show-hoststats":   {"show", "hoststats"},
		"show-relays":      {"show", "relays"},
		"show-status":      {"show", "status"},
		"schedule-all":     {"schedule", "all"},
		"remove-all":       {"remove", "all"},
		"monitor":          {"monitor"},
	},
	"iked": {
		"reload":       {"reload"},
		"active":       {"active"},
		"passive":      {"passive"},
		"couple":       {"couple"},
		"decouple":     {"decouple"},
		"log-brief":    {"log", "brief"},
		"log-verbose":  {"log", "verbose"},
		"monitor":      {"monitor"},
		"show-sa":      {"show", "sa"},
		"show-ca":      {"show", "ca"},
		"reset-all":    {"reset", "all"},
		"reset-ca":     {"reset", "ca"},
		"reset-id":     {"reset", "id"},
		"reset-policy": {"reset", "policy"},
	},
	"relayd": {
		"reload":        {"reload"},
		"poll":          {"poll"},
		"monitor":       {"monitor"},
		"stop":          {"stop"},
		"log-brief":     {"log", "brief"},
		"log-verbose":   {"log", "verbose"},
		"show-hosts":    {"show", "hosts"},
		"show-sessions": {"show", "sessions"},
		"show-summary":  {"show", "summary"},
		"show-routers":  {"show", "routers"},
		"show-redirects": {"show", "redirects"},
		"show-relays":   {"show", "relays"},
	},
	"snmpd": {
		"log-brief":   {"log", "brief"},
		"log-verbose": {"log", "verbose"},
	},
	"ntpd": {
		"peers":   {"-s", "peers"},
		"sensors": {"-s", "sensors"},
		"status":  {"-s", "status"},
		"all":     {"-s", "all"},
	},
}

// GET /api/v1/services/{name}/commands - List available commands
func handleServiceCommands(w http.ResponseWriter, r *http.Request, cfg ServiceConfig) {
	if r.Method != http.MethodGet {
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
		return
	}

	commands, exists := serviceCommands[cfg.Name]
	if !exists {
		writeJSON(w, http.StatusOK, APIResponse{
			Status:  "success",
			Message: "No control commands available for this service",
			Data:    []string{},
		})
		return
	}

	// Return list of available commands
	cmdList := make([]string, 0, len(commands))
	for cmd := range commands {
		cmdList = append(cmdList, cmd)
	}

	writeJSON(w, http.StatusOK, APIResponse{
		Status: "success",
		Data: map[string]interface{}{
			"service":  cfg.Name,
			"commands": cmdList,
		},
	})
}

// POST /api/v1/services/{name}/control - Execute control command
func handleServiceControl(w http.ResponseWriter, r *http.Request, cfg ServiceConfig) {
	if r.Method != http.MethodPost {
		writeJSON(w, http.StatusMethodNotAllowed, APIResponse{
			Status: "error",
			Error:  "Method not allowed",
		})
		return
	}

	if cfg.Control == "" {
		writeJSON(w, http.StatusBadRequest, APIResponse{
			Status: "error",
			Error:  fmt.Sprintf("Service '%s' has no control utility", cfg.Name),
		})
		return
	}

	var req ControlCommandRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeJSON(w, http.StatusBadRequest, APIResponse{
			Status: "error",
			Error:  "Invalid request body",
		})
		return
	}

	// Check if this is a predefined command
	commands, hasCommands := serviceCommands[cfg.Name]
	var args []string

	if hasCommands {
		predefinedArgs, exists := commands[req.Command]
		if exists {
			args = predefinedArgs
			// Append any additional args from request
			args = append(args, req.Args...)
		} else {
			// Allow raw command execution for flexibility
			args = append([]string{req.Command}, req.Args...)
		}
	} else {
		// For services without predefined commands, use raw input
		args = append([]string{req.Command}, req.Args...)
	}

	// Execute the control command
	output, err := runCommand(cfg.Control, args...)

	response := map[string]interface{}{
		"command": req.Command,
		"args":    args,
		"output":  output,
	}

	if err != nil {
		response["error"] = err.Error()
		writeJSON(w, http.StatusOK, APIResponse{
			Status:  "warning",
			Message: "Command executed with errors",
			Data:    response,
		})
		return
	}

	writeJSON(w, http.StatusOK, APIResponse{
		Status:  "success",
		Message: "Command executed successfully",
		Data:    response,
	})
}
