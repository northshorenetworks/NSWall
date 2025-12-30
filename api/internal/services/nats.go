// Package services provides NATS client for agent mode
package services

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"os/exec"
	"strings"
	"time"

	"github.com/nats-io/nats.go"
)

// NATS subject patterns
const (
	SubjectAgentStatusFmt  = "nswall.agent.%s.status"
	SubjectAgentConfig     = "nswall.agent.%s.config"
	SubjectAgentCommand    = "nswall.agent.%s.command"
	SubjectBroadcastConfig = "nswall.broadcast.config"
	SubjectBroadcastCmd    = "nswall.broadcast.command"
)

// NATSAgent handles NATS communication for agent mode
type NATSAgent struct {
	nc          *nats.Conn
	agentID     string
	nshPath     string
	stopCh      chan struct{}
	configApply func(config []byte) error
}

// AgentStatusMsg is the status message sent to controller
type AgentStatusMsg struct {
	ID            string          `json:"id"`
	Hostname      string          `json:"hostname"`
	Version       string          `json:"version"`
	Status        string          `json:"status"`
	Uptime        int64           `json:"uptime"`
	PFEnabled     bool            `json:"pf_enabled"`
	PFStates      int64           `json:"pf_states"`
	Interfaces    []InterfaceInfo `json:"interfaces"`
	CPULoad       float64         `json:"cpu_load"`
	MemoryPercent float64         `json:"memory_percent"`
	MemoryTotal   int64           `json:"memory_total"`
	MemoryUsed    int64           `json:"memory_used"`
	ConfigHash    string          `json:"config_hash"`
}

// InterfaceInfo represents interface status
type InterfaceInfo struct {
	Name   string   `json:"name"`
	Status string   `json:"status"`
	IPv4   []string `json:"ipv4,omitempty"`
	IPv6   []string `json:"ipv6,omitempty"`
}

// CommandMsg is a command from the controller
type CommandMsg struct {
	Command string `json:"command"`
	Timeout int    `json:"timeout,omitempty"`
}

// CommandResultMsg is the command result
type CommandResultMsg struct {
	Success  bool   `json:"success"`
	Output   string `json:"output"`
	Error    string `json:"error,omitempty"`
	ExitCode int    `json:"exit_code"`
	Duration int64  `json:"duration_ms"`
}

// NewNATSAgent creates a new NATS agent client
func NewNATSAgent(controllerURL, agentID, nshPath string) (*NATSAgent, error) {
	nc, err := nats.Connect(controllerURL,
		nats.Name("nswall-agent-"+agentID),
		nats.ReconnectWait(2*time.Second),
		nats.MaxReconnects(-1),
		nats.DisconnectErrHandler(func(nc *nats.Conn, err error) {
			log.Printf("NATS disconnected: %v", err)
		}),
		nats.ReconnectHandler(func(nc *nats.Conn) {
			log.Printf("NATS reconnected to %s", nc.ConnectedUrl())
		}),
		nats.ErrorHandler(func(nc *nats.Conn, sub *nats.Subscription, err error) {
			log.Printf("NATS error: %v", err)
		}),
	)
	if err != nil {
		return nil, fmt.Errorf("failed to connect to NATS: %w", err)
	}

	return &NATSAgent{
		nc:      nc,
		agentID: agentID,
		nshPath: nshPath,
		stopCh:  make(chan struct{}),
	}, nil
}

// Start begins the agent loop
func (a *NATSAgent) Start() error {
	// Subscribe to config pushes
	configSubject := fmt.Sprintf(SubjectAgentConfig, a.agentID)
	_, err := a.nc.Subscribe(configSubject, a.handleConfig)
	if err != nil {
		return fmt.Errorf("failed to subscribe to config: %w", err)
	}

	// Subscribe to commands
	cmdSubject := fmt.Sprintf(SubjectAgentCommand, a.agentID)
	_, err = a.nc.Subscribe(cmdSubject, a.handleCommand)
	if err != nil {
		return fmt.Errorf("failed to subscribe to commands: %w", err)
	}

	// Subscribe to broadcast config
	_, err = a.nc.Subscribe(SubjectBroadcastConfig, a.handleConfig)
	if err != nil {
		return fmt.Errorf("failed to subscribe to broadcast config: %w", err)
	}

	// Subscribe to broadcast commands
	_, err = a.nc.Subscribe(SubjectBroadcastCmd, a.handleCommand)
	if err != nil {
		return fmt.Errorf("failed to subscribe to broadcast commands: %w", err)
	}

	// Start status publishing loop
	go a.statusLoop()

	log.Printf("NATS agent started, ID: %s", a.agentID)
	return nil
}

// Stop stops the agent
func (a *NATSAgent) Stop() {
	close(a.stopCh)
	a.nc.Close()
}

// statusLoop publishes status every 30 seconds
func (a *NATSAgent) statusLoop() {
	ticker := time.NewTicker(30 * time.Second)
	defer ticker.Stop()

	// Send initial status
	a.publishStatus()

	for {
		select {
		case <-ticker.C:
			a.publishStatus()
		case <-a.stopCh:
			return
		}
	}
}

func (a *NATSAgent) publishStatus() {
	status := a.collectStatus()
	data, err := json.Marshal(status)
	if err != nil {
		log.Printf("Failed to marshal status: %v", err)
		return
	}

	subject := fmt.Sprintf(SubjectAgentStatusFmt, a.agentID)
	if err := a.nc.Publish(subject, data); err != nil {
		log.Printf("Failed to publish status: %v", err)
	}
}

func (a *NATSAgent) collectStatus() *AgentStatusMsg {
	status := &AgentStatusMsg{
		ID:      a.agentID,
		Version: "2.0.0",
		Status:  "online",
	}

	// Hostname
	if hostname, err := os.Hostname(); err == nil {
		status.Hostname = hostname
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
			status.Uptime = time.Now().Unix() - bootSec
		}
	}

	// PF status
	if output, err := exec.Command("pfctl", "-si").Output(); err == nil {
		status.PFEnabled = strings.Contains(string(output), "Status: Enabled")
		lines := strings.Split(string(output), "\n")
		for _, line := range lines {
			if strings.Contains(line, "current entries") {
				fmt.Sscanf(strings.TrimSpace(line), "%d", &status.PFStates)
				break
			}
		}
	}

	// Load average
	if output, err := exec.Command("sysctl", "-n", "vm.loadavg").Output(); err == nil {
		fields := strings.Fields(string(output))
		if len(fields) >= 1 {
			fmt.Sscanf(fields[0], "%f", &status.CPULoad)
		}
	}

	// Memory
	if output, err := exec.Command("sysctl", "-n", "hw.physmem").Output(); err == nil {
		fmt.Sscanf(strings.TrimSpace(string(output)), "%d", &status.MemoryTotal)
	}

	// Interfaces
	if output, err := exec.Command("ifconfig", "-l").Output(); err == nil {
		ifaces := strings.Fields(string(output))
		for _, iface := range ifaces {
			if strings.HasPrefix(iface, "lo") || strings.HasPrefix(iface, "pflog") ||
				strings.HasPrefix(iface, "enc") || strings.HasPrefix(iface, "pfsync") {
				continue
			}
			info := InterfaceInfo{Name: iface, Status: "up"}
			// Get IP addresses
			if ipOut, err := exec.Command("ifconfig", iface).Output(); err == nil {
				lines := strings.Split(string(ipOut), "\n")
				for _, line := range lines {
					line = strings.TrimSpace(line)
					if strings.HasPrefix(line, "inet ") {
						parts := strings.Fields(line)
						if len(parts) >= 2 {
							info.IPv4 = append(info.IPv4, parts[1])
						}
					} else if strings.HasPrefix(line, "inet6 ") {
						parts := strings.Fields(line)
						if len(parts) >= 2 && !strings.HasPrefix(parts[1], "fe80") {
							info.IPv6 = append(info.IPv6, parts[1])
						}
					}
				}
			}
			status.Interfaces = append(status.Interfaces, info)
		}
	}

	// Config hash (PF rules)
	if output, err := exec.Command("pfctl", "-sr").Output(); err == nil {
		h := 0
		for _, c := range string(output) {
			h = h*31 + int(c)
		}
		status.ConfigHash = fmt.Sprintf("%x", h&0xffffffff)
	}

	return status
}

func (a *NATSAgent) handleConfig(msg *nats.Msg) {
	log.Printf("Received config push")

	var config struct {
		PFRules string `json:"pf_rules,omitempty"`
		Hash    string `json:"hash"`
	}

	if err := json.Unmarshal(msg.Data, &config); err != nil {
		a.respond(msg, false, "", err.Error())
		return
	}

	// Apply PF rules if provided
	if config.PFRules != "" {
		if err := a.applyPFRules(config.PFRules); err != nil {
			a.respond(msg, false, "", err.Error())
			return
		}
	}

	a.respond(msg, true, "Config applied", "")
}

func (a *NATSAgent) applyPFRules(rules string) error {
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

	// Validate rules first
	cmd := exec.Command("pfctl", "-nf", tmpFile.Name())
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("validation failed: %s", output)
	}

	// Apply rules
	cmd = exec.Command("pfctl", "-f", tmpFile.Name())
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("apply failed: %s", output)
	}

	log.Printf("PF rules applied successfully")
	return nil
}

func (a *NATSAgent) handleCommand(msg *nats.Msg) {
	var cmd CommandMsg
	if err := json.Unmarshal(msg.Data, &cmd); err != nil {
		a.respondCmd(msg, &CommandResultMsg{Success: false, Error: err.Error()})
		return
	}

	log.Printf("Executing command: %s", cmd.Command)
	start := time.Now()

	// Run command via nsh
	ctx := context.Background()
	if cmd.Timeout > 0 {
		var cancel context.CancelFunc
		ctx, cancel = context.WithTimeout(ctx, time.Duration(cmd.Timeout)*time.Second)
		defer cancel()
	}

	execCmd := exec.CommandContext(ctx, a.nshPath, "-c", cmd.Command)
	output, err := execCmd.CombinedOutput()

	result := &CommandResultMsg{
		Output:   string(output),
		Duration: time.Since(start).Milliseconds(),
	}

	if err != nil {
		result.Success = false
		result.Error = err.Error()
		if exitErr, ok := err.(*exec.ExitError); ok {
			result.ExitCode = exitErr.ExitCode()
		}
	} else {
		result.Success = true
		result.ExitCode = 0
	}

	a.respondCmd(msg, result)
}

func (a *NATSAgent) respond(msg *nats.Msg, success bool, message, errMsg string) {
	if msg.Reply == "" {
		return
	}
	resp := map[string]interface{}{
		"success": success,
		"message": message,
	}
	if errMsg != "" {
		resp["error"] = errMsg
	}
	data, _ := json.Marshal(resp)
	msg.Respond(data)
}

func (a *NATSAgent) respondCmd(msg *nats.Msg, result *CommandResultMsg) {
	if msg.Reply == "" {
		return
	}
	data, _ := json.Marshal(result)
	msg.Respond(data)
}
