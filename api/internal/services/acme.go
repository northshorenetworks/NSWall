package services

import (
	"bufio"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strings"
	"sync"
	"time"
)

// ACMEConfig represents acme-client configuration
type ACMEConfig struct {
	Enabled       bool              `json:"enabled"`
	Domains       []ACMEDomain      `json:"domains"`
	Authority     string            `json:"authority"` // letsencrypt, letsencrypt-staging, buypass, zerossl
	AccountKey    string            `json:"account_key"`
	ChallengeType string            `json:"challenge_type"` // http-01, dns-01
	AutoRenew     bool              `json:"auto_renew"`
	RenewDays     int               `json:"renew_days"` // days before expiry to renew
	Hooks         ACMEHooks         `json:"hooks"`
}

// ACMEDomain represents a domain configuration
type ACMEDomain struct {
	Domain      string   `json:"domain"`
	AlternativeNames []string `json:"alternative_names"`
	CertPath    string   `json:"cert_path"`
	KeyPath     string   `json:"key_path"`
	FullChain   string   `json:"fullchain_path"`
	LastRenewed time.Time `json:"last_renewed"`
	ExpiresAt   time.Time `json:"expires_at"`
	Status      string   `json:"status"` // valid, expired, pending, error
}

// ACMEHooks defines post-renewal hooks
type ACMEHooks struct {
	PostRenew   []string `json:"post_renew"`   // commands to run after renewal
	ReloadHTTPD bool     `json:"reload_httpd"` // reload httpd after renewal
	ReloadRelayd bool    `json:"reload_relayd"` // reload relayd after renewal
}

// CertInfo contains certificate details
type CertInfo struct {
	Domain      string    `json:"domain"`
	Issuer      string    `json:"issuer"`
	Subject     string    `json:"subject"`
	NotBefore   time.Time `json:"not_before"`
	NotAfter    time.Time `json:"not_after"`
	DaysLeft    int       `json:"days_left"`
	Serial      string    `json:"serial"`
	SANs        []string  `json:"sans"`
	Fingerprint string    `json:"fingerprint"`
}

// ACMEService manages Let's Encrypt certificates via acme-client
type ACMEService struct {
	configPath string
	config     ACMEConfig
	mu         sync.RWMutex
}

// ACME authorities
var ACMEAuthorities = map[string]string{
	"letsencrypt":         "https://acme-v02.api.letsencrypt.org/directory",
	"letsencrypt-staging": "https://acme-staging-v02.api.letsencrypt.org/directory",
	"buypass":             "https://api.buypass.com/acme/directory",
	"zerossl":             "https://acme.zerossl.com/v2/DV90",
}

// NewACMEService creates a new ACME service
func NewACMEService(configPath string) *ACMEService {
	svc := &ACMEService{
		configPath: configPath,
		config: ACMEConfig{
			Authority:     "letsencrypt",
			ChallengeType: "http-01",
			AutoRenew:     true,
			RenewDays:     30,
			Hooks: ACMEHooks{
				ReloadHTTPD: true,
			},
		},
	}

	os.MkdirAll(configPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *ACMEService) loadConfig() {
	configFile := filepath.Join(s.configPath, "acme.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *ACMEService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "acme.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// GetConfig returns the ACME configuration
func (s *ACMEService) GetConfig() ACMEConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the ACME configuration
func (s *ACMEService) UpdateConfig(config ACMEConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.config = config
	return s.saveConfig()
}

// GetDomains returns all configured domains
func (s *ACMEService) GetDomains() []ACMEDomain {
	s.mu.RLock()
	defer s.mu.RUnlock()

	// Update status for each domain
	domains := make([]ACMEDomain, len(s.config.Domains))
	for i, d := range s.config.Domains {
		domains[i] = d
		if info, err := s.GetCertInfo(d.Domain); err == nil {
			domains[i].ExpiresAt = info.NotAfter
			if info.DaysLeft < 0 {
				domains[i].Status = "expired"
			} else if info.DaysLeft < s.config.RenewDays {
				domains[i].Status = "renewing"
			} else {
				domains[i].Status = "valid"
			}
		}
	}
	return domains
}

// AddDomain adds a new domain for certificate management
func (s *ACMEService) AddDomain(domain ACMEDomain) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	// Set defaults
	if domain.CertPath == "" {
		domain.CertPath = fmt.Sprintf("/etc/ssl/%s.crt", domain.Domain)
	}
	if domain.KeyPath == "" {
		domain.KeyPath = fmt.Sprintf("/etc/ssl/private/%s.key", domain.Domain)
	}
	if domain.FullChain == "" {
		domain.FullChain = fmt.Sprintf("/etc/ssl/%s.fullchain.pem", domain.Domain)
	}
	domain.Status = "pending"

	// Check if domain already exists
	for _, d := range s.config.Domains {
		if d.Domain == domain.Domain {
			return fmt.Errorf("domain %s already configured", domain.Domain)
		}
	}

	s.config.Domains = append(s.config.Domains, domain)
	return s.saveConfig()
}

// RemoveDomain removes a domain
func (s *ACMEService) RemoveDomain(domain string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	for i, d := range s.config.Domains {
		if d.Domain == domain {
			s.config.Domains = append(s.config.Domains[:i], s.config.Domains[i+1:]...)
			return s.saveConfig()
		}
	}
	return fmt.Errorf("domain not found")
}

// IssueCertificate requests a new certificate
func (s *ACMEService) IssueCertificate(domain string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	var domainConfig *ACMEDomain
	for i := range s.config.Domains {
		if s.config.Domains[i].Domain == domain {
			domainConfig = &s.config.Domains[i]
			break
		}
	}
	if domainConfig == nil {
		return fmt.Errorf("domain not configured")
	}

	// Build acme-client command
	args := []string{"-v"}

	// Add alternative names
	for _, alt := range domainConfig.AlternativeNames {
		args = append(args, "-a", alt)
	}

	args = append(args, domain)

	cmd := exec.Command("acme-client", args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("acme-client failed: %s: %w", string(output), err)
	}

	// Update domain status
	domainConfig.LastRenewed = time.Now()
	domainConfig.Status = "valid"

	// Run post-renewal hooks
	s.runHooks()

	return s.saveConfig()
}

// RenewCertificate renews an existing certificate
func (s *ACMEService) RenewCertificate(domain string) error {
	return s.IssueCertificate(domain)
}

// RenewAll renews all certificates that need renewal
func (s *ACMEService) RenewAll() ([]string, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	var renewed []string
	var errors []string

	for i := range s.config.Domains {
		d := &s.config.Domains[i]

		// Check if renewal is needed
		info, err := s.getCertInfoUnlocked(d.Domain)
		if err != nil || info.DaysLeft < s.config.RenewDays {
			cmd := exec.Command("acme-client", "-v", d.Domain)
			if output, err := cmd.CombinedOutput(); err != nil {
				errors = append(errors, fmt.Sprintf("%s: %s", d.Domain, string(output)))
			} else {
				d.LastRenewed = time.Now()
				d.Status = "valid"
				renewed = append(renewed, d.Domain)
			}
		}
	}

	if len(renewed) > 0 {
		s.runHooks()
		s.saveConfig()
	}

	if len(errors) > 0 {
		return renewed, fmt.Errorf("some renewals failed: %s", strings.Join(errors, "; "))
	}
	return renewed, nil
}

func (s *ACMEService) runHooks() {
	if s.config.Hooks.ReloadHTTPD {
		exec.Command("rcctl", "reload", "httpd").Run()
	}
	if s.config.Hooks.ReloadRelayd {
		exec.Command("rcctl", "reload", "relayd").Run()
	}
	for _, cmd := range s.config.Hooks.PostRenew {
		exec.Command("sh", "-c", cmd).Run()
	}
}

// GetCertInfo returns information about a certificate
func (s *ACMEService) GetCertInfo(domain string) (*CertInfo, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.getCertInfoUnlocked(domain)
}

func (s *ACMEService) getCertInfoUnlocked(domain string) (*CertInfo, error) {
	certPath := fmt.Sprintf("/etc/ssl/%s.crt", domain)

	// Find the configured cert path
	for _, d := range s.config.Domains {
		if d.Domain == domain {
			certPath = d.CertPath
			break
		}
	}

	// Parse certificate with openssl
	cmd := exec.Command("openssl", "x509", "-in", certPath, "-noout",
		"-subject", "-issuer", "-dates", "-serial", "-fingerprint", "-ext", "subjectAltName")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return nil, fmt.Errorf("failed to read certificate: %w", err)
	}

	info := &CertInfo{Domain: domain}
	lines := strings.Split(string(output), "\n")

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if strings.HasPrefix(line, "subject=") {
			info.Subject = strings.TrimPrefix(line, "subject=")
		} else if strings.HasPrefix(line, "issuer=") {
			info.Issuer = strings.TrimPrefix(line, "issuer=")
		} else if strings.HasPrefix(line, "notBefore=") {
			t, _ := time.Parse("Jan  2 15:04:05 2006 MST", strings.TrimPrefix(line, "notBefore="))
			info.NotBefore = t
		} else if strings.HasPrefix(line, "notAfter=") {
			t, _ := time.Parse("Jan  2 15:04:05 2006 MST", strings.TrimPrefix(line, "notAfter="))
			info.NotAfter = t
		} else if strings.HasPrefix(line, "serial=") {
			info.Serial = strings.TrimPrefix(line, "serial=")
		} else if strings.HasPrefix(line, "SHA256 Fingerprint=") {
			info.Fingerprint = strings.TrimPrefix(line, "SHA256 Fingerprint=")
		} else if strings.Contains(line, "DNS:") {
			re := regexp.MustCompile(`DNS:([^,\s]+)`)
			matches := re.FindAllStringSubmatch(line, -1)
			for _, m := range matches {
				if len(m) > 1 {
					info.SANs = append(info.SANs, m[1])
				}
			}
		}
	}

	info.DaysLeft = int(time.Until(info.NotAfter).Hours() / 24)

	return info, nil
}

// GenerateACMEConfig generates /etc/acme-client.conf
func (s *ACMEService) GenerateACMEConfig() error {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var buf strings.Builder

	// Write authority section
	authorityURL := ACMEAuthorities[s.config.Authority]
	if authorityURL == "" {
		authorityURL = ACMEAuthorities["letsencrypt"]
	}

	buf.WriteString(fmt.Sprintf(`#
# NSWall ACME Client Configuration
# Generated by NSWall - Do not edit manually
#

authority letsencrypt {
    api url "%s"
    account key "/etc/acme/letsencrypt-privkey.pem"
}

authority letsencrypt-staging {
    api url "https://acme-staging-v02.api.letsencrypt.org/directory"
    account key "/etc/acme/letsencrypt-staging-privkey.pem"
}

`, authorityURL))

	// Write domain sections
	for _, d := range s.config.Domains {
		buf.WriteString(fmt.Sprintf(`domain %s {
    alternative names { %s }
    domain key "%s"
    domain full chain certificate "%s"
    sign with %s
}

`, d.Domain, strings.Join(d.AlternativeNames, " "), d.KeyPath, d.FullChain, s.config.Authority))
	}

	return os.WriteFile("/etc/acme-client.conf", []byte(buf.String()), 0644)
}

// GetStatus returns the overall ACME service status
func (s *ACMEService) GetStatus() map[string]interface{} {
	s.mu.RLock()
	defer s.mu.RUnlock()

	totalDomains := len(s.config.Domains)
	validCerts := 0
	expiringCerts := 0
	expiredCerts := 0

	for _, d := range s.config.Domains {
		if info, err := s.getCertInfoUnlocked(d.Domain); err == nil {
			if info.DaysLeft < 0 {
				expiredCerts++
			} else if info.DaysLeft < s.config.RenewDays {
				expiringCerts++
			} else {
				validCerts++
			}
		}
	}

	return map[string]interface{}{
		"enabled":        s.config.Enabled,
		"authority":      s.config.Authority,
		"auto_renew":     s.config.AutoRenew,
		"total_domains":  totalDomains,
		"valid_certs":    validCerts,
		"expiring_certs": expiringCerts,
		"expired_certs":  expiredCerts,
	}
}

// CheckChallengeReady verifies the HTTP-01 challenge is reachable
func (s *ACMEService) CheckChallengeReady(domain string) error {
	// Verify httpd is configured for ACME challenges
	cmd := exec.Command("curl", "-s", "-o", "/dev/null", "-w", "%{http_code}",
		fmt.Sprintf("http://%s/.well-known/acme-challenge/test", domain))
	output, err := cmd.Output()
	if err != nil {
		return fmt.Errorf("challenge endpoint not reachable: %w", err)
	}

	// 404 is expected (file doesn't exist), anything else might be a problem
	code := strings.TrimSpace(string(output))
	if code != "404" && code != "200" {
		return fmt.Errorf("unexpected response code: %s", code)
	}

	return nil
}
