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

// RelaydConfig represents relayd configuration
type RelaydConfig struct {
	Enabled     bool            `json:"enabled"`
	LogUpdates  bool            `json:"log_updates"`
	Interval    int             `json:"interval"` // health check interval in seconds
	Timeout     int             `json:"timeout"`  // health check timeout
	Prefork     int             `json:"prefork"`  // number of preforked relay processes
	Tables      []RelaydTable   `json:"tables"`
	Redirects   []RelaydRedirect `json:"redirects"`
	Relays      []RelaydRelay   `json:"relays"`
	Protocols   []RelaydProtocol `json:"protocols"`
}

// RelaydTable represents a host table
type RelaydTable struct {
	Name    string        `json:"name"`
	Hosts   []RelaydHost  `json:"hosts"`
	Check   RelaydCheck   `json:"check"`
	Mode    string        `json:"mode"` // roundrobin, loadbalance, hash, random, leastconn, srchash
	Disable bool          `json:"disable"`
}

// RelaydHost represents a backend host
type RelaydHost struct {
	Address  string `json:"address"`
	Port     int    `json:"port"`
	Weight   int    `json:"weight"`
	Priority int    `json:"priority"`
	Retry    int    `json:"retry"`
	Status   string `json:"status"` // up, down, unknown
}

// RelaydCheck defines health check configuration
type RelaydCheck struct {
	Type     string `json:"type"` // tcp, http, https, icmp, script
	Path     string `json:"path"` // for http checks
	Code     int    `json:"code"` // expected HTTP code
	Digest   string `json:"digest"` // expected content hash
	Send     string `json:"send"` // data to send
	Expect   string `json:"expect"` // expected response
	SSL      bool   `json:"ssl"`
	Interval int    `json:"interval"`
	Timeout  int    `json:"timeout"`
}

// RelaydRedirect represents a redirect/rdr configuration
type RelaydRedirect struct {
	Name        string `json:"name"`
	ListenOn    string `json:"listen_on"`
	ListenPort  int    `json:"listen_port"`
	Interface   string `json:"interface"`
	Sticky      bool   `json:"sticky"`
	Table       string `json:"table"`
	TablePort   int    `json:"table_port"`
	Tag         string `json:"tag"`
	SessionTimeout int `json:"session_timeout"`
	Disable     bool   `json:"disable"`
}

// RelaydRelay represents a layer 7 relay
type RelaydRelay struct {
	Name        string `json:"name"`
	ListenOn    string `json:"listen_on"`
	ListenPort  int    `json:"listen_port"`
	Protocol    string `json:"protocol"`
	Table       string `json:"forward_to"`
	TablePort   int    `json:"forward_port"`
	Backup      string `json:"backup"`
	SSL         RelaydSSL `json:"ssl"`
	Timeout     int    `json:"timeout"`
	Disable     bool   `json:"disable"`
}

// RelaydSSL contains SSL/TLS configuration
type RelaydSSL struct {
	Enabled     bool   `json:"enabled"`
	Certificate string `json:"certificate"`
	Key         string `json:"key"`
	CAFile      string `json:"ca_file"`
	Ciphers     string `json:"ciphers"`
	TLSVersion  string `json:"tls_version"` // tlsv1.2, tlsv1.3
	NoTLSv1_0   bool   `json:"no_tlsv1_0"`
	NoTLSv1_1   bool   `json:"no_tlsv1_1"`
}

// RelaydProtocol defines a protocol filter
type RelaydProtocol struct {
	Name       string           `json:"name"`
	Type       string           `json:"type"` // http, dns, tcp
	HTTP       RelaydHTTPProto  `json:"http,omitempty"`
	RequestRules  []RelaydRule  `json:"request_rules"`
	ResponseRules []RelaydRule  `json:"response_rules"`
}

// RelaydHTTPProto contains HTTP protocol settings
type RelaydHTTPProto struct {
	HeaderLen    int  `json:"header_len"`
	ReturnError  string `json:"return_error"`
	WebSockets   bool `json:"websockets"`
}

// RelaydRule represents a filter rule
type RelaydRule struct {
	Action  string `json:"action"` // pass, block, match, hash
	Type    string `json:"type"`   // header, cookie, path, query, url
	Name    string `json:"name"`
	Value   string `json:"value"`
	Set     string `json:"set"`
	Remove  bool   `json:"remove"`
	Log     bool   `json:"log"`
}

// RelaydStatus represents current relayd status
type RelaydStatus struct {
	Running     bool              `json:"running"`
	PID         int               `json:"pid"`
	Uptime      int64             `json:"uptime"`
	Tables      []TableStatus     `json:"tables"`
	Redirects   []RedirectStatus  `json:"redirects"`
	Relays      []RelayStatus     `json:"relays"`
	Sessions    int               `json:"sessions"`
}

// TableStatus represents a table's current status
type TableStatus struct {
	Name     string       `json:"name"`
	Status   string       `json:"status"` // active, disabled
	Hosts    []HostStatus `json:"hosts"`
}

// HostStatus represents a host's health status
type HostStatus struct {
	Address    string  `json:"address"`
	Port       int     `json:"port"`
	Status     string  `json:"status"` // up, down, disabled
	CheckTime  int64   `json:"check_time"`
	CheckCount int     `json:"check_count"`
	UpCount    int     `json:"up_count"`
	DownCount  int     `json:"down_count"`
	Retry      int     `json:"retry"`
}

// RedirectStatus represents redirect status
type RedirectStatus struct {
	Name     string `json:"name"`
	Status   string `json:"status"`
	Total    int64  `json:"total"`
	Last     int64  `json:"last"`
	Avg      int64  `json:"avg"`
}

// RelayStatus represents relay status
type RelayStatus struct {
	Name      string `json:"name"`
	Status    string `json:"status"`
	Sessions  int    `json:"sessions"`
	LastUsed  int64  `json:"last_used"`
}

// RelaydService manages relayd load balancer
type RelaydService struct {
	configPath string
	config     RelaydConfig
	mu         sync.RWMutex
}

// NewRelaydService creates a new relayd service
func NewRelaydService(configPath string) *RelaydService {
	svc := &RelaydService{
		configPath: configPath,
		config: RelaydConfig{
			Interval: 10,
			Timeout:  200,
			Prefork:  5,
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *RelaydService) loadConfig() {
	configFile := filepath.Join(s.configPath, "relayd.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *RelaydService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "relayd.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the relayd configuration
func (s *RelaydService) GetConfig() RelaydConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the relayd configuration
func (s *RelaydService) UpdateConfig(config RelaydConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetStatus returns current relayd status
func (s *RelaydService) GetStatus() (*RelaydStatus, error) {
	status := &RelaydStatus{}

	// Check if running
	output, err := exec.Command("rcctl", "check", "relayd").CombinedOutput()
	status.Running = err == nil && strings.Contains(string(output), "ok")

	if !status.Running {
		return status, nil
	}

	// Get PID
	pidBytes, _ := os.ReadFile("/var/run/relayd.pid")
	status.PID, _ = strconv.Atoi(strings.TrimSpace(string(pidBytes)))

	// Parse relayctl show summary
	output, err = exec.Command("relayctl", "show", "summary").Output()
	if err == nil {
		status.Tables = s.parseTableStatus(string(output))
	}

	// Parse session count
	output, _ = exec.Command("relayctl", "show", "sessions").Output()
	status.Sessions = strings.Count(string(output), "\n") - 1 // exclude header

	return status, nil
}

func (s *RelaydService) parseTableStatus(output string) []TableStatus {
	var tables []TableStatus
	var currentTable *TableStatus

	scanner := bufio.NewScanner(strings.NewReader(output))
	for scanner.Scan() {
		line := scanner.Text()

		if strings.HasPrefix(line, "table ") {
			if currentTable != nil {
				tables = append(tables, *currentTable)
			}
			parts := strings.Fields(line)
			currentTable = &TableStatus{
				Name:   strings.TrimSuffix(parts[1], ":"),
				Status: "active",
			}
		} else if currentTable != nil && strings.Contains(line, "->") {
			// Host line
			re := regexp.MustCompile(`(\d+\.\d+\.\d+\.\d+):(\d+)\s+(\w+)`)
			if matches := re.FindStringSubmatch(line); len(matches) >= 4 {
				port, _ := strconv.Atoi(matches[2])
				currentTable.Hosts = append(currentTable.Hosts, HostStatus{
					Address: matches[1],
					Port:    port,
					Status:  matches[3],
				})
			}
		}
	}

	if currentTable != nil {
		tables = append(tables, *currentTable)
	}

	return tables
}

// AddTable adds a new backend table
func (s *RelaydService) AddTable(table RelaydTable) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, t := range s.config.Tables {
		if t.Name == table.Name {
			return fmt.Errorf("table %s already exists", table.Name)
		}
	}

	if table.Mode == "" {
		table.Mode = "roundrobin"
	}

	s.config.Tables = append(s.config.Tables, table)
	return s.saveConfig()
}

// UpdateTable updates an existing table
func (s *RelaydService) UpdateTable(name string, table RelaydTable) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, t := range s.config.Tables {
		if t.Name == name {
			table.Name = name
			s.config.Tables[i] = table
			return s.saveConfig()
		}
	}
	return fmt.Errorf("table not found")
}

// DeleteTable removes a table
func (s *RelaydService) DeleteTable(name string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, t := range s.config.Tables {
		if t.Name == name {
			s.config.Tables = append(s.config.Tables[:i], s.config.Tables[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("table not found")
}

// AddRelay adds a new layer 7 relay
func (s *RelaydService) AddRelay(relay RelaydRelay) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, r := range s.config.Relays {
		if r.Name == relay.Name {
			return fmt.Errorf("relay %s already exists", relay.Name)
		}
	}

	s.config.Relays = append(s.config.Relays, relay)
	return s.saveConfig()
}

// AddRedirect adds a new layer 4 redirect
func (s *RelaydService) AddRedirect(redirect RelaydRedirect) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, r := range s.config.Redirects {
		if r.Name == redirect.Name {
			return fmt.Errorf("redirect %s already exists", redirect.Name)
		}
	}

	s.config.Redirects = append(s.config.Redirects, redirect)
	return s.saveConfig()
}

// GenerateConfig generates /etc/relayd.conf
func (s *RelaydService) GenerateConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var buf strings.Builder

	buf.WriteString(`#
# NSWall Relayd Configuration
# Generated by NSWall - Do not edit manually
#

`)

	// Global options
	if s.config.LogUpdates {
		buf.WriteString("log updates\n")
	}
	buf.WriteString(fmt.Sprintf("interval %d\n", s.config.Interval))
	buf.WriteString(fmt.Sprintf("timeout %d\n", s.config.Timeout))
	buf.WriteString(fmt.Sprintf("prefork %d\n\n", s.config.Prefork))

	// Tables
	for _, t := range s.config.Tables {
		buf.WriteString(fmt.Sprintf("table <%s> {\n", t.Name))

		if t.Check.Type != "" {
			buf.WriteString(fmt.Sprintf("    check %s", t.Check.Type))
			if t.Check.Path != "" {
				buf.WriteString(fmt.Sprintf(" \"%s\"", t.Check.Path))
			}
			if t.Check.Code != 0 {
				buf.WriteString(fmt.Sprintf(" code %d", t.Check.Code))
			}
			buf.WriteString("\n")
		}

		for _, h := range t.Hosts {
			line := fmt.Sprintf("    %s", h.Address)
			if h.Port != 0 {
				line += fmt.Sprintf(" port %d", h.Port)
			}
			if h.Weight != 0 {
				line += fmt.Sprintf(" weight %d", h.Weight)
			}
			if h.Priority != 0 {
				line += fmt.Sprintf(" priority %d", h.Priority)
			}
			buf.WriteString(line + "\n")
		}
		buf.WriteString("}\n\n")
	}

	// Protocols
	for _, p := range s.config.Protocols {
		buf.WriteString(fmt.Sprintf("protocol \"%s\" {\n", p.Name))
		if p.Type != "" {
			buf.WriteString(fmt.Sprintf("    %s\n", p.Type))
		}

		for _, r := range p.RequestRules {
			buf.WriteString(fmt.Sprintf("    %s request %s", r.Action, r.Type))
			if r.Name != "" {
				buf.WriteString(fmt.Sprintf(" \"%s\"", r.Name))
			}
			if r.Value != "" {
				buf.WriteString(fmt.Sprintf(" value \"%s\"", r.Value))
			}
			buf.WriteString("\n")
		}

		for _, r := range p.ResponseRules {
			buf.WriteString(fmt.Sprintf("    %s response %s", r.Action, r.Type))
			if r.Name != "" {
				buf.WriteString(fmt.Sprintf(" \"%s\"", r.Name))
			}
			buf.WriteString("\n")
		}

		buf.WriteString("}\n\n")
	}

	// Redirects (layer 4)
	for _, r := range s.config.Redirects {
		if r.Disable {
			continue
		}
		buf.WriteString(fmt.Sprintf("redirect \"%s\" {\n", r.Name))
		buf.WriteString(fmt.Sprintf("    listen on %s port %d\n", r.ListenOn, r.ListenPort))
		buf.WriteString(fmt.Sprintf("    forward to <%s>", r.Table))
		if r.TablePort != 0 {
			buf.WriteString(fmt.Sprintf(" port %d", r.TablePort))
		}
		buf.WriteString(fmt.Sprintf(" mode %s\n", s.getTableMode(r.Table)))
		if r.Sticky {
			buf.WriteString("    sticky-address\n")
		}
		buf.WriteString("}\n\n")
	}

	// Relays (layer 7)
	for _, r := range s.config.Relays {
		if r.Disable {
			continue
		}
		buf.WriteString(fmt.Sprintf("relay \"%s\" {\n", r.Name))
		buf.WriteString(fmt.Sprintf("    listen on %s port %d", r.ListenOn, r.ListenPort))
		if r.SSL.Enabled {
			buf.WriteString(" tls")
		}
		buf.WriteString("\n")

		if r.Protocol != "" {
			buf.WriteString(fmt.Sprintf("    protocol \"%s\"\n", r.Protocol))
		}

		buf.WriteString(fmt.Sprintf("    forward to <%s>", r.Table))
		if r.TablePort != 0 {
			buf.WriteString(fmt.Sprintf(" port %d", r.TablePort))
		}
		buf.WriteString(fmt.Sprintf(" mode %s check tcp\n", s.getTableMode(r.Table)))

		if r.SSL.Enabled && r.SSL.Certificate != "" {
			buf.WriteString(fmt.Sprintf("    tls certificate \"%s\"\n", r.SSL.Certificate))
		}

		buf.WriteString("}\n\n")
	}

	return os.WriteFile("/etc/relayd.conf", []byte(buf.String()), 0644)
}

func (s *RelaydService) getTableMode(name string) string {
	for _, t := range s.config.Tables {
		if t.Name == name && t.Mode != "" {
			return t.Mode
		}
	}
	return "roundrobin"
}

// Reload reloads relayd configuration
func (s *RelaydService) Reload() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}
	return exec.Command("rcctl", "reload", "relayd").Run()
}

// Enable enables relayd service
func (s *RelaydService) Enable() error {
	s.mu.Lock()
	s.config.Enabled = true
	s.saveConfig()
	s.mu.Unlock()

	if err := s.GenerateConfig(); err != nil {
		return err
	}

	exec.Command("rcctl", "enable", "relayd").Run()
	return exec.Command("rcctl", "start", "relayd").Run()
}

// Disable disables relayd service
func (s *RelaydService) Disable() error {
	s.mu.Lock()
	s.config.Enabled = false
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "stop", "relayd").Run()
	return exec.Command("rcctl", "disable", "relayd").Run()
}

// SetHostStatus enables or disables a host in a table
func (s *RelaydService) SetHostStatus(table, host string, enable bool) error {
	action := "host disable"
	if enable {
		action = "host enable"
	}
	return exec.Command("relayctl", action, host, table).Run()
}
