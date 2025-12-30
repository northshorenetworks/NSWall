package services

import (
	"bufio"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
	"sync"
)

// UnwindConfig represents unwind DNS resolver configuration
type UnwindConfig struct {
	Enabled            bool              `json:"enabled"`
	Forwarders         []UnwindForwarder `json:"forwarders"`
	BlockLists         []string          `json:"block_lists"`
	CaptivePortal      bool              `json:"captive_portal"`
	Force              bool              `json:"force"` // Force use even if DHCP says otherwise
	AcceptBogus        bool              `json:"accept_bogus"` // Accept DNSSEC bogus responses
	DNSOverTLS         bool              `json:"dns_over_tls"`
	Preference         []string          `json:"preference"` // order: recursor, forwarder, DoT
}

// UnwindForwarder represents a DNS forwarder
type UnwindForwarder struct {
	Address     string `json:"address"`
	Port        int    `json:"port"`
	DoT         bool   `json:"dot"` // DNS over TLS
	DoTHostname string `json:"dot_hostname"`
	Priority    int    `json:"priority"`
}

// UnwindStatus represents unwind operational status
type UnwindStatus struct {
	Running        bool   `json:"running"`
	PID            int    `json:"pid"`
	ResolverState  string `json:"resolver_state"` // recursor, forwarder, DoT, autoforwarder
	CaptivePortal  string `json:"captive_portal"` // unknown, not behind, behind
	DNSSEC         string `json:"dnssec"` // unchecked, checked
	TrustAnchor    string `json:"trust_anchor"`
	Uptime         int64  `json:"uptime"`
}

// UnwindStats represents unwind statistics
type UnwindStats struct {
	QueriesTotal    int64 `json:"queries_total"`
	QueriesRecursor int64 `json:"queries_recursor"`
	QueriesForwarder int64 `json:"queries_forwarder"`
	QueriesDoT      int64 `json:"queries_dot"`
	CacheHits       int64 `json:"cache_hits"`
	CacheMisses     int64 `json:"cache_misses"`
	CacheSize       int   `json:"cache_size"`
	Blocked         int64 `json:"blocked"`
}

// UnwindHistogram represents query time histogram
type UnwindHistogram struct {
	Bucket string `json:"bucket"`
	Count  int64  `json:"count"`
}

// UnwindService manages unwind DNS resolver
type UnwindService struct {
	configPath string
	config     UnwindConfig
	mu         sync.RWMutex
}

// NewUnwindService creates a new unwind service
func NewUnwindService(configPath string) *UnwindService {
	svc := &UnwindService{
		configPath: configPath,
		config: UnwindConfig{
			Enabled:       true,
			CaptivePortal: true,
			DNSOverTLS:    true,
			Preference:    []string{"DoT", "forwarder", "recursor"},
			Forwarders: []UnwindForwarder{
				{Address: "1.1.1.1", Port: 853, DoT: true, DoTHostname: "cloudflare-dns.com"},
				{Address: "9.9.9.9", Port: 853, DoT: true, DoTHostname: "dns.quad9.net"},
			},
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *UnwindService) loadConfig() {
	configFile := filepath.Join(s.configPath, "unwind.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *UnwindService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "unwind.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the unwind configuration
func (s *UnwindService) GetConfig() UnwindConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the unwind configuration
func (s *UnwindService) UpdateConfig(config UnwindConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetStatus returns unwind operational status
func (s *UnwindService) GetStatus() (*UnwindStatus, error) {
	status := &UnwindStatus{}

	// Check if running
	output, err := exec.Command("rcctl", "check", "unwind").CombinedOutput()
	status.Running = err == nil && strings.Contains(string(output), "ok")

	if !status.Running {
		return status, nil
	}

	// Get PID
	pidBytes, _ := os.ReadFile("/var/run/unwind.pid")
	status.PID, _ = strconv.Atoi(strings.TrimSpace(string(pidBytes)))

	// Get status from unwindctl
	output, err = exec.Command("unwindctl", "status").Output()
	if err == nil {
		s.parseStatus(string(output), status)
	}

	return status, nil
}

func (s *UnwindService) parseStatus(output string, status *UnwindStatus) {
	scanner := bufio.NewScanner(strings.NewReader(output))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		parts := strings.SplitN(line, ":", 2)
		if len(parts) != 2 {
			continue
		}

		key := strings.TrimSpace(parts[0])
		value := strings.TrimSpace(parts[1])

		switch key {
		case "resolver state":
			status.ResolverState = value
		case "captive portal":
			status.CaptivePortal = value
		case "DNSSEC":
			status.DNSSEC = value
		case "trust anchor":
			status.TrustAnchor = value
		}
	}
}

// GetStats returns unwind statistics
func (s *UnwindService) GetStats() (*UnwindStats, error) {
	stats := &UnwindStats{}

	output, err := exec.Command("unwindctl", "status").Output()
	if err != nil {
		return nil, err
	}

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
		case "cache size":
			stats.CacheSize, _ = strconv.Atoi(value)
		}
	}

	return stats, nil
}

// GetHistogram returns query time histogram
func (s *UnwindService) GetHistogram() ([]UnwindHistogram, error) {
	output, err := exec.Command("unwindctl", "status", "histogram").Output()
	if err != nil {
		return nil, err
	}

	var histograms []UnwindHistogram
	scanner := bufio.NewScanner(strings.NewReader(string(output)))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		fields := strings.Fields(line)
		if len(fields) >= 2 {
			count, _ := strconv.ParseInt(fields[1], 10, 64)
			histograms = append(histograms, UnwindHistogram{
				Bucket: fields[0],
				Count:  count,
			})
		}
	}

	return histograms, nil
}

// AddForwarder adds a DNS forwarder
func (s *UnwindService) AddForwarder(forwarder UnwindForwarder) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	if forwarder.Port == 0 {
		if forwarder.DoT {
			forwarder.Port = 853
		} else {
			forwarder.Port = 53
		}
	}

	s.config.Forwarders = append(s.config.Forwarders, forwarder)
	return s.saveConfig()
}

// RemoveForwarder removes a DNS forwarder
func (s *UnwindService) RemoveForwarder(address string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, f := range s.config.Forwarders {
		if f.Address == address {
			s.config.Forwarders = append(s.config.Forwarders[:i], s.config.Forwarders[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("forwarder not found")
}

// AddBlockList adds a block list URL
func (s *UnwindService) AddBlockList(url string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, bl := range s.config.BlockLists {
		if bl == url {
			return fmt.Errorf("block list already exists")
		}
	}

	s.config.BlockLists = append(s.config.BlockLists, url)
	return s.saveConfig()
}

// GenerateConfig generates /etc/unwind.conf
func (s *UnwindService) GenerateConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var buf strings.Builder

	buf.WriteString(`#
# NSWall Unwind Configuration
# Generated by NSWall - Do not edit manually
#

`)

	// Preference order
	if len(s.config.Preference) > 0 {
		buf.WriteString(fmt.Sprintf("preference { %s }\n\n", strings.Join(s.config.Preference, " ")))
	}

	// Force option
	if s.config.Force {
		buf.WriteString("force\n\n")
	}

	// Accept bogus
	if s.config.AcceptBogus {
		buf.WriteString("accept bogus\n\n")
	}

	// Forwarders
	if len(s.config.Forwarders) > 0 {
		buf.WriteString("forwarder {\n")
		for _, f := range s.config.Forwarders {
			if f.DoT {
				buf.WriteString(fmt.Sprintf("    %s port %d authentication name \"%s\"\n",
					f.Address, f.Port, f.DoTHostname))
			} else {
				buf.WriteString(fmt.Sprintf("    %s port %d\n", f.Address, f.Port))
			}
		}
		buf.WriteString("}\n\n")
	}

	// Block lists
	for _, bl := range s.config.BlockLists {
		buf.WriteString(fmt.Sprintf("block list \"%s\"\n", bl))
	}

	return os.WriteFile("/etc/unwind.conf", []byte(buf.String()), 0644)
}

// Reload reloads unwind configuration
func (s *UnwindService) Reload() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}
	return exec.Command("rcctl", "reload", "unwind").Run()
}

// Enable enables the unwind service
func (s *UnwindService) Enable() error {
	s.mu.Lock()
	s.config.Enabled = true
	s.saveConfig()
	s.mu.Unlock()

	if err := s.GenerateConfig(); err != nil {
		return err
	}

	exec.Command("rcctl", "enable", "unwind").Run()
	return exec.Command("rcctl", "start", "unwind").Run()
}

// Disable disables the unwind service
func (s *UnwindService) Disable() error {
	s.mu.Lock()
	s.config.Enabled = false
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "stop", "unwind").Run()
	return exec.Command("rcctl", "disable", "unwind").Run()
}

// FlushCache flushes the DNS cache
func (s *UnwindService) FlushCache() error {
	return exec.Command("unwindctl", "reload").Run()
}

// TestResolver tests DNS resolution
func (s *UnwindService) TestResolver(domain string) (map[string]interface{}, error) {
	output, err := exec.Command("host", domain, "127.0.0.1").Output()
	if err != nil {
		return nil, fmt.Errorf("DNS resolution failed: %w", err)
	}

	result := map[string]interface{}{
		"domain":   domain,
		"resolver": "127.0.0.1",
		"output":   strings.TrimSpace(string(output)),
		"success":  true,
	}

	return result, nil
}

// Popular DoT providers
var DoTProviders = map[string]UnwindForwarder{
	"cloudflare": {Address: "1.1.1.1", Port: 853, DoT: true, DoTHostname: "cloudflare-dns.com"},
	"cloudflare-family": {Address: "1.1.1.3", Port: 853, DoT: true, DoTHostname: "family.cloudflare-dns.com"},
	"google": {Address: "8.8.8.8", Port: 853, DoT: true, DoTHostname: "dns.google"},
	"quad9": {Address: "9.9.9.9", Port: 853, DoT: true, DoTHostname: "dns.quad9.net"},
	"quad9-ecs": {Address: "9.9.9.11", Port: 853, DoT: true, DoTHostname: "dns11.quad9.net"},
	"adguard": {Address: "94.140.14.14", Port: 853, DoT: true, DoTHostname: "dns.adguard.com"},
	"adguard-family": {Address: "94.140.14.15", Port: 853, DoT: true, DoTHostname: "dns-family.adguard.com"},
}

// GetDoTProviders returns list of popular DoT providers
func (s *UnwindService) GetDoTProviders() map[string]UnwindForwarder {
	return DoTProviders
}
