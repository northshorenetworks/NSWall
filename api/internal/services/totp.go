package services

import (
	"crypto/hmac"
	"crypto/rand"
	"crypto/sha1"
	"encoding/base32"
	"encoding/binary"
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"
)

// TOTPConfig stores 2FA configuration for a user
type TOTPConfig struct {
	Secret       string   `json:"secret"`
	Enabled      bool     `json:"enabled"`
	BackupCodes  []string `json:"backup_codes"`
	UsedBackups  []string `json:"used_backups"`
	EnabledAt    time.Time `json:"enabled_at,omitempty"`
	LastUsed     time.Time `json:"last_used,omitempty"`
	FailedCount  int      `json:"failed_count"`
	LockedUntil  time.Time `json:"locked_until,omitempty"`
}

// TOTPService manages TOTP-based two-factor authentication
type TOTPService struct {
	configPath string
	configs    map[string]*TOTPConfig // username -> config
	mu         sync.RWMutex
	issuer     string
	digits     int
	period     int
}

// NewTOTPService creates a new TOTPService
func NewTOTPService(configPath, issuer string) *TOTPService {
	svc := &TOTPService{
		configPath: configPath,
		configs:    make(map[string]*TOTPConfig),
		issuer:     issuer,
		digits:     6,
		period:     30,
	}
	svc.loadConfigs()
	return svc
}

func (s *TOTPService) loadConfigs() {
	configFile := filepath.Join(s.configPath, "totp.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}

	json.Unmarshal(data, &s.configs)
}

func (s *TOTPService) saveConfigs() error {
	s.mu.RLock()
	data, err := json.MarshalIndent(s.configs, "", "  ")
	s.mu.RUnlock()

	if err != nil {
		return err
	}

	configFile := filepath.Join(s.configPath, "totp.json")
	return os.WriteFile(configFile, data, 0600)
}

// GenerateSecret generates a new TOTP secret for a user
func (s *TOTPService) GenerateSecret(username string) (secret, qrURL string, backupCodes []string, err error) {
	// Generate 20-byte secret
	secretBytes := make([]byte, 20)
	if _, err := rand.Read(secretBytes); err != nil {
		return "", "", nil, err
	}

	secret = base32.StdEncoding.WithPadding(base32.NoPadding).EncodeToString(secretBytes)

	// Generate backup codes
	backupCodes = make([]string, 10)
	for i := 0; i < 10; i++ {
		code := make([]byte, 4)
		rand.Read(code)
		backupCodes[i] = fmt.Sprintf("%08d", binary.BigEndian.Uint32(code)%100000000)
	}

	// Generate QR code URL (otpauth format)
	qrURL = fmt.Sprintf("otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d",
		s.issuer, username, secret, s.issuer, s.digits, s.period)

	// Store config (not enabled yet)
	s.mu.Lock()
	s.configs[username] = &TOTPConfig{
		Secret:      secret,
		Enabled:     false,
		BackupCodes: backupCodes,
		UsedBackups: make([]string, 0),
	}
	s.mu.Unlock()

	s.saveConfigs()

	return secret, qrURL, backupCodes, nil
}

// EnableTOTP enables 2FA for a user after verifying initial code
func (s *TOTPService) EnableTOTP(username, code string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	config, exists := s.configs[username]
	if !exists {
		return fmt.Errorf("no TOTP secret found for user")
	}

	if config.Enabled {
		return fmt.Errorf("TOTP already enabled")
	}

	// Verify the code
	if !s.verifyCode(config.Secret, code) {
		return fmt.Errorf("invalid verification code")
	}

	config.Enabled = true
	config.EnabledAt = time.Now()

	return s.saveConfigs()
}

// DisableTOTP disables 2FA for a user
func (s *TOTPService) DisableTOTP(username, code string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	config, exists := s.configs[username]
	if !exists || !config.Enabled {
		return fmt.Errorf("TOTP not enabled for user")
	}

	// Verify the code (or backup code)
	if !s.verifyCode(config.Secret, code) && !s.verifyBackupCode(config, code) {
		return fmt.Errorf("invalid verification code")
	}

	delete(s.configs, username)
	return s.saveConfigs()
}

// Verify checks if a TOTP code is valid for a user
func (s *TOTPService) Verify(username, code string) (bool, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	config, exists := s.configs[username]
	if !exists || !config.Enabled {
		return false, nil // TOTP not enabled, allow login
	}

	// Check if locked out
	if !config.LockedUntil.IsZero() && time.Now().Before(config.LockedUntil) {
		return false, fmt.Errorf("account locked due to too many failed attempts")
	}

	// Try TOTP code first
	if s.verifyCode(config.Secret, code) {
		config.LastUsed = time.Now()
		config.FailedCount = 0
		s.saveConfigs()
		return true, nil
	}

	// Try backup code
	if s.verifyBackupCode(config, code) {
		config.LastUsed = time.Now()
		config.FailedCount = 0
		s.saveConfigs()
		return true, nil
	}

	// Failed attempt
	config.FailedCount++
	if config.FailedCount >= 5 {
		config.LockedUntil = time.Now().Add(15 * time.Minute)
	}
	s.saveConfigs()

	return false, fmt.Errorf("invalid code")
}

// IsEnabled checks if TOTP is enabled for a user
func (s *TOTPService) IsEnabled(username string) bool {
	s.mu.RLock()
	defer s.mu.RUnlock()

	config, exists := s.configs[username]
	return exists && config.Enabled
}

// GetBackupCodesRemaining returns the number of unused backup codes
func (s *TOTPService) GetBackupCodesRemaining(username string) int {
	s.mu.RLock()
	defer s.mu.RUnlock()

	config, exists := s.configs[username]
	if !exists {
		return 0
	}

	return len(config.BackupCodes) - len(config.UsedBackups)
}

// RegenerateBackupCodes generates new backup codes
func (s *TOTPService) RegenerateBackupCodes(username, code string) ([]string, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	config, exists := s.configs[username]
	if !exists || !config.Enabled {
		return nil, fmt.Errorf("TOTP not enabled for user")
	}

	// Verify current code
	if !s.verifyCode(config.Secret, code) {
		return nil, fmt.Errorf("invalid verification code")
	}

	// Generate new backup codes
	backupCodes := make([]string, 10)
	for i := 0; i < 10; i++ {
		codeBytes := make([]byte, 4)
		rand.Read(codeBytes)
		backupCodes[i] = fmt.Sprintf("%08d", binary.BigEndian.Uint32(codeBytes)%100000000)
	}

	config.BackupCodes = backupCodes
	config.UsedBackups = make([]string, 0)

	s.saveConfigs()
	return backupCodes, nil
}

func (s *TOTPService) verifyCode(secret, code string) bool {
	// Remove spaces
	code = strings.ReplaceAll(code, " ", "")
	if len(code) != s.digits {
		return false
	}

	// Check current time window and adjacent windows
	now := time.Now().Unix()
	for _, offset := range []int64{-1, 0, 1} {
		counter := (now / int64(s.period)) + offset
		expected := s.generateCode(secret, counter)
		if code == expected {
			return true
		}
	}

	return false
}

func (s *TOTPService) generateCode(secret string, counter int64) string {
	// Decode secret
	key, err := base32.StdEncoding.WithPadding(base32.NoPadding).DecodeString(strings.ToUpper(secret))
	if err != nil {
		return ""
	}

	// Convert counter to bytes
	buf := make([]byte, 8)
	binary.BigEndian.PutUint64(buf, uint64(counter))

	// HMAC-SHA1
	mac := hmac.New(sha1.New, key)
	mac.Write(buf)
	hash := mac.Sum(nil)

	// Dynamic truncation
	offset := hash[len(hash)-1] & 0x0f
	code := binary.BigEndian.Uint32(hash[offset:offset+4]) & 0x7fffffff

	// Format to 6 digits
	return fmt.Sprintf("%06d", code%1000000)
}

func (s *TOTPService) verifyBackupCode(config *TOTPConfig, code string) bool {
	code = strings.ReplaceAll(code, " ", "")
	code = strings.ReplaceAll(code, "-", "")

	for _, backup := range config.BackupCodes {
		// Check if not already used
		used := false
		for _, u := range config.UsedBackups {
			if u == backup {
				used = true
				break
			}
		}

		if !used && backup == code {
			config.UsedBackups = append(config.UsedBackups, code)
			return true
		}
	}

	return false
}

// GetStatus returns TOTP status for a user
func (s *TOTPService) GetStatus(username string) map[string]interface{} {
	s.mu.RLock()
	defer s.mu.RUnlock()

	config, exists := s.configs[username]
	if !exists {
		return map[string]interface{}{
			"enabled": false,
		}
	}

	return map[string]interface{}{
		"enabled":            config.Enabled,
		"enabled_at":         config.EnabledAt,
		"last_used":          config.LastUsed,
		"backup_codes_remaining": len(config.BackupCodes) - len(config.UsedBackups),
		"locked":             !config.LockedUntil.IsZero() && time.Now().Before(config.LockedUntil),
		"locked_until":       config.LockedUntil,
	}
}
