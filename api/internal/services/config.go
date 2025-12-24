package services

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/models"
)

// ConfigService handles configuration management
type ConfigService struct {
	nshPath    string
	configDir  string
	configFile string
}

// NewConfigService creates a new ConfigService
func NewConfigService(nshPath string) *ConfigService {
	return &ConfigService{
		nshPath:    nshPath,
		configDir:  "/etc/nsh",
		configFile: "/etc/nsh/nsh.conf",
	}
}

// GetRunningConfig returns the current running configuration
func (c *ConfigService) GetRunningConfig(ctx context.Context) (*models.Config, error) {
	cmd := exec.CommandContext(ctx, c.nshPath, "-c", "show running-config")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return nil, fmt.Errorf("failed to get running config: %v", err)
	}

	return &models.Config{
		Raw: string(output),
	}, nil
}

// GetStartupConfig returns the startup configuration
func (c *ConfigService) GetStartupConfig(ctx context.Context) (*models.Config, error) {
	content, err := os.ReadFile(c.configFile)
	if err != nil {
		return nil, fmt.Errorf("failed to read startup config: %v", err)
	}

	return &models.Config{
		Raw: string(content),
	}, nil
}

// GetConfigDiff returns the difference between running and startup config
func (c *ConfigService) GetConfigDiff(ctx context.Context) (*models.ConfigDiff, error) {
	running, err := c.GetRunningConfig(ctx)
	if err != nil {
		return nil, err
	}

	startup, err := c.GetStartupConfig(ctx)
	if err != nil {
		return nil, err
	}

	// Write to temp files for diff
	runFile := filepath.Join(os.TempDir(), "nswall_running.conf")
	startFile := filepath.Join(os.TempDir(), "nswall_startup.conf")

	os.WriteFile(runFile, []byte(running.Raw), 0600)
	os.WriteFile(startFile, []byte(startup.Raw), 0600)
	defer os.Remove(runFile)
	defer os.Remove(startFile)

	// Run diff
	diffCmd := exec.CommandContext(ctx, "diff", "-u", startFile, runFile)
	diffOutput, _ := diffCmd.CombinedOutput()

	same := len(diffOutput) == 0

	return &models.ConfigDiff{
		Running: running.Raw,
		Startup: startup.Raw,
		Diff:    string(diffOutput),
		Same:    same,
	}, nil
}

// SaveConfig saves the running configuration to startup
func (c *ConfigService) SaveConfig(ctx context.Context) error {
	cmd := exec.CommandContext(ctx, c.nshPath, "-c", "write-config")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to save config: %s", string(output))
	}
	return nil
}

// BackupConfig creates a backup of the current configuration
func (c *ConfigService) BackupConfig(ctx context.Context, name string) (string, error) {
	if name == "" {
		name = time.Now().Format("20060102-150405")
	}

	backupDir := filepath.Join(c.configDir, "backups")
	if err := os.MkdirAll(backupDir, 0700); err != nil {
		return "", fmt.Errorf("failed to create backup directory: %v", err)
	}

	backupFile := filepath.Join(backupDir, fmt.Sprintf("nsh.conf.%s", name))

	// Get running config
	running, err := c.GetRunningConfig(ctx)
	if err != nil {
		return "", err
	}

	if err := os.WriteFile(backupFile, []byte(running.Raw), 0600); err != nil {
		return "", fmt.Errorf("failed to write backup: %v", err)
	}

	return backupFile, nil
}

// ListBackups returns available configuration backups
func (c *ConfigService) ListBackups(ctx context.Context) ([]map[string]interface{}, error) {
	backupDir := filepath.Join(c.configDir, "backups")

	entries, err := os.ReadDir(backupDir)
	if err != nil {
		if os.IsNotExist(err) {
			return []map[string]interface{}{}, nil
		}
		return nil, err
	}

	var backups []map[string]interface{}
	for _, entry := range entries {
		if entry.IsDir() {
			continue
		}

		info, err := entry.Info()
		if err != nil {
			continue
		}

		backup := map[string]interface{}{
			"name":     entry.Name(),
			"path":     filepath.Join(backupDir, entry.Name()),
			"size":     info.Size(),
			"modified": info.ModTime(),
		}
		backups = append(backups, backup)
	}

	return backups, nil
}

// RestoreBackup restores a configuration backup
func (c *ConfigService) RestoreBackup(ctx context.Context, name string) error {
	backupFile := filepath.Join(c.configDir, "backups", name)

	content, err := os.ReadFile(backupFile)
	if err != nil {
		return fmt.Errorf("backup not found: %v", err)
	}

	// Write to startup config
	if err := os.WriteFile(c.configFile, content, 0600); err != nil {
		return fmt.Errorf("failed to restore config: %v", err)
	}

	// Reload configuration
	cmd := exec.CommandContext(ctx, c.nshPath, "-c", "reload")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("config restored but reload failed: %s", string(output))
	}

	return nil
}

// DeleteBackup deletes a configuration backup
func (c *ConfigService) DeleteBackup(ctx context.Context, name string) error {
	backupFile := filepath.Join(c.configDir, "backups", name)

	if err := os.Remove(backupFile); err != nil {
		return fmt.Errorf("failed to delete backup: %v", err)
	}

	return nil
}

// ValidateConfig validates a configuration
func (c *ConfigService) ValidateConfig(ctx context.Context, config string) (bool, []string, error) {
	// Write to temp file
	tmpFile := filepath.Join(os.TempDir(), "nswall_validate.conf")
	if err := os.WriteFile(tmpFile, []byte(config), 0600); err != nil {
		return false, nil, err
	}
	defer os.Remove(tmpFile)

	// Try to parse the config
	cmd := exec.CommandContext(ctx, c.nshPath, "-n", "-f", tmpFile)
	output, err := cmd.CombinedOutput()

	if err != nil {
		// Parse errors from output
		var errors []string
		lines := strings.Split(string(output), "\n")
		for _, line := range lines {
			line = strings.TrimSpace(line)
			if line != "" && (strings.Contains(line, "error") || strings.Contains(line, "Error") || strings.Contains(line, "invalid")) {
				errors = append(errors, line)
			}
		}
		if len(errors) == 0 {
			errors = append(errors, string(output))
		}
		return false, errors, nil
	}

	return true, nil, nil
}

// ApplyConfig applies a configuration snippet
func (c *ConfigService) ApplyConfig(ctx context.Context, config string) error {
	// Write config to temp file
	tmpFile := filepath.Join(os.TempDir(), "nswall_apply.conf")
	if err := os.WriteFile(tmpFile, []byte(config), 0600); err != nil {
		return err
	}
	defer os.Remove(tmpFile)

	// Apply config
	cmd := exec.CommandContext(ctx, c.nshPath, "-f", tmpFile)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to apply config: %s", string(output))
	}

	return nil
}

// GetHostname returns the system hostname
func (c *ConfigService) GetHostname(ctx context.Context) (string, error) {
	return os.Hostname()
}

// SetHostname sets the system hostname
func (c *ConfigService) SetHostname(ctx context.Context, hostname string) error {
	cmd := exec.CommandContext(ctx, c.nshPath, "-c", fmt.Sprintf("hostname %s", hostname))
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to set hostname: %s", string(output))
	}
	return nil
}

// GetPFConfig returns the PF configuration
func (c *ConfigService) GetPFConfig(ctx context.Context) (string, error) {
	content, err := os.ReadFile("/etc/pf.conf")
	if err != nil {
		return "", fmt.Errorf("pf.conf not found: %v", err)
	}
	return string(content), nil
}

// GetBGPConfig returns the BGP configuration
func (c *ConfigService) GetBGPConfig(ctx context.Context) (string, error) {
	content, err := os.ReadFile("/etc/bgpd.conf")
	if err != nil {
		return "", fmt.Errorf("bgpd.conf not found: %v", err)
	}
	return string(content), nil
}

// GetOSPFConfig returns the OSPF configuration
func (c *ConfigService) GetOSPFConfig(ctx context.Context) (string, error) {
	content, err := os.ReadFile("/etc/ospfd.conf")
	if err != nil {
		return "", fmt.Errorf("ospfd.conf not found: %v", err)
	}
	return string(content), nil
}

// GetIKEConfig returns the IKE configuration
func (c *ConfigService) GetIKEConfig(ctx context.Context) (string, error) {
	content, err := os.ReadFile("/etc/iked.conf")
	if err != nil {
		return "", fmt.Errorf("iked.conf not found: %v", err)
	}
	return string(content), nil
}

// ExportConfig exports the full system configuration as a tarball
func (c *ConfigService) ExportConfig(ctx context.Context) (string, error) {
	exportFile := filepath.Join(os.TempDir(), fmt.Sprintf("nswall-export-%s.tgz", time.Now().Format("20060102-150405")))

	// Create tarball of configuration files
	files := []string{
		c.configFile,
		"/etc/pf.conf",
		"/etc/hostname.*",
		"/etc/mygate",
		"/etc/resolv.conf",
		"/etc/dhcpd.conf",
		"/etc/bgpd.conf",
		"/etc/ospfd.conf",
		"/etc/iked.conf",
		"/etc/relayd.conf",
	}

	args := []string{"-czf", exportFile}
	for _, f := range files {
		// Expand globs
		matches, _ := filepath.Glob(f)
		if len(matches) > 0 {
			args = append(args, matches...)
		} else if _, err := os.Stat(f); err == nil {
			args = append(args, f)
		}
	}

	cmd := exec.CommandContext(ctx, "tar", args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return "", fmt.Errorf("failed to create export: %s", string(output))
	}

	return exportFile, nil
}
