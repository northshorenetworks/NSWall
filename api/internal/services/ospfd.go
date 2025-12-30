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

// OSPFConfig represents OpenOSPFD configuration
type OSPFConfig struct {
	Enabled      bool         `json:"enabled"`
	RouterID     string       `json:"router_id"`
	FIBUpdate    bool         `json:"fib_update"`
	RDomain      int          `json:"rdomain"`
	RFC1583      bool         `json:"rfc1583"` // RFC1583 compatibility
	StubRouter   bool         `json:"stub_router"`
	SPFDelay     int          `json:"spf_delay"`     // ms
	SPFHoldtime  int          `json:"spf_holdtime"`  // ms
	Redistribute []OSPFRedist `json:"redistribute"`
	Areas        []OSPFArea   `json:"areas"`
}

// OSPFRedist represents route redistribution
type OSPFRedist struct {
	Type     string `json:"type"` // connected, static, default, bgp
	Set      string `json:"set,omitempty"`
	Metric   int    `json:"metric,omitempty"`
	MetricType int  `json:"metric_type,omitempty"` // 1 or 2
}

// OSPFArea represents an OSPF area
type OSPFArea struct {
	ID          string          `json:"id"` // 0.0.0.0 format or integer
	Stub        bool            `json:"stub"`
	StubDefault bool            `json:"stub_default"`
	NSSA        bool            `json:"nssa"`
	Interfaces  []OSPFInterface `json:"interfaces"`
	Ranges      []OSPFRange     `json:"ranges"`
}

// OSPFInterface represents an interface in an area
type OSPFInterface struct {
	Name           string `json:"name"`
	Cost           int    `json:"cost,omitempty"`
	HelloInterval  int    `json:"hello_interval,omitempty"`  // seconds
	DeadInterval   int    `json:"dead_interval,omitempty"`   // seconds (usually 4x hello)
	RetransmitInterval int `json:"retransmit_interval,omitempty"`
	TransmitDelay  int    `json:"transmit_delay,omitempty"`
	Priority       int    `json:"priority,omitempty"`        // DR election
	Passive        bool   `json:"passive"`
	Metric         int    `json:"metric,omitempty"`
	Type           string `json:"type,omitempty"` // broadcast, point-to-point, point-to-multipoint
	AuthType       string `json:"auth_type,omitempty"` // simple, crypt
	AuthKey        string `json:"auth_key,omitempty"`
	AuthKeyID      int    `json:"auth_key_id,omitempty"`
	DependOn       string `json:"depend_on,omitempty"`
	Demote         string `json:"demote,omitempty"`
}

// OSPFRange represents an area range
type OSPFRange struct {
	Prefix      string `json:"prefix"`
	NotAdvertise bool  `json:"not_advertise"`
}

// OSPFStatus represents operational status
type OSPFStatus struct {
	Running       bool              `json:"running"`
	PID           int               `json:"pid"`
	RouterID      string            `json:"router_id"`
	SPFRuns       int               `json:"spf_runs"`
	NeighborCount int               `json:"neighbor_count"`
	Neighbors     []OSPFNeighbor    `json:"neighbors"`
	Interfaces    []OSPFIfaceStatus `json:"interfaces"`
	Database      OSPFDatabase      `json:"database"`
}

// OSPFNeighbor represents a neighbor
type OSPFNeighbor struct {
	RouterID     string `json:"router_id"`
	Address      string `json:"address"`
	Interface    string `json:"interface"`
	State        string `json:"state"` // Down, Init, 2-Way, ExStart, Exchange, Loading, Full
	Priority     int    `json:"priority"`
	DeadTimer    int    `json:"dead_timer"`
	AreaID       string `json:"area_id"`
	DR           string `json:"dr,omitempty"`
	BDR          string `json:"bdr,omitempty"`
	Uptime       string `json:"uptime"`
}

// OSPFIfaceStatus represents interface status
type OSPFIfaceStatus struct {
	Name          string `json:"name"`
	AreaID        string `json:"area_id"`
	State         string `json:"state"` // Down, Point-to-Point, DR, BDR, DROther
	Cost          int    `json:"cost"`
	Hello         int    `json:"hello"`
	Dead          int    `json:"dead"`
	NeighborCount int    `json:"neighbor_count"`
	DR            string `json:"dr"`
	BDR           string `json:"bdr"`
}

// OSPFDatabase represents LSDB summary
type OSPFDatabase struct {
	RouterLSA     int `json:"router_lsa"`
	NetworkLSA    int `json:"network_lsa"`
	SummaryLSA    int `json:"summary_lsa"`
	ASBRSummaryLSA int `json:"asbr_summary_lsa"`
	ExternalLSA   int `json:"external_lsa"`
	NSSAExternalLSA int `json:"nssa_external_lsa"`
}

// OSPFRoute represents an OSPF route
type OSPFRoute struct {
	Destination  string `json:"destination"`
	NextHop      string `json:"next_hop"`
	Interface    string `json:"interface"`
	Cost         int    `json:"cost"`
	Area         string `json:"area"`
	Type         string `json:"type"` // Intra, Inter, Ext1, Ext2
	UpTime       string `json:"uptime"`
}

// OSPFService manages OpenOSPFD
type OSPFService struct {
	configPath string
	config     OSPFConfig
	mu         sync.RWMutex
}

// NewOSPFService creates a new OSPF service
func NewOSPFService(configPath string) *OSPFService {
	svc := &OSPFService{
		configPath: configPath,
		config: OSPFConfig{
			Enabled:     false,
			FIBUpdate:   true,
			RFC1583:     false,
			SPFDelay:    1000,
			SPFHoldtime: 5000,
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *OSPFService) loadConfig() {
	configFile := filepath.Join(s.configPath, "ospfd.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *OSPFService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "ospfd.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the OSPF configuration
func (s *OSPFService) GetConfig() OSPFConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the OSPF configuration
func (s *OSPFService) UpdateConfig(config OSPFConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetStatus returns OSPF operational status
func (s *OSPFService) GetStatus() (*OSPFStatus, error) {
	status := &OSPFStatus{}

	// Check if running
	output, err := exec.Command("rcctl", "check", "ospfd").CombinedOutput()
	status.Running = err == nil && strings.Contains(string(output), "ok")

	if !status.Running {
		return status, nil
	}

	// Get PID
	pidBytes, _ := os.ReadFile("/var/run/ospfd.pid")
	status.PID, _ = strconv.Atoi(strings.TrimSpace(string(pidBytes)))

	// Get neighbors
	output, err = exec.Command("ospfctl", "show", "neighbor").Output()
	if err == nil {
		status.Neighbors = s.parseNeighbors(string(output))
		status.NeighborCount = len(status.Neighbors)
	}

	// Get interface status
	output, err = exec.Command("ospfctl", "show", "interface").Output()
	if err == nil {
		status.Interfaces = s.parseInterfaces(string(output))
	}

	// Get database summary
	output, err = exec.Command("ospfctl", "show", "database", "summary").Output()
	if err == nil {
		status.Database = s.parseDatabase(string(output))
	}

	s.mu.RLock()
	status.RouterID = s.config.RouterID
	s.mu.RUnlock()

	return status, nil
}

func (s *OSPFService) parseNeighbors(output string) []OSPFNeighbor {
	var neighbors []OSPFNeighbor

	scanner := bufio.NewScanner(strings.NewReader(output))
	// Skip header
	scanner.Scan()

	// Example: ID              Pri State        DeadTime Address         Iface  Uptime
	//          192.168.1.1       1 Full/BDR      36.250s 192.168.1.1     em0    12d02h42m
	neighborRegex := regexp.MustCompile(`^(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)`)

	for scanner.Scan() {
		line := scanner.Text()
		matches := neighborRegex.FindStringSubmatch(line)
		if matches == nil {
			continue
		}

		priority, _ := strconv.Atoi(matches[2])

		neighbor := OSPFNeighbor{
			RouterID:  matches[1],
			Priority:  priority,
			State:     matches[3],
			Address:   matches[5],
			Interface: matches[6],
			Uptime:    matches[7],
		}

		neighbors = append(neighbors, neighbor)
	}

	return neighbors
}

func (s *OSPFService) parseInterfaces(output string) []OSPFIfaceStatus {
	var interfaces []OSPFIfaceStatus

	scanner := bufio.NewScanner(strings.NewReader(output))
	// Skip header
	scanner.Scan()

	// Example: Interface    Address          State  HelloTimer Linkstate
	//          em0          192.168.1.1      DR        9.234s UP

	for scanner.Scan() {
		line := scanner.Text()
		fields := strings.Fields(line)
		if len(fields) < 5 {
			continue
		}

		iface := OSPFIfaceStatus{
			Name:  fields[0],
			State: fields[2],
		}

		interfaces = append(interfaces, iface)
	}

	return interfaces
}

func (s *OSPFService) parseDatabase(output string) OSPFDatabase {
	db := OSPFDatabase{}

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if strings.Contains(line, "Router LSA") {
			fmt.Sscanf(line, "Router LSA: %d", &db.RouterLSA)
		} else if strings.Contains(line, "Network LSA") {
			fmt.Sscanf(line, "Network LSA: %d", &db.NetworkLSA)
		} else if strings.Contains(line, "Summary LSA") {
			fmt.Sscanf(line, "Summary LSA: %d", &db.SummaryLSA)
		} else if strings.Contains(line, "AS External LSA") {
			fmt.Sscanf(line, "AS External LSA: %d", &db.ExternalLSA)
		}
	}

	return db
}

// GetRoutes returns OSPF routes
func (s *OSPFService) GetRoutes() ([]OSPFRoute, error) {
	output, err := exec.Command("ospfctl", "show", "rib").Output()
	if err != nil {
		return nil, err
	}

	return s.parseRoutes(string(output)), nil
}

func (s *OSPFService) parseRoutes(output string) []OSPFRoute {
	var routes []OSPFRoute

	scanner := bufio.NewScanner(strings.NewReader(output))
	// Skip header
	scanner.Scan()

	// Example: Destination         Nexthop             Metric  Type  Area
	//          192.168.1.0/24      192.168.1.1         10      Intra 0.0.0.0

	for scanner.Scan() {
		line := scanner.Text()
		fields := strings.Fields(line)
		if len(fields) < 5 {
			continue
		}

		cost, _ := strconv.Atoi(fields[2])

		route := OSPFRoute{
			Destination: fields[0],
			NextHop:     fields[1],
			Cost:        cost,
			Type:        fields[3],
			Area:        fields[4],
		}

		routes = append(routes, route)
	}

	return routes
}

// AddArea adds an OSPF area
func (s *OSPFService) AddArea(area OSPFArea) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, a := range s.config.Areas {
		if a.ID == area.ID {
			return fmt.Errorf("area already exists")
		}
	}

	s.config.Areas = append(s.config.Areas, area)
	return s.saveConfig()
}

// RemoveArea removes an OSPF area
func (s *OSPFService) RemoveArea(areaID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, a := range s.config.Areas {
		if a.ID == areaID {
			s.config.Areas = append(s.config.Areas[:i], s.config.Areas[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("area not found")
}

// UpdateArea updates an OSPF area
func (s *OSPFService) UpdateArea(areaID string, area OSPFArea) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, a := range s.config.Areas {
		if a.ID == areaID {
			s.config.Areas[i] = area
			return s.saveConfig()
		}
	}
	return fmt.Errorf("area not found")
}

// AddInterface adds an interface to an area
func (s *OSPFService) AddInterface(areaID string, iface OSPFInterface) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, a := range s.config.Areas {
		if a.ID == areaID {
			// Check for duplicate
			for _, existing := range a.Interfaces {
				if existing.Name == iface.Name {
					return fmt.Errorf("interface already in area")
				}
			}
			s.config.Areas[i].Interfaces = append(s.config.Areas[i].Interfaces, iface)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("area not found")
}

// RemoveInterface removes an interface from an area
func (s *OSPFService) RemoveInterface(areaID string, ifaceName string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, a := range s.config.Areas {
		if a.ID == areaID {
			for j, iface := range a.Interfaces {
				if iface.Name == ifaceName {
					s.config.Areas[i].Interfaces = append(
						s.config.Areas[i].Interfaces[:j],
						s.config.Areas[i].Interfaces[j+1:]...,
					)
					return s.saveConfig()
				}
			}
			return fmt.Errorf("interface not found")
		}
	}
	return fmt.Errorf("area not found")
}

// GenerateConfig generates /etc/ospfd.conf
func (s *OSPFService) GenerateConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var buf strings.Builder

	buf.WriteString(`#
# NSWall OpenOSPFD Configuration
# Generated by NSWall - Do not edit manually
#

`)

	// Global config
	if s.config.RouterID != "" {
		buf.WriteString(fmt.Sprintf("router-id %s\n", s.config.RouterID))
	}
	if !s.config.FIBUpdate {
		buf.WriteString("fib-update no\n")
	}
	if s.config.RDomain > 0 {
		buf.WriteString(fmt.Sprintf("rdomain %d\n", s.config.RDomain))
	}
	if s.config.RFC1583 {
		buf.WriteString("rfc1583compat yes\n")
	}
	if s.config.StubRouter {
		buf.WriteString("stub router yes\n")
	}
	if s.config.SPFDelay > 0 && s.config.SPFDelay != 1000 {
		buf.WriteString(fmt.Sprintf("spf-delay msec %d\n", s.config.SPFDelay))
	}
	if s.config.SPFHoldtime > 0 && s.config.SPFHoldtime != 5000 {
		buf.WriteString(fmt.Sprintf("spf-holdtime msec %d\n", s.config.SPFHoldtime))
	}
	buf.WriteString("\n")

	// Redistribution
	for _, redist := range s.config.Redistribute {
		line := fmt.Sprintf("redistribute %s", redist.Type)
		if redist.Metric > 0 {
			line += fmt.Sprintf(" metric %d", redist.Metric)
		}
		if redist.MetricType > 0 {
			line += fmt.Sprintf(" type %d", redist.MetricType)
		}
		if redist.Set != "" {
			line += fmt.Sprintf(" set { %s }", redist.Set)
		}
		buf.WriteString(line + "\n")
	}
	if len(s.config.Redistribute) > 0 {
		buf.WriteString("\n")
	}

	// Areas
	for _, area := range s.config.Areas {
		buf.WriteString(fmt.Sprintf("area %s {\n", area.ID))

		// Area type
		if area.Stub {
			if area.StubDefault {
				buf.WriteString("\tstub default\n")
			} else {
				buf.WriteString("\tstub\n")
			}
		} else if area.NSSA {
			buf.WriteString("\tnssa\n")
		}

		// Ranges
		for _, r := range area.Ranges {
			line := fmt.Sprintf("\trange %s", r.Prefix)
			if r.NotAdvertise {
				line += " not-advertise"
			}
			buf.WriteString(line + "\n")
		}

		// Interfaces
		for _, iface := range area.Interfaces {
			buf.WriteString(fmt.Sprintf("\tinterface %s {\n", iface.Name))

			if iface.Cost > 0 {
				buf.WriteString(fmt.Sprintf("\t\tcost %d\n", iface.Cost))
			}
			if iface.HelloInterval > 0 {
				buf.WriteString(fmt.Sprintf("\t\thello-interval %d\n", iface.HelloInterval))
			}
			if iface.DeadInterval > 0 {
				buf.WriteString(fmt.Sprintf("\t\tdead-interval %d\n", iface.DeadInterval))
			}
			if iface.RetransmitInterval > 0 {
				buf.WriteString(fmt.Sprintf("\t\tretransmit-interval %d\n", iface.RetransmitInterval))
			}
			if iface.TransmitDelay > 0 {
				buf.WriteString(fmt.Sprintf("\t\ttransmit-delay %d\n", iface.TransmitDelay))
			}
			if iface.Priority >= 0 {
				buf.WriteString(fmt.Sprintf("\t\trouter-priority %d\n", iface.Priority))
			}
			if iface.Passive {
				buf.WriteString("\t\tpassive\n")
			}
			if iface.Type != "" {
				buf.WriteString(fmt.Sprintf("\t\ttype %s\n", iface.Type))
			}
			if iface.AuthType != "" {
				buf.WriteString(fmt.Sprintf("\t\tauth-type %s\n", iface.AuthType))
				if iface.AuthKey != "" {
					if iface.AuthType == "crypt" && iface.AuthKeyID > 0 {
						buf.WriteString(fmt.Sprintf("\t\tauth-md-keyid %d\n", iface.AuthKeyID))
					}
					buf.WriteString(fmt.Sprintf("\t\tauth-key \"%s\"\n", iface.AuthKey))
				}
			}
			if iface.DependOn != "" {
				buf.WriteString(fmt.Sprintf("\t\tdepend on %s\n", iface.DependOn))
			}
			if iface.Demote != "" {
				buf.WriteString(fmt.Sprintf("\t\tdemote %s\n", iface.Demote))
			}

			buf.WriteString("\t}\n")
		}

		buf.WriteString("}\n\n")
	}

	return os.WriteFile("/etc/ospfd.conf", []byte(buf.String()), 0640)
}

// Enable enables the ospfd service
func (s *OSPFService) Enable() error {
	s.mu.Lock()
	s.config.Enabled = true
	s.saveConfig()
	s.mu.Unlock()

	if err := s.GenerateConfig(); err != nil {
		return err
	}

	exec.Command("rcctl", "enable", "ospfd").Run()
	return exec.Command("rcctl", "start", "ospfd").Run()
}

// Disable disables the ospfd service
func (s *OSPFService) Disable() error {
	s.mu.Lock()
	s.config.Enabled = false
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "stop", "ospfd").Run()
	return exec.Command("rcctl", "disable", "ospfd").Run()
}

// Reload reloads ospfd configuration
func (s *OSPFService) Reload() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}
	return exec.Command("ospfctl", "reload").Run()
}

// ValidateConfig validates the configuration
func (s *OSPFService) ValidateConfig() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}

	output, err := exec.Command("ospfd", "-n").CombinedOutput()
	if err != nil {
		return fmt.Errorf("config validation failed: %s", string(output))
	}

	return nil
}

// ClearNeighbor clears a specific neighbor
func (s *OSPFService) ClearNeighbor(routerID string) error {
	// ospfctl doesn't have direct neighbor clear, need to use interface reload
	return s.Reload()
}

// GetFIB returns OSPF FIB entries
func (s *OSPFService) GetFIB() ([]OSPFRoute, error) {
	output, err := exec.Command("ospfctl", "show", "fib").Output()
	if err != nil {
		return nil, err
	}

	return s.parseRoutes(string(output)), nil
}
