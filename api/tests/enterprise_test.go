// Package tests provides unit tests for enterprise features
package tests

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/services"
)

// --- TOTP Service Tests ---

func TestTOTPServiceCreation(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewTOTPService(tempDir, "NSWall")
	if svc == nil {
		t.Fatal("Expected TOTPService to be created")
	}
}

func TestTOTPGenerateSecret(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewTOTPService(tempDir, "NSWall")

	secret, qrURL, backupCodes, err := svc.GenerateSecret("testuser")
	if err != nil {
		t.Fatalf("Failed to generate secret: %v", err)
	}

	if secret == "" {
		t.Error("Expected non-empty secret")
	}

	if !strings.Contains(qrURL, "otpauth://totp/") {
		t.Error("Expected valid otpauth URL")
	}

	if !strings.Contains(qrURL, "NSWall") {
		t.Error("Expected issuer in QR URL")
	}

	if len(backupCodes) != 10 {
		t.Errorf("Expected 10 backup codes, got %d", len(backupCodes))
	}

	// Check backup codes are 8 digits
	for _, code := range backupCodes {
		if len(code) != 8 {
			t.Errorf("Expected 8-digit backup code, got %s", code)
		}
	}
}

func TestTOTPIsEnabled(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewTOTPService(tempDir, "NSWall")

	// Initially not enabled
	if svc.IsEnabled("newuser") {
		t.Error("Expected TOTP not enabled for new user")
	}

	// Generate secret (not enabled yet)
	svc.GenerateSecret("newuser")
	if svc.IsEnabled("newuser") {
		t.Error("Expected TOTP not enabled after just generating secret")
	}
}

func TestTOTPGetStatus(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewTOTPService(tempDir, "NSWall")

	status := svc.GetStatus("nonexistent")
	if status["enabled"].(bool) != false {
		t.Error("Expected enabled=false for nonexistent user")
	}

	svc.GenerateSecret("testuser")
	status = svc.GetStatus("testuser")
	if status["enabled"].(bool) != false {
		t.Error("Expected enabled=false before verification")
	}

	remaining := status["backup_codes_remaining"].(int)
	if remaining != 10 {
		t.Errorf("Expected 10 backup codes remaining, got %d", remaining)
	}
}

func TestTOTPVerifyWithoutEnabled(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewTOTPService(tempDir, "NSWall")

	// Verify for user without TOTP should return true (allow login)
	valid, err := svc.Verify("nouser", "123456")
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if valid != false {
		t.Error("Expected false for non-existent user (TOTP not enabled)")
	}
}

// --- Audit Service Tests ---

func TestAuditServiceCreation(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewAuditService(tempDir, 1000)
	if svc == nil {
		t.Fatal("Expected AuditService to be created")
	}
}

func TestAuditLog(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewAuditService(tempDir, 1000)

	entry := services.AuditEntry{
		Action:    services.ActionLogin,
		Username:  "admin",
		IPAddress: "192.168.1.1",
		Resource:  "session",
		Success:   true,
		Details:   map[string]interface{}{"method": "password"},
	}

	if err := svc.Log(entry); err != nil {
		t.Fatalf("Failed to log entry: %v", err)
	}

	// Retrieve entries
	entries, err := svc.GetEntries(services.AuditQuery{Limit: 10})
	if err != nil {
		t.Fatalf("Failed to get entries: %v", err)
	}

	if len(entries) != 1 {
		t.Errorf("Expected 1 entry, got %d", len(entries))
	}

	if entries[0].Action != services.ActionLogin {
		t.Errorf("Expected action %s, got %s", services.ActionLogin, entries[0].Action)
	}
}

func TestAuditLogConvenienceMethods(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewAuditService(tempDir, 1000)

	svc.LogLogin("testuser", "10.0.0.1", true)
	svc.LogLogin("testuser", "10.0.0.1", false)

	entries, _ := svc.GetEntries(services.AuditQuery{Username: "testuser", Limit: 10})
	if len(entries) != 2 {
		t.Errorf("Expected 2 entries, got %d", len(entries))
	}
}

func TestAuditGetStats(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewAuditService(tempDir, 1000)

	svc.LogLogin("user1", "10.0.0.1", true)
	svc.LogLogin("user2", "10.0.0.2", false)

	stats := svc.GetStats()
	if stats["total_entries"].(int) != 2 {
		t.Errorf("Expected 2 total entries, got %v", stats["total_entries"])
	}
}

func TestAuditQueryByAction(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewAuditService(tempDir, 1000)

	svc.LogLogin("user1", "10.0.0.1", true)
	svc.Log(services.AuditEntry{
		Action:   services.ActionConfigChange,
		Username: "admin",
	})

	entries, _ := svc.GetEntries(services.AuditQuery{
		Action: services.ActionLogin,
		Limit:  10,
	})
	if len(entries) != 1 {
		t.Errorf("Expected 1 login entry, got %d", len(entries))
	}
}

// --- Webhook Service Tests ---

func TestWebhookServiceCreation(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewWebhookService(tempDir)
	if svc == nil {
		t.Fatal("Expected WebhookService to be created")
	}
}

func TestWebhookAddAndRemove(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewWebhookService(tempDir)

	// Add webhook
	webhook := services.WebhookConfig{
		Name:    "test",
		Type:    services.WebhookSlack,
		URL:     "https://hooks.slack.com/test",
		Enabled: true,
	}

	id, err := svc.AddWebhook(webhook)
	if err != nil {
		t.Fatalf("Failed to add webhook: %v", err)
	}
	if id == "" {
		t.Error("Expected non-empty webhook ID")
	}

	// List webhooks
	webhooks := svc.GetWebhooks()
	if len(webhooks) != 1 {
		t.Errorf("Expected 1 webhook, got %d", len(webhooks))
	}

	// Delete webhook
	if err := svc.DeleteWebhook(id); err != nil {
		t.Fatalf("Failed to delete webhook: %v", err)
	}

	webhooks = svc.GetWebhooks()
	if len(webhooks) != 0 {
		t.Errorf("Expected 0 webhooks after delete, got %d", len(webhooks))
	}
}

func TestWebhookTypes(t *testing.T) {
	types := []services.WebhookType{
		services.WebhookSlack,
		services.WebhookDiscord,
		services.WebhookTeams,
		services.WebhookGeneric,
	}

	for _, wt := range types {
		if string(wt) == "" {
			t.Error("Webhook type should not be empty")
		}
	}
}

// --- Config History Service Tests ---

func TestConfigHistoryServiceCreation(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewConfigHistoryService(tempDir, nil)
	if svc == nil {
		t.Fatal("Expected ConfigHistoryService to be created")
	}
}

func TestConfigHistorySaveAndList(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewConfigHistoryService(tempDir, nil)

	// Save version (will save whatever config files exist)
	version, err := svc.SaveVersion("testuser", "Test backup")
	if err != nil {
		t.Fatalf("Failed to save version: %v", err)
	}

	if version.ID == "" {
		t.Error("Expected non-empty version ID")
	}

	if version.Username != "testuser" {
		t.Errorf("Expected username 'testuser', got '%s'", version.Username)
	}

	// List versions
	versions, err := svc.GetVersions()
	if err != nil {
		t.Fatalf("Failed to get versions: %v", err)
	}

	if len(versions) < 1 {
		t.Error("Expected at least 1 version")
	}
}

func TestConfigHistoryGetVersion(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewConfigHistoryService(tempDir, nil)

	saved, _ := svc.SaveVersion("admin", "Initial config")

	retrieved, err := svc.GetVersion(saved.ID)
	if err != nil {
		t.Fatalf("Failed to get version: %v", err)
	}

	if retrieved.ID != saved.ID {
		t.Errorf("Version ID mismatch: %s vs %s", retrieved.ID, saved.ID)
	}
}

func TestConfigHistoryCompare(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewConfigHistoryService(tempDir, nil)

	v1, _ := svc.SaveVersion("admin", "Version 1")
	v2, _ := svc.SaveVersion("admin", "Version 2")

	diffs, err := svc.Compare(v1.ID, v2.ID)
	if err != nil {
		t.Fatalf("Failed to compare versions: %v", err)
	}

	// Diffs may be empty if files are identical
	_ = diffs
}

func TestConfigHistoryDelete(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewConfigHistoryService(tempDir, nil)

	version, _ := svc.SaveVersion("admin", "To be deleted")

	if err := svc.DeleteVersion(version.ID); err != nil {
		t.Fatalf("Failed to delete version: %v", err)
	}

	_, err := svc.GetVersion(version.ID)
	if err == nil {
		t.Error("Expected error getting deleted version")
	}
}

// --- Backup Service Tests ---

func TestBackupServiceCreation(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewBackupService(tempDir, tempDir, nil, nil)
	if svc == nil {
		t.Fatal("Expected BackupService to be created")
	}
}

func TestBackupGetConfig(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewBackupService(tempDir, tempDir, nil, nil)

	config := svc.GetConfig()
	if config.MaxBackups == 0 {
		t.Error("Expected non-zero MaxBackups")
	}
	if config.RetentionDays == 0 {
		t.Error("Expected non-zero RetentionDays")
	}
}

func TestBackupGetStatus(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewBackupService(tempDir, tempDir, nil, nil)

	status := svc.GetStatus()
	if _, ok := status["enabled"]; !ok {
		t.Error("Expected 'enabled' in status")
	}
	if _, ok := status["schedule"]; !ok {
		t.Error("Expected 'schedule' in status")
	}
}

func TestBackupCreateAndList(t *testing.T) {
	tempDir := t.TempDir()
	backupDir := filepath.Join(tempDir, "backups")
	configDir := filepath.Join(tempDir, "config")
	os.MkdirAll(backupDir, 0755)
	os.MkdirAll(configDir, 0755)

	svc := services.NewBackupService(backupDir, configDir, nil, nil)

	backup, err := svc.CreateBackup("manual", "Test backup")
	if err != nil {
		t.Fatalf("Failed to create backup: %v", err)
	}

	if backup.ID == "" {
		t.Error("Expected non-empty backup ID")
	}

	if backup.Type != "manual" {
		t.Errorf("Expected type 'manual', got '%s'", backup.Type)
	}

	// List backups
	backups, err := svc.ListBackups()
	if err != nil {
		t.Fatalf("Failed to list backups: %v", err)
	}

	if len(backups) < 1 {
		t.Error("Expected at least 1 backup")
	}
}

func TestBackupDelete(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewBackupService(tempDir, tempDir, nil, nil)

	backup, _ := svc.CreateBackup("manual", "To delete")

	if err := svc.DeleteBackup(backup.ID); err != nil {
		t.Fatalf("Failed to delete backup: %v", err)
	}

	_, err := svc.GetBackup(backup.ID)
	if err == nil {
		t.Error("Expected error getting deleted backup")
	}
}

func TestBackupUpdateConfig(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewBackupService(tempDir, tempDir, nil, nil)

	newConfig := services.BackupConfig{
		Enabled:       true,
		Schedule:      "weekly",
		RetentionDays: 60,
		MaxBackups:    100,
	}

	if err := svc.UpdateConfig(newConfig); err != nil {
		t.Fatalf("Failed to update config: %v", err)
	}

	config := svc.GetConfig()
	if config.Schedule != "weekly" {
		t.Errorf("Expected schedule 'weekly', got '%s'", config.Schedule)
	}
	if config.RetentionDays != 60 {
		t.Errorf("Expected retention 60, got %d", config.RetentionDays)
	}
}

// --- Rate Limiter Tests (in middleware package) ---
// Rate limiter tests are in middleware_test.go

// --- Benchmark Tests ---

func BenchmarkAuditLog(b *testing.B) {
	tempDir := b.TempDir()
	svc := services.NewAuditService(tempDir, 10000)

	entry := services.AuditEntry{
		Action:    services.ActionLogin,
		Username:  "admin",
		IPAddress: "192.168.1.1",
		Success:   true,
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.Log(entry)
	}
}

func BenchmarkTOTPGenerateSecret(b *testing.B) {
	tempDir := b.TempDir()
	svc := services.NewTOTPService(tempDir, "NSWall")

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.GenerateSecret("user" + string(rune(i)))
	}
}

func BenchmarkConfigHistorySave(b *testing.B) {
	tempDir := b.TempDir()
	svc := services.NewConfigHistoryService(tempDir, nil)

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.SaveVersion("admin", "Benchmark backup")
	}
}

// Helper to verify time is recent
func isRecent(t time.Time, within time.Duration) bool {
	return time.Since(t) < within
}
