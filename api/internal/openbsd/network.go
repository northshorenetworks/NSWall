// Package openbsd provides native OpenBSD network interfaces
package openbsd

import (
	"net"
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
// Note: Getting stats requires reading from sysctl or /dev
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
