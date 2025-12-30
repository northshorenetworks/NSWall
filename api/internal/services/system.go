// Package services provides business logic for the NSWall API
package services

import (
	"bufio"
	"context"
	"fmt"
	"os"
	"os/exec"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/models"
	"github.com/northshorenetworks/nswall/api/internal/openbsd"
)

// SystemService handles system-related operations
type SystemService struct {
	nshPath string
}

// NewSystemService creates a new SystemService
func NewSystemService(nshPath string) *SystemService {
	return &SystemService{nshPath: nshPath}
}

// GetSystemInfo returns system information using native syscalls
func (s *SystemService) GetSystemInfo() (*models.SystemInfo, error) {
	// Use native openbsd package - no command parsing needed
	hostname, _ := openbsd.GetHostname()
	if hostname == "" {
		hostname, _ = os.Hostname() // fallback
	}

	osVersion, _ := openbsd.GetOSRelease()
	arch, _ := openbsd.GetMachine()
	bootTime, _ := openbsd.GetBootTime()
	cpuInfo, _ := openbsd.GetCPUInfo()

	uptime := time.Since(bootTime)

	// Get load average - native sysctl
	var loadAvg []float64
	if load, err := openbsd.GetLoadAvg(); err == nil {
		loadAvg = []float64{load.Load1, load.Load5, load.Load15}
	} else {
		loadAvg = []float64{0, 0, 0}
	}

	cpuCount := 1
	if cpuInfo != nil && cpuInfo.NCPUs > 0 {
		cpuCount = cpuInfo.NCPUs
	}

	info := &models.SystemInfo{
		Hostname:    hostname,
		OS:          "OpenBSD",
		Version:     osVersion,
		Arch:        arch,
		Uptime:      int64(uptime.Seconds()),
		UptimeHuman: formatDuration(uptime),
		BootTime:    bootTime,
		Time:        time.Now(),
		Timezone:    getTimezone(),
		LoadAvg:     loadAvg,
		CPUCount:    cpuCount,
	}

	return info, nil
}

// GetMemoryInfo returns memory usage information using native syscalls
func (s *SystemService) GetMemoryInfo() (*models.MemoryInfo, error) {
	// Use native sysctl for total memory
	total, err := openbsd.GetPhysMem()
	if err != nil {
		return nil, fmt.Errorf("failed to get physical memory: %w", err)
	}

	// Get VM stats from sysctl
	var used, free, cached uint64
	if vmStats, err := openbsd.GetVMStats(); err == nil && vmStats != nil {
		pageSize := uint64(vmStats.PageSize)
		if pageSize == 0 {
			pageSize = 4096
		}
		free = uint64(vmStats.FreePages) * pageSize
		used = uint64(vmStats.ActivePages) * pageSize
		cached = uint64(vmStats.InactivePages) * pageSize
	} else {
		// Fallback to vmstat parsing if sysctl fails
		vmstat, _ := exec.Command("vmstat", "-s").Output()
		used, free, cached = parseVmstat(string(vmstat))
	}

	// Get swap info (still requires swapctl)
	swapTotal, swapUsed := getSwapInfo()

	var usedPct float64
	if total > 0 {
		usedPct = float64(used) / float64(total) * 100
	}

	return &models.MemoryInfo{
		Total:     total,
		Used:      used,
		Free:      free,
		Cached:    cached,
		UsedPct:   usedPct,
		SwapTotal: swapTotal,
		SwapUsed:  swapUsed,
	}, nil
}

// GetDiskInfo returns disk usage information
// Note: Could use statvfs syscall, but df is reliable and portable
func (s *SystemService) GetDiskInfo() ([]models.DiskInfo, error) {
	output, err := exec.Command("df", "-k").Output()
	if err != nil {
		return nil, err
	}

	var disks []models.DiskInfo
	lines := strings.Split(string(output), "\n")

	for i, line := range lines {
		if i == 0 || line == "" {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 6 {
			continue
		}

		total, _ := strconv.ParseUint(fields[1], 10, 64)
		used, _ := strconv.ParseUint(fields[2], 10, 64)
		avail, _ := strconv.ParseUint(fields[3], 10, 64)
		pctStr := strings.TrimSuffix(fields[4], "%")
		pct, _ := strconv.ParseFloat(pctStr, 64)

		disk := models.DiskInfo{
			Filesystem: fields[0],
			Mount:      fields[5],
			Total:      total * 1024,
			Used:       used * 1024,
			Available:  avail * 1024,
			UsedPct:    pct,
		}
		disks = append(disks, disk)
	}

	return disks, nil
}

// RunNshCommand executes an nsh command
func (s *SystemService) RunNshCommand(ctx context.Context, command string, timeout int) (*models.CommandResponse, error) {
	if timeout <= 0 {
		timeout = 30
	}

	ctx, cancel := context.WithTimeout(ctx, time.Duration(timeout)*time.Second)
	defer cancel()

	start := time.Now()
	cmd := exec.CommandContext(ctx, s.nshPath, "-c", command)
	output, err := cmd.CombinedOutput()
	duration := time.Since(start)

	exitCode := 0
	if err != nil {
		if exitErr, ok := err.(*exec.ExitError); ok {
			exitCode = exitErr.ExitCode()
		} else {
			exitCode = 1
		}
	}

	return &models.CommandResponse{
		Command:  command,
		Output:   string(output),
		ExitCode: exitCode,
		Duration: duration.String(),
	}, nil
}

// GetVersion returns nsh version info
func (s *SystemService) GetVersion() (string, error) {
	output, err := exec.Command(s.nshPath, "-v").Output()
	if err != nil {
		// Try alternate method
		output, err = exec.Command(s.nshPath, "-c", "show version").Output()
	}
	return strings.TrimSpace(string(output)), err
}

// GetLogs returns recent system logs
func (s *SystemService) GetLogs(facility string, lines int) ([]models.LogEntry, error) {
	if lines <= 0 {
		lines = 100
	}

	args := []string{"-n", strconv.Itoa(lines)}
	if facility != "" {
		args = append(args, "/var/log/"+facility)
	} else {
		args = append(args, "/var/log/messages")
	}

	output, err := exec.Command("tail", args...).Output()
	if err != nil {
		return nil, err
	}

	var logs []models.LogEntry
	scanner := bufio.NewScanner(strings.NewReader(string(output)))

	for scanner.Scan() {
		line := scanner.Text()
		entry := parseLogLine(line)
		if entry != nil {
			logs = append(logs, *entry)
		}
	}

	return logs, nil
}

// Helper functions

func getTimezone() string {
	// Could use time.Now().Zone() but date gives the abbrev
	tz, _ := exec.Command("date", "+%Z").Output()
	return strings.TrimSpace(string(tz))
}

func formatDuration(d time.Duration) string {
	days := int(d.Hours()) / 24
	hours := int(d.Hours()) % 24
	minutes := int(d.Minutes()) % 60

	if days > 0 {
		return fmt.Sprintf("%dd %dh %dm", days, hours, minutes)
	}
	if hours > 0 {
		return fmt.Sprintf("%dh %dm", hours, minutes)
	}
	return fmt.Sprintf("%dm", minutes)
}

// parseVmstat is kept as fallback for when sysctl vm.uvmexp fails
func parseVmstat(output string) (used, free, cached uint64) {
	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if strings.Contains(line, "pages free") {
			fields := strings.Fields(line)
			if len(fields) > 0 {
				pages, _ := strconv.ParseUint(fields[0], 10, 64)
				free = pages * 4096 // 4KB pages
			}
		}
		if strings.Contains(line, "pages active") {
			fields := strings.Fields(line)
			if len(fields) > 0 {
				pages, _ := strconv.ParseUint(fields[0], 10, 64)
				used += pages * 4096
			}
		}
		if strings.Contains(line, "pages inactive") {
			fields := strings.Fields(line)
			if len(fields) > 0 {
				pages, _ := strconv.ParseUint(fields[0], 10, 64)
				cached = pages * 4096
			}
		}
	}
	return
}

func getSwapInfo() (total, used uint64) {
	output, err := exec.Command("swapctl", "-s").Output()
	if err != nil {
		return 0, 0
	}

	// Parse: total: 1234 1K-blocks allocated, 567 used, 667 available
	re := regexp.MustCompile(`total:\s+(\d+)\s+.*allocated,\s+(\d+)\s+used`)
	matches := re.FindStringSubmatch(string(output))
	if len(matches) >= 3 {
		total, _ = strconv.ParseUint(matches[1], 10, 64)
		used, _ = strconv.ParseUint(matches[2], 10, 64)
		total *= 1024
		used *= 1024
	}
	return
}

func parseLogLine(line string) *models.LogEntry {
	// Standard syslog format: "Dec 24 10:15:30 host program[pid]: message"
	if len(line) < 16 {
		return nil
	}

	// Try to parse timestamp
	parts := strings.SplitN(line, " ", 5)
	if len(parts) < 5 {
		return &models.LogEntry{
			Message: line,
		}
	}

	timestampStr := strings.Join(parts[:3], " ")
	timestamp, _ := time.Parse("Jan _2 15:04:05", timestampStr)
	if timestamp.Year() == 0 {
		timestamp = timestamp.AddDate(time.Now().Year(), 0, 0)
	}

	host := parts[3]
	rest := parts[4]

	// Extract program and message
	var program, message string
	if idx := strings.Index(rest, ":"); idx > 0 {
		program = strings.TrimSuffix(rest[:idx], "]")
		if pidIdx := strings.Index(program, "["); pidIdx > 0 {
			program = program[:pidIdx]
		}
		message = strings.TrimSpace(rest[idx+1:])
	} else {
		message = rest
	}

	return &models.LogEntry{
		Timestamp: timestamp,
		Host:      host,
		Program:   program,
		Message:   message,
	}
}
