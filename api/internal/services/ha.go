package services

import (
	"bufio"
	"context"
	"fmt"
	"os/exec"
	"regexp"
	"strconv"
	"strings"

	"github.com/northshorenetworks/nswall/api/internal/models"
)

// HAService handles High Availability operations (CARP, pfsync)
type HAService struct {
	nshPath string
}

// NewHAService creates a new HAService
func NewHAService(nshPath string) *HAService {
	return &HAService{nshPath: nshPath}
}

// GetCARPInterfaces returns all CARP interfaces and their status
func (h *HAService) GetCARPInterfaces(ctx context.Context) ([]models.CARPInterface, error) {
	output, err := exec.CommandContext(ctx, "ifconfig", "-a").Output()
	if err != nil {
		return nil, err
	}

	var carp []models.CARPInterface
	var currentIface string
	var currentCarp *models.CARPInterface

	scanner := bufio.NewScanner(strings.NewReader(string(output)))
	for scanner.Scan() {
		line := scanner.Text()

		// New interface
		if len(line) > 0 && line[0] != ' ' && line[0] != '\t' {
			if currentCarp != nil {
				carp = append(carp, *currentCarp)
				currentCarp = nil
			}

			parts := strings.SplitN(line, ":", 2)
			if len(parts) > 0 {
				currentIface = parts[0]
			}
			continue
		}

		line = strings.TrimSpace(line)

		// Look for carp: line
		if strings.HasPrefix(line, "carp:") {
			// Parse: carp: MASTER vhid 1 advbase 1 advskew 0
			currentCarp = &models.CARPInterface{
				Interface: currentIface,
			}

			parts := strings.Fields(line)
			for i, p := range parts {
				if p == "MASTER" || p == "BACKUP" || p == "INIT" {
					currentCarp.State = p
				}
				if p == "vhid" && i+1 < len(parts) {
					currentCarp.VHID, _ = strconv.Atoi(parts[i+1])
				}
				if p == "advbase" && i+1 < len(parts) {
					currentCarp.AdvBase, _ = strconv.Atoi(parts[i+1])
				}
				if p == "advskew" && i+1 < len(parts) {
					currentCarp.AdvSkew, _ = strconv.Atoi(parts[i+1])
				}
			}
		}

		// Get the virtual IP from inet line after carp:
		if currentCarp != nil && strings.HasPrefix(line, "inet ") {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				currentCarp.VirtualIP = parts[1]
			}
		}
	}

	if currentCarp != nil {
		carp = append(carp, *currentCarp)
	}

	return carp, nil
}

// GetCARPStatus returns CARP status for a specific interface
func (h *HAService) GetCARPStatus(ctx context.Context, iface string) (*models.CARPInterface, error) {
	output, err := exec.CommandContext(ctx, "ifconfig", iface).Output()
	if err != nil {
		return nil, fmt.Errorf("interface not found: %s", iface)
	}

	carp := &models.CARPInterface{
		Interface: iface,
	}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "carp:") {
			parts := strings.Fields(line)
			for i, p := range parts {
				if p == "MASTER" || p == "BACKUP" || p == "INIT" {
					carp.State = p
				}
				if p == "vhid" && i+1 < len(parts) {
					carp.VHID, _ = strconv.Atoi(parts[i+1])
				}
				if p == "advbase" && i+1 < len(parts) {
					carp.AdvBase, _ = strconv.Atoi(parts[i+1])
				}
				if p == "advskew" && i+1 < len(parts) {
					carp.AdvSkew, _ = strconv.Atoi(parts[i+1])
				}
			}
		}

		if strings.HasPrefix(line, "inet ") && carp.VirtualIP == "" {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				carp.VirtualIP = parts[1]
			}
		}
	}

	if carp.VHID == 0 {
		return nil, fmt.Errorf("no CARP configuration on interface: %s", iface)
	}

	return carp, nil
}

// ConfigureCARPInterface configures CARP on an interface
func (h *HAService) ConfigureCARPInterface(ctx context.Context, iface string, config *models.CARPConfig) error {
	args := []string{iface}

	if config.VHID > 0 {
		args = append(args, "vhid", strconv.Itoa(config.VHID))
	}

	if config.AdvBase > 0 {
		args = append(args, "advbase", strconv.Itoa(config.AdvBase))
	}

	if config.AdvSkew >= 0 {
		args = append(args, "advskew", strconv.Itoa(config.AdvSkew))
	}

	if config.Pass != "" {
		args = append(args, "pass", config.Pass)
	}

	if config.VirtualIP != "" {
		args = append(args, "inet", config.VirtualIP)
	}

	cmd := exec.CommandContext(ctx, "ifconfig", args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to configure CARP: %s", string(output))
	}

	return nil
}

// SetCARPPreempt enables/disables CARP preemption
func (h *HAService) SetCARPPreempt(ctx context.Context, enable bool) error {
	var value string
	if enable {
		value = "1"
	} else {
		value = "0"
	}

	cmd := exec.CommandContext(ctx, "sysctl", "net.inet.carp.preempt="+value)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to set CARP preempt: %s", string(output))
	}
	return nil
}

// SetCARPDemotion adjusts CARP demotion counter
func (h *HAService) SetCARPDemotion(ctx context.Context, group string, value int) error {
	// Set demotion for a carp group
	cmd := exec.CommandContext(ctx, "ifconfig", "-g", group, "carpdemote", strconv.Itoa(value))
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to set CARP demotion: %s", string(output))
	}
	return nil
}

// GetPFSyncInfo returns pfsync status
func (h *HAService) GetPFSyncInfo(ctx context.Context) (*models.PFSyncInfo, error) {
	output, err := exec.CommandContext(ctx, "ifconfig", "pfsync0").Output()
	if err != nil {
		return nil, fmt.Errorf("pfsync0 interface not found")
	}

	info := &models.PFSyncInfo{
		Interface: "pfsync0",
	}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "pfsync:") {
			// Parse: pfsync: syncdev: em1 syncpeer: 10.0.0.2 maxupd: 128 defer: on
			parts := strings.Fields(line)
			for i, p := range parts {
				if p == "syncdev:" && i+1 < len(parts) {
					info.SyncIf = parts[i+1]
				}
				if p == "syncpeer:" && i+1 < len(parts) {
					info.SyncPeer = parts[i+1]
				}
				if p == "maxupd:" && i+1 < len(parts) {
					info.MaxUpd, _ = strconv.Atoi(parts[i+1])
				}
				if p == "defer:" && i+1 < len(parts) {
					info.Defer = parts[i+1] == "on"
				}
			}
		}
	}

	// Get statistics
	stats, _ := h.getPFSyncStats(ctx)
	info.Stats = stats

	return info, nil
}

// ConfigurePFSync configures the pfsync interface
func (h *HAService) ConfigurePFSync(ctx context.Context, syncIf, syncPeer string, maxupd int, deferSync bool) error {
	args := []string{"pfsync0"}

	if syncIf != "" {
		args = append(args, "syncdev", syncIf)
	}

	if syncPeer != "" {
		args = append(args, "syncpeer", syncPeer)
	}

	if maxupd > 0 {
		args = append(args, "maxupd", strconv.Itoa(maxupd))
	}

	if deferSync {
		args = append(args, "defer")
	} else {
		args = append(args, "-defer")
	}

	cmd := exec.CommandContext(ctx, "ifconfig", args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to configure pfsync: %s", string(output))
	}

	// Bring up the interface
	cmd = exec.CommandContext(ctx, "ifconfig", "pfsync0", "up")
	cmd.Run()

	return nil
}

// getPFSyncStats returns pfsync statistics
func (h *HAService) getPFSyncStats(ctx context.Context) (*models.PFSyncStats, error) {
	output, err := exec.CommandContext(ctx, "netstat", "-I", "pfsync0", "-s").Output()
	if err != nil {
		return nil, err
	}

	stats := &models.PFSyncStats{}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)

		re := regexp.MustCompile(`(\d+)\s+(\w+)`)
		matches := re.FindStringSubmatch(line)
		if len(matches) < 3 {
			continue
		}

		val, _ := strconv.ParseUint(matches[1], 10, 64)
		name := strings.ToLower(matches[2])

		if strings.Contains(name, "insert") {
			if strings.Contains(line, "sent") {
				stats.SentInserts = val
			} else if strings.Contains(line, "received") {
				stats.ReceivedInserts = val
			}
		} else if strings.Contains(name, "update") {
			if strings.Contains(line, "sent") {
				stats.SentUpdates = val
			} else if strings.Contains(line, "received") {
				stats.ReceivedUpdates = val
			}
		} else if strings.Contains(name, "delete") {
			if strings.Contains(line, "sent") {
				stats.SentDeletes = val
			} else if strings.Contains(line, "received") {
				stats.ReceivedDeletes = val
			}
		} else if strings.Contains(name, "error") {
			stats.Errors = val
		}
	}

	return stats, nil
}
