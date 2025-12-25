package services

import (
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
	"sync"
)

// PflowConfig represents pflow NetFlow/IPFIX export configuration
type PflowConfig struct {
	Enabled     bool          `json:"enabled"`
	Interfaces  []PflowIface  `json:"interfaces"`
}

// PflowIface represents a pflow interface configuration
type PflowIface struct {
	Name        string `json:"name"`       // pflow0, pflow1, etc.
	FlowSrc     string `json:"flowsrc"`    // source IP for flow packets
	FlowDst     string `json:"flowdst"`    // collector IP:port
	Version     int    `json:"version"`    // 5, 9, or 10 (IPFIX)
	Description string `json:"description"`
	Enabled     bool   `json:"enabled"`
}

// PflowStats represents pflow interface statistics
type PflowStats struct {
	Interface    string `json:"interface"`
	FlowsSent    int64  `json:"flows_sent"`
	PacketsSent  int64  `json:"packets_sent"`
	BytesSent    int64  `json:"bytes_sent"`
	FlowsDropped int64  `json:"flows_dropped"`
	Errors       int64  `json:"errors"`
}

// PflowService manages pflow NetFlow export
type PflowService struct {
	configPath string
	config     PflowConfig
	mu         sync.RWMutex
}

// NewPflowService creates a new pflow service
func NewPflowService(configPath string) *PflowService {
	svc := &PflowService{
		configPath: configPath,
		config: PflowConfig{
			Enabled: false,
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *PflowService) loadConfig() {
	configFile := filepath.Join(s.configPath, "pflow.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *PflowService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "pflow.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the pflow configuration
func (s *PflowService) GetConfig() PflowConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the pflow configuration
func (s *PflowService) UpdateConfig(config PflowConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetInterfaces returns all pflow interfaces
func (s *PflowService) GetInterfaces() ([]PflowIface, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	// Augment with live data from ifconfig
	interfaces := make([]PflowIface, len(s.config.Interfaces))
	copy(interfaces, s.config.Interfaces)

	for i := range interfaces {
		output, err := exec.Command("ifconfig", interfaces[i].Name).Output()
		if err == nil {
			interfaces[i].Enabled = strings.Contains(string(output), "UP")
		}
	}

	return interfaces, nil
}

// AddInterface creates a new pflow interface
func (s *PflowService) AddInterface(iface PflowIface) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	// Validate
	if iface.FlowDst == "" {
		return fmt.Errorf("flow destination is required")
	}
	if iface.Version == 0 {
		iface.Version = 10 // Default to IPFIX
	}

	// Generate interface name if not provided
	if iface.Name == "" {
		iface.Name = fmt.Sprintf("pflow%d", len(s.config.Interfaces))
	}

	// Check if already exists
	for _, existing := range s.config.Interfaces {
		if existing.Name == iface.Name {
			return fmt.Errorf("interface %s already exists", iface.Name)
		}
	}

	s.config.Interfaces = append(s.config.Interfaces, iface)
	return s.saveConfig()
}

// UpdateInterface updates a pflow interface
func (s *PflowService) UpdateInterface(name string, iface PflowIface) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, existing := range s.config.Interfaces {
		if existing.Name == name {
			iface.Name = name
			s.config.Interfaces[i] = iface
			return s.saveConfig()
		}
	}
	return fmt.Errorf("interface not found")
}

// DeleteInterface removes a pflow interface
func (s *PflowService) DeleteInterface(name string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, iface := range s.config.Interfaces {
		if iface.Name == name {
			s.config.Interfaces = append(s.config.Interfaces[:i], s.config.Interfaces[i+1:]...)

			// Destroy the interface
			exec.Command("ifconfig", name, "destroy").Run()

			return s.saveConfig()
		}
	}
	return fmt.Errorf("interface not found")
}

// ApplyConfig applies the pflow configuration to the system
func (s *PflowService) ApplyConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	for _, iface := range s.config.Interfaces {
		if !iface.Enabled {
			// Destroy if exists
			exec.Command("ifconfig", iface.Name, "destroy").Run()
			continue
		}

		// Create interface
		if err := exec.Command("ifconfig", iface.Name, "create").Run(); err != nil {
			// May already exist, continue
		}

		// Configure pflow parameters
		args := []string{iface.Name}

		if iface.FlowSrc != "" {
			args = append(args, "flowsrc", iface.FlowSrc)
		}

		if iface.FlowDst != "" {
			args = append(args, "flowdst", iface.FlowDst)
		}

		// Set version: pflowproto 5, 9, or 10
		args = append(args, "pflowproto", strconv.Itoa(iface.Version))

		args = append(args, "up")

		if err := exec.Command("ifconfig", args...).Run(); err != nil {
			return fmt.Errorf("failed to configure %s: %w", iface.Name, err)
		}
	}

	// Generate hostname.pflow* files for persistence
	return s.generateHostnameFiles()
}

func (s *PflowService) generateHostnameFiles() error {
	for _, iface := range s.config.Interfaces {
		if !iface.Enabled {
			// Remove hostname file if exists
			os.Remove(fmt.Sprintf("/etc/hostname.%s", iface.Name))
			continue
		}

		var content strings.Builder
		content.WriteString("up\n")

		if iface.FlowSrc != "" {
			content.WriteString(fmt.Sprintf("flowsrc %s\n", iface.FlowSrc))
		}
		content.WriteString(fmt.Sprintf("flowdst %s\n", iface.FlowDst))
		content.WriteString(fmt.Sprintf("pflowproto %d\n", iface.Version))

		if iface.Description != "" {
			content.WriteString(fmt.Sprintf("description \"%s\"\n", iface.Description))
		}

		filename := fmt.Sprintf("/etc/hostname.%s", iface.Name)
		if err := os.WriteFile(filename, []byte(content.String()), 0640); err != nil {
			return err
		}
	}
	return nil
}

// GetStats returns pflow interface statistics
func (s *PflowService) GetStats() ([]PflowStats, error) {
	var stats []PflowStats

	for _, iface := range s.config.Interfaces {
		output, err := exec.Command("netstat", "-I", iface.Name, "-b").Output()
		if err != nil {
			continue
		}

		// Parse netstat output
		lines := strings.Split(string(output), "\n")
		for _, line := range lines[1:] { // Skip header
			fields := strings.Fields(line)
			if len(fields) >= 7 && fields[0] == iface.Name {
				stat := PflowStats{Interface: iface.Name}
				stat.PacketsSent, _ = strconv.ParseInt(fields[4], 10, 64)
				stat.BytesSent, _ = strconv.ParseInt(fields[6], 10, 64)
				stat.Errors, _ = strconv.ParseInt(fields[5], 10, 64)
				stats = append(stats, stat)
				break
			}
		}
	}

	return stats, nil
}

// AttachToPF attaches a pflow interface to PF for state tracking
func (s *PflowService) AttachToPF(pflowName string) error {
	// Add pflow to PF options
	// This requires modifying pf.conf to include: set state-defaults pflow
	// For now, we'll use pfctl to set the option dynamically

	// Read current pf.conf
	pfConf, err := os.ReadFile("/etc/pf.conf")
	if err != nil {
		return err
	}

	// Check if pflow is already configured
	if strings.Contains(string(pfConf), "pflow") {
		return nil // Already configured
	}

	// Add pflow option - needs to be done via pf.conf modification
	// The set state-defaults line should be near the top
	return fmt.Errorf("pflow PF integration requires manual pf.conf configuration")
}

// GetStatus returns pflow service status
func (s *PflowService) GetStatus() map[string]interface{} {
	s.mu.RLock()
	defer s.mu.RUnlock()

	activeInterfaces := 0
	for _, iface := range s.config.Interfaces {
		if iface.Enabled {
			activeInterfaces++
		}
	}

	return map[string]interface{}{
		"enabled":           s.config.Enabled,
		"total_interfaces":  len(s.config.Interfaces),
		"active_interfaces": activeInterfaces,
	}
}

// ValidateCollector tests connectivity to a NetFlow collector
func (s *PflowService) ValidateCollector(address string) error {
	// Simple connectivity test - try to resolve and reach the host
	parts := strings.Split(address, ":")
	if len(parts) != 2 {
		return fmt.Errorf("invalid address format, expected host:port")
	}

	// Use nc to test UDP connectivity
	cmd := exec.Command("nc", "-z", "-u", "-w", "2", parts[0], parts[1])
	if err := cmd.Run(); err != nil {
		return fmt.Errorf("cannot reach collector at %s", address)
	}

	return nil
}
