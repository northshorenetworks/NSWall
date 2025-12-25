package services

import (
	"archive/tar"
	"compress/gzip"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"sort"
	"strings"
	"sync"
	"time"
)

// BackupConfig defines backup configuration
type BackupConfig struct {
	Enabled          bool     `json:"enabled"`
	Schedule         string   `json:"schedule"` // cron-like: "daily", "weekly", "hourly", "@HH:MM"
	RetentionDays    int      `json:"retention_days"`
	MaxBackups       int      `json:"max_backups"`
	IncludeFiles     []string `json:"include_files"`
	ExcludePatterns  []string `json:"exclude_patterns"`
	CompressBackups  bool     `json:"compress_backups"`
	EncryptBackups   bool     `json:"encrypt_backups"`
	RemoteEnabled    bool     `json:"remote_enabled"`
	RemoteType       string   `json:"remote_type"` // "scp", "sftp", "s3"
	RemoteHost       string   `json:"remote_host"`
	RemotePath       string   `json:"remote_path"`
	RemoteUser       string   `json:"remote_user"`
	RemoteKeyPath    string   `json:"remote_key_path"`
	NotifyOnSuccess  bool     `json:"notify_on_success"`
	NotifyOnFailure  bool     `json:"notify_on_failure"`
}

// BackupInfo represents a backup archive
type BackupInfo struct {
	ID          string    `json:"id"`
	Filename    string    `json:"filename"`
	Timestamp   time.Time `json:"timestamp"`
	Size        int64     `json:"size"`
	Hash        string    `json:"hash"`
	FileCount   int       `json:"file_count"`
	Compressed  bool      `json:"compressed"`
	Encrypted   bool      `json:"encrypted"`
	Description string    `json:"description"`
	Type        string    `json:"type"` // "manual", "scheduled", "pre-update"
}

// BackupService manages automated and manual backups
type BackupService struct {
	backupPath   string
	configPath   string
	config       BackupConfig
	mu           sync.RWMutex
	stopChan     chan struct{}
	running      bool
	lastBackup   time.Time
	lastError    error
	webhooks     *WebhookService
	audit        *AuditService
}

// DefaultBackupFiles lists default files to backup
var DefaultBackupFiles = []string{
	"/etc/pf.conf",
	"/etc/hostname.*",
	"/etc/mygate",
	"/etc/resolv.conf",
	"/etc/dhcpd.conf",
	"/etc/dhclient.conf",
	"/etc/iked.conf",
	"/etc/ipsec.conf",
	"/etc/rc.conf.local",
	"/etc/sysctl.conf",
	"/etc/ntpd.conf",
	"/etc/hosts",
	"/var/unbound/etc/unbound.conf",
	"/etc/relayd.conf",
	"/etc/rad.conf",
	"/etc/bgpd.conf",
	"/etc/ospfd.conf",
}

// NewBackupService creates a new backup service
func NewBackupService(backupPath, configPath string, webhooks *WebhookService, audit *AuditService) *BackupService {
	svc := &BackupService{
		backupPath: backupPath,
		configPath: configPath,
		stopChan:   make(chan struct{}),
		webhooks:   webhooks,
		audit:      audit,
		config: BackupConfig{
			Enabled:         true,
			Schedule:        "daily",
			RetentionDays:   30,
			MaxBackups:      50,
			IncludeFiles:    DefaultBackupFiles,
			CompressBackups: true,
			NotifyOnFailure: true,
		},
	}

	os.MkdirAll(backupPath, 0700)
	svc.loadConfig()

	return svc
}

func (s *BackupService) loadConfig() {
	configFile := filepath.Join(s.configPath, "backup.json")
	data, err := os.ReadFile(configFile)
	if err != nil {
		return
	}
	json.Unmarshal(data, &s.config)
}

func (s *BackupService) saveConfig() error {
	configFile := filepath.Join(s.configPath, "backup.json")
	data, err := json.MarshalIndent(s.config, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(configFile, data, 0600)
}

// Start starts the automated backup scheduler
func (s *BackupService) Start() {
	s.mu.Lock()
	if s.running {
		s.mu.Unlock()
		return
	}
	s.running = true
	s.mu.Unlock()

	go s.scheduler()
}

// Stop stops the automated backup scheduler
func (s *BackupService) Stop() {
	s.mu.Lock()
	defer s.mu.Unlock()

	if s.running {
		close(s.stopChan)
		s.running = false
	}
}

func (s *BackupService) scheduler() {
	for {
		nextRun := s.calculateNextRun()
		timer := time.NewTimer(time.Until(nextRun))

		select {
		case <-timer.C:
			if s.config.Enabled {
				_, err := s.CreateBackup("scheduled", "Automated scheduled backup")
				if err != nil {
					s.lastError = err
					if s.config.NotifyOnFailure && s.webhooks != nil {
						s.webhooks.Send(WebhookEvent{
							Type:      "backup_failed",
							Severity:  "error",
							Title:     "Backup Failed",
							Message:   fmt.Sprintf("Scheduled backup failed: %v", err),
							Timestamp: time.Now(),
							Source:    getHostname(),
						})
					}
				} else if s.config.NotifyOnSuccess && s.webhooks != nil {
					s.webhooks.Send(WebhookEvent{
						Type:      "backup_success",
						Severity:  "info",
						Title:     "Backup Completed",
						Message:   "Scheduled backup completed successfully",
						Timestamp: time.Now(),
						Source:    getHostname(),
					})
				}
			}

		case <-s.stopChan:
			timer.Stop()
			return
		}
	}
}

func (s *BackupService) calculateNextRun() time.Time {
	now := time.Now()

	switch s.config.Schedule {
	case "hourly":
		return now.Add(time.Hour).Truncate(time.Hour)
	case "daily":
		next := time.Date(now.Year(), now.Month(), now.Day()+1, 2, 0, 0, 0, now.Location())
		return next
	case "weekly":
		daysUntilSunday := (7 - int(now.Weekday())) % 7
		if daysUntilSunday == 0 && now.Hour() >= 2 {
			daysUntilSunday = 7
		}
		next := time.Date(now.Year(), now.Month(), now.Day()+daysUntilSunday, 2, 0, 0, 0, now.Location())
		return next
	default:
		// Check for @HH:MM format
		if strings.HasPrefix(s.config.Schedule, "@") {
			timeStr := strings.TrimPrefix(s.config.Schedule, "@")
			parts := strings.Split(timeStr, ":")
			if len(parts) == 2 {
				var hour, minute int
				fmt.Sscanf(parts[0], "%d", &hour)
				fmt.Sscanf(parts[1], "%d", &minute)

				next := time.Date(now.Year(), now.Month(), now.Day(), hour, minute, 0, 0, now.Location())
				if next.Before(now) {
					next = next.Add(24 * time.Hour)
				}
				return next
			}
		}
		// Default to daily at 2 AM
		return now.Add(24 * time.Hour)
	}
}

// CreateBackup creates a new backup
func (s *BackupService) CreateBackup(backupType, description string) (*BackupInfo, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	timestamp := time.Now()
	backupID := fmt.Sprintf("%s-%s", timestamp.Format("20060102-150405"), generateShortID())

	var filename string
	if s.config.CompressBackups {
		filename = fmt.Sprintf("nswall-backup-%s.tar.gz", backupID)
	} else {
		filename = fmt.Sprintf("nswall-backup-%s.tar", backupID)
	}

	backupFile := filepath.Join(s.backupPath, filename)

	// Create backup archive
	file, err := os.Create(backupFile)
	if err != nil {
		return nil, fmt.Errorf("failed to create backup file: %w", err)
	}
	defer file.Close()

	var tw *tar.Writer
	var gw *gzip.Writer

	if s.config.CompressBackups {
		gw = gzip.NewWriter(file)
		tw = tar.NewWriter(gw)
	} else {
		tw = tar.NewWriter(file)
	}

	fileCount := 0
	hasher := sha256.New()

	// Add files to archive
	for _, pattern := range s.config.IncludeFiles {
		matches, err := filepath.Glob(pattern)
		if err != nil {
			continue
		}

		for _, match := range matches {
			if s.shouldExclude(match) {
				continue
			}

			if err := s.addFileToTar(tw, match, hasher); err != nil {
				continue
			}
			fileCount++
		}
	}

	// Close writers
	tw.Close()
	if gw != nil {
		gw.Close()
	}

	// Get file size
	stat, err := os.Stat(backupFile)
	if err != nil {
		return nil, err
	}

	backup := &BackupInfo{
		ID:          backupID,
		Filename:    filename,
		Timestamp:   timestamp,
		Size:        stat.Size(),
		Hash:        hex.EncodeToString(hasher.Sum(nil))[:16],
		FileCount:   fileCount,
		Compressed:  s.config.CompressBackups,
		Encrypted:   s.config.EncryptBackups,
		Description: description,
		Type:        backupType,
	}

	// Save backup metadata
	s.saveBackupMetadata(backup)

	// Upload to remote if enabled
	if s.config.RemoteEnabled {
		go s.uploadToRemote(backup)
	}

	// Cleanup old backups
	s.cleanupOldBackups()

	s.lastBackup = timestamp
	s.lastError = nil

	// Log audit entry
	if s.audit != nil {
		s.audit.Log(AuditEntry{
			Action:    "backup_create",
			Username:  "system",
			Resource:  backup.Filename,
			Details:   map[string]interface{}{"type": backupType, "files": fileCount, "size": stat.Size()},
			Timestamp: timestamp,
			IPAddress: "localhost",
		})
	}

	return backup, nil
}

func (s *BackupService) addFileToTar(tw *tar.Writer, path string, hasher io.Writer) error {
	file, err := os.Open(path)
	if err != nil {
		return err
	}
	defer file.Close()

	stat, err := file.Stat()
	if err != nil {
		return err
	}

	header := &tar.Header{
		Name:    path,
		Size:    stat.Size(),
		Mode:    int64(stat.Mode()),
		ModTime: stat.ModTime(),
	}

	if err := tw.WriteHeader(header); err != nil {
		return err
	}

	// Write to both tar and hasher
	mw := io.MultiWriter(tw, hasher)
	_, err = io.Copy(mw, file)
	return err
}

func (s *BackupService) shouldExclude(path string) bool {
	for _, pattern := range s.config.ExcludePatterns {
		if matched, _ := filepath.Match(pattern, filepath.Base(path)); matched {
			return true
		}
	}
	return false
}

func (s *BackupService) saveBackupMetadata(backup *BackupInfo) error {
	metaFile := filepath.Join(s.backupPath, backup.ID+".json")
	data, err := json.MarshalIndent(backup, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(metaFile, data, 0600)
}

func (s *BackupService) uploadToRemote(backup *BackupInfo) error {
	// Placeholder for remote upload implementation
	// Would use SCP, SFTP, or S3 based on config.RemoteType
	return nil
}

func (s *BackupService) cleanupOldBackups() {
	entries, err := os.ReadDir(s.backupPath)
	if err != nil {
		return
	}

	// Collect backup metadata
	type backupEntry struct {
		info *BackupInfo
		path string
	}
	var backups []backupEntry

	for _, entry := range entries {
		if !strings.HasSuffix(entry.Name(), ".json") {
			continue
		}

		metaPath := filepath.Join(s.backupPath, entry.Name())
		data, err := os.ReadFile(metaPath)
		if err != nil {
			continue
		}

		var info BackupInfo
		if err := json.Unmarshal(data, &info); err != nil {
			continue
		}

		backups = append(backups, backupEntry{info: &info, path: metaPath})
	}

	// Sort by timestamp, newest first
	sort.Slice(backups, func(i, j int) bool {
		return backups[i].info.Timestamp.After(backups[j].info.Timestamp)
	})

	cutoff := time.Now().AddDate(0, 0, -s.config.RetentionDays)

	// Remove old backups
	for i, backup := range backups {
		shouldDelete := false

		// Check max backups
		if i >= s.config.MaxBackups {
			shouldDelete = true
		}

		// Check retention days
		if backup.info.Timestamp.Before(cutoff) {
			shouldDelete = true
		}

		if shouldDelete {
			// Delete backup file
			backupFile := filepath.Join(s.backupPath, backup.info.Filename)
			os.Remove(backupFile)
			// Delete metadata
			os.Remove(backup.path)
		}
	}
}

// ListBackups returns all available backups
func (s *BackupService) ListBackups() ([]BackupInfo, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	entries, err := os.ReadDir(s.backupPath)
	if err != nil {
		return nil, err
	}

	var backups []BackupInfo

	for _, entry := range entries {
		if !strings.HasSuffix(entry.Name(), ".json") {
			continue
		}

		metaPath := filepath.Join(s.backupPath, entry.Name())
		data, err := os.ReadFile(metaPath)
		if err != nil {
			continue
		}

		var info BackupInfo
		if err := json.Unmarshal(data, &info); err != nil {
			continue
		}

		backups = append(backups, info)
	}

	// Sort by timestamp, newest first
	sort.Slice(backups, func(i, j int) bool {
		return backups[i].Timestamp.After(backups[j].Timestamp)
	})

	return backups, nil
}

// GetBackup returns a specific backup
func (s *BackupService) GetBackup(id string) (*BackupInfo, error) {
	metaFile := filepath.Join(s.backupPath, id+".json")
	data, err := os.ReadFile(metaFile)
	if err != nil {
		return nil, err
	}

	var info BackupInfo
	if err := json.Unmarshal(data, &info); err != nil {
		return nil, err
	}

	return &info, nil
}

// RestoreBackup restores configuration from a backup
func (s *BackupService) RestoreBackup(id, username string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	backup, err := s.GetBackup(id)
	if err != nil {
		return fmt.Errorf("backup not found: %w", err)
	}

	backupFile := filepath.Join(s.backupPath, backup.Filename)
	file, err := os.Open(backupFile)
	if err != nil {
		return fmt.Errorf("failed to open backup: %w", err)
	}
	defer file.Close()

	var tr *tar.Reader

	if backup.Compressed {
		gr, err := gzip.NewReader(file)
		if err != nil {
			return fmt.Errorf("failed to decompress backup: %w", err)
		}
		defer gr.Close()
		tr = tar.NewReader(gr)
	} else {
		tr = tar.NewReader(file)
	}

	// First, create a pre-restore backup
	_, err = s.createBackupUnlocked("pre-restore", fmt.Sprintf("Auto-backup before restore from %s", id))
	if err != nil {
		return fmt.Errorf("failed to create pre-restore backup: %w", err)
	}

	// Extract files
	for {
		header, err := tr.Next()
		if err == io.EOF {
			break
		}
		if err != nil {
			return fmt.Errorf("failed to read backup: %w", err)
		}

		// Create parent directories
		os.MkdirAll(filepath.Dir(header.Name), 0755)

		// Write file
		outFile, err := os.OpenFile(header.Name, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, os.FileMode(header.Mode))
		if err != nil {
			continue
		}

		io.Copy(outFile, tr)
		outFile.Close()
	}

	// Log audit entry
	if s.audit != nil {
		s.audit.Log(AuditEntry{
			Action:    "backup_restore",
			Username:  username,
			Resource:  backup.Filename,
			Details:   map[string]interface{}{"backup_id": id},
			Timestamp: time.Now(),
			IPAddress: "localhost",
		})
	}

	// Send notification
	if s.webhooks != nil {
		s.webhooks.Send(WebhookEvent{
			Type:      "backup_restore",
			Severity:  "warning",
			Title:     "Configuration Restored",
			Message:   fmt.Sprintf("User %s restored configuration from backup %s", username, id),
			Timestamp: time.Now(),
			Source:    getHostname(),
		})
	}

	return nil
}

func (s *BackupService) createBackupUnlocked(backupType, description string) (*BackupInfo, error) {
	timestamp := time.Now()
	backupID := fmt.Sprintf("%s-%s", timestamp.Format("20060102-150405"), generateShortID())

	var filename string
	if s.config.CompressBackups {
		filename = fmt.Sprintf("nswall-backup-%s.tar.gz", backupID)
	} else {
		filename = fmt.Sprintf("nswall-backup-%s.tar", backupID)
	}

	backupFile := filepath.Join(s.backupPath, filename)

	file, err := os.Create(backupFile)
	if err != nil {
		return nil, err
	}
	defer file.Close()

	var tw *tar.Writer
	var gw *gzip.Writer

	if s.config.CompressBackups {
		gw = gzip.NewWriter(file)
		tw = tar.NewWriter(gw)
	} else {
		tw = tar.NewWriter(file)
	}

	fileCount := 0
	hasher := sha256.New()

	for _, pattern := range s.config.IncludeFiles {
		matches, _ := filepath.Glob(pattern)
		for _, match := range matches {
			if s.shouldExclude(match) {
				continue
			}
			if err := s.addFileToTar(tw, match, hasher); err != nil {
				continue
			}
			fileCount++
		}
	}

	tw.Close()
	if gw != nil {
		gw.Close()
	}

	stat, _ := os.Stat(backupFile)

	backup := &BackupInfo{
		ID:          backupID,
		Filename:    filename,
		Timestamp:   timestamp,
		Size:        stat.Size(),
		Hash:        hex.EncodeToString(hasher.Sum(nil))[:16],
		FileCount:   fileCount,
		Compressed:  s.config.CompressBackups,
		Description: description,
		Type:        backupType,
	}

	s.saveBackupMetadata(backup)
	return backup, nil
}

// DeleteBackup deletes a backup
func (s *BackupService) DeleteBackup(id string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	backup, err := s.GetBackup(id)
	if err != nil {
		return err
	}

	// Delete backup file
	backupFile := filepath.Join(s.backupPath, backup.Filename)
	os.Remove(backupFile)

	// Delete metadata
	metaFile := filepath.Join(s.backupPath, id+".json")
	return os.Remove(metaFile)
}

// DownloadBackup returns the backup file path for download
func (s *BackupService) DownloadBackup(id string) (string, error) {
	backup, err := s.GetBackup(id)
	if err != nil {
		return "", err
	}

	backupFile := filepath.Join(s.backupPath, backup.Filename)
	if _, err := os.Stat(backupFile); err != nil {
		return "", fmt.Errorf("backup file not found")
	}

	return backupFile, nil
}

// GetConfig returns the backup configuration
func (s *BackupService) GetConfig() BackupConfig {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.config
}

// UpdateConfig updates the backup configuration
func (s *BackupService) UpdateConfig(config BackupConfig) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	s.config = config
	return s.saveConfig()
}

// GetStatus returns backup service status
func (s *BackupService) GetStatus() map[string]interface{} {
	s.mu.RLock()
	defer s.mu.RUnlock()

	backups, _ := s.ListBackups()

	var totalSize int64
	for _, b := range backups {
		totalSize += b.Size
	}

	status := map[string]interface{}{
		"enabled":      s.config.Enabled,
		"running":      s.running,
		"schedule":     s.config.Schedule,
		"backup_count": len(backups),
		"total_size":   totalSize,
		"next_run":     s.calculateNextRun(),
	}

	if !s.lastBackup.IsZero() {
		status["last_backup"] = s.lastBackup
	}

	if s.lastError != nil {
		status["last_error"] = s.lastError.Error()
	}

	return status
}

func generateShortID() string {
	b := make([]byte, 4)
	rand.Read(b)
	return hex.EncodeToString(b)[:6]
}
