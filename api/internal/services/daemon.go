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

// DaemonService handles daemon/service management
type DaemonService struct {
	nshPath string
}

// NewDaemonService creates a new DaemonService
func NewDaemonService(nshPath string) *DaemonService {
	return &DaemonService{nshPath: nshPath}
}

// SupportedDaemons lists all daemons that NSWall can manage
var SupportedDaemons = []string{
	"bgpd",
	"dhcpd",
	"dhcpleased",
	"dhcrelay",
	"dvmrpd",
	"eigrpd",
	"ftp-proxy",
	"ifstated",
	"iked",
	"inetd",
	"ldapd",
	"ldpd",
	"npppd",
	"ntpd",
	"ospfd",
	"ospf6d",
	"rad",
	"relayd",
	"resolvd",
	"ripd",
	"sasyncd",
	"slaacd",
	"smtpd",
	"snmpd",
	"sshd",
	"tftpd",
	"tftp-proxy",
	"unbound",
}

// GetServices returns status of all known services
func (d *DaemonService) GetServices(ctx context.Context) ([]models.Service, error) {
	var services []models.Service

	for _, name := range SupportedDaemons {
		svc, err := d.GetService(ctx, name)
		if err != nil {
			// Service might not be installed, skip it
			continue
		}
		services = append(services, *svc)
	}

	return services, nil
}

// GetService returns status of a specific service
func (d *DaemonService) GetService(ctx context.Context, name string) (*models.Service, error) {
	svc := &models.Service{
		Name: name,
	}

	// Check if enabled
	enabled, _ := d.isEnabled(ctx, name)
	svc.Enabled = enabled

	// Check if running
	running, pid := d.isRunning(ctx, name)
	if running {
		svc.Status = "running"
		svc.PID = pid
	} else {
		svc.Status = "stopped"
	}

	// Get description
	svc.Description = getServiceDescription(name)

	return svc, nil
}

// StartService starts a service
func (d *DaemonService) StartService(ctx context.Context, name string) error {
	// Validate service name
	if !isValidDaemon(name) {
		return fmt.Errorf("unknown service: %s", name)
	}

	cmd := exec.CommandContext(ctx, "rcctl", "start", name)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to start %s: %s", name, string(output))
	}
	return nil
}

// StopService stops a service
func (d *DaemonService) StopService(ctx context.Context, name string) error {
	if !isValidDaemon(name) {
		return fmt.Errorf("unknown service: %s", name)
	}

	cmd := exec.CommandContext(ctx, "rcctl", "stop", name)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to stop %s: %s", name, string(output))
	}
	return nil
}

// RestartService restarts a service
func (d *DaemonService) RestartService(ctx context.Context, name string) error {
	if !isValidDaemon(name) {
		return fmt.Errorf("unknown service: %s", name)
	}

	cmd := exec.CommandContext(ctx, "rcctl", "restart", name)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to restart %s: %s", name, string(output))
	}
	return nil
}

// ReloadService reloads a service configuration
func (d *DaemonService) ReloadService(ctx context.Context, name string) error {
	if !isValidDaemon(name) {
		return fmt.Errorf("unknown service: %s", name)
	}

	// First try rcctl reload
	cmd := exec.CommandContext(ctx, "rcctl", "reload", name)
	output, err := cmd.CombinedOutput()
	if err != nil {
		// Try HUP signal
		cmd = exec.CommandContext(ctx, "pkill", "-HUP", name)
		if err := cmd.Run(); err != nil {
			return fmt.Errorf("failed to reload %s: %s", name, string(output))
		}
	}
	return nil
}

// EnableService enables a service at boot
func (d *DaemonService) EnableService(ctx context.Context, name string) error {
	if !isValidDaemon(name) {
		return fmt.Errorf("unknown service: %s", name)
	}

	cmd := exec.CommandContext(ctx, "rcctl", "enable", name)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to enable %s: %s", name, string(output))
	}
	return nil
}

// DisableService disables a service at boot
func (d *DaemonService) DisableService(ctx context.Context, name string) error {
	if !isValidDaemon(name) {
		return fmt.Errorf("unknown service: %s", name)
	}

	cmd := exec.CommandContext(ctx, "rcctl", "disable", name)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to disable %s: %s", name, string(output))
	}
	return nil
}

// GetServiceConfig returns service configuration
func (d *DaemonService) GetServiceConfig(ctx context.Context, name string) (string, error) {
	if !isValidDaemon(name) {
		return "", fmt.Errorf("unknown service: %s", name)
	}

	cmd := exec.CommandContext(ctx, "rcctl", "get", name)
	output, err := cmd.Output()
	if err != nil {
		return "", fmt.Errorf("failed to get config for %s: %v", name, err)
	}
	return string(output), nil
}

// SetServiceFlags sets service flags
func (d *DaemonService) SetServiceFlags(ctx context.Context, name, flags string) error {
	if !isValidDaemon(name) {
		return fmt.Errorf("unknown service: %s", name)
	}

	cmd := exec.CommandContext(ctx, "rcctl", "set", name, "flags", flags)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to set flags for %s: %s", name, string(output))
	}
	return nil
}

// GetAllRcctl returns full rcctl ls output
func (d *DaemonService) GetAllRcctl(ctx context.Context) (string, error) {
	cmd := exec.CommandContext(ctx, "rcctl", "ls", "all")
	output, err := cmd.Output()
	if err != nil {
		return "", err
	}
	return string(output), nil
}

// Helper functions

func (d *DaemonService) isEnabled(ctx context.Context, name string) (bool, error) {
	cmd := exec.CommandContext(ctx, "rcctl", "get", name, "status")
	output, err := cmd.Output()
	if err != nil {
		return false, err
	}
	return strings.TrimSpace(string(output)) == "on", nil
}

func (d *DaemonService) isRunning(ctx context.Context, name string) (bool, int) {
	cmd := exec.CommandContext(ctx, "rcctl", "check", name)
	output, _ := cmd.Output()

	outputStr := string(output)
	if strings.Contains(outputStr, "(ok)") {
		// Parse PID if available
		re := regexp.MustCompile(`\(pid\s+(\d+)\)`)
		if matches := re.FindStringSubmatch(outputStr); len(matches) > 1 {
			pid, _ := strconv.Atoi(matches[1])
			return true, pid
		}
		return true, 0
	}
	return false, 0
}

func isValidDaemon(name string) bool {
	for _, d := range SupportedDaemons {
		if d == name {
			return true
		}
	}
	return false
}

func getServiceDescription(name string) string {
	descriptions := map[string]string{
		"bgpd":       "Border Gateway Protocol daemon",
		"dhcpd":      "DHCP server",
		"dhcpleased": "DHCP client daemon",
		"dhcrelay":   "DHCP relay agent",
		"dvmrpd":     "Distance Vector Multicast Routing Protocol daemon",
		"eigrpd":     "Enhanced Interior Gateway Routing Protocol daemon",
		"ftp-proxy":  "FTP proxy for pf",
		"ifstated":   "Interface state daemon",
		"iked":       "IKEv2/IPsec daemon",
		"inetd":      "Internet super-server",
		"ldapd":      "LDAP server",
		"ldpd":       "Label Distribution Protocol daemon",
		"npppd":      "PPP daemon",
		"ntpd":       "Network Time Protocol daemon",
		"ospfd":      "Open Shortest Path First daemon",
		"ospf6d":     "OSPF for IPv6 daemon",
		"rad":        "Router Advertisement daemon",
		"relayd":     "Relay daemon for load balancing",
		"resolvd":    "DNS resolver daemon",
		"ripd":       "Routing Information Protocol daemon",
		"sasyncd":    "IPsec SA synchronization daemon",
		"slaacd":     "SLAAC daemon",
		"smtpd":      "Simple Mail Transfer Protocol daemon",
		"snmpd":      "Simple Network Management Protocol daemon",
		"sshd":       "Secure Shell daemon",
		"tftpd":      "Trivial File Transfer Protocol server",
		"tftp-proxy": "TFTP proxy for pf",
		"unbound":    "DNS resolver and cache",
	}

	if desc, ok := descriptions[name]; ok {
		return desc
	}
	return name
}

// GetProcessList returns running processes
func (d *DaemonService) GetProcessList(ctx context.Context) (string, error) {
	cmd := exec.CommandContext(ctx, "ps", "aux")
	output, err := cmd.Output()
	if err != nil {
		return "", err
	}
	return string(output), nil
}

// GetNetstat returns network statistics
func (d *DaemonService) GetNetstat(ctx context.Context, options string) (string, error) {
	args := []string{"-an"}
	if options != "" {
		args = strings.Fields(options)
	}

	cmd := exec.CommandContext(ctx, "netstat", args...)
	output, err := cmd.Output()
	if err != nil {
		return "", err
	}
	return string(output), nil
}

// GetSockets returns listening sockets
func (d *DaemonService) GetSockets(ctx context.Context) ([]map[string]string, error) {
	output, err := exec.CommandContext(ctx, "netstat", "-an", "-p", "tcp").Output()
	if err != nil {
		return nil, err
	}

	var sockets []map[string]string

	scanner := bufio.NewScanner(strings.NewReader(string(output)))
	for scanner.Scan() {
		line := scanner.Text()
		if !strings.Contains(line, "LISTEN") {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 5 {
			continue
		}

		socket := map[string]string{
			"protocol": fields[0],
			"recv_q":   fields[1],
			"send_q":   fields[2],
			"local":    fields[3],
			"foreign":  fields[4],
			"state":    "LISTEN",
		}
		sockets = append(sockets, socket)
	}

	return sockets, nil
}
