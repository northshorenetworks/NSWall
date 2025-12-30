package services

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"sync"
	"time"
)

// AuditAction represents the type of action being audited
type AuditAction string

const (
	ActionLogin         AuditAction = "login"
	ActionLogout        AuditAction = "logout"
	ActionLoginFailed   AuditAction = "login_failed"
	ActionConfigView    AuditAction = "config_view"
	ActionConfigChange  AuditAction = "config_change"
	ActionConfigBackup  AuditAction = "config_backup"
	ActionConfigRestore AuditAction = "config_restore"
	ActionRuleCreate    AuditAction = "rule_create"
	ActionRuleUpdate    AuditAction = "rule_update"
	ActionRuleDelete    AuditAction = "rule_delete"
	ActionUserCreate    AuditAction = "user_create"
	ActionUserUpdate    AuditAction = "user_update"
	ActionUserDelete    AuditAction = "user_delete"
	ActionServiceStart  AuditAction = "service_start"
	ActionServiceStop   AuditAction = "service_stop"
	ActionPFReload      AuditAction = "pf_reload"
	ActionFleetPush     AuditAction = "fleet_push"
	ActionAPICall       AuditAction = "api_call"
	Action2FAEnable     AuditAction = "2fa_enable"
	Action2FADisable    AuditAction = "2fa_disable"
)

// AuditEntry represents a single audit log entry
type AuditEntry struct {
	ID         string                 `json:"id"`
	Timestamp  time.Time              `json:"timestamp"`
	Action     AuditAction            `json:"action"`
	Username   string                 `json:"username"`
	IPAddress  string                 `json:"ip_address"`
	UserAgent  string                 `json:"user_agent,omitempty"`
	Resource   string                 `json:"resource,omitempty"`
	Details    string                 `json:"details,omitempty"`
	Success    bool                   `json:"success"`
	Metadata   map[string]interface{} `json:"metadata,omitempty"`
}

// AuditService manages audit logging
type AuditService struct {
	logPath    string
	entries    []AuditEntry
	mu         sync.RWMutex
	maxEntries int
	webhooks   *WebhookService
}

// NewAuditService creates a new AuditService
func NewAuditService(configPath string, webhooks *WebhookService) *AuditService {
	svc := &AuditService{
		logPath:    filepath.Join(configPath, "audit"),
		entries:    make([]AuditEntry, 0),
		maxEntries: 10000,
		webhooks:   webhooks,
	}

	// Create audit directory
	os.MkdirAll(svc.logPath, 0700)

	// Load today's log
	svc.loadCurrentLog()

	return svc
}

func (s *AuditService) loadCurrentLog() {
	filename := filepath.Join(s.logPath, time.Now().Format("2006-01-02")+".json")
	data, err := os.ReadFile(filename)
	if err != nil {
		return
	}

	var entries []AuditEntry
	if json.Unmarshal(data, &entries) == nil {
		s.entries = entries
	}
}

func (s *AuditService) saveEntry(entry AuditEntry) {
	filename := filepath.Join(s.logPath, time.Now().Format("2006-01-02")+".json")

	// Read existing entries for today
	var todayEntries []AuditEntry
	if data, err := os.ReadFile(filename); err == nil {
		json.Unmarshal(data, &todayEntries)
	}

	todayEntries = append(todayEntries, entry)

	data, _ := json.MarshalIndent(todayEntries, "", "  ")
	os.WriteFile(filename, data, 0600)
}

// Log creates a new audit entry
func (s *AuditService) Log(action AuditAction, username, ipAddress, resource, details string, success bool, metadata map[string]interface{}) {
	entry := AuditEntry{
		ID:        generateAuditID(),
		Timestamp: time.Now(),
		Action:    action,
		Username:  username,
		IPAddress: ipAddress,
		Resource:  resource,
		Details:   details,
		Success:   success,
		Metadata:  metadata,
	}

	s.mu.Lock()
	s.entries = append(s.entries, entry)

	// Trim old entries from memory
	if len(s.entries) > s.maxEntries {
		s.entries = s.entries[len(s.entries)-s.maxEntries:]
	}
	s.mu.Unlock()

	// Persist to disk
	go s.saveEntry(entry)

	// Send webhook for security-related events
	if s.webhooks != nil && s.isSecurityEvent(action) {
		go s.sendWebhook(entry)
	}
}

func (s *AuditService) isSecurityEvent(action AuditAction) bool {
	switch action {
	case ActionLoginFailed, ActionUserCreate, ActionUserDelete,
		Action2FAEnable, Action2FADisable, ActionConfigRestore:
		return true
	}
	return false
}

func (s *AuditService) sendWebhook(entry AuditEntry) {
	if s.webhooks == nil {
		return
	}

	severity := "info"
	if !entry.Success || entry.Action == ActionLoginFailed {
		severity = "warning"
	}

	s.webhooks.Send(WebhookEvent{
		Type:      string(entry.Action),
		Severity:  severity,
		Title:     fmt.Sprintf("Audit: %s", entry.Action),
		Message:   fmt.Sprintf("User %s performed %s on %s: %s", entry.Username, entry.Action, entry.Resource, entry.Details),
		Timestamp: entry.Timestamp,
		Source:    entry.IPAddress,
		Data: map[string]interface{}{
			"username": entry.Username,
			"action":   entry.Action,
			"success":  entry.Success,
		},
	})
}

// GetEntries returns audit entries with optional filters
func (s *AuditService) GetEntries(limit int, username, action, startDate, endDate string) []AuditEntry {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var start, end time.Time
	if startDate != "" {
		start, _ = time.Parse("2006-01-02", startDate)
	}
	if endDate != "" {
		end, _ = time.Parse("2006-01-02", endDate)
		end = end.Add(24 * time.Hour) // Include entire end day
	}

	filtered := make([]AuditEntry, 0)
	for i := len(s.entries) - 1; i >= 0 && len(filtered) < limit; i-- {
		entry := s.entries[i]

		if username != "" && entry.Username != username {
			continue
		}
		if action != "" && string(entry.Action) != action {
			continue
		}
		if !start.IsZero() && entry.Timestamp.Before(start) {
			continue
		}
		if !end.IsZero() && entry.Timestamp.After(end) {
			continue
		}

		filtered = append(filtered, entry)
	}

	return filtered
}

// GetEntriesFromDate loads entries from a specific date file
func (s *AuditService) GetEntriesFromDate(date string) ([]AuditEntry, error) {
	filename := filepath.Join(s.logPath, date+".json")
	data, err := os.ReadFile(filename)
	if err != nil {
		return nil, err
	}

	var entries []AuditEntry
	if err := json.Unmarshal(data, &entries); err != nil {
		return nil, err
	}

	return entries, nil
}

// GetAvailableDates returns dates that have audit logs
func (s *AuditService) GetAvailableDates() ([]string, error) {
	files, err := os.ReadDir(s.logPath)
	if err != nil {
		return nil, err
	}

	dates := make([]string, 0)
	for _, f := range files {
		if !f.IsDir() && filepath.Ext(f.Name()) == ".json" {
			date := f.Name()[:len(f.Name())-5] // Remove .json
			dates = append(dates, date)
		}
	}

	return dates, nil
}

// GetStats returns audit statistics
func (s *AuditService) GetStats(days int) map[string]interface{} {
	s.mu.RLock()
	defer s.mu.RUnlock()

	cutoff := time.Now().AddDate(0, 0, -days)

	stats := map[string]int{
		"total":         0,
		"logins":        0,
		"failed_logins": 0,
		"config_changes": 0,
		"user_changes":  0,
	}

	actionCounts := make(map[string]int)
	userCounts := make(map[string]int)

	for _, entry := range s.entries {
		if entry.Timestamp.Before(cutoff) {
			continue
		}

		stats["total"]++
		actionCounts[string(entry.Action)]++
		userCounts[entry.Username]++

		switch entry.Action {
		case ActionLogin:
			stats["logins"]++
		case ActionLoginFailed:
			stats["failed_logins"]++
		case ActionConfigChange, ActionConfigBackup, ActionConfigRestore:
			stats["config_changes"]++
		case ActionUserCreate, ActionUserUpdate, ActionUserDelete:
			stats["user_changes"]++
		}
	}

	return map[string]interface{}{
		"summary":       stats,
		"by_action":     actionCounts,
		"by_user":       userCounts,
		"period_days":   days,
	}
}

// Convenience methods for common audit events

// LogLogin logs a successful login
func (s *AuditService) LogLogin(username, ip, userAgent string) {
	s.Log(ActionLogin, username, ip, "auth", "User logged in", true, map[string]interface{}{
		"user_agent": userAgent,
	})
}

// LogLogout logs a logout
func (s *AuditService) LogLogout(username, ip string) {
	s.Log(ActionLogout, username, ip, "auth", "User logged out", true, nil)
}

// LogLoginFailed logs a failed login attempt
func (s *AuditService) LogLoginFailed(username, ip, reason string) {
	s.Log(ActionLoginFailed, username, ip, "auth", reason, false, nil)
}

// LogConfigChange logs a configuration change
func (s *AuditService) LogConfigChange(username, ip, section, description string) {
	s.Log(ActionConfigChange, username, ip, section, description, true, nil)
}

// LogRuleChange logs a firewall rule change
func (s *AuditService) LogRuleChange(action AuditAction, username, ip string, ruleID int, description string) {
	s.Log(action, username, ip, fmt.Sprintf("rule/%d", ruleID), description, true, map[string]interface{}{
		"rule_id": ruleID,
	})
}

// LogAPICall logs an API call
func (s *AuditService) LogAPICall(username, ip, method, path string, statusCode int) {
	success := statusCode >= 200 && statusCode < 400
	s.Log(ActionAPICall, username, ip, path, fmt.Sprintf("%s %s -> %d", method, path, statusCode), success, map[string]interface{}{
		"method":      method,
		"status_code": statusCode,
	})
}

func generateAuditID() string {
	b := make([]byte, 8)
	rand.Read(b)
	return hex.EncodeToString(b)
}
