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

// NTPConfig represents ntpd configuration
type NTPConfig struct {
	Enabled       bool          `json:"enabled"`
	ListenOn      []string      `json:"listen_on"` // interfaces to listen on
	Servers       []NTPServer   `json:"servers"`
	Pools         []NTPPool     `json:"pools"`
	Sensors       []NTPSensor   `json:"sensors"`
	Constraints   []string      `json:"constraints"` // HTTPS time constraints
}

// NTPServer represents an NTP server
type NTPServer struct {
	Address  string `json:"address"`
	Weight   int    `json:"weight"`
	Stratum  int    `json:"stratum"`
	Trusted  bool   `json:"trusted"`
}

// NTPPool represents an NTP server pool
type NTPPool struct {
	Address string `json:"address"`
	Weight  int    `json:"weight"`
}

// NTPSensor represents a hardware time sensor
type NTPSensor struct {
	Device     string `json:"device"`
	Correction int    `json:"correction"` // microseconds
	Weight     int    `json:"weight"`
	Refid      string `json:"refid"`
}

// NTPStatus represents ntpd operational status
type NTPStatus struct {
	Running     bool        `json:"running"`
	PID         int         `json:"pid"`
	Synced      bool        `json:"synced"`
	Stratum     int         `json:"stratum"`
	Offset      float64     `json:"offset"` // seconds
	Delay       float64     `json:"delay"`
	Jitter      float64     `json:"jitter"`
	ReferenceID string      `json:"reference_id"`
	Peers       []NTPPeer   `json:"peers"`
	Sensors     []NTPSensorStatus `json:"sensors"`
}

// NTPPeer represents an NTP peer status
type NTPPeer struct {
	Address     string  `json:"address"`
	Stratum     int     `json:"stratum"`
	Weight      int     `json:"weight"`
	Offset      float64 `json:"offset"`
	Delay       float64 `json:"delay"`
	Jitter      float64 `json:"jitter"`
	State       string  `json:"state"` // sync, valid, invalid, etc.
	Next        int     `json:"next"`  // seconds until next poll
	Poll        int     `json:"poll"`  // poll interval
}

// NTPSensorStatus represents a sensor status
type NTPSensorStatus struct {
	Device     string  `json:"device"`
	Stratum    int     `json:"stratum"`
	Offset     float64 `json:"offset"`
	Correction float64 `json:"correction"`
	Weight     int     `json:"weight"`
	RefID      string  `json:"refid"`
	State      string  `json:"state"`
}

// NTPService manages ntpd time synchronization
type NTPService struct {
	configPath string
	config     NTPConfig
	mu         sync.RWMutex
}

// Common NTP pools
var DefaultNTPPools = []NTPPool{
	{Address: "pool.ntp.org", Weight: 1},
	{Address: "time.cloudflare.com", Weight: 2},
}

// NewNTPService creates a new NTP service
func NewNTPService(configPath string) *NTPService {
	svc := &NTPService{
		configPath: configPath,
		config: NTPConfig{
			Enabled:  true,
			ListenOn: []string{"*"},
			Pools:    DefaultNTPPools,
			Constraints: []string{
				"https://www.google.com/",
			},
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *NTPService) loadConfig() {
	configFile := filepath.Join(s.configPath, "ntpd.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *NTPService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "ntpd.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the NTP configuration
func (s *NTPService) GetConfig() NTPConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the NTP configuration
func (s *NTPService) UpdateConfig(config NTPConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetStatus returns ntpd operational status
func (s *NTPService) GetStatus() (*NTPStatus, error) {
	status := &NTPStatus{}

	// Check if running
	output, err := exec.Command("rcctl", "check", "ntpd").CombinedOutput()
	status.Running = err == nil && strings.Contains(string(output), "ok")

	if !status.Running {
		return status, nil
	}

	// Get PID
	pidBytes, _ := os.ReadFile("/var/run/ntpd.pid")
	status.PID, _ = strconv.Atoi(strings.TrimSpace(string(pidBytes)))

	// Get status from ntpctl
	output, err = exec.Command("ntpctl", "-s", "status").Output()
	if err == nil {
		s.parseStatus(string(output), status)
	}

	// Get peer status
	output, err = exec.Command("ntpctl", "-s", "peers").Output()
	if err == nil {
		status.Peers = s.parsePeers(string(output))
	}

	// Get sensor status
	output, err = exec.Command("ntpctl", "-s", "sensors").Output()
	if err == nil {
		status.Sensors = s.parseSensors(string(output))
	}

	return status, nil
}

func (s *NTPService) parseStatus(output string, status *NTPStatus) {
	scanner := bufio.NewScanner(strings.NewReader(output))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())

		if strings.Contains(line, "clock synced") {
			status.Synced = true
		} else if strings.Contains(line, "clock unsynced") {
			status.Synced = false
		}

		// Parse stratum
		if matches := regexp.MustCompile(`stratum\s+(\d+)`).FindStringSubmatch(line); len(matches) > 1 {
			status.Stratum, _ = strconv.Atoi(matches[1])
		}

		// Parse reference ID
		if matches := regexp.MustCompile(`reference\s+(\S+)`).FindStringSubmatch(line); len(matches) > 1 {
			status.ReferenceID = matches[1]
		}
	}
}

func (s *NTPService) parsePeers(output string) []NTPPeer {
	var peers []NTPPeer

	scanner := bufio.NewScanner(strings.NewReader(output))
	// Skip header line
	scanner.Scan()

	for scanner.Scan() {
		line := scanner.Text()
		fields := strings.Fields(line)
		if len(fields) < 8 {
			continue
		}

		peer := NTPPeer{
			State:   fields[0],
			Address: fields[1],
		}

		peer.Weight, _ = strconv.Atoi(fields[2])
		peer.Stratum, _ = strconv.Atoi(fields[3])
		peer.Offset, _ = strconv.ParseFloat(strings.TrimSuffix(fields[4], "s"), 64)
		peer.Delay, _ = strconv.ParseFloat(strings.TrimSuffix(fields[5], "s"), 64)
		peer.Jitter, _ = strconv.ParseFloat(strings.TrimSuffix(fields[6], "s"), 64)
		peer.Poll, _ = strconv.Atoi(strings.TrimSuffix(fields[7], "s"))

		peers = append(peers, peer)
	}

	return peers
}

func (s *NTPService) parseSensors(output string) []NTPSensorStatus {
	var sensors []NTPSensorStatus

	scanner := bufio.NewScanner(strings.NewReader(output))
	// Skip header
	scanner.Scan()

	for scanner.Scan() {
		line := scanner.Text()
		fields := strings.Fields(line)
		if len(fields) < 6 {
			continue
		}

		sensor := NTPSensorStatus{
			State:  fields[0],
			Device: fields[1],
			RefID:  fields[2],
		}

		sensor.Stratum, _ = strconv.Atoi(fields[3])
		sensor.Offset, _ = strconv.ParseFloat(strings.TrimSuffix(fields[4], "s"), 64)
		sensor.Correction, _ = strconv.ParseFloat(strings.TrimSuffix(fields[5], "s"), 64)

		sensors = append(sensors, sensor)
	}

	return sensors
}

// AddServer adds an NTP server
func (s *NTPService) AddServer(server NTPServer) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, srv := range s.config.Servers {
		if srv.Address == server.Address {
			return fmt.Errorf("server already exists")
		}
	}

	s.config.Servers = append(s.config.Servers, server)
	return s.saveConfig()
}

// RemoveServer removes an NTP server
func (s *NTPService) RemoveServer(address string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, srv := range s.config.Servers {
		if srv.Address == address {
			s.config.Servers = append(s.config.Servers[:i], s.config.Servers[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("server not found")
}

// AddPool adds an NTP pool
func (s *NTPService) AddPool(pool NTPPool) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for _, p := range s.config.Pools {
		if p.Address == pool.Address {
			return fmt.Errorf("pool already exists")
		}
	}

	s.config.Pools = append(s.config.Pools, pool)
	return s.saveConfig()
}

// RemovePool removes an NTP pool
func (s *NTPService) RemovePool(address string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, pool := range s.config.Pools {
		if pool.Address == address {
			s.config.Pools = append(s.config.Pools[:i], s.config.Pools[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("pool not found")
}

// GenerateConfig generates /etc/ntpd.conf
func (s *NTPService) GenerateConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var buf strings.Builder

	buf.WriteString(`#
# NSWall NTPd Configuration
# Generated by NSWall - Do not edit manually
#

`)

	// Listen addresses
	for _, listen := range s.config.ListenOn {
		if listen == "*" {
			buf.WriteString("listen on *\n")
		} else {
			buf.WriteString(fmt.Sprintf("listen on %s\n", listen))
		}
	}
	buf.WriteString("\n")

	// Constraints (HTTPS time validation)
	for _, constraint := range s.config.Constraints {
		buf.WriteString(fmt.Sprintf("constraint from \"%s\"\n", constraint))
	}
	if len(s.config.Constraints) > 0 {
		buf.WriteString("\n")
	}

	// Servers
	for _, srv := range s.config.Servers {
		line := fmt.Sprintf("server %s", srv.Address)
		if srv.Weight > 0 {
			line += fmt.Sprintf(" weight %d", srv.Weight)
		}
		if srv.Trusted {
			line += " trusted"
		}
		buf.WriteString(line + "\n")
	}

	// Pools
	for _, pool := range s.config.Pools {
		line := fmt.Sprintf("servers %s", pool.Address)
		if pool.Weight > 0 {
			line += fmt.Sprintf(" weight %d", pool.Weight)
		}
		buf.WriteString(line + "\n")
	}

	// Sensors
	for _, sensor := range s.config.Sensors {
		line := fmt.Sprintf("sensor %s", sensor.Device)
		if sensor.Correction != 0 {
			line += fmt.Sprintf(" correction %d", sensor.Correction)
		}
		if sensor.Weight > 0 {
			line += fmt.Sprintf(" weight %d", sensor.Weight)
		}
		if sensor.Refid != "" {
			line += fmt.Sprintf(" refid \"%s\"", sensor.Refid)
		}
		buf.WriteString(line + "\n")
	}

	return os.WriteFile("/etc/ntpd.conf", []byte(buf.String()), 0644)
}

// Reload reloads ntpd configuration
func (s *NTPService) Reload() error {
	if err := s.GenerateConfig(); err != nil {
		return err
	}
	// ntpd doesn't support reload, need restart
	return exec.Command("rcctl", "restart", "ntpd").Run()
}

// Enable enables the ntpd service
func (s *NTPService) Enable() error {
	s.mu.Lock()
	s.config.Enabled = true
	s.saveConfig()
	s.mu.Unlock()

	if err := s.GenerateConfig(); err != nil {
		return err
	}

	exec.Command("rcctl", "enable", "ntpd").Run()
	return exec.Command("rcctl", "start", "ntpd").Run()
}

// Disable disables the ntpd service
func (s *NTPService) Disable() error {
	s.mu.Lock()
	s.config.Enabled = false
	s.saveConfig()
	s.mu.Unlock()

	exec.Command("rcctl", "stop", "ntpd").Run()
	return exec.Command("rcctl", "disable", "ntpd").Run()
}

// SyncNow forces an immediate time sync
func (s *NTPService) SyncNow() error {
	// Use rdate for immediate sync
	status, _ := s.GetStatus()
	if len(status.Peers) > 0 {
		for _, peer := range status.Peers {
			if peer.State == "valid" || peer.State == "sync" {
				return exec.Command("rdate", "-n", peer.Address).Run()
			}
		}
	}

	// Fallback to pool.ntp.org
	return exec.Command("rdate", "-n", "pool.ntp.org").Run()
}

// GetCurrentTime returns current system time info
func (s *NTPService) GetCurrentTime() map[string]interface{} {
	now := time.Now()

	return map[string]interface{}{
		"local_time":    now.Format(time.RFC3339),
		"utc_time":      now.UTC().Format(time.RFC3339),
		"timezone":      now.Location().String(),
		"unix_timestamp": now.Unix(),
	}
}

// SetTimezone sets the system timezone
func (s *NTPService) SetTimezone(tz string) error {
	zonefile := fmt.Sprintf("/usr/share/zoneinfo/%s", tz)
	if _, err := os.Stat(zonefile); err != nil {
		return fmt.Errorf("invalid timezone: %s", tz)
	}

	os.Remove("/etc/localtime")
	return os.Symlink(zonefile, "/etc/localtime")
}

// GetAvailableTimezones returns list of available timezones
func (s *NTPService) GetAvailableTimezones() []string {
	var timezones []string

	// Walk the zoneinfo directory
	filepath.Walk("/usr/share/zoneinfo", func(path string, info os.FileInfo, err error) error {
		if err != nil || info.IsDir() {
			return nil
		}

		// Skip non-timezone files
		rel, _ := filepath.Rel("/usr/share/zoneinfo", path)
		if strings.HasPrefix(rel, "posix") || strings.HasPrefix(rel, "right") {
			return nil
		}

		timezones = append(timezones, rel)
		return nil
	})

	return timezones
}
