package services

import (
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"sort"
	"strings"
	"sync"
	"time"
)

// ConfigVersion represents a saved configuration version
type ConfigVersion struct {
	ID          string    `json:"id"`
	Timestamp   time.Time `json:"timestamp"`
	Username    string    `json:"username"`
	Description string    `json:"description"`
	Hash        string    `json:"hash"`
	Size        int64     `json:"size"`
	Files       []string  `json:"files"`
}

// ConfigDiff represents a difference between configurations
type ConfigDiff struct {
	File      string   `json:"file"`
	Type      string   `json:"type"` // added, removed, modified
	OldLines  []string `json:"old_lines,omitempty"`
	NewLines  []string `json:"new_lines,omitempty"`
	AddedLines int     `json:"added_lines"`
	RemovedLines int  `json:"removed_lines"`
}

// ConfigHistoryService manages configuration versioning
type ConfigHistoryService struct {
	historyPath  string
	configFiles  []string
	maxVersions  int
	mu           sync.RWMutex
	webhooks     *WebhookService
}

// NewConfigHistoryService creates a new config history service
func NewConfigHistoryService(configPath string, webhooks *WebhookService) *ConfigHistoryService {
	svc := &ConfigHistoryService{
		historyPath: filepath.Join(configPath, "history"),
		configFiles: []string{
			"/etc/pf.conf",
			"/etc/hostname.*",
			"/etc/mygate",
			"/etc/resolv.conf",
			"/etc/dhcpd.conf",
			"/etc/iked.conf",
			"/var/unbound/etc/unbound.conf",
		},
		maxVersions: 100,
		webhooks:    webhooks,
	}

	os.MkdirAll(svc.historyPath, 0700)
	return svc
}

// SaveVersion saves the current configuration as a new version
func (s *ConfigHistoryService) SaveVersion(username, description string) (*ConfigVersion, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	version := &ConfigVersion{
		ID:          generateVersionID(),
		Timestamp:   time.Now(),
		Username:    username,
		Description: description,
		Files:       make([]string, 0),
	}

	// Create version directory
	versionDir := filepath.Join(s.historyPath, version.ID)
	os.MkdirAll(versionDir, 0700)

	// Copy all config files
	hasher := sha256.New()
	totalSize := int64(0)

	for _, pattern := range s.configFiles {
		matches, _ := filepath.Glob(pattern)
		for _, file := range matches {
			data, err := os.ReadFile(file)
			if err != nil {
				continue
			}

			// Save to version directory
			destFile := filepath.Join(versionDir, filepath.Base(file))
			os.WriteFile(destFile, data, 0600)

			hasher.Write(data)
			totalSize += int64(len(data))
			version.Files = append(version.Files, filepath.Base(file))
		}
	}

	version.Hash = hex.EncodeToString(hasher.Sum(nil))[:16]
	version.Size = totalSize

	// Save version metadata
	metaFile := filepath.Join(versionDir, "version.json")
	metaData, _ := json.MarshalIndent(version, "", "  ")
	os.WriteFile(metaFile, metaData, 0600)

	// Cleanup old versions
	s.cleanupOldVersions()

	// Send webhook
	if s.webhooks != nil {
		s.webhooks.AlertConfigChange(username, description)
	}

	return version, nil
}

// GetVersions returns all saved versions
func (s *ConfigHistoryService) GetVersions() ([]ConfigVersion, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	entries, err := os.ReadDir(s.historyPath)
	if err != nil {
		return nil, err
	}

	versions := make([]ConfigVersion, 0)
	for _, entry := range entries {
		if !entry.IsDir() {
			continue
		}

		metaFile := filepath.Join(s.historyPath, entry.Name(), "version.json")
		data, err := os.ReadFile(metaFile)
		if err != nil {
			continue
		}

		var version ConfigVersion
		if json.Unmarshal(data, &version) == nil {
			versions = append(versions, version)
		}
	}

	// Sort by timestamp, newest first
	sort.Slice(versions, func(i, j int) bool {
		return versions[i].Timestamp.After(versions[j].Timestamp)
	})

	return versions, nil
}

// GetVersion returns a specific version
func (s *ConfigHistoryService) GetVersion(id string) (*ConfigVersion, error) {
	metaFile := filepath.Join(s.historyPath, id, "version.json")
	data, err := os.ReadFile(metaFile)
	if err != nil {
		return nil, err
	}

	var version ConfigVersion
	if err := json.Unmarshal(data, &version); err != nil {
		return nil, err
	}

	return &version, nil
}

// GetVersionFile returns a specific file from a version
func (s *ConfigHistoryService) GetVersionFile(versionID, filename string) (string, error) {
	filePath := filepath.Join(s.historyPath, versionID, filename)
	data, err := os.ReadFile(filePath)
	if err != nil {
		return "", err
	}
	return string(data), nil
}

// Compare compares two versions and returns differences
func (s *ConfigHistoryService) Compare(versionID1, versionID2 string) ([]ConfigDiff, error) {
	v1, err := s.GetVersion(versionID1)
	if err != nil {
		return nil, fmt.Errorf("version 1 not found: %w", err)
	}

	v2, err := s.GetVersion(versionID2)
	if err != nil {
		return nil, fmt.Errorf("version 2 not found: %w", err)
	}

	diffs := make([]ConfigDiff, 0)

	// Get all files from both versions
	allFiles := make(map[string]bool)
	for _, f := range v1.Files {
		allFiles[f] = true
	}
	for _, f := range v2.Files {
		allFiles[f] = true
	}

	for file := range allFiles {
		file1 := filepath.Join(s.historyPath, versionID1, file)
		file2 := filepath.Join(s.historyPath, versionID2, file)

		content1, err1 := os.ReadFile(file1)
		content2, err2 := os.ReadFile(file2)

		diff := ConfigDiff{File: file}

		if err1 != nil && err2 == nil {
			// File added in version 2
			diff.Type = "added"
			diff.NewLines = strings.Split(string(content2), "\n")
			diff.AddedLines = len(diff.NewLines)
		} else if err1 == nil && err2 != nil {
			// File removed in version 2
			diff.Type = "removed"
			diff.OldLines = strings.Split(string(content1), "\n")
			diff.RemovedLines = len(diff.OldLines)
		} else if err1 == nil && err2 == nil {
			// Compare contents
			if string(content1) != string(content2) {
				diff.Type = "modified"
				diff.OldLines, diff.NewLines = computeDiff(string(content1), string(content2))
				diff.AddedLines = len(diff.NewLines)
				diff.RemovedLines = len(diff.OldLines)
			} else {
				continue // No difference
			}
		} else {
			continue
		}

		diffs = append(diffs, diff)
	}

	return diffs, nil
}

// CompareWithCurrent compares a version with current configuration
func (s *ConfigHistoryService) CompareWithCurrent(versionID string) ([]ConfigDiff, error) {
	v, err := s.GetVersion(versionID)
	if err != nil {
		return nil, err
	}

	diffs := make([]ConfigDiff, 0)

	for _, file := range v.Files {
		versionFile := filepath.Join(s.historyPath, versionID, file)
		versionContent, err := os.ReadFile(versionFile)
		if err != nil {
			continue
		}

		// Find current file
		var currentPath string
		for _, pattern := range s.configFiles {
			matches, _ := filepath.Glob(pattern)
			for _, m := range matches {
				if filepath.Base(m) == file {
					currentPath = m
					break
				}
			}
		}

		if currentPath == "" {
			continue
		}

		currentContent, err := os.ReadFile(currentPath)
		if err != nil {
			// File was removed
			diff := ConfigDiff{
				File:         file,
				Type:         "removed",
				OldLines:     strings.Split(string(versionContent), "\n"),
				RemovedLines: len(strings.Split(string(versionContent), "\n")),
			}
			diffs = append(diffs, diff)
			continue
		}

		if string(versionContent) != string(currentContent) {
			oldLines, newLines := computeDiff(string(versionContent), string(currentContent))
			diff := ConfigDiff{
				File:         file,
				Type:         "modified",
				OldLines:     oldLines,
				NewLines:     newLines,
				AddedLines:   len(newLines),
				RemovedLines: len(oldLines),
			}
			diffs = append(diffs, diff)
		}
	}

	return diffs, nil
}

// Restore restores configuration from a saved version
func (s *ConfigHistoryService) Restore(versionID, username string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	version, err := s.GetVersion(versionID)
	if err != nil {
		return err
	}

	// First, save current config as a backup
	backupVersion := &ConfigVersion{
		ID:          generateVersionID(),
		Timestamp:   time.Now(),
		Username:    username,
		Description: fmt.Sprintf("Auto-backup before restore from %s", versionID),
		Files:       make([]string, 0),
	}

	backupDir := filepath.Join(s.historyPath, backupVersion.ID)
	os.MkdirAll(backupDir, 0700)

	for _, pattern := range s.configFiles {
		matches, _ := filepath.Glob(pattern)
		for _, file := range matches {
			data, err := os.ReadFile(file)
			if err != nil {
				continue
			}
			destFile := filepath.Join(backupDir, filepath.Base(file))
			os.WriteFile(destFile, data, 0600)
			backupVersion.Files = append(backupVersion.Files, filepath.Base(file))
		}
	}

	metaFile := filepath.Join(backupDir, "version.json")
	metaData, _ := json.MarshalIndent(backupVersion, "", "  ")
	os.WriteFile(metaFile, metaData, 0600)

	// Now restore files from the target version
	for _, file := range version.Files {
		srcFile := filepath.Join(s.historyPath, versionID, file)
		srcData, err := os.ReadFile(srcFile)
		if err != nil {
			continue
		}

		// Find the original path
		for _, pattern := range s.configFiles {
			matches, _ := filepath.Glob(pattern)
			for _, m := range matches {
				if filepath.Base(m) == file {
					os.WriteFile(m, srcData, 0644)
					break
				}
			}
		}
	}

	// Reload PF rules
	exec.Command("pfctl", "-f", "/etc/pf.conf").Run()

	// Send webhook
	if s.webhooks != nil {
		s.webhooks.Send(WebhookEvent{
			Type:      "config_restore",
			Severity:  "warning",
			Title:     "Configuration Restored",
			Message:   fmt.Sprintf("User %s restored configuration from version %s (%s)", username, versionID, version.Description),
			Timestamp: time.Now(),
			Source:    getHostname(),
		})
	}

	return nil
}

// DeleteVersion deletes a saved version
func (s *ConfigHistoryService) DeleteVersion(versionID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	versionDir := filepath.Join(s.historyPath, versionID)
	return os.RemoveAll(versionDir)
}

func (s *ConfigHistoryService) cleanupOldVersions() {
	entries, err := os.ReadDir(s.historyPath)
	if err != nil {
		return
	}

	if len(entries) <= s.maxVersions {
		return
	}

	// Get versions with timestamps
	type versionTime struct {
		id   string
		time time.Time
	}
	versions := make([]versionTime, 0)

	for _, entry := range entries {
		if !entry.IsDir() {
			continue
		}

		metaFile := filepath.Join(s.historyPath, entry.Name(), "version.json")
		data, err := os.ReadFile(metaFile)
		if err != nil {
			continue
		}

		var v ConfigVersion
		if json.Unmarshal(data, &v) == nil {
			versions = append(versions, versionTime{id: v.ID, time: v.Timestamp})
		}
	}

	// Sort by time, oldest first
	sort.Slice(versions, func(i, j int) bool {
		return versions[i].time.Before(versions[j].time)
	})

	// Delete oldest versions
	toDelete := len(versions) - s.maxVersions
	for i := 0; i < toDelete; i++ {
		os.RemoveAll(filepath.Join(s.historyPath, versions[i].id))
	}
}

func computeDiff(old, new string) (removed, added []string) {
	oldLines := strings.Split(old, "\n")
	newLines := strings.Split(new, "\n")

	oldSet := make(map[string]bool)
	newSet := make(map[string]bool)

	for _, line := range oldLines {
		oldSet[line] = true
	}
	for _, line := range newLines {
		newSet[line] = true
	}

	removed = make([]string, 0)
	added = make([]string, 0)

	for _, line := range oldLines {
		if !newSet[line] && strings.TrimSpace(line) != "" {
			removed = append(removed, line)
		}
	}

	for _, line := range newLines {
		if !oldSet[line] && strings.TrimSpace(line) != "" {
			added = append(added, line)
		}
	}

	return removed, added
}

func generateVersionID() string {
	b := make([]byte, 8)
	rand.Read(b)
	return fmt.Sprintf("%s-%s", time.Now().Format("20060102-150405"), hex.EncodeToString(b)[:6])
}
