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
)

// BGPConfig represents OpenBGPD configuration
type BGPConfig struct {
	Enabled      bool          `json:"enabled"`
	ASNumber     uint32        `json:"as_number"`
	RouterID     string        `json:"router_id"`
	Holdtime     int           `json:"holdtime"` // default 90
	FIBUpdate    bool          `json:"fib_update"`
	LogUpdates   bool          `json:"log_updates"`
	Networks     []BGPNetwork  `json:"networks"`
	Neighbors    []BGPNeighbor `json:"neighbors"`
	Groups       []BGPGroup    `json:"groups"`
	Filters      []BGPFilter   `json:"filters"`
	RDomains     []BGPRDomain  `json:"rdomains"`
}

// BGPNetwork represents a network to announce
type BGPNetwork struct {
	Prefix   string `json:"prefix"`
	Set      string `json:"set,omitempty"` // community, localpref, etc.
}

// BGPNeighbor represents a BGP peer
type BGPNeighbor struct {
	Address       string   `json:"address"`
	RemoteAS      uint32   `json:"remote_as"`
	Description   string   `json:"description"`
	Multihop      int      `json:"multihop,omitempty"`
	LocalAddress  string   `json:"local_address,omitempty"`
	Holdtime      int      `json:"holdtime,omitempty"`
	MaxPrefix     int      `json:"max_prefix,omitempty"`
	MaxPrefixRestart int   `json:"max_prefix_restart,omitempty"`
	Announce      string   `json:"announce,omitempty"` // self, all, none
	Depend        string   `json:"depend,omitempty"`   // interface dependency
	Demote        string   `json:"demote,omitempty"`   // carp demotion group
	TTLSecurity   bool     `json:"ttl_security"`
	PassiveMode   bool     `json:"passive"`
	DownRule      string   `json:"down_rule,omitempty"`
	Group         string   `json:"group,omitempty"`
	Enabled       bool     `json:"enabled"`
}

// BGPGroup represents a peer group
type BGPGroup struct {
	Name          string   `json:"name"`
	RemoteAS      uint32   `json:"remote_as"`
	Announce      string   `json:"announce,omitempty"`
	Holdtime      int      `json:"holdtime,omitempty"`
	LocalAddress  string   `json:"local_address,omitempty"`
}

// BGPFilter represents an input/output filter
type BGPFilter struct {
	Name       string   `json:"name"`
	Direction  string   `json:"direction"` // in, out
	Match      BGPMatch `json:"match"`
	Action     string   `json:"action"` // allow, deny
	Set        string   `json:"set,omitempty"` // set commands
	From       string   `json:"from,omitempty"` // neighbor or any
}

// BGPMatch represents filter match criteria
type BGPMatch struct {
	Prefix       string `json:"prefix,omitempty"`
	PrefixLen    string `json:"prefix_len,omitempty"` // >= 24 or <= 32
	ASPath       string `json:"as_path,omitempty"`
	Community    string `json:"community,omitempty"`
	LargeCommunity string `json:"large_community,omitempty"`
	NextHop      string `json:"next_hop,omitempty"`
	PeerAS       string `json:"peer_as,omitempty"`
}

// BGPRDomain represents a routing domain
type BGPRDomain struct {
	ID        int      `json:"id"`
	Neighbors []string `json:"neighbors"`
}

// BGPStatus represents operational status
type BGPStatus struct {
	Running       bool         `json:"running"`
	PID           int          `json:"pid"`
	ASNumber      uint32       `json:"as_number"`
	RouterID      string       `json:"router_id"`
	NeighborCount int          `json:"neighbor_count"`
	Neighbors     []BGPPeerStatus `json:"neighbors"`
	RIBStats      BGPRIBStats  `json:"rib_stats"`
}

// BGPPeerStatus represents a peer's status
type BGPPeerStatus struct {
	Address         string  `json:"address"`
	Description     string  `json:"description"`
	RemoteAS        uint32  `json:"remote_as"`
	State           string  `json:"state"` // Idle, Connect, Active, OpenSent, OpenConfirm, Established
	Uptime          string  `json:"uptime"`
	PrefixReceived  int     `json:"prefix_received"`
	PrefixSent      int     `json:"prefix_sent"`
	LastRead        string  `json:"last_read"`
	LastWrite       string  `json:"last_write"`
	MessagesReceived int    `json:"messages_received"`
	MessagesSent    int     `json:"messages_sent"`
}

// BGPRIBStats represents RIB statistics
type BGPRIBStats struct {
	TotalPrefixes   int `json:"total_prefixes"`
	ActivePrefixes  int `json:"active_prefixes"`
	AdjRibIn        int `json:"adj_rib_in"`
	AdjRibOut       int `json:"adj_rib_out"`
}

// BGPRoute represents a route in the RIB
type BGPRoute struct {
	Prefix      string   `json:"prefix"`
	NextHop     string   `json:"next_hop"`
	ASPath      []uint32 `json:"as_path"`
	Origin      string   `json:"origin"` // IGP, EGP, Incomplete
	LocalPref   int      `json:"local_pref"`
	MED         int      `json:"med"`
	Communities []string `json:"communities"`
	Valid       bool     `json:"valid"`
	Best        bool     `json:"best"`
	FromPeer    string   `json:"from_peer"`
}

// BGPService manages OpenBGPD
type BGPService struct {
	configPath string
	config     BGPConfig
	mu         sync.RWMutex
}

// NewBGPService creates a new BGP service
func NewBGPService(configPath string) *BGPService {
	svc := &BGPService{
		configPath: configPath,
		config: BGPConfig{
			Enabled:   false,
			Holdtime:  90,
			FIBUpdate: true,
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *BGPService) loadConfig() {
	configFile := filepath.Join(s.configPath, "bgpd.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *BGPService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "bgpd.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the BGP configuration
func (s *BGPService) GetConfig() BGPConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the BGP configuration
func (s *BGPService) UpdateConfig(config BGPConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetStatus returns BGP operational status
func (s *BGPService) GetStatus() (*BGPStatus, error) {
	status := &BGPStatus{}

	// Check if running
	output, err := exec.Command("rcctl", "check", "bgpd").CombinedOutput()
	status.Running = err == nil && strings.Contains(string(output), "ok")

	if !status.Running {
		return status, nil
	}

	// Get PID
	pidBytes, _ := os.ReadFile("/var/run/bgpd.pid")
	status.PID, _ = strconv.Atoi(strings.TrimSpace(string(pidBytes)))

	// Get neighbor summary
	output, err = exec.Command("bgpctl", "show", "summary").Output()
	if err == nil {
		status.Neighbors = s.parseNeighborSummary(string(output))
		status.NeighborCount = len(status.Neighbors)
	}

	// Get RIB stats
	output, err = exec.Command("bgpctl", "show", "rib", "memory").Output()
	if err == nil {
		status.RIBStats = s.parseRIBStats(string(output))
	}

	s.mu.RLock()
	status.ASNumber = s.config.ASNumber
	status.RouterID = s.config.RouterID
	s.mu.RUnlock()

	return status, nil
}

func (s *BGPService) parseNeighborSummary(output string) []BGPPeerStatus {
	var peers []BGPPeerStatus

	scanner := bufio.NewScanner(strings.NewReader(output))
	// Skip header
	scanner.Scan()

	// Example: 10.0.0.1         4 65001   1234    5678       0    0 01:23:45 Established
	peerRegex := regexp.MustCompile(`^(\S+)\s+\d+\s+(\d+)\s+(\d+)\s+(\d+)\s+\d+\s+\d+\s+(\S+)\s+(\S+)`)

	for scanner.Scan() {
		line := scanner.Text()
		matches := peerRegex.FindStringSubmatch(line)
		if matches == nil {
			continue
		}

		remoteAS, _ := strconv.ParseUint(matches[2], 10, 32)
		msgsRecv, _ := strconv.Atoi(matches[3])
		msgsSent, _ := strconv.Atoi(matches[4])

		peer := BGPPeerStatus{
			Address:          matches[1],
			RemoteAS:         uint32(remoteAS),
			MessagesReceived: msgsRecv,
			MessagesSent:     msgsSent,
			Uptime:           matches[5],
			State:            matches[6],
		}

		peers = append(peers, peer)
	}

	return peers
}

func (s *BGPService) parseRIBStats(output string) BGPRIBStats {
	stats := BGPRIBStats{}

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if strings.Contains(line, "RIB entries:") {
			fmt.Sscanf(line, "RIB entries: %d", &stats.TotalPrefixes)
		} else if strings.Contains(line, "Adj-RIB-In") {
			fmt.Sscanf(line, "Adj-RIB-In entries: %d", &stats.AdjRibIn)
		} else if strings.Contains(line, "Adj-RIB-Out") {
			fmt.Sscanf(line, "Adj-RIB-Out entries: %d", &stats.AdjRibOut)
		}
	}

	return stats
}

// GetNeighborDetail returns detailed info about a neighbor
func (s *BGPService) GetNeighborDetail(address string) (*BGPPeerStatus, error) {
	output, err := exec.Command("bgpctl", "show", "neighbor", address).Output()
	if err != nil {
		return nil, fmt.Errorf("failed to get neighbor details: %w", err)
	}

	peer := &BGPPeerStatus{Address: address}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if strings.HasPrefix(line, "Description:") {
			peer.Description = strings.TrimSpace(strings.TrimPrefix(line, "Description:"))
		} else if strings.HasPrefix(line, "BGP state:") {
			peer.State = strings.TrimSpace(strings.TrimPrefix(line, "BGP state:"))
		} else if strings.HasPrefix(line, "Last read") {
			peer.LastRead = strings.TrimSpace(strings.TrimPrefix(line, "Last read"))
		} else if strings.Contains(line, "prefixes received") {
			fmt.Sscanf(line, "%d prefixes received", &peer.PrefixReceived)
		} else if strings.Contains(line, "prefixes sent") {
			fmt.Sscanf(line, "%d prefixes sent", &peer.PrefixSent)
		}
	}

	return peer, nil
}

// GetRoutes returns BGP routes
func (s *BGPService) GetRoutes(peer string, family string) ([]BGPRoute, error) {
	args := []string{"show", "rib"}
	if peer != "" {
		args = append(args, "neighbor", peer)
	}
	if family == "ipv6" {
		args = append(args, "inet6")
	}
	args = append(args, "detail")

	output, err := exec.Command("bgpctl", args...).Output()
	if err != nil {
		return nil, err
	}

	return s.parseRoutes(string(output)), nil
}

func (s *BGPService) parseRoutes(output string) []BGPRoute {
	var routes []BGPRoute
	var current *BGPRoute

	scanner := bufio.NewScanner(strings.NewReader(output))
	for scanner.Scan() {
		line := scanner.Text()

		// New route starts with prefix
		if len(line) > 0 && (line[0] >= '0' && line[0] <= '9') {
			if current != nil {
				routes = append(routes, *current)
			}
			fields := strings.Fields(line)
			if len(fields) > 0 {
				current = &BGPRoute{
					Prefix: fields[0],
				}
				// Check for flags like * (valid) or > (best)
				if len(fields) > 1 {
					if strings.Contains(fields[1], "*") {
						current.Valid = true
					}
					if strings.Contains(fields[1], ">") {
						current.Best = true
					}
				}
			}
		} else if current != nil && strings.Contains(line, "Nexthop") {
			fields := strings.Fields(line)
			for i, f := range fields {
				if f == "Nexthop" && i+1 < len(fields) {
					current.NextHop = fields[i+1]
				}
			}
		} else if current != nil && strings.Contains(line, "AS path:") {
			asPathStr := strings.TrimPrefix(strings.TrimSpace(line), "AS path:")
			for _, asStr := range strings.Fields(asPathStr) {
				if asn, err := strconv.ParseUint(asStr, 10, 32); err == nil {
					current.ASPath = append(current.ASPath, uint32(asn))
				}
			}
		} else if current != nil && strings.Contains(line, "Community:") {
			commStr := strings.TrimPrefix(strings.TrimSpace(line), "Community:")
			current.Communities = strings.Fields(commStr)
		} else if current != nil && strings.Contains(line, "Localpref") {
			fmt.Sscanf(line, " Localpref %d", &current.LocalPref)
		}
	}

	if current != nil {
		routes = append(routes, *current)
	}

	return routes
}

// AddNeighbor adds a BGP neighbor
func (s *BGPService) AddNeighbor(neighbor BGPNeighbor) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, n := range s.config.Neighbors {
		if n.Address == neighbor.Address {
			return fmt.Errorf("neighbor already exists")
		}
	}

	neighbor.Enabled = true
	s.config.Neighbors = append(s.config.Neighbors, neighbor)
	return s.saveConfig()
}

// RemoveNeighbor removes a BGP neighbor
func (s *BGPService) RemoveNeighbor(address string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, n := range s.config.Neighbors {
		if n.Address == address {
			s.config.Neighbors = append(s.config.Neighbors[:i], s.config.Neighbors[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("neighbor not found")
}

// UpdateNeighbor updates a BGP neighbor
func (s *BGPService) UpdateNeighbor(address string, neighbor BGPNeighbor) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, n := range s.config.Neighbors {
		if n.Address == address {
			s.config.Neighbors[i] = neighbor
			return s.saveConfig()
		}
	}
	return fmt.Errorf("neighbor not found")
}

// EnableNeighbor enables a neighbor session
func (s *BGPService) EnableNeighbor(address string) error {
	return exec.Command("bgpctl", "neighbor", address, "up").Run()
}

// DisableNeighbor disables a neighbor session
func (s *BGPService) DisableNeighbor(address string) error {
	return exec.Command("bgpctl", "neighbor", address, "down").Run()
}

// ClearNeighbor clears and resets a neighbor session
func (s *BGPService) ClearNeighbor(address string) error {
	return exec.Command("bgpctl", "neighbor", address, "clear").Run()
}

// RefreshNeighbor triggers route refresh
func (s *BGPService) RefreshNeighbor(address string) error {
	return exec.Command("bgpctl", "neighbor", address, "refresh").Run()
}

// AddNetwork adds a network to announce
func (s *BGPService) AddNetwork(network BGPNetwork) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, n := range s.config.Networks {
		if n.Prefix == network.Prefix {
			return fmt.Errorf("network already exists")
		}
	}

	s.config.Networks = append(s.config.Networks, network)
	return s.saveConfig()
}

// RemoveNetwork removes a network
func (s *BGPService) RemoveNetwork(prefix string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, n := range s.config.Networks {
		if n.Prefix == prefix {
			s.config.Networks = append(s.config.Networks[:i], s.config.Networks[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("network not found")
}

// GenerateConfig generates /etc/bgpd.conf
func (s *BGPService) GenerateConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var buf strings.Builder

	buf.WriteString(`#
# NSWall OpenBGPD Configuration
# Generated by NSWall - Do not edit manually
#

`)

	// Global config
	buf.WriteString(fmt.Sprintf("AS %d\n", s.config.ASNumber))
	if s.config.RouterID != "" {
		buf.WriteString(fmt.Sprintf("router-id %s\n", s.config.RouterID))
	}
	if s.config.Holdtime > 0 && s.config.Holdtime != 90 {
		buf.WriteString(fmt.Sprintf("holdtime %d\n", s.config.Holdtime))
	}
	if !s.config.FIBUpdate {
		buf.WriteString("fib-update no\n")
	}
	if s.config.LogUpdates {
		buf.WriteString("log updates\n")
	}
	buf.WriteString("\n")

	// Networks
	if len(s.config.Networks) > 0 {
		for _, net := range s.config.Networks {
			line := fmt.Sprintf("network %s", net.Prefix)
			if net.Set != "" {
				line += fmt.Sprintf(" set { %s }", net.Set)
			}
			buf.WriteString(line + "\n")
		}
		buf.WriteString("\n")
	}

	// Groups
	for _, group := range s.config.Groups {
		buf.WriteString(fmt.Sprintf("group \"%s\" {\n", group.Name))
		buf.WriteString(fmt.Sprintf("\tremote-as %d\n", group.RemoteAS))
		if group.LocalAddress != "" {
			buf.WriteString(fmt.Sprintf("\tlocal-address %s\n", group.LocalAddress))
		}
		if group.Holdtime > 0 {
			buf.WriteString(fmt.Sprintf("\tholdtime %d\n", group.Holdtime))
		}
		if group.Announce != "" {
			buf.WriteString(fmt.Sprintf("\tannounce %s\n", group.Announce))
		}
		buf.WriteString("}\n\n")
	}

	// Neighbors
	for _, neighbor := range s.config.Neighbors {
		if !neighbor.Enabled {
			buf.WriteString("# ")
		}
		buf.WriteString(fmt.Sprintf("neighbor %s {\n", neighbor.Address))
		buf.WriteString(fmt.Sprintf("\tremote-as %d\n", neighbor.RemoteAS))
		if neighbor.Description != "" {
			buf.WriteString(fmt.Sprintf("\tdescr \"%s\"\n", neighbor.Description))
		}
		if neighbor.LocalAddress != "" {
			buf.WriteString(fmt.Sprintf("\tlocal-address %s\n", neighbor.LocalAddress))
		}
		if neighbor.Multihop > 0 {
			buf.WriteString(fmt.Sprintf("\tmultihop %d\n", neighbor.Multihop))
		}
		if neighbor.Holdtime > 0 {
			buf.WriteString(fmt.Sprintf("\tholdtime %d\n", neighbor.Holdtime))
		}
		if neighbor.MaxPrefix > 0 {
			line := fmt.Sprintf("\tmax-prefix %d", neighbor.MaxPrefix)
			if neighbor.MaxPrefixRestart > 0 {
				line += fmt.Sprintf(" restart %d", neighbor.MaxPrefixRestart)
			}
			buf.WriteString(line + "\n")
		}
		if neighbor.Announce != "" {
			buf.WriteString(fmt.Sprintf("\tannounce %s\n", neighbor.Announce))
		}
		if neighbor.Depend != "" {
			buf.WriteString(fmt.Sprintf("\tdepend on %s\n", neighbor.Depend))
		}
		if neighbor.Demote != "" {
			buf.WriteString(fmt.Sprintf("\tdemote %s\n", neighbor.Demote))
		}
		if neighbor.TTLSecurity {
			buf.WriteString("\tttl-security yes\n")
		}
		if neighbor.PassiveMode {
			buf.WriteString("\tpassive\n")
		}
		if neighbor.DownRule != "" {
			buf.WriteString(fmt.Sprintf("\tdown \"%s\"\n", neighbor.DownRule))
		}
		buf.WriteString("}\n\n")
	}

	// Filters
	for _, filter := range s.config.Filters {
		direction := filter.Direction
		from := filter.From
		if from == "" {
			from = "any"
		}

		line := fmt.Sprintf("%s ", filter.Action)
		if filter.Direction == "in" {
			line += fmt.Sprintf("from %s ", from)
		} else {
			line += fmt.Sprintf("to %s ", from)
		}

		// Match criteria
		if filter.Match.Prefix != "" {
			line += fmt.Sprintf("prefix %s ", filter.Match.Prefix)
		}
		if filter.Match.Community != "" {
			line += fmt.Sprintf("community %s ", filter.Match.Community)
		}
		if filter.Match.ASPath != "" {
			line += fmt.Sprintf("as-path \"%s\" ", filter.Match.ASPath)
		}

		if filter.Set != "" {
			line += fmt.Sprintf("set { %s }", filter.Set)
		}

		buf.WriteString(line + "\n")
		_ = direction // used in logic above
	}

	return os.WriteFile("/etc/bgpd.conf", []byte(buf.String()), 0640)
}

// Enable enables the bgpd service
func (s *BGPService) Enable() error {
	s.mu.Lock()
	s.config.Enabled = true
	s.saveConfig()
	s.mu.Unlock()

	if err := s.GenerateConfig(); err != nil {
		return err
	}

	exec.Command("rcctl", "enable", "bgpd").Run()
	return exec.Command("rcctl", "start", "bgpd").Run()
}

// Disable disables the bgpd service
func (s *BGPService) Disable() error {
	s.mu.Lock()
	s.config.Enabled = false
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "stop", "bgpd").Run()
	return exec.Command("rcctl", "disable", "bgpd").Run()
}

// Reload reloads bgpd configuration
func (s *BGPService) Reload() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}
	return exec.Command("bgpctl", "reload").Run()
}

// ValidateConfig validates the configuration
func (s *BGPService) ValidateConfig() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}

	output, err := exec.Command("bgpd", "-n").CombinedOutput()
	if err != nil {
		return fmt.Errorf("config validation failed: %s", string(output))
	}

	return nil
}
