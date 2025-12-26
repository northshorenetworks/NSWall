package services

import (
	"bytes"
	"crypto/rand"
	"crypto/tls"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"path/filepath"
	"sync"
	"time"
)

// WebhookType defines the type of webhook
type WebhookType string

const (
	WebhookSlack   WebhookType = "slack"
	WebhookDiscord WebhookType = "discord"
	WebhookTeams   WebhookType = "teams"
	WebhookGeneric WebhookType = "generic"
)

// WebhookConfig defines a webhook configuration
type WebhookConfig struct {
	ID          string            `json:"id"`
	Name        string            `json:"name"`
	Type        WebhookType       `json:"type"`
	URL         string            `json:"url"`
	Enabled     bool              `json:"enabled"`
	Events      []string          `json:"events"` // agent_offline, config_change, high_states, blocked_spike, etc.
	Headers     map[string]string `json:"headers,omitempty"`
	Secret      string            `json:"secret,omitempty"`
	RateLimit   int               `json:"rate_limit,omitempty"`   // Max messages per minute
	LastSent    time.Time         `json:"last_sent,omitempty"`
	FailCount   int               `json:"fail_count,omitempty"`
	CreatedAt   time.Time         `json:"created_at"`
}

// WebhookEvent represents an event to send
type WebhookEvent struct {
	Type      string                 `json:"type"`
	Severity  string                 `json:"severity"` // info, warning, error, critical
	Title     string                 `json:"title"`
	Message   string                 `json:"message"`
	Timestamp time.Time              `json:"timestamp"`
	Data      map[string]interface{} `json:"data,omitempty"`
	Source    string                 `json:"source,omitempty"` // hostname or agent ID
}

// WebhookService manages webhook notifications
type WebhookService struct {
	configPath string
	webhooks   map[string]*WebhookConfig
	mu         sync.RWMutex
	client     *http.Client
	rateLimits map[string][]time.Time // webhook ID -> send times
}

// NewWebhookService creates a new WebhookService
func NewWebhookService(configPath string) *WebhookService {
	svc := &WebhookService{
		configPath: configPath,
		webhooks:   make(map[string]*WebhookConfig),
		rateLimits: make(map[string][]time.Time),
		client: &http.Client{
			Timeout: 10 * time.Second,
			Transport: &http.Transport{
				TLSClientConfig: &tls.Config{InsecureSkipVerify: false},
			},
		},
	}
	svc.loadConfig()
	return svc
}

func (s *WebhookService) loadConfig() {
	configFile := filepath.Join(s.configPath, "webhooks.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}

	var webhooks []*WebhookConfig
	if json.Unmarshal(data, &webhooks) == nil {
		for _, wh := range webhooks {
			s.webhooks[wh.ID] = wh
		}
	}
}

func (s *WebhookService) saveConfig() error {
	s.mu.RLock()
	webhooks := make([]*WebhookConfig, 0, len(s.webhooks))
	for _, wh := range s.webhooks {
		webhooks = append(webhooks, wh)
	}
	s.mu.RUnlock()

	data, err := json.MarshalIndent(webhooks, "", "  ")
	if err != nil {
		return err
	}

	configFile := filepath.Join(s.configPath, "webhooks.json")
	return os.WriteFile(configFile, data, 0600)
}

// AddWebhook adds a new webhook
func (s *WebhookService) AddWebhook(webhook *WebhookConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	if webhook.ID == "" {
		webhook.ID = generateWebhookID()
	}
	webhook.CreatedAt = time.Now()
	s.webhooks[webhook.ID] = webhook

	return s.saveConfig()
}

// UpdateWebhook updates an existing webhook
func (s *WebhookService) UpdateWebhook(webhook *WebhookConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	if _, exists := s.webhooks[webhook.ID]; !exists {
		return fmt.Errorf("webhook not found: %s", webhook.ID)
	}

	s.webhooks[webhook.ID] = webhook
	return s.saveConfig()
}

// DeleteWebhook removes a webhook
func (s *WebhookService) DeleteWebhook(id string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	delete(s.webhooks, id)
	return s.saveConfig()
}

// GetWebhooks returns all webhooks
func (s *WebhookService) GetWebhooks() []*WebhookConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()

	webhooks := make([]*WebhookConfig, 0, len(s.webhooks))
	for _, wh := range s.webhooks {
		// Don't expose secret
		copy := *wh
		copy.Secret = ""
		webhooks = append(webhooks, &copy)
	}
	return webhooks
}

// GetWebhook returns a specific webhook
func (s *WebhookService) GetWebhook(id string) *WebhookConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.webhooks[id]
}

// Send sends an event to all matching webhooks
func (s *WebhookService) Send(event WebhookEvent) {
	s.mu.RLock()
	webhooks := make([]*WebhookConfig, 0)
	for _, wh := range s.webhooks {
		if wh.Enabled && s.matchesEvent(wh, event.Type) {
			webhooks = append(webhooks, wh)
		}
	}
	s.mu.RUnlock()

	for _, wh := range webhooks {
		go s.sendToWebhook(wh, event)
	}
}

func (s *WebhookService) matchesEvent(wh *WebhookConfig, eventType string) bool {
	if len(wh.Events) == 0 {
		return true // No filter = all events
	}
	for _, e := range wh.Events {
		if e == eventType || e == "*" {
			return true
		}
	}
	return false
}

func (s *WebhookService) checkRateLimit(webhookID string, limit int) bool {
	if limit <= 0 {
		return true
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	now := time.Now()
	cutoff := now.Add(-time.Minute)

	// Clean old entries
	times := s.rateLimits[webhookID]
	newTimes := make([]time.Time, 0)
	for _, t := range times {
		if t.After(cutoff) {
			newTimes = append(newTimes, t)
		}
	}

	if len(newTimes) >= limit {
		return false
	}

	newTimes = append(newTimes, now)
	s.rateLimits[webhookID] = newTimes
	return true
}

func (s *WebhookService) sendToWebhook(wh *WebhookConfig, event WebhookEvent) {
	if !s.checkRateLimit(wh.ID, wh.RateLimit) {
		return
	}

	var payload []byte
	var err error

	switch wh.Type {
	case WebhookSlack:
		payload, err = s.formatSlack(event)
	case WebhookDiscord:
		payload, err = s.formatDiscord(event)
	case WebhookTeams:
		payload, err = s.formatTeams(event)
	default:
		payload, err = json.Marshal(event)
	}

	if err != nil {
		s.recordFailure(wh.ID)
		return
	}

	req, err := http.NewRequest("POST", wh.URL, bytes.NewReader(payload))
	if err != nil {
		s.recordFailure(wh.ID)
		return
	}

	req.Header.Set("Content-Type", "application/json")
	for k, v := range wh.Headers {
		req.Header.Set(k, v)
	}

	resp, err := s.client.Do(req)
	if err != nil {
		s.recordFailure(wh.ID)
		return
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 200 && resp.StatusCode < 300 {
		s.recordSuccess(wh.ID)
	} else {
		s.recordFailure(wh.ID)
	}
}

func (s *WebhookService) formatSlack(event WebhookEvent) ([]byte, error) {
	color := s.getColorForSeverity(event.Severity)

	payload := map[string]interface{}{
		"attachments": []map[string]interface{}{
			{
				"color":  color,
				"title":  event.Title,
				"text":   event.Message,
				"footer": fmt.Sprintf("NSWall | %s", event.Source),
				"ts":     event.Timestamp.Unix(),
				"fields": []map[string]interface{}{
					{"title": "Severity", "value": event.Severity, "short": true},
					{"title": "Type", "value": event.Type, "short": true},
				},
			},
		},
	}

	return json.Marshal(payload)
}

func (s *WebhookService) formatDiscord(event WebhookEvent) ([]byte, error) {
	color := s.getColorIntForSeverity(event.Severity)

	payload := map[string]interface{}{
		"embeds": []map[string]interface{}{
			{
				"title":       event.Title,
				"description": event.Message,
				"color":       color,
				"timestamp":   event.Timestamp.Format(time.RFC3339),
				"footer": map[string]string{
					"text": fmt.Sprintf("NSWall | %s", event.Source),
				},
				"fields": []map[string]interface{}{
					{"name": "Severity", "value": event.Severity, "inline": true},
					{"name": "Type", "value": event.Type, "inline": true},
				},
			},
		},
	}

	return json.Marshal(payload)
}

func (s *WebhookService) formatTeams(event WebhookEvent) ([]byte, error) {
	color := s.getColorForSeverity(event.Severity)

	payload := map[string]interface{}{
		"@type":      "MessageCard",
		"@context":   "http://schema.org/extensions",
		"themeColor": color[1:], // Remove # prefix
		"summary":    event.Title,
		"sections": []map[string]interface{}{
			{
				"activityTitle": event.Title,
				"facts": []map[string]string{
					{"name": "Severity", "value": event.Severity},
					{"name": "Type", "value": event.Type},
					{"name": "Source", "value": event.Source},
					{"name": "Time", "value": event.Timestamp.Format(time.RFC3339)},
				},
				"text": event.Message,
			},
		},
	}

	return json.Marshal(payload)
}

func (s *WebhookService) getColorForSeverity(severity string) string {
	switch severity {
	case "critical":
		return "#dc2626" // red
	case "error":
		return "#ea580c" // orange
	case "warning":
		return "#ca8a04" // yellow
	default:
		return "#2563eb" // blue
	}
}

func (s *WebhookService) getColorIntForSeverity(severity string) int {
	switch severity {
	case "critical":
		return 14423830 // red
	case "error":
		return 15358988 // orange
	case "warning":
		return 13273604 // yellow
	default:
		return 2459131 // blue
	}
}

func (s *WebhookService) recordSuccess(webhookID string) {
	s.mu.Lock()
	defer s.mu.Unlock()

	if wh, exists := s.webhooks[webhookID]; exists {
		wh.LastSent = time.Now()
		wh.FailCount = 0
	}
}

func (s *WebhookService) recordFailure(webhookID string) {
	s.mu.Lock()
	defer s.mu.Unlock()

	if wh, exists := s.webhooks[webhookID]; exists {
		wh.FailCount++
		// Disable after 10 consecutive failures
		if wh.FailCount >= 10 {
			wh.Enabled = false
		}
	}
}

// TestWebhook sends a test message to a webhook
func (s *WebhookService) TestWebhook(id string) error {
	wh := s.GetWebhook(id)
	if wh == nil {
		return fmt.Errorf("webhook not found: %s", id)
	}

	event := WebhookEvent{
		Type:      "test",
		Severity:  "info",
		Title:     "NSWall Test Notification",
		Message:   "This is a test message from NSWall. If you see this, your webhook is configured correctly!",
		Timestamp: time.Now(),
		Source:    "NSWall",
	}

	s.sendToWebhook(wh, event)
	return nil
}

// Predefined alert functions

// AlertAgentOffline sends an agent offline alert
func (s *WebhookService) AlertAgentOffline(agentID, hostname string) {
	s.Send(WebhookEvent{
		Type:      "agent_offline",
		Severity:  "error",
		Title:     "Agent Offline",
		Message:   fmt.Sprintf("Agent %s (%s) has gone offline and is no longer responding to heartbeats.", hostname, agentID),
		Timestamp: time.Now(),
		Source:    hostname,
		Data:      map[string]interface{}{"agent_id": agentID},
	})
}

// AlertHighStates sends a high state count alert
func (s *WebhookService) AlertHighStates(current, limit int64, percent float64) {
	s.Send(WebhookEvent{
		Type:      "high_states",
		Severity:  "warning",
		Title:     "High State Table Usage",
		Message:   fmt.Sprintf("PF state table is at %.1f%% capacity (%d/%d states).", percent, current, limit),
		Timestamp: time.Now(),
		Source:    getHostname(),
		Data:      map[string]interface{}{"current": current, "limit": limit, "percent": percent},
	})
}

// AlertBlockedSpike sends a blocked traffic spike alert
func (s *WebhookService) AlertBlockedSpike(blockedPerSec float64, threshold float64) {
	s.Send(WebhookEvent{
		Type:      "blocked_spike",
		Severity:  "warning",
		Title:     "Blocked Traffic Spike",
		Message:   fmt.Sprintf("Blocked packet rate (%.0f/sec) exceeds threshold (%.0f/sec).", blockedPerSec, threshold),
		Timestamp: time.Now(),
		Source:    getHostname(),
		Data:      map[string]interface{}{"rate": blockedPerSec, "threshold": threshold},
	})
}

// AlertConfigChange sends a configuration change alert
func (s *WebhookService) AlertConfigChange(user, description string) {
	s.Send(WebhookEvent{
		Type:      "config_change",
		Severity:  "info",
		Title:     "Configuration Changed",
		Message:   fmt.Sprintf("User %s made a configuration change: %s", user, description),
		Timestamp: time.Now(),
		Source:    getHostname(),
		Data:      map[string]interface{}{"user": user, "description": description},
	})
}

// AlertBackupComplete sends a backup completion alert
func (s *WebhookService) AlertBackupComplete(backupName string, size int64) {
	s.Send(WebhookEvent{
		Type:      "backup_complete",
		Severity:  "info",
		Title:     "Backup Complete",
		Message:   fmt.Sprintf("Configuration backup '%s' completed successfully.", backupName),
		Timestamp: time.Now(),
		Source:    getHostname(),
		Data:      map[string]interface{}{"backup": backupName, "size": size},
	})
}

// AlertLoginFailed sends a failed login alert
func (s *WebhookService) AlertLoginFailed(username, ip string, attempts int) {
	severity := "warning"
	if attempts >= 5 {
		severity = "error"
	}

	s.Send(WebhookEvent{
		Type:      "login_failed",
		Severity:  severity,
		Title:     "Failed Login Attempt",
		Message:   fmt.Sprintf("Failed login attempt for user '%s' from IP %s (%d attempts).", username, ip, attempts),
		Timestamp: time.Now(),
		Source:    getHostname(),
		Data:      map[string]interface{}{"username": username, "ip": ip, "attempts": attempts},
	})
}

func generateWebhookID() string {
	b := make([]byte, 8)
	rand.Read(b)
	return hex.EncodeToString(b)
}

func getHostname() string {
	hostname, _ := os.Hostname()
	if hostname == "" {
		hostname = "NSWall"
	}
	return hostname
}
