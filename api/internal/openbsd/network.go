// Package openbsd provides native OpenBSD network interfaces
package openbsd

import (
	"bufio"
	"net"
	"os/exec"
	"strconv"
	"strings"
)

// Interface represents a network interface with structured data
type Interface struct {
	Name         string   `json:"name"`
	Index        int      `json:"index"`
	MTU          int      `json:"mtu"`
	Flags        string   `json:"flags"`
	HardwareAddr string   `json:"hardware_addr,omitempty"`
	IPv4Addrs    []string `json:"ipv4_addrs,omitempty"`
	IPv6Addrs    []string `json:"ipv6_addrs,omitempty"`
	Up           bool     `json:"up"`
	Loopback     bool     `json:"loopback"`
	Multicast    bool     `json:"multicast"`
	Broadcast    bool     `json:"broadcast"`
	PointToPoint bool     `json:"point_to_point"`
}

// GetInterfaces returns all network interfaces using Go's native net package
// This is much cleaner than parsing ifconfig output
func GetInterfaces() ([]Interface, error) {
	ifaces, err := net.Interfaces()
	if err != nil {
		return nil, err
	}

	result := make([]Interface, 0, len(ifaces))
	for _, iface := range ifaces {
		ni := Interface{
			Name:         iface.Name,
			Index:        iface.Index,
			MTU:          iface.MTU,
			Flags:        iface.Flags.String(),
			Up:           iface.Flags&net.FlagUp != 0,
			Loopback:     iface.Flags&net.FlagLoopback != 0,
			Multicast:    iface.Flags&net.FlagMulticast != 0,
			Broadcast:    iface.Flags&net.FlagBroadcast != 0,
			PointToPoint: iface.Flags&net.FlagPointToPoint != 0,
		}

		if len(iface.HardwareAddr) > 0 {
			ni.HardwareAddr = iface.HardwareAddr.String()
		}

		// Get addresses
		addrs, err := iface.Addrs()
		if err == nil {
			for _, addr := range addrs {
				switch v := addr.(type) {
				case *net.IPNet:
					if v.IP.To4() != nil {
						ni.IPv4Addrs = append(ni.IPv4Addrs, v.String())
					} else {
						ni.IPv6Addrs = append(ni.IPv6Addrs, v.String())
					}
				case *net.IPAddr:
					if v.IP.To4() != nil {
						ni.IPv4Addrs = append(ni.IPv4Addrs, v.String())
					} else {
						ni.IPv6Addrs = append(ni.IPv6Addrs, v.String())
					}
				}
			}
		}

		result = append(result, ni)
	}

	return result, nil
}

// GetInterface returns a specific interface by name
func GetInterface(name string) (*Interface, error) {
	iface, err := net.InterfaceByName(name)
	if err != nil {
		return nil, err
	}

	ni := &Interface{
		Name:         iface.Name,
		Index:        iface.Index,
		MTU:          iface.MTU,
		Flags:        iface.Flags.String(),
		Up:           iface.Flags&net.FlagUp != 0,
		Loopback:     iface.Flags&net.FlagLoopback != 0,
		Multicast:    iface.Flags&net.FlagMulticast != 0,
		Broadcast:    iface.Flags&net.FlagBroadcast != 0,
		PointToPoint: iface.Flags&net.FlagPointToPoint != 0,
	}

	if len(iface.HardwareAddr) > 0 {
		ni.HardwareAddr = iface.HardwareAddr.String()
	}

	addrs, err := iface.Addrs()
	if err == nil {
		for _, addr := range addrs {
			switch v := addr.(type) {
			case *net.IPNet:
				if v.IP.To4() != nil {
					ni.IPv4Addrs = append(ni.IPv4Addrs, v.String())
				} else {
					ni.IPv6Addrs = append(ni.IPv6Addrs, v.String())
				}
			case *net.IPAddr:
				if v.IP.To4() != nil {
					ni.IPv4Addrs = append(ni.IPv4Addrs, v.String())
				} else {
					ni.IPv6Addrs = append(ni.IPv6Addrs, v.String())
				}
			}
		}
	}

	return ni, nil
}

// InterfaceStats contains interface statistics
type InterfaceStats struct {
	Name       string `json:"name"`
	BytesIn    uint64 `json:"bytes_in"`
	BytesOut   uint64 `json:"bytes_out"`
	PacketsIn  uint64 `json:"packets_in"`
	PacketsOut uint64 `json:"packets_out"`
	ErrorsIn   uint64 `json:"errors_in"`
	ErrorsOut  uint64 `json:"errors_out"`
	DropsIn    uint64 `json:"drops_in"`
	DropsOut   uint64 `json:"drops_out"`
}

// GetInterfaceStats returns statistics for a specific interface
// Uses netstat -I <interface> -b for byte/packet counters
func GetInterfaceStats(name string) (*InterfaceStats, error) {
	output, err := exec.Command("netstat", "-I", name, "-b", "-n").Output()
	if err != nil {
		return nil, err
	}

	stats := &InterfaceStats{Name: name}

	lines := strings.Split(string(output), "\n")
	if len(lines) < 2 {
		return stats, nil
	}

	// Parse header to find column positions
	// Format: Name Mtu Network Address Ipkts Ierrs Ibytes Opkts Oerrs Obytes Coll Drop
	fields := strings.Fields(lines[1])
	if len(fields) >= 10 {
		stats.PacketsIn, _ = strconv.ParseUint(fields[4], 10, 64)
		stats.ErrorsIn, _ = strconv.ParseUint(fields[5], 10, 64)
		stats.BytesIn, _ = strconv.ParseUint(fields[6], 10, 64)
		stats.PacketsOut, _ = strconv.ParseUint(fields[7], 10, 64)
		stats.ErrorsOut, _ = strconv.ParseUint(fields[8], 10, 64)
		stats.BytesOut, _ = strconv.ParseUint(fields[9], 10, 64)
		if len(fields) >= 12 {
			stats.DropsIn, _ = strconv.ParseUint(fields[11], 10, 64)
		}
	}

	return stats, nil
}

// GetAllInterfaceStats returns statistics for all interfaces
func GetAllInterfaceStats() ([]InterfaceStats, error) {
	output, err := exec.Command("netstat", "-i", "-b", "-n").Output()
	if err != nil {
		return nil, err
	}

	var stats []InterfaceStats
	scanner := bufio.NewScanner(strings.NewReader(string(output)))

	// Skip header
	if scanner.Scan() {
		// header line
	}

	for scanner.Scan() {
		line := scanner.Text()
		fields := strings.Fields(line)

		if len(fields) < 10 {
			continue
		}

		// Skip loopback and system interfaces for throughput
		name := fields[0]
		if strings.HasPrefix(name, "lo") ||
			strings.HasPrefix(name, "pflog") ||
			strings.HasPrefix(name, "enc") {
			continue
		}

		s := InterfaceStats{Name: name}
		s.PacketsIn, _ = strconv.ParseUint(fields[4], 10, 64)
		s.ErrorsIn, _ = strconv.ParseUint(fields[5], 10, 64)
		s.BytesIn, _ = strconv.ParseUint(fields[6], 10, 64)
		s.PacketsOut, _ = strconv.ParseUint(fields[7], 10, 64)
		s.ErrorsOut, _ = strconv.ParseUint(fields[8], 10, 64)
		s.BytesOut, _ = strconv.ParseUint(fields[9], 10, 64)
		if len(fields) >= 12 {
			s.DropsIn, _ = strconv.ParseUint(fields[11], 10, 64)
		}

		stats = append(stats, s)
	}

	return stats, nil
}

// GetWireGuardInterfaces returns only WireGuard interfaces
func GetWireGuardInterfaces() ([]Interface, error) {
	all, err := GetInterfaces()
	if err != nil {
		return nil, err
	}

	var wg []Interface
	for _, iface := range all {
		if len(iface.Name) >= 2 && iface.Name[:2] == "wg" {
			wg = append(wg, iface)
		}
	}
	return wg, nil
}

// GetVLANInterfaces returns only VLAN interfaces
func GetVLANInterfaces() ([]Interface, error) {
	all, err := GetInterfaces()
	if err != nil {
		return nil, err
	}

	var vlans []Interface
	for _, iface := range all {
		if len(iface.Name) >= 4 && iface.Name[:4] == "vlan" {
			vlans = append(vlans, iface)
		}
	}
	return vlans, nil
}

// GetBridgeInterfaces returns only bridge interfaces
func GetBridgeInterfaces() ([]Interface, error) {
	all, err := GetInterfaces()
	if err != nil {
		return nil, err
	}

	var bridges []Interface
	for _, iface := range all {
		if len(iface.Name) >= 6 && iface.Name[:6] == "bridge" {
			bridges = append(bridges, iface)
		}
	}
	return bridges, nil
}

// GetCARPInterfaces returns only CARP interfaces
func GetCARPInterfaces() ([]Interface, error) {
	all, err := GetInterfaces()
	if err != nil {
		return nil, err
	}

	var carp []Interface
	for _, iface := range all {
		if len(iface.Name) >= 4 && iface.Name[:4] == "carp" {
			carp = append(carp, iface)
		}
	}
	return carp, nil
}

// WireGuardPeerStats contains transfer statistics for a WireGuard peer
type WireGuardPeerStats struct {
	Interface     string   `json:"interface"`
	PublicKey     string   `json:"public_key"`
	Description   string   `json:"description,omitempty"` // Peer description (OpenBSD 7.4+)
	Endpoint      string   `json:"endpoint,omitempty"`
	AllowedIPs    []string `json:"allowed_ips,omitempty"`
	BytesReceived uint64   `json:"bytes_received"`
	BytesSent     uint64   `json:"bytes_sent"`
	LastHandshake string   `json:"last_handshake,omitempty"`
}

// WireGuardInterfaceStats contains stats for a WireGuard interface and its peers
type WireGuardInterfaceStats struct {
	Name       string               `json:"name"`
	PublicKey  string               `json:"public_key,omitempty"`
	ListenPort int                  `json:"listen_port,omitempty"`
	Peers      []WireGuardPeerStats `json:"peers"`
}

// GetWireGuardStats returns transfer statistics for all WireGuard peers
func GetWireGuardStats() ([]WireGuardInterfaceStats, error) {
	// Find all wg interfaces
	ifaces, err := GetWireGuardInterfaces()
	if err != nil {
		return nil, err
	}

	var results []WireGuardInterfaceStats

	for _, iface := range ifaces {
		stats, err := GetWireGuardInterfaceStats(iface.Name)
		if err != nil {
			continue
		}
		results = append(results, *stats)
	}

	return results, nil
}

// GetWireGuardInterfaceStats returns stats for a specific WireGuard interface
func GetWireGuardInterfaceStats(name string) (*WireGuardInterfaceStats, error) {
	output, err := exec.Command("ifconfig", name).Output()
	if err != nil {
		return nil, err
	}

	stats := &WireGuardInterfaceStats{
		Name: name,
	}

	lines := strings.Split(string(output), "\n")
	var currentPeer *WireGuardPeerStats

	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "wgpubkey") {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				stats.PublicKey = parts[1]
			}
		} else if strings.HasPrefix(line, "wgport") {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				stats.ListenPort, _ = strconv.Atoi(parts[1])
			}
		} else if strings.HasPrefix(line, "wgpeer") {
			// Save previous peer
			if currentPeer != nil {
				stats.Peers = append(stats.Peers, *currentPeer)
			}

			currentPeer = &WireGuardPeerStats{
				Interface: name,
			}
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				currentPeer.PublicKey = parts[1]
			}
		} else if currentPeer != nil {
			if strings.HasPrefix(line, "wgpeerdesc") {
				// Parse peer description (OpenBSD 7.4+)
				// Format: wgpeerdesc "description string"
				parts := strings.SplitN(line, " ", 2)
				if len(parts) >= 2 {
					desc := parts[1]
					// Remove surrounding quotes if present
					if len(desc) >= 2 && desc[0] == '"' && desc[len(desc)-1] == '"' {
						desc = desc[1 : len(desc)-1]
					}
					currentPeer.Description = desc
				}
			} else if strings.HasPrefix(line, "wgendpoint") {
				parts := strings.Fields(line)
				if len(parts) >= 2 {
					currentPeer.Endpoint = parts[1]
				}
			} else if strings.HasPrefix(line, "wgaip") {
				parts := strings.Fields(line)
				if len(parts) >= 2 {
					currentPeer.AllowedIPs = append(currentPeer.AllowedIPs, parts[1])
				}
			} else if strings.HasPrefix(line, "tx:") {
				// Parse "tx: 12345, rx: 67890"
				parts := strings.Split(line, ",")
				for _, part := range parts {
					part = strings.TrimSpace(part)
					if strings.HasPrefix(part, "tx:") {
						val := strings.TrimPrefix(part, "tx:")
						val = strings.TrimSpace(val)
						currentPeer.BytesSent, _ = strconv.ParseUint(val, 10, 64)
					} else if strings.HasPrefix(part, "rx:") {
						val := strings.TrimPrefix(part, "rx:")
						val = strings.TrimSpace(val)
						currentPeer.BytesReceived, _ = strconv.ParseUint(val, 10, 64)
					}
				}
			} else if strings.HasPrefix(line, "last handshake:") {
				currentPeer.LastHandshake = strings.TrimPrefix(line, "last handshake:")
				currentPeer.LastHandshake = strings.TrimSpace(currentPeer.LastHandshake)
			}
		}
	}

	// Save last peer
	if currentPeer != nil {
		stats.Peers = append(stats.Peers, *currentPeer)
	}

	return stats, nil
}
