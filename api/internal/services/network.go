package services

import (
	"bufio"
	"context"
	"fmt"
	"os/exec"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/models"
)

// NetworkService handles network-related operations
type NetworkService struct {
	nshPath string
}

// NewNetworkService creates a new NetworkService
func NewNetworkService(nshPath string) *NetworkService {
	return &NetworkService{nshPath: nshPath}
}

// GetInterfaces returns all network interfaces
func (n *NetworkService) GetInterfaces(ctx context.Context) ([]models.Interface, error) {
	output, err := exec.CommandContext(ctx, "ifconfig", "-a").Output()
	if err != nil {
		return nil, err
	}

	return parseIfconfig(string(output)), nil
}

// GetInterface returns a specific interface
func (n *NetworkService) GetInterface(ctx context.Context, name string) (*models.Interface, error) {
	output, err := exec.CommandContext(ctx, "ifconfig", name).Output()
	if err != nil {
		return nil, fmt.Errorf("interface not found: %s", name)
	}

	ifaces := parseIfconfig(string(output))
	if len(ifaces) == 0 {
		return nil, fmt.Errorf("interface not found: %s", name)
	}

	// Get statistics
	ifaces[0].Statistics = n.getInterfaceStats(ctx, name)

	return &ifaces[0], nil
}

// ConfigureInterface configures an interface
func (n *NetworkService) ConfigureInterface(ctx context.Context, name string, config *models.InterfaceConfig) error {
	// Use nsh to configure interface
	var commands []string

	if config.Description != "" {
		commands = append(commands, fmt.Sprintf("interface %s\ndescription %s", name, config.Description))
	}

	if config.MTU > 0 {
		commands = append(commands, fmt.Sprintf("interface %s\nmtu %d", name, config.MTU))
	}

	for _, ip := range config.IPv4 {
		commands = append(commands, fmt.Sprintf("interface %s\nip %s", name, ip))
	}

	for _, ip := range config.IPv6 {
		commands = append(commands, fmt.Sprintf("interface %s\nipv6 %s", name, ip))
	}

	if config.AdminUp != nil {
		if *config.AdminUp {
			commands = append(commands, fmt.Sprintf("interface %s\nno shutdown", name))
		} else {
			commands = append(commands, fmt.Sprintf("interface %s\nshutdown", name))
		}
	}

	for _, cmd := range commands {
		fullCmd := exec.CommandContext(ctx, n.nshPath, "-c", cmd)
		if _, err := fullCmd.CombinedOutput(); err != nil {
			return fmt.Errorf("failed to configure interface: %v", err)
		}
	}

	return nil
}

// GetRoutes returns the routing table
func (n *NetworkService) GetRoutes(ctx context.Context) ([]models.Route, error) {
	output, err := exec.CommandContext(ctx, "netstat", "-rn").Output()
	if err != nil {
		return nil, err
	}

	return parseRoutes(string(output)), nil
}

// AddRoute adds a route
func (n *NetworkService) AddRoute(ctx context.Context, route *models.RouteConfig) error {
	args := []string{"add"}

	if route.Gateway != "" {
		args = append(args, route.Destination, route.Gateway)
	} else if route.Interface != "" {
		args = append(args, "-interface", route.Destination, route.Interface)
	} else {
		return fmt.Errorf("gateway or interface required")
	}

	if route.Label != "" {
		args = append(args, "-label", route.Label)
	}

	if route.Priority > 0 {
		args = append(args, "-priority", strconv.Itoa(route.Priority))
	}

	cmd := exec.CommandContext(ctx, "route", args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to add route: %s", string(output))
	}

	return nil
}

// DeleteRoute deletes a route
func (n *NetworkService) DeleteRoute(ctx context.Context, destination string) error {
	cmd := exec.CommandContext(ctx, "route", "delete", destination)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to delete route: %s", string(output))
	}
	return nil
}

// GetArpTable returns the ARP table
func (n *NetworkService) GetArpTable(ctx context.Context) ([]models.ARPEntry, error) {
	output, err := exec.CommandContext(ctx, "arp", "-an").Output()
	if err != nil {
		return nil, err
	}

	return parseArpTable(string(output)), nil
}

// DeleteArpEntry deletes an ARP entry
func (n *NetworkService) DeleteArpEntry(ctx context.Context, ip string) error {
	cmd := exec.CommandContext(ctx, "arp", "-d", ip)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to delete ARP entry: %s", string(output))
	}
	return nil
}

// FlushArpTable flushes the ARP table
func (n *NetworkService) FlushArpTable(ctx context.Context) error {
	cmd := exec.CommandContext(ctx, "arp", "-da")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to flush ARP table: %s", string(output))
	}
	return nil
}

// getInterfaceStats returns interface statistics
func (n *NetworkService) getInterfaceStats(ctx context.Context, name string) *models.InterfaceStats {
	output, err := exec.CommandContext(ctx, "netstat", "-I", name, "-b").Output()
	if err != nil {
		return nil
	}

	lines := strings.Split(string(output), "\n")
	if len(lines) < 2 {
		return nil
	}

	// Parse the second line (first is header)
	fields := strings.Fields(lines[1])
	if len(fields) < 9 {
		return nil
	}

	stats := &models.InterfaceStats{}
	stats.RxPackets, _ = strconv.ParseUint(fields[4], 10, 64)
	stats.RxErrors, _ = strconv.ParseUint(fields[5], 10, 64)
	stats.RxBytes, _ = strconv.ParseUint(fields[6], 10, 64)
	stats.TxPackets, _ = strconv.ParseUint(fields[7], 10, 64)
	stats.TxErrors, _ = strconv.ParseUint(fields[8], 10, 64)
	if len(fields) > 9 {
		stats.TxBytes, _ = strconv.ParseUint(fields[9], 10, 64)
	}
	if len(fields) > 10 {
		stats.Collisions, _ = strconv.ParseUint(fields[10], 10, 64)
	}

	return stats
}

// Helper functions

func parseIfconfig(output string) []models.Interface {
	var interfaces []models.Interface
	var current *models.Interface

	scanner := bufio.NewScanner(strings.NewReader(output))
	for scanner.Scan() {
		line := scanner.Text()

		// New interface starts without leading whitespace
		if len(line) > 0 && line[0] != ' ' && line[0] != '\t' {
			if current != nil {
				interfaces = append(interfaces, *current)
			}

			// Parse: em0: flags=8843<UP,BROADCAST,RUNNING,SIMPLEX,MULTICAST> mtu 1500
			parts := strings.SplitN(line, ":", 2)
			if len(parts) < 2 {
				continue
			}

			current = &models.Interface{
				Name: parts[0],
			}

			// Parse flags and MTU
			rest := parts[1]
			if flagsMatch := regexp.MustCompile(`flags=\d+<([^>]*)>`).FindStringSubmatch(rest); len(flagsMatch) > 1 {
				current.Flags = strings.Split(flagsMatch[1], ",")
				if containsFlag(current.Flags, "UP") {
					current.AdminStatus = "up"
					current.Status = "up"
				} else {
					current.AdminStatus = "down"
					current.Status = "down"
				}
				if containsFlag(current.Flags, "RUNNING") {
					current.Status = "up"
				}
			}

			if mtuMatch := regexp.MustCompile(`mtu (\d+)`).FindStringSubmatch(rest); len(mtuMatch) > 1 {
				current.MTU, _ = strconv.Atoi(mtuMatch[1])
			}

			continue
		}

		if current == nil {
			continue
		}

		line = strings.TrimSpace(line)

		// Parse address lines
		if strings.HasPrefix(line, "inet ") {
			ip := parseInetLine(line)
			if ip != nil {
				current.IPv4 = append(current.IPv4, *ip)
			}
		} else if strings.HasPrefix(line, "inet6 ") {
			ip := parseInet6Line(line)
			if ip != nil {
				current.IPv6 = append(current.IPv6, *ip)
			}
		} else if strings.HasPrefix(line, "lladdr ") || strings.HasPrefix(line, "ether ") {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				current.MAC = parts[1]
			}
		} else if strings.HasPrefix(line, "description:") {
			current.Description = strings.TrimPrefix(line, "description:")
			current.Description = strings.TrimSpace(current.Description)
		} else if strings.HasPrefix(line, "groups:") {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				current.Group = parts[1]
			}
		} else if strings.HasPrefix(line, "media:") {
			// Parse: media: Ethernet autoselect (1000baseT full-duplex)
			if speedMatch := regexp.MustCompile(`\((\d+\w+)\s+(\w+-duplex)\)`).FindStringSubmatch(line); len(speedMatch) > 2 {
				current.Speed = speedMatch[1]
				current.Duplex = speedMatch[2]
			}
		} else if strings.HasPrefix(line, "vlan:") {
			// Parse: vlan: 100 parent interface: em0
			if vlanMatch := regexp.MustCompile(`vlan:\s+(\d+)`).FindStringSubmatch(line); len(vlanMatch) > 1 {
				current.VlanID, _ = strconv.Atoi(vlanMatch[1])
			}
			if parentMatch := regexp.MustCompile(`parent interface:\s+(\w+)`).FindStringSubmatch(line); len(parentMatch) > 1 {
				current.Parent = parentMatch[1]
			}
		}
	}

	if current != nil {
		interfaces = append(interfaces, *current)
	}

	return interfaces
}

func parseInetLine(line string) *models.IPAddr {
	// Parse: inet 192.168.1.1 netmask 0xffffff00 broadcast 192.168.1.255
	parts := strings.Fields(line)
	if len(parts) < 4 {
		return nil
	}

	addr := parts[1]
	var prefix int

	for i, p := range parts {
		if p == "netmask" && i+1 < len(parts) {
			prefix = netmaskToPrefix(parts[i+1])
			break
		}
	}

	return &models.IPAddr{
		Address: addr,
		Prefix:  prefix,
		CIDR:    fmt.Sprintf("%s/%d", addr, prefix),
	}
}

func parseInet6Line(line string) *models.IPAddr {
	// Parse: inet6 fe80::1%em0 prefixlen 64 scopeid 0x1
	parts := strings.Fields(line)
	if len(parts) < 4 {
		return nil
	}

	addr := parts[1]
	// Remove scope ID
	if idx := strings.Index(addr, "%"); idx > 0 {
		addr = addr[:idx]
	}

	var prefix int
	for i, p := range parts {
		if p == "prefixlen" && i+1 < len(parts) {
			prefix, _ = strconv.Atoi(parts[i+1])
			break
		}
	}

	return &models.IPAddr{
		Address: addr,
		Prefix:  prefix,
		CIDR:    fmt.Sprintf("%s/%d", addr, prefix),
	}
}

func netmaskToPrefix(mask string) int {
	// Convert hex netmask to prefix length
	if strings.HasPrefix(mask, "0x") {
		val, err := strconv.ParseUint(mask[2:], 16, 32)
		if err != nil {
			return 0
		}
		count := 0
		for val > 0 {
			count += int(val & 1)
			val >>= 1
		}
		return count
	}
	return 0
}

func containsFlag(flags []string, flag string) bool {
	for _, f := range flags {
		if f == flag {
			return true
		}
	}
	return false
}

func parseRoutes(output string) []models.Route {
	var routes []models.Route

	lines := strings.Split(output, "\n")
	inRoutes := false

	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "Destination") || strings.HasPrefix(line, "Internet:") || strings.HasPrefix(line, "Internet6:") {
			inRoutes = true
			continue
		}

		if !inRoutes || line == "" {
			continue
		}

		if strings.HasPrefix(line, "Routing tables") || strings.HasPrefix(line, "Encap:") {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 3 {
			continue
		}

		route := models.Route{
			Destination: fields[0],
			Gateway:     fields[1],
			Flags:       fields[2],
		}

		if len(fields) > 3 {
			route.Interface = fields[len(fields)-1]
		}

		// Parse priority if present
		for i, f := range fields {
			if f == "prio" && i+1 < len(fields) {
				route.Priority, _ = strconv.Atoi(fields[i+1])
			}
		}

		routes = append(routes, route)
	}

	return routes
}

func parseArpTable(output string) []models.ARPEntry {
	var entries []models.ARPEntry

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" || !strings.HasPrefix(line, "?") {
			continue
		}

		// Format: ? (192.168.1.1) at 00:11:22:33:44:55 on em0 permanent [ethernet]
		fields := strings.Fields(line)
		if len(fields) < 6 {
			continue
		}

		ip := strings.Trim(fields[1], "()")
		mac := fields[3]
		iface := ""
		flags := ""

		for i, f := range fields {
			if f == "on" && i+1 < len(fields) {
				iface = fields[i+1]
			}
		}

		// Collect flags
		var flagList []string
		for _, f := range fields[5:] {
			if f != "on" && f != iface {
				flagList = append(flagList, f)
			}
		}
		flags = strings.Join(flagList, " ")

		entry := models.ARPEntry{
			IPAddress:  ip,
			MACAddress: mac,
			Interface:  iface,
			Flags:      flags,
		}
		entries = append(entries, entry)
	}

	return entries
}

// CreateVlan creates a VLAN interface
func (n *NetworkService) CreateVlan(ctx context.Context, vlanID int, parent string) error {
	name := fmt.Sprintf("vlan%d", vlanID)

	cmd := exec.CommandContext(ctx, "ifconfig", name, "create")
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("failed to create vlan: %s", string(output))
	}

	cmd = exec.CommandContext(ctx, "ifconfig", name, "vlan", strconv.Itoa(vlanID), "vlandev", parent)
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("failed to configure vlan: %s", string(output))
	}

	return nil
}

// DeleteVlan deletes a VLAN interface
func (n *NetworkService) DeleteVlan(ctx context.Context, name string) error {
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	cmd := exec.CommandContext(ctx, "ifconfig", name, "destroy")
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("failed to delete vlan: %s", string(output))
	}
	return nil
}
