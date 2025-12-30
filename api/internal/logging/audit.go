// Package logging provides audit logging for security events
package logging

import (
	"encoding/json"
	"fmt"
	"os"
	"sync"
	"time"
)

// AuditEventType represents the type of audit event
type AuditEventType string

const (
	// Authentication events
	AuditLogin          AuditEventType = "LOGIN"
	AuditLoginFailed    AuditEventType = "LOGIN_FAILED"
	AuditLogout         AuditEventType = "LOGOUT"
	AuditPasswordChange AuditEventType = "PASSWORD_CHANGE"
	AuditAPIKeyCreated  AuditEventType = "API_KEY_CREATED"
	AuditAPIKeyRevoked  AuditEventType = "API_KEY_REVOKED"

	// User management
	AuditUserCreated  AuditEventType = "USER_CREATED"
	AuditUserModified AuditEventType = "USER_MODIFIED"
	AuditUserDeleted  AuditEventType = "USER_DELETED"
	AuditUserEnabled  AuditEventType = "USER_ENABLED"
	AuditUserDisabled AuditEventType = "USER_DISABLED"

	// Configuration changes
	AuditConfigChanged  AuditEventType = "CONFIG_CHANGED"
	AuditConfigBackup   AuditEventType = "CONFIG_BACKUP"
	AuditConfigRestore  AuditEventType = "CONFIG_RESTORE"
	AuditConfigCommit   AuditEventType = "CONFIG_COMMIT"
	AuditConfigRollback AuditEventType = "CONFIG_ROLLBACK"

	// Firewall events
	AuditPFRuleAdded   AuditEventType = "PF_RULE_ADDED"
	AuditPFRuleRemoved AuditEventType = "PF_RULE_REMOVED"
	AuditPFTableUpdate AuditEventType = "PF_TABLE_UPDATE"
	AuditPFEnabled     AuditEventType = "PF_ENABLED"
	AuditPFDisabled    AuditEventType = "PF_DISABLED"
	AuditPFReload      AuditEventType = "PF_RELOAD"

	// Network events
	AuditInterfaceUp     AuditEventType = "INTERFACE_UP"
	AuditInterfaceDown   AuditEventType = "INTERFACE_DOWN"
	AuditInterfaceConfig AuditEventType = "INTERFACE_CONFIG"
	AuditRouteAdded      AuditEventType = "ROUTE_ADDED"
	AuditRouteRemoved    AuditEventType = "ROUTE_REMOVED"

	// VPN events
	AuditVPNTunnelUp   AuditEventType = "VPN_TUNNEL_UP"
	AuditVPNTunnelDown AuditEventType = "VPN_TUNNEL_DOWN"
	AuditVPNPeerAdded  AuditEventType = "VPN_PEER_ADDED"
	AuditVPNPeerRemoved AuditEventType = "VPN_PEER_REMOVED"

	// Service events
	AuditServiceStart   AuditEventType = "SERVICE_START"
	AuditServiceStop    AuditEventType = "SERVICE_STOP"
	AuditServiceRestart AuditEventType = "SERVICE_RESTART"
	AuditServiceEnable  AuditEventType = "SERVICE_ENABLE"
	AuditServiceDisable AuditEventType = "SERVICE_DISABLE"

	// HA events
	AuditCARPMaster   AuditEventType = "CARP_MASTER"
	AuditCARPBackup   AuditEventType = "CARP_BACKUP"
	AuditHAFailover   AuditEventType = "HA_FAILOVER"
	AuditHAPreempt    AuditEventType = "HA_PREEMPT"

	// System events
	AuditSystemCommand  AuditEventType = "SYSTEM_COMMAND"
	AuditSystemReboot   AuditEventType = "SYSTEM_REBOOT"
	AuditSystemShutdown AuditEventType = "SYSTEM_SHUTDOWN"
	AuditSystemUpgrade  AuditEventType = "SYSTEM_UPGRADE"
)

// AuditEvent represents an audit log entry
type AuditEvent struct {
	Timestamp   time.Time              `json:"timestamp"`
	EventType   AuditEventType         `json:"event_type"`
	Severity    string                 `json:"severity"` // info, warning, critical
	UserID      string                 `json:"user_id,omitempty"`
	Username    string                 `json:"username,omitempty"`
	ClientIP    string                 `json:"client_ip,omitempty"`
	RequestID   string                 `json:"request_id,omitempty"`
	Resource    string                 `json:"resource,omitempty"`
	Action      string                 `json:"action,omitempty"`
	Result      string                 `json:"result"` // success, failure, denied
	Message     string                 `json:"message"`
	Details     map[string]interface{} `json:"details,omitempty"`
	OldValue    interface{}            `json:"old_value,omitempty"`
	NewValue    interface{}            `json:"new_value,omitempty"`
}

// AuditLogger handles audit logging
type AuditLogger struct {
	mu       sync.Mutex
	file     *os.File
	filePath string
	maxSize  int64 // Max file size in bytes
	maxAge   int   // Max age in days
}

// AuditConfig holds audit logger configuration
type AuditConfig struct {
	FilePath string `json:"file_path"` // Path to audit log file
	MaxSize  int64  `json:"max_size"`  // Max size in MB
	MaxAge   int    `json:"max_age"`   // Max age in days
	Syslog   bool   `json:"syslog"`    // Also send to syslog
}

var (
	auditLogger *AuditLogger
	auditOnce   sync.Once
)

// InitAudit initializes the audit logger
func InitAudit(cfg AuditConfig) error {
	var err error
	auditOnce.Do(func() {
		auditLogger, err = NewAuditLogger(cfg)
	})
	return err
}

// Audit returns the audit logger
func Audit() *AuditLogger {
	if auditLogger == nil {
		auditLogger, _ = NewAuditLogger(AuditConfig{
			FilePath: "/var/log/nswall/audit.log",
			MaxSize:  100, // 100 MB
			MaxAge:   90,  // 90 days
		})
	}
	return auditLogger
}

// NewAuditLogger creates a new audit logger
func NewAuditLogger(cfg AuditConfig) (*AuditLogger, error) {
	// Ensure log directory exists
	if err := os.MkdirAll("/var/log/nswall", 0750); err != nil {
		// Fallback to /tmp for non-root users
		cfg.FilePath = "/tmp/nswall-audit.log"
	}

	if cfg.FilePath == "" {
		cfg.FilePath = "/var/log/nswall/audit.log"
	}

	f, err := os.OpenFile(cfg.FilePath, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0640)
	if err != nil {
		return nil, fmt.Errorf("failed to open audit log: %w", err)
	}

	return &AuditLogger{
		file:     f,
		filePath: cfg.FilePath,
		maxSize:  cfg.MaxSize * 1024 * 1024, // Convert MB to bytes
		maxAge:   cfg.MaxAge,
	}, nil
}

// Log records an audit event
func (a *AuditLogger) Log(event AuditEvent) {
	if event.Timestamp.IsZero() {
		event.Timestamp = time.Now().UTC()
	}

	a.mu.Lock()
	defer a.mu.Unlock()

	// Check if rotation is needed
	if a.needsRotation() {
		a.rotate()
	}

	data, err := json.Marshal(event)
	if err != nil {
		return
	}

	a.file.WriteString(string(data) + "\n")
}

func (a *AuditLogger) needsRotation() bool {
	if a.maxSize <= 0 {
		return false
	}

	info, err := a.file.Stat()
	if err != nil {
		return false
	}

	return info.Size() >= a.maxSize
}

func (a *AuditLogger) rotate() {
	// Close current file
	a.file.Close()

	// Rename with timestamp
	timestamp := time.Now().Format("20060102-150405")
	newPath := fmt.Sprintf("%s.%s", a.filePath, timestamp)
	os.Rename(a.filePath, newPath)

	// Create new file
	a.file, _ = os.OpenFile(a.filePath, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0640)

	// Cleanup old files (async)
	go a.cleanupOldFiles()
}

func (a *AuditLogger) cleanupOldFiles() {
	// This would implement cleanup of files older than maxAge days
	// Implementation depends on requirements
}

// Close closes the audit logger
func (a *AuditLogger) Close() error {
	a.mu.Lock()
	defer a.mu.Unlock()
	return a.file.Close()
}

// Convenience methods for common audit events

// LogLogin records a successful login
func (a *AuditLogger) LogLogin(username, clientIP, requestID string) {
	a.Log(AuditEvent{
		EventType: AuditLogin,
		Severity:  "info",
		Username:  username,
		ClientIP:  clientIP,
		RequestID: requestID,
		Result:    "success",
		Message:   fmt.Sprintf("User %s logged in from %s", username, clientIP),
	})
}

// LogLoginFailed records a failed login attempt
func (a *AuditLogger) LogLoginFailed(username, clientIP, requestID, reason string) {
	a.Log(AuditEvent{
		EventType: AuditLoginFailed,
		Severity:  "warning",
		Username:  username,
		ClientIP:  clientIP,
		RequestID: requestID,
		Result:    "failure",
		Message:   fmt.Sprintf("Failed login attempt for user %s from %s: %s", username, clientIP, reason),
		Details:   map[string]interface{}{"reason": reason},
	})
}

// LogLogout records a logout
func (a *AuditLogger) LogLogout(username, clientIP, requestID string) {
	a.Log(AuditEvent{
		EventType: AuditLogout,
		Severity:  "info",
		Username:  username,
		ClientIP:  clientIP,
		RequestID: requestID,
		Result:    "success",
		Message:   fmt.Sprintf("User %s logged out", username),
	})
}

// LogConfigChange records a configuration change
func (a *AuditLogger) LogConfigChange(username, clientIP, requestID, resource string, oldValue, newValue interface{}) {
	a.Log(AuditEvent{
		EventType: AuditConfigChanged,
		Severity:  "info",
		Username:  username,
		ClientIP:  clientIP,
		RequestID: requestID,
		Resource:  resource,
		Action:    "modify",
		Result:    "success",
		Message:   fmt.Sprintf("User %s modified %s", username, resource),
		OldValue:  oldValue,
		NewValue:  newValue,
	})
}

// LogServiceChange records a service state change
func (a *AuditLogger) LogServiceChange(eventType AuditEventType, username, clientIP, serviceName string) {
	a.Log(AuditEvent{
		EventType: eventType,
		Severity:  "info",
		Username:  username,
		ClientIP:  clientIP,
		Resource:  serviceName,
		Result:    "success",
		Message:   fmt.Sprintf("User %s %s service %s", username, eventType, serviceName),
	})
}

// LogSystemCommand records a system command execution
func (a *AuditLogger) LogSystemCommand(username, clientIP, requestID, command, result string) {
	severity := "info"
	if result != "success" {
		severity = "warning"
	}
	a.Log(AuditEvent{
		EventType: AuditSystemCommand,
		Severity:  severity,
		Username:  username,
		ClientIP:  clientIP,
		RequestID: requestID,
		Action:    "execute",
		Result:    result,
		Message:   fmt.Sprintf("User %s executed command", username),
		Details:   map[string]interface{}{"command": command},
	})
}

// LogSecurityEvent records a security-related event
func (a *AuditLogger) LogSecurityEvent(eventType AuditEventType, severity, username, clientIP, message string, details map[string]interface{}) {
	a.Log(AuditEvent{
		EventType: eventType,
		Severity:  severity,
		Username:  username,
		ClientIP:  clientIP,
		Result:    "info",
		Message:   message,
		Details:   details,
	})
}
