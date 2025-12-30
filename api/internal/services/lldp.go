package services

import (
	"bufio"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"sync"
	"time"
)

// LLDPConfig represents lldpd configuration
type LLDPConfig struct {
	Enabled       bool     `json:"enabled"`
	Interfaces    []string `json:"interfaces"` // empty = all interfaces
	HoldTime      int      `json:"hold_time"`  // multiplier for TTL
	Interval      int      `json:"interval"`   // transmit interval in seconds
	SystemName    string   `json:"system_name"`
	SystemDesc    string   `json:"system_description"`
	ManagementIP  string   `json:"management_ip"`
	EnableCDP     bool     `json:"enable_cdp"`  // Cisco Discovery Protocol
	EnableEDP     bool     `json:"enable_edp"`  // Extreme Discovery Protocol
	EnableSONMP   bool     `json:"enable_sonmp"` // SynOptics Network Management Protocol
}

// LLDPNeighbor represents a discovered LLDP neighbor
type LLDPNeighbor struct {
	LocalInterface    string            `json:"local_interface"`
	ChassisID         string            `json:"chassis_id"`
	ChassisIDType     string            `json:"chassis_id_type"`
	PortID            string            `json:"port_id"`
	PortIDType        string            `json:"port_id_type"`
	PortDescription   string            `json:"port_description"`
	SystemName        string            `json:"system_name"`
	SystemDescription string            `json:"system_description"`
	Capabilities      []string          `json:"capabilities"`
	EnabledCaps       []string          `json:"enabled_capabilities"`
	ManagementAddress string            `json:"management_address"`
	VLAN              int               `json:"vlan"`
	VLANName          string            `json:"vlan_name"`
	TTL               int               `json:"ttl"`
	Age               int               `json:"age"`
	Protocol          string            `json:"protocol"` // LLDP, CDP, EDP
	CustomTLVs        map[string]string `json:"custom_tlvs"`
}

// LLDPLocalInfo represents local LLDP information
type LLDPLocalInfo struct {
	ChassisID        string   `json:"chassis_id"`
	SystemName       string   `json:"system_name"`
	SystemDescription string  `json:"system_description"`
	Capabilities     []string `json:"capabilities"`
	Interfaces       []LLDPLocalInterface `json:"interfaces"`
}

// LLDPLocalInterface represents local interface LLDP info
type LLDPLocalInterface struct {
	Name            string `json:"name"`
	PortID          string `json:"port_id"`
	PortDescription string `json:"port_description"`
	Status          string `json:"status"`
	TTL             int    `json:"ttl"`
}

// LLDPStatistics contains LLDP statistics
type LLDPStatistics struct {
	Interface    string `json:"interface"`
	FramesIn     int64  `json:"frames_in"`
	FramesOut    int64  `json:"frames_out"`
	FramesDiscard int64 `json:"frames_discard"`
	FramesError  int64  `json:"frames_error"`
	TLVsDiscard  int64  `json:"tlvs_discard"`
	TLVsUnknown  int64  `json:"tlvs_unknown"`
	AgeoutsTotal int64  `json:"ageouts_total"`
}

// LLDPService manages LLDP discovery
type LLDPService struct {
	configPath string
	config     LLDPConfig
	neighbors  []LLDPNeighbor
	lastUpdate time.Time
	mu         sync.RWMutex
}

// NewLLDPService creates a new LLDP service
func NewLLDPService(configPath string) *LLDPService {
	svc := &LLDPService{
		configPath: configPath,
		config: LLDPConfig{
			Enabled:  true,
			HoldTime: 4,
			Interval: 30,
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *LLDPService) loadConfig() {
	configFile := filepath.Join(s.configPath, "lldp.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *LLDPService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "lldp.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the LLDP configuration
func (s *LLDPService) GetConfig() LLDPConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the LLDP configuration
func (s *LLDPService) UpdateConfig(config LLDPConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetNeighbors returns all discovered LLDP neighbors
func (s *LLDPService) GetNeighbors() ([]LLDPNeighbor, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	// Parse lldp show neighbors
	output, err := exec.Command("lldp", "show", "neighbors").Output()
	if err != nil {
		return nil, fmt.Errorf("failed to get LLDP neighbors: %w", err)
	}

	neighbors := s.parseNeighbors(string(output))
	s.neighbors = neighbors
	s.lastUpdate = time.Now()

	return neighbors, nil
}

func (s *LLDPService) parseNeighbors(output string) []LLDPNeighbor {
	var neighbors []LLDPNeighbor
	var current *LLDPNeighbor

	scanner := bufio.NewScanner(strings.NewReader(output))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())

		if strings.HasPrefix(line, "Interface:") {
			if current != nil {
				neighbors = append(neighbors, *current)
			}
			current = &LLDPNeighbor{
				CustomTLVs: make(map[string]string),
			}
			parts := strings.SplitN(line, ":", 2)
			if len(parts) > 1 {
				current.LocalInterface = strings.TrimSpace(parts[1])
			}
		} else if current != nil {
			parts := strings.SplitN(line, ":", 2)
			if len(parts) != 2 {
				continue
			}
			key := strings.TrimSpace(parts[0])
			value := strings.TrimSpace(parts[1])

			switch key {
			case "ChassisID":
				current.ChassisID = value
			case "SysName":
				current.SystemName = value
			case "SysDescr":
				current.SystemDescription = value
			case "PortID":
				current.PortID = value
			case "PortDescr":
				current.PortDescription = value
			case "TTL":
				current.TTL, _ = strconv.Atoi(value)
			case "Age":
				current.Age, _ = strconv.Atoi(strings.TrimSuffix(value, "s"))
			case "MgmtIP":
				current.ManagementAddress = value
			case "Capability":
				current.Capabilities = append(current.Capabilities, value)
			case "VLAN":
				current.VLAN, _ = strconv.Atoi(value)
			case "Protocol":
				current.Protocol = value
			default:
				current.CustomTLVs[key] = value
			}
		}
	}

	if current != nil {
		neighbors = append(neighbors, *current)
	}

	return neighbors
}

// GetNeighborByInterface returns neighbors on a specific interface
func (s *LLDPService) GetNeighborByInterface(iface string) ([]LLDPNeighbor, error) {
	neighbors, err := s.GetNeighbors()
	if err != nil {
		return nil, err
	}

	var filtered []LLDPNeighbor
	for _, n := range neighbors {
		if n.LocalInterface == iface {
			filtered = append(filtered, n)
		}
	}
	return filtered, nil
}

// GetLocalInfo returns local LLDP information
func (s *LLDPService) GetLocalInfo() (*LLDPLocalInfo, error) {
	output, err := exec.Command("lldp", "show", "local").Output()
	if err != nil {
		return nil, fmt.Errorf("failed to get local LLDP info: %w", err)
	}

	info := &LLDPLocalInfo{}
	scanner := bufio.NewScanner(strings.NewReader(string(output)))

	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		parts := strings.SplitN(line, ":", 2)
		if len(parts) != 2 {
			continue
		}

		key := strings.TrimSpace(parts[0])
		value := strings.TrimSpace(parts[1])

		switch key {
		case "ChassisID":
			info.ChassisID = value
		case "SysName":
			info.SystemName = value
		case "SysDescr":
			info.SystemDescription = value
		case "Capability":
			info.Capabilities = append(info.Capabilities, value)
		}
	}

	return info, nil
}

// GetStatistics returns LLDP statistics per interface
func (s *LLDPService) GetStatistics() ([]LLDPStatistics, error) {
	output, err := exec.Command("lldp", "show", "statistics").Output()
	if err != nil {
		return nil, fmt.Errorf("failed to get LLDP statistics: %w", err)
	}

	var stats []LLDPStatistics
	var current *LLDPStatistics

	scanner := bufio.NewScanner(strings.NewReader(string(output)))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())

		if strings.HasPrefix(line, "Interface") {
			if current != nil {
				stats = append(stats, *current)
			}
			parts := strings.SplitN(line, ":", 2)
			if len(parts) > 1 {
				current = &LLDPStatistics{
					Interface: strings.TrimSpace(parts[1]),
				}
			}
		} else if current != nil {
			parts := strings.SplitN(line, ":", 2)
			if len(parts) != 2 {
				continue
			}

			value, _ := strconv.ParseInt(strings.TrimSpace(parts[1]), 10, 64)
			switch strings.TrimSpace(parts[0]) {
			case "Frames In":
				current.FramesIn = value
			case "Frames Out":
				current.FramesOut = value
			case "Frames Discarded":
				current.FramesDiscard = value
			case "Frames Error":
				current.FramesError = value
			case "TLVs Discarded":
				current.TLVsDiscard = value
			case "TLVs Unknown":
				current.TLVsUnknown = value
			case "Ageouts":
				current.AgeoutsTotal = value
			}
		}
	}

	if current != nil {
		stats = append(stats, *current)
	}

	return stats, nil
}

// GetTopology builds a network topology map from LLDP data
func (s *LLDPService) GetTopology() (map[string]interface{}, error) {
	neighbors, err := s.GetNeighbors()
	if err != nil {
		return nil, err
	}

	localInfo, _ := s.GetLocalInfo()

	// Build topology graph
	nodes := make(map[string]map[string]interface{})
	links := []map[string]interface{}{}

	// Add local node
	localNode := map[string]interface{}{
		"id":          localInfo.ChassisID,
		"name":        localInfo.SystemName,
		"description": localInfo.SystemDescription,
		"type":        "local",
	}
	nodes[localInfo.ChassisID] = localNode

	// Add neighbor nodes and links
	for _, n := range neighbors {
		// Add neighbor node if not exists
		if _, exists := nodes[n.ChassisID]; !exists {
			nodes[n.ChassisID] = map[string]interface{}{
				"id":          n.ChassisID,
				"name":        n.SystemName,
				"description": n.SystemDescription,
				"type":        "neighbor",
				"management":  n.ManagementAddress,
			}
		}

		// Add link
		links = append(links, map[string]interface{}{
			"source":        localInfo.ChassisID,
			"target":        n.ChassisID,
			"local_port":    n.LocalInterface,
			"remote_port":   n.PortID,
			"remote_port_desc": n.PortDescription,
		})
	}

	// Convert nodes map to list
	nodeList := make([]map[string]interface{}, 0, len(nodes))
	for _, n := range nodes {
		nodeList = append(nodeList, n)
	}

	return map[string]interface{}{
		"nodes":       nodeList,
		"links":       links,
		"last_update": s.lastUpdate,
	}, nil
}

// Enable enables the lldpd service
func (s *LLDPService) Enable() error {
	s.mu.Lock()
	s.config.Enabled = true
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "enable", "lldpd").Run()
	return exec.Command("rcctl", "start", "lldpd").Run()
}

// Disable disables the lldpd service
func (s *LLDPService) Disable() error {
	s.mu.Lock()
	s.config.Enabled = false
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "stop", "lldpd").Run()
	return exec.Command("rcctl", "disable", "lldpd").Run()
}

// GetStatus returns lldpd service status
func (s *LLDPService) GetStatus() map[string]interface{} {
	s.mu.RLock()
	defer s.mu.RUnlock()

	running := false
	output, err := exec.Command("rcctl", "check", "lldpd").CombinedOutput()
	if err == nil && strings.Contains(string(output), "ok") {
		running = true
	}

	neighborCount := len(s.neighbors)

	return map[string]interface{}{
		"enabled":       s.config.Enabled,
		"running":       running,
		"neighbor_count": neighborCount,
		"last_update":   s.lastUpdate,
	}
}
