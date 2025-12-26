package services

import (
	"bytes"
	"crypto/rand"
	"crypto/tls"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"sync"
	"time"
)

// AgentMode determines if this instance is a manager or agent
type AgentMode string

const (
	ModeStandalone AgentMode = "standalone"
	ModeManager    AgentMode = "manager"
	ModeAgent      AgentMode = "agent"
)

// AgentStatus represents the health status of an agent
type AgentStatus string

const (
	StatusOnline      AgentStatus = "online"
	StatusOffline     AgentStatus = "offline"
	StatusDegraded    AgentStatus = "degraded"
	StatusMaintenance AgentStatus = "maintenance"
)

// AgentInfo contains information about a managed appliance
type AgentInfo struct {
	ID            string            `json:"id"`
	Hostname      string            `json:"hostname"`
	Version       string            `json:"version"`
	Status        AgentStatus       `json:"status"`
	LastSeen      time.Time         `json:"last_seen"`
	Uptime        int64             `json:"uptime"`
	PFEnabled     bool              `json:"pf_enabled"`
	PFStates      int64             `json:"pf_states"`
	Interfaces    []string          `json:"interfaces"`
	CPULoad       float64           `json:"cpu_load"`
	MemoryPercent float64           `json:"memory_percent"`
	Tags          map[string]string `json:"tags,omitempty"`
	ConfigHash    string            `json:"config_hash,omitempty"`
	IP            string            `json:"ip,omitempty"`
}

// AgentConfig contains configuration pushed from manager
type AgentConfig struct {
	PFRules     string            `json:"pf_rules,omitempty"`
	Hostname    string            `json:"hostname,omitempty"`
	Interfaces  map[string]string `json:"interfaces,omitempty"`
	Routes      []RouteConfig     `json:"routes,omitempty"`
	DHCP        *DHCPConfig       `json:"dhcp,omitempty"`
	Tags        map[string]string `json:"tags,omitempty"`
	ConfigHash  string            `json:"config_hash"`
	AppliedAt   time.Time         `json:"applied_at,omitempty"`
	Version     int               `json:"version"`
}

// RouteConfig defines a route
type RouteConfig struct {
	Destination string `json:"destination"`
	Gateway     string `json:"gateway"`
	Interface   string `json:"interface,omitempty"`
}

// DHCPConfig defines DHCP settings
type DHCPConfig struct {
	Enabled   bool   `json:"enabled"`
	Interface string `json:"interface"`
	RangeFrom string `json:"range_from"`
	RangeTo   string `json:"range_to"`
	DNS       string `json:"dns"`
	Gateway   string `json:"gateway"`
}

// AgentEvent represents an event from an agent
type AgentEvent struct {
	ID        string                 `json:"id"`
	AgentID   string                 `json:"agent_id"`
	Timestamp time.Time              `json:"timestamp"`
	Type      string                 `json:"type"` // log, alert, config_change, etc.
	Severity  string                 `json:"severity"` // info, warning, error, critical
	Message   string                 `json:"message"`
	Data      map[string]interface{} `json:"data,omitempty"`
}

// FleetService manages multi-box fleet operations
type FleetService struct {
	mode           AgentMode
	agentID        string
	managerURL     string
	apiKey         string
	configPath     string
	agents         map[string]*AgentInfo
	events         []AgentEvent
	pendingConfigs map[string]*AgentConfig
	mu             sync.RWMutex
	eventMu        sync.RWMutex
	client         *http.Client
	stopCh         chan struct{}
}

// NewFleetService creates a new fleet service
func NewFleetService(configPath string) *FleetService {
	// Generate agent ID if not exists
	agentID := generateAgentID(configPath)

	return &FleetService{
		mode:           ModeStandalone,
		agentID:        agentID,
		configPath:     configPath,
		agents:         make(map[string]*AgentInfo),
		events:         make([]AgentEvent, 0),
		pendingConfigs: make(map[string]*AgentConfig),
		client: &http.Client{
			Timeout: 30 * time.Second,
			Transport: &http.Transport{
				TLSClientConfig: &tls.Config{
					InsecureSkipVerify: true, // For self-signed certs in LAB
				},
			},
		},
		stopCh: make(chan struct{}),
	}
}

func generateAgentID(configPath string) string {
	idFile := filepath.Join(configPath, ".agent_id")
	if data, err := os.ReadFile(idFile); err == nil {
		return strings.TrimSpace(string(data))
	}

	// Generate new ID
	b := make([]byte, 8)
	rand.Read(b)
	id := hex.EncodeToString(b)

	// Try to save it
	os.WriteFile(idFile, []byte(id), 0600)
	return id
}

// GetMode returns the current operating mode
func (f *FleetService) GetMode() AgentMode {
	f.mu.RLock()
	defer f.mu.RUnlock()
	return f.mode
}

// SetMode sets the operating mode
func (f *FleetService) SetMode(mode AgentMode, managerURL, apiKey string) error {
	f.mu.Lock()
	defer f.mu.Unlock()

	f.mode = mode
	f.managerURL = managerURL
	f.apiKey = apiKey

	// Save config
	config := map[string]string{
		"mode":        string(mode),
		"manager_url": managerURL,
		"api_key":     apiKey,
	}
	data, _ := json.Marshal(config)
	configFile := filepath.Join(f.configPath, "fleet.json")
	os.WriteFile(configFile, data, 0600)

	return nil
}

// StartAgent starts the agent heartbeat loop
func (f *FleetService) StartAgent() {
	if f.mode != ModeAgent {
		return
	}

	go func() {
		ticker := time.NewTicker(30 * time.Second)
		defer ticker.Stop()

		// Initial registration
		f.sendHeartbeat()

		for {
			select {
			case <-ticker.C:
				f.sendHeartbeat()
			case <-f.stopCh:
				return
			}
		}
	}()
}

// Stop stops the fleet service
func (f *FleetService) Stop() {
	close(f.stopCh)
}

func (f *FleetService) sendHeartbeat() {
	info := f.collectLocalInfo()

	data, _ := json.Marshal(info)
	url := fmt.Sprintf("%s/api/v1/fleet/heartbeat", f.managerURL)

	req, _ := http.NewRequest("POST", url, bytes.NewReader(data))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", f.apiKey)

	resp, err := f.client.Do(req)
	if err != nil {
		f.addEvent("heartbeat_failed", "error", fmt.Sprintf("Failed to send heartbeat: %v", err), nil)
		return
	}
	defer resp.Body.Close()

	// Check if there's a pending config
	if resp.StatusCode == http.StatusOK {
		var response struct {
			PendingConfig *AgentConfig `json:"pending_config,omitempty"`
		}
		if json.NewDecoder(resp.Body).Decode(&response) == nil && response.PendingConfig != nil {
			f.applyConfig(response.PendingConfig)
		}
	}
}

func (f *FleetService) collectLocalInfo() *AgentInfo {
	info := &AgentInfo{
		ID:      f.agentID,
		Version: "2.0.0",
		Status:  StatusOnline,
	}

	// Hostname
	if hostname, err := os.Hostname(); err == nil {
		info.Hostname = hostname
	}

	// Uptime
	if output, err := exec.Command("sysctl", "-n", "kern.boottime").Output(); err == nil {
		str := string(output)
		if idx := strings.Index(str, "sec = "); idx >= 0 {
			rest := str[idx+6:]
			if endIdx := strings.Index(rest, ","); endIdx > 0 {
				rest = rest[:endIdx]
			}
			var bootSec int64
			fmt.Sscanf(rest, "%d", &bootSec)
			info.Uptime = time.Now().Unix() - bootSec
		}
	}

	// PF status
	if output, err := exec.Command("pfctl", "-si").Output(); err == nil {
		info.PFEnabled = strings.Contains(string(output), "Status: Enabled")
		// Parse state count
		lines := strings.Split(string(output), "\n")
		for _, line := range lines {
			if strings.Contains(line, "current entries") {
				fmt.Sscanf(line, "%d", &info.PFStates)
				break
			}
		}
	}

	// Load average
	if output, err := exec.Command("sysctl", "-n", "vm.loadavg").Output(); err == nil {
		fields := strings.Fields(string(output))
		if len(fields) >= 1 {
			fmt.Sscanf(fields[0], "%f", &info.CPULoad)
		}
	}

	// Interfaces
	if output, err := exec.Command("ifconfig", "-l").Output(); err == nil {
		ifaces := strings.Fields(string(output))
		for _, iface := range ifaces {
			if !strings.HasPrefix(iface, "lo") && !strings.HasPrefix(iface, "pflog") && !strings.HasPrefix(iface, "enc") {
				info.Interfaces = append(info.Interfaces, iface)
			}
		}
	}

	// Config hash
	if data, err := exec.Command("pfctl", "-sr").Output(); err == nil {
		info.ConfigHash = hashConfig(string(data))
	}

	return info
}

func hashConfig(config string) string {
	// Simple hash for config comparison
	h := 0
	for _, c := range config {
		h = h*31 + int(c)
	}
	return fmt.Sprintf("%x", h&0xffffffff)
}

func (f *FleetService) applyConfig(config *AgentConfig) error {
	f.addEvent("config_apply", "info", "Applying configuration from manager", map[string]interface{}{
		"version":     config.Version,
		"config_hash": config.ConfigHash,
	})

	// Apply PF rules if provided
	if config.PFRules != "" {
		if err := f.applyPFRules(config.PFRules); err != nil {
			f.addEvent("config_failed", "error", fmt.Sprintf("Failed to apply PF rules: %v", err), nil)
			return err
		}
	}

	// Apply routes if provided
	for _, route := range config.Routes {
		cmd := exec.Command("route", "add", "-net", route.Destination, route.Gateway)
		if route.Interface != "" {
			cmd = exec.Command("route", "add", "-net", route.Destination, "-interface", route.Interface, route.Gateway)
		}
		cmd.Run() // Ignore errors for existing routes
	}

	config.AppliedAt = time.Now()
	f.addEvent("config_applied", "info", "Configuration applied successfully", nil)

	return nil
}

func (f *FleetService) applyPFRules(rules string) error {
	// Write to temp file
	tmpFile, err := os.CreateTemp("", "pf-*.conf")
	if err != nil {
		return err
	}
	defer os.Remove(tmpFile.Name())

	if _, err := tmpFile.WriteString(rules); err != nil {
		return err
	}
	tmpFile.Close()

	// Test rules first
	cmd := exec.Command("pfctl", "-nf", tmpFile.Name())
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("rule validation failed: %s", output)
	}

	// Apply rules
	cmd = exec.Command("pfctl", "-f", tmpFile.Name())
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("rule apply failed: %s", output)
	}

	return nil
}

// Manager functions

// RegisterAgent registers or updates an agent
func (f *FleetService) RegisterAgent(info *AgentInfo) {
	f.mu.Lock()
	defer f.mu.Unlock()

	info.LastSeen = time.Now()
	f.agents[info.ID] = info
}

// GetAgents returns all registered agents
func (f *FleetService) GetAgents() []*AgentInfo {
	f.mu.RLock()
	defer f.mu.RUnlock()

	agents := make([]*AgentInfo, 0, len(f.agents))
	for _, agent := range f.agents {
		// Mark as offline if not seen recently
		a := *agent
		if time.Since(a.LastSeen) > 2*time.Minute {
			a.Status = StatusOffline
		}
		agents = append(agents, &a)
	}
	return agents
}

// GetAgent returns a specific agent
func (f *FleetService) GetAgent(id string) *AgentInfo {
	f.mu.RLock()
	defer f.mu.RUnlock()
	return f.agents[id]
}

// SetPendingConfig sets a pending config for an agent
func (f *FleetService) SetPendingConfig(agentID string, config *AgentConfig) {
	f.mu.Lock()
	defer f.mu.Unlock()
	f.pendingConfigs[agentID] = config
}

// GetPendingConfig gets and clears the pending config for an agent
func (f *FleetService) GetPendingConfig(agentID string) *AgentConfig {
	f.mu.Lock()
	defer f.mu.Unlock()

	config := f.pendingConfigs[agentID]
	delete(f.pendingConfigs, agentID)
	return config
}

// PushConfigToAgent pushes a configuration to a specific agent
func (f *FleetService) PushConfigToAgent(agentID string, config *AgentConfig) error {
	f.mu.RLock()
	agent := f.agents[agentID]
	f.mu.RUnlock()

	if agent == nil {
		return fmt.Errorf("agent not found: %s", agentID)
	}

	// If agent is online, push directly; otherwise queue it
	if agent.Status == StatusOnline && agent.IP != "" {
		url := fmt.Sprintf("https://%s:8443/api/v1/fleet/apply-config", agent.IP)
		data, _ := json.Marshal(config)

		req, _ := http.NewRequest("POST", url, bytes.NewReader(data))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-API-Key", f.apiKey)

		resp, err := f.client.Do(req)
		if err == nil {
			defer resp.Body.Close()
			if resp.StatusCode == http.StatusOK {
				return nil
			}
			body, _ := io.ReadAll(resp.Body)
			return fmt.Errorf("push failed: %s", body)
		}
		// Fall through to queuing
	}

	// Queue for next heartbeat
	f.SetPendingConfig(agentID, config)
	return nil
}

// Event management

func (f *FleetService) addEvent(eventType, severity, message string, data map[string]interface{}) {
	f.eventMu.Lock()
	defer f.eventMu.Unlock()

	event := AgentEvent{
		ID:        generateEventID(),
		AgentID:   f.agentID,
		Timestamp: time.Now(),
		Type:      eventType,
		Severity:  severity,
		Message:   message,
		Data:      data,
	}

	f.events = append(f.events, event)

	// Keep last 1000 events
	if len(f.events) > 1000 {
		f.events = f.events[len(f.events)-1000:]
	}

	// Send to manager if in agent mode
	if f.mode == ModeAgent && f.managerURL != "" {
		go f.sendEvent(event)
	}
}

func (f *FleetService) sendEvent(event AgentEvent) {
	data, _ := json.Marshal(event)
	url := fmt.Sprintf("%s/api/v1/fleet/events", f.managerURL)

	req, _ := http.NewRequest("POST", url, bytes.NewReader(data))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", f.apiKey)

	f.client.Do(req)
}

// ReceiveEvent receives an event from an agent (manager side)
func (f *FleetService) ReceiveEvent(event AgentEvent) {
	f.eventMu.Lock()
	defer f.eventMu.Unlock()

	f.events = append(f.events, event)

	// Keep last 10000 events on manager
	if len(f.events) > 10000 {
		f.events = f.events[len(f.events)-10000:]
	}
}

// GetEvents returns recent events
func (f *FleetService) GetEvents(limit int, agentID string, eventType string) []AgentEvent {
	f.eventMu.RLock()
	defer f.eventMu.RUnlock()

	filtered := make([]AgentEvent, 0)
	for i := len(f.events) - 1; i >= 0 && len(filtered) < limit; i-- {
		event := f.events[i]
		if agentID != "" && event.AgentID != agentID {
			continue
		}
		if eventType != "" && event.Type != eventType {
			continue
		}
		filtered = append(filtered, event)
	}

	return filtered
}

// GetFleetSummary returns a summary of the fleet
func (f *FleetService) GetFleetSummary() map[string]interface{} {
	f.mu.RLock()
	defer f.mu.RUnlock()

	online := 0
	offline := 0
	degraded := 0
	totalStates := int64(0)

	for _, agent := range f.agents {
		status := agent.Status
		if time.Since(agent.LastSeen) > 2*time.Minute {
			status = StatusOffline
		}
		switch status {
		case StatusOnline:
			online++
		case StatusOffline:
			offline++
		case StatusDegraded:
			degraded++
		}
		totalStates += agent.PFStates
	}

	return map[string]interface{}{
		"total_agents":  len(f.agents),
		"online":        online,
		"offline":       offline,
		"degraded":      degraded,
		"total_states":  totalStates,
		"recent_events": len(f.events),
	}
}

func generateEventID() string {
	b := make([]byte, 8)
	rand.Read(b)
	return hex.EncodeToString(b)
}

// GetAgentID returns this agent's ID
func (f *FleetService) GetAgentID() string {
	return f.agentID
}
