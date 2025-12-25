package services

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"golang.org/x/crypto/bcrypt"
)

// SetupStatus represents the current setup state
type SetupStatus struct {
	Completed       bool      `json:"completed"`
	CompletedAt     time.Time `json:"completed_at,omitempty"`
	CurrentStep     int       `json:"current_step"`
	TotalSteps      int       `json:"total_steps"`
	Version         string    `json:"version"`
	FirstBootTime   time.Time `json:"first_boot_time"`
}

// SetupConfig holds the configuration gathered during setup
type SetupConfig struct {
	// Step 1: Management Interface
	ManagementInterface string `json:"management_interface"`
	ManagementIP        string `json:"management_ip"`
	ManagementNetmask   string `json:"management_netmask"`
	ManagementGateway   string `json:"management_gateway"`
	UseDHCP             bool   `json:"use_dhcp"`

	// Step 2: Admin Account
	AdminUsername string `json:"admin_username"`
	AdminPassword string `json:"admin_password,omitempty"` // Not persisted
	AdminEmail    string `json:"admin_email"`

	// Step 3: API/Web Settings
	APIPort       int    `json:"api_port"`
	EnableHTTPS   bool   `json:"enable_https"`
	Hostname      string `json:"hostname"`
	Timezone      string `json:"timezone"`

	// Step 4: DNS Settings
	DNSServers    []string `json:"dns_servers"`
	EnableLocalDNS bool    `json:"enable_local_dns"`

	// Step 5: Security Settings
	Enable2FA          bool `json:"enable_2fa"`
	EnableAuditLogging bool `json:"enable_audit_logging"`
	EnableAPIRateLimit bool `json:"enable_api_rate_limit"`
}

// InterfaceInfo describes a network interface for selection
type InterfaceInfo struct {
	Name        string   `json:"name"`
	Description string   `json:"description"`
	MAC         string   `json:"mac"`
	Status      string   `json:"status"`
	IPv4        []string `json:"ipv4"`
	IPv6        []string `json:"ipv6"`
	Speed       string   `json:"speed"`
	MediaType   string   `json:"media_type"`
}

// SetupService manages the initial setup wizard
type SetupService struct {
	configPath string
	status     SetupStatus
	config     SetupConfig
	mu         sync.RWMutex
}

// NewSetupService creates a new setup service
func NewSetupService(configPath string) *SetupService {
	svc := &SetupService{
		configPath: configPath,
		status: SetupStatus{
			TotalSteps:    5,
			CurrentStep:   1,
			Version:       "2.0.0",
			FirstBootTime: time.Now(),
		},
		config: SetupConfig{
			APIPort:            8443,
			EnableHTTPS:        true,
			Timezone:           "UTC",
			DNSServers:         []string{"1.1.1.1", "8.8.8.8"},
			EnableAuditLogging: true,
			EnableAPIRateLimit: true,
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadStatus()
	svc.loadConfig()

	return svc
}

func (s *SetupService) loadStatus() {
	statusFile := filepath.Join(s.configPath, "setup_status.json")
	data, err := os.ReadFile(statusFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.status)
}

func (s *SetupService) saveStatus() error {
	statusFile := filepath.Join(s.configPath, "setup_status.json")
	data, err := json.MarshalIndent(s.status, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(statusFile, data, 0600)
}

func (s *SetupService) loadConfig() {
	configFile := filepath.Join(s.configPath, "setup_config.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *SetupService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "setup_config.json")
	// Don't persist password
	configToSave := s.config
	configToSave.AdminPassword = ""
	data, err := json.MarshalIndent(configToSave, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// IsSetupComplete returns true if initial setup has been completed
func (s *SetupService) IsSetupComplete() bool {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.status.Completed
}

// GetStatus returns the current setup status
func (s *SetupService) GetStatus() SetupStatus {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.status
}

// GetConfig returns the current setup configuration
func (s *SetupService) GetConfig() SetupConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	// Don't return password
	config := s.config
	config.AdminPassword = ""
	return config
}

// GetAvailableInterfaces returns list of network interfaces
func (s *SetupService) GetAvailableInterfaces() ([]InterfaceInfo, error) {
	interfaces := []InterfaceInfo{}

	// Try to get interfaces using ifconfig on OpenBSD
	output, err := exec.Command("ifconfig").Output()
	if err != nil {
		// Fallback to Go's net package
		return s.getInterfacesGo()
	}

	// Parse ifconfig output
	lines := strings.Split(string(output), "\n")
	var current *InterfaceInfo

	for _, line := range lines {
		if len(line) == 0 {
			continue
		}

		// New interface starts at column 0
		if line[0] != ' ' && line[0] != '\t' {
			if current != nil && isPhysicalInterface(current.Name) {
				interfaces = append(interfaces, *current)
			}
			parts := strings.Split(line, ":")
			if len(parts) > 0 {
				current = &InterfaceInfo{
					Name: strings.TrimSpace(parts[0]),
				}
				// Parse flags from first line
				if strings.Contains(line, "UP") {
					current.Status = "up"
				} else {
					current.Status = "down"
				}
			}
		} else if current != nil {
			line = strings.TrimSpace(line)
			// Parse interface details
			if strings.HasPrefix(line, "lladdr ") {
				current.MAC = strings.TrimPrefix(line, "lladdr ")
				current.MAC = strings.Split(current.MAC, " ")[0]
			} else if strings.HasPrefix(line, "inet ") {
				parts := strings.Fields(line)
				if len(parts) >= 2 {
					current.IPv4 = append(current.IPv4, parts[1])
				}
			} else if strings.HasPrefix(line, "inet6 ") {
				parts := strings.Fields(line)
				if len(parts) >= 2 && !strings.HasPrefix(parts[1], "fe80") {
					current.IPv6 = append(current.IPv6, parts[1])
				}
			} else if strings.HasPrefix(line, "media: ") {
				current.MediaType = strings.TrimPrefix(line, "media: ")
			} else if strings.HasPrefix(line, "status: ") {
				current.Status = strings.TrimPrefix(line, "status: ")
			} else if strings.HasPrefix(line, "description: ") {
				current.Description = strings.TrimPrefix(line, "description: ")
			}
		}
	}

	// Don't forget the last interface
	if current != nil && isPhysicalInterface(current.Name) {
		interfaces = append(interfaces, *current)
	}

	return interfaces, nil
}

func (s *SetupService) getInterfacesGo() ([]InterfaceInfo, error) {
	interfaces := []InterfaceInfo{}

	ifaces, err := net.Interfaces()
	if err != nil {
		return nil, err
	}

	for _, iface := range ifaces {
		if !isPhysicalInterface(iface.Name) {
			continue
		}

		info := InterfaceInfo{
			Name:   iface.Name,
			MAC:    iface.HardwareAddr.String(),
			Status: "down",
		}

		if iface.Flags&net.FlagUp != 0 {
			info.Status = "up"
		}

		addrs, _ := iface.Addrs()
		for _, addr := range addrs {
			if ipnet, ok := addr.(*net.IPNet); ok {
				if ipnet.IP.To4() != nil {
					info.IPv4 = append(info.IPv4, ipnet.IP.String())
				} else {
					info.IPv6 = append(info.IPv6, ipnet.IP.String())
				}
			}
		}

		interfaces = append(interfaces, info)
	}

	return interfaces, nil
}

// isPhysicalInterface returns true for physical network interfaces
func isPhysicalInterface(name string) bool {
	// OpenBSD physical interface prefixes
	physicalPrefixes := []string{
		"em", "re", "bge", "bnx", "ix", "ixl", "igc",
		"vio", "vmx", "xnf", "hvn", // Virtual
		"ral", "athn", "iwn", "iwm", "bwi", // Wireless
		"axe", "axen", "cdce", "ure", "urndis", // USB
	}

	for _, prefix := range physicalPrefixes {
		if strings.HasPrefix(name, prefix) {
			return true
		}
	}

	return false
}

// GetAvailableTimezones returns list of available timezones
func (s *SetupService) GetAvailableTimezones() []string {
	// Common timezones
	return []string{
		"UTC",
		"America/New_York",
		"America/Chicago",
		"America/Denver",
		"America/Los_Angeles",
		"America/Toronto",
		"America/Vancouver",
		"Europe/London",
		"Europe/Paris",
		"Europe/Berlin",
		"Europe/Amsterdam",
		"Europe/Zurich",
		"Asia/Tokyo",
		"Asia/Shanghai",
		"Asia/Singapore",
		"Asia/Dubai",
		"Australia/Sydney",
		"Australia/Melbourne",
		"Pacific/Auckland",
	}
}

// ValidateStep validates the configuration for a specific step
func (s *SetupService) ValidateStep(step int, config SetupConfig) error {
	switch step {
	case 1: // Management Interface
		if config.ManagementInterface == "" {
			return fmt.Errorf("management interface is required")
		}
		if !config.UseDHCP {
			if config.ManagementIP == "" {
				return fmt.Errorf("IP address is required for static configuration")
			}
			if net.ParseIP(config.ManagementIP) == nil {
				return fmt.Errorf("invalid IP address format")
			}
			if config.ManagementNetmask == "" {
				return fmt.Errorf("netmask is required for static configuration")
			}
		}

	case 2: // Admin Account
		if config.AdminUsername == "" {
			return fmt.Errorf("admin username is required")
		}
		if len(config.AdminUsername) < 3 {
			return fmt.Errorf("username must be at least 3 characters")
		}
		if config.AdminPassword == "" {
			return fmt.Errorf("admin password is required")
		}
		if len(config.AdminPassword) < 8 {
			return fmt.Errorf("password must be at least 8 characters")
		}
		// Check password strength
		if !hasUpperCase(config.AdminPassword) || !hasLowerCase(config.AdminPassword) || !hasNumber(config.AdminPassword) {
			return fmt.Errorf("password must contain uppercase, lowercase, and numbers")
		}

	case 3: // API/Web Settings
		if config.APIPort < 1 || config.APIPort > 65535 {
			return fmt.Errorf("port must be between 1 and 65535")
		}
		if config.Hostname == "" {
			return fmt.Errorf("hostname is required")
		}

	case 4: // DNS Settings
		if len(config.DNSServers) == 0 {
			return fmt.Errorf("at least one DNS server is required")
		}
		for _, dns := range config.DNSServers {
			if net.ParseIP(dns) == nil {
				return fmt.Errorf("invalid DNS server: %s", dns)
			}
		}

	case 5: // Security Settings
		// No validation needed, all options are optional
	}

	return nil
}

// SaveStep saves the configuration for a specific step
func (s *SetupService) SaveStep(step int, config SetupConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	// Validate first
	if err := s.ValidateStep(step, config); err != nil {
		return err
	}

	// Merge step-specific config
	switch step {
	case 1:
		s.config.ManagementInterface = config.ManagementInterface
		s.config.ManagementIP = config.ManagementIP
		s.config.ManagementNetmask = config.ManagementNetmask
		s.config.ManagementGateway = config.ManagementGateway
		s.config.UseDHCP = config.UseDHCP
	case 2:
		s.config.AdminUsername = config.AdminUsername
		s.config.AdminPassword = config.AdminPassword
		s.config.AdminEmail = config.AdminEmail
	case 3:
		s.config.APIPort = config.APIPort
		s.config.EnableHTTPS = config.EnableHTTPS
		s.config.Hostname = config.Hostname
		s.config.Timezone = config.Timezone
	case 4:
		s.config.DNSServers = config.DNSServers
		s.config.EnableLocalDNS = config.EnableLocalDNS
	case 5:
		s.config.Enable2FA = config.Enable2FA
		s.config.EnableAuditLogging = config.EnableAuditLogging
		s.config.EnableAPIRateLimit = config.EnableAPIRateLimit
	}

	s.status.CurrentStep = step + 1
	if step >= s.status.TotalSteps {
		s.status.CurrentStep = s.status.TotalSteps
	}

	if err := s.saveConfig(); err != nil {
		return err
	}

	return s.saveStatus()
}

// CompleteSetup finalizes the setup and applies configuration
func (s *SetupService) CompleteSetup() error {
	s.mu.Lock()
	defer s.mu.Unlock()

	// Apply all configurations
	if err := s.applyNetworkConfig(); err != nil {
		return fmt.Errorf("failed to apply network config: %w", err)
	}

	if err := s.createAdminUser(); err != nil {
		return fmt.Errorf("failed to create admin user: %w", err)
	}

	if err := s.applySystemConfig(); err != nil {
		return fmt.Errorf("failed to apply system config: %w", err)
	}

	if err := s.applySecurityConfig(); err != nil {
		return fmt.Errorf("failed to apply security config: %w", err)
	}

	// Mark setup as complete
	s.status.Completed = true
	s.status.CompletedAt = time.Now()

	return s.saveStatus()
}

func (s *SetupService) applyNetworkConfig() error {
	iface := s.config.ManagementInterface

	if s.config.UseDHCP {
		// Write hostname.if file for DHCP
		hostnameFile := fmt.Sprintf("/etc/hostname.%s", iface)
		content := "dhcp\n"
		return os.WriteFile(hostnameFile, []byte(content), 0640)
	}

	// Static IP configuration
	hostnameFile := fmt.Sprintf("/etc/hostname.%s", iface)
	content := fmt.Sprintf("inet %s %s\n", s.config.ManagementIP, s.config.ManagementNetmask)
	if err := os.WriteFile(hostnameFile, []byte(content), 0640); err != nil {
		return err
	}

	// Set default gateway
	if s.config.ManagementGateway != "" {
		mygate := "/etc/mygate"
		if err := os.WriteFile(mygate, []byte(s.config.ManagementGateway+"\n"), 0644); err != nil {
			return err
		}
	}

	return nil
}

func (s *SetupService) createAdminUser() error {
	// Hash password
	hash, err := bcrypt.GenerateFromPassword([]byte(s.config.AdminPassword), bcrypt.DefaultCost)
	if err != nil {
		return err
	}

	// Create user entry
	user := map[string]interface{}{
		"username":      s.config.AdminUsername,
		"password_hash": string(hash),
		"email":         s.config.AdminEmail,
		"role":          "admin",
		"created_at":    time.Now(),
		"enabled":       true,
	}

	usersFile := filepath.Join(s.configPath, "users.json")

	// Read existing users or create new
	var users []map[string]interface{}
	data, err := os.ReadFile(usersFile)
	if err == nil {
		json.Unmarshal(data, &users)
	}

	// Add new admin user
	users = append(users, user)

	userData, err := json.MarshalIndent(users, "", "  ")
	if err != nil {
		return err
	}

	return os.WriteFile(usersFile, userData, 0600)
}

func (s *SetupService) applySystemConfig() error {
	// Set hostname
	if s.config.Hostname != "" {
		if err := os.WriteFile("/etc/myname", []byte(s.config.Hostname+"\n"), 0644); err != nil {
			// Non-fatal, log but continue
		}
	}

	// Set timezone
	if s.config.Timezone != "" {
		zonefile := fmt.Sprintf("/usr/share/zoneinfo/%s", s.config.Timezone)
		if _, err := os.Stat(zonefile); err == nil {
			os.Remove("/etc/localtime")
			os.Symlink(zonefile, "/etc/localtime")
		}
	}

	// Configure DNS servers in resolv.conf
	if len(s.config.DNSServers) > 0 {
		var content strings.Builder
		for _, dns := range s.config.DNSServers {
			content.WriteString(fmt.Sprintf("nameserver %s\n", dns))
		}
		os.WriteFile("/etc/resolv.conf", []byte(content.String()), 0644)
	}

	// Generate API config
	apiConfig := map[string]interface{}{
		"port":     s.config.APIPort,
		"https":    s.config.EnableHTTPS,
		"hostname": s.config.Hostname,
	}
	apiConfigData, _ := json.MarshalIndent(apiConfig, "", "  ")
	os.WriteFile(filepath.Join(s.configPath, "api.json"), apiConfigData, 0600)

	return nil
}

func (s *SetupService) applySecurityConfig() error {
	securityConfig := map[string]interface{}{
		"require_2fa":     s.config.Enable2FA,
		"audit_logging":   s.config.EnableAuditLogging,
		"rate_limiting":   s.config.EnableAPIRateLimit,
	}

	data, _ := json.MarshalIndent(securityConfig, "", "  ")
	return os.WriteFile(filepath.Join(s.configPath, "security.json"), data, 0600)
}

// GenerateSetupToken creates a one-time token for initial setup access
func (s *SetupService) GenerateSetupToken() (string, error) {
	if s.status.Completed {
		return "", fmt.Errorf("setup already completed")
	}

	token := make([]byte, 32)
	if _, err := rand.Read(token); err != nil {
		return "", err
	}

	tokenStr := hex.EncodeToString(token)

	// Save token
	tokenFile := filepath.Join(s.configPath, "setup_token")
	if err := os.WriteFile(tokenFile, []byte(tokenStr), 0600); err != nil {
		return "", err
	}

	return tokenStr, nil
}

// ValidateSetupToken validates a setup token
func (s *SetupService) ValidateSetupToken(token string) bool {
	if s.status.Completed {
		return false
	}

	tokenFile := filepath.Join(s.configPath, "setup_token")
	data, err := os.ReadFile(tokenFile)
	if err != nil {
		return false
	}

	return strings.TrimSpace(string(data)) == token
}

// Helper functions
func hasUpperCase(s string) bool {
	for _, r := range s {
		if r >= 'A' && r <= 'Z' {
			return true
		}
	}
	return false
}

func hasLowerCase(s string) bool {
	for _, r := range s {
		if r >= 'a' && r <= 'z' {
			return true
		}
	}
	return false
}

func hasNumber(s string) bool {
	for _, r := range s {
		if r >= '0' && r <= '9' {
			return true
		}
	}
	return false
}

// ResetSetup allows resetting the setup (for testing or re-configuration)
func (s *SetupService) ResetSetup() error {
	s.mu.Lock()
	defer s.mu.Unlock()

	s.status = SetupStatus{
		TotalSteps:    5,
		CurrentStep:   1,
		Version:       "2.0.0",
		FirstBootTime: time.Now(),
	}

	s.config = SetupConfig{
		APIPort:            8443,
		EnableHTTPS:        true,
		Timezone:           "UTC",
		DNSServers:         []string{"1.1.1.1", "8.8.8.8"},
		EnableAuditLogging: true,
		EnableAPIRateLimit: true,
	}

	// Remove setup files
	os.Remove(filepath.Join(s.configPath, "setup_status.json"))
	os.Remove(filepath.Join(s.configPath, "setup_config.json"))
	os.Remove(filepath.Join(s.configPath, "setup_token"))

	return nil
}
