// Package openbsd provides native routing table access
package openbsd

import (
	"fmt"
	"net"
	"syscall"
	"unsafe"

	"golang.org/x/sys/unix"
)

// Route represents a routing table entry
type Route struct {
	Destination string `json:"destination"`
	Gateway     string `json:"gateway"`
	Flags       string `json:"flags"`
	Interface   string `json:"interface,omitempty"`
	Priority    int    `json:"priority,omitempty"`
}

// RouteFlags for OpenBSD
const (
	RTF_UP        = 0x1
	RTF_GATEWAY   = 0x2
	RTF_HOST      = 0x4
	RTF_REJECT    = 0x8
	RTF_DYNAMIC   = 0x10
	RTF_MODIFIED  = 0x20
	RTF_STATIC    = 0x800
	RTF_BLACKHOLE = 0x1000
	RTF_LOCAL     = 0x200000
	RTF_BROADCAST = 0x400000
	RTF_MULTICAST = 0x800000
	RTF_CONNECTED = 0x800000
	RTF_CLONING   = 0x100
	RTF_LLINFO    = 0x400
)

// rt_msghdr structure for routing messages
type rtMsghdr struct {
	Msglen  uint16
	Version uint8
	Type    uint8
	Hdrlen  uint16
	Index   uint16
	Tableid uint16
	Priority uint8
	Mpls    uint8
	Addrs   int32
	Flags   int32
	Fmask   int32
	Pid     int32
	Seq     int32
	Errno   int32
	Inits   uint32
	Rmx     rtMetrics
}

type rtMetrics struct {
	Pksent    uint64
	Expire    int64
	Locks     uint32
	Mtu       uint32
	Refcnt    uint32
	Hopcount  uint32
	Recvpipe  uint32
	Sendpipe  uint32
	Ssthresh  uint32
	Rtt       uint32
	Rttvar    uint32
	Pad       uint32
}

// formatFlags converts route flags to a string
func formatRouteFlags(flags int32) string {
	var s string
	if flags&RTF_UP != 0 {
		s += "U"
	}
	if flags&RTF_GATEWAY != 0 {
		s += "G"
	}
	if flags&RTF_HOST != 0 {
		s += "H"
	}
	if flags&RTF_REJECT != 0 {
		s += "R"
	}
	if flags&RTF_DYNAMIC != 0 {
		s += "D"
	}
	if flags&RTF_MODIFIED != 0 {
		s += "M"
	}
	if flags&RTF_STATIC != 0 {
		s += "S"
	}
	if flags&RTF_BLACKHOLE != 0 {
		s += "B"
	}
	if flags&RTF_LOCAL != 0 {
		s += "l"
	}
	if flags&RTF_BROADCAST != 0 {
		s += "b"
	}
	if flags&RTF_CLONING != 0 {
		s += "C"
	}
	if flags&RTF_LLINFO != 0 {
		s += "L"
	}
	return s
}

// RTA constants for which addresses are present
const (
	RTA_DST     = 0x1
	RTA_GATEWAY = 0x2
	RTA_NETMASK = 0x4
	RTA_GENMASK = 0x8
	RTA_IFP     = 0x10
	RTA_IFA     = 0x20
	RTA_AUTHOR  = 0x40
	RTA_BRD     = 0x80
	RTA_SRC     = 0x100
	RTA_SRCMASK = 0x200
	RTA_LABEL   = 0x400
)

// GetRoutes retrieves the routing table using sysctl
func GetRoutes() ([]Route, error) {
	// Use NET_RT_DUMP to get all routes
	// sysctl: net.route.0.0.dump.0
	mib := []int32{
		syscall.CTL_NET,
		syscall.AF_ROUTE,
		0,
		0, // address family (0 = all)
		1, // NET_RT_DUMP
		0, // tableid
	}

	// First call to get size
	n := uintptr(0)
	_, _, errno := unix.Syscall6(
		unix.SYS___SYSCTL,
		uintptr(unsafe.Pointer(&mib[0])),
		uintptr(len(mib)),
		0,
		uintptr(unsafe.Pointer(&n)),
		0,
		0,
	)
	if errno != 0 {
		return nil, fmt.Errorf("sysctl size: %w", errno)
	}

	if n == 0 {
		return nil, nil
	}

	// Allocate buffer and get data
	buf := make([]byte, n)
	_, _, errno = unix.Syscall6(
		unix.SYS___SYSCTL,
		uintptr(unsafe.Pointer(&mib[0])),
		uintptr(len(mib)),
		uintptr(unsafe.Pointer(&buf[0])),
		uintptr(unsafe.Pointer(&n)),
		0,
		0,
	)
	if errno != 0 {
		return nil, fmt.Errorf("sysctl: %w", errno)
	}

	return parseRouteMessages(buf[:n])
}

// parseRouteMessages parses the routing messages from sysctl
func parseRouteMessages(buf []byte) ([]Route, error) {
	var routes []Route

	for len(buf) >= int(unsafe.Sizeof(rtMsghdr{})) {
		hdr := (*rtMsghdr)(unsafe.Pointer(&buf[0]))

		if hdr.Msglen < uint16(unsafe.Sizeof(rtMsghdr{})) {
			break
		}

		msgLen := int(hdr.Msglen)
		if msgLen > len(buf) {
			break
		}

		// Only process RTM_GET messages (type 4)
		if hdr.Type == 4 || hdr.Type == 1 || hdr.Type == 2 { // RTM_ADD, RTM_DELETE, RTM_GET
			route := parseRouteMessage(buf[:msgLen], hdr)
			if route != nil && route.Destination != "" {
				routes = append(routes, *route)
			}
		}

		buf = buf[msgLen:]
	}

	return routes, nil
}

// parseRouteMessage parses a single routing message
func parseRouteMessage(msg []byte, hdr *rtMsghdr) *Route {
	route := &Route{
		Flags:    formatRouteFlags(hdr.Flags),
		Priority: int(hdr.Priority),
	}

	// Skip the header
	offset := int(unsafe.Sizeof(rtMsghdr{}))
	if offset >= len(msg) {
		return nil
	}

	// Parse sockaddrs based on which are present
	addrs := int(hdr.Addrs)

	// RTA_DST
	if addrs&RTA_DST != 0 {
		sa, n := parseSockaddr(msg[offset:])
		if sa != nil {
			route.Destination = sa.String()
		}
		offset += n
	}

	// RTA_GATEWAY
	if addrs&RTA_GATEWAY != 0 && offset < len(msg) {
		sa, n := parseSockaddr(msg[offset:])
		if sa != nil {
			route.Gateway = sa.String()
		}
		offset += n
	}

	// RTA_NETMASK
	if addrs&RTA_NETMASK != 0 && offset < len(msg) {
		// Netmask modifies destination
		_, n := parseSockaddr(msg[offset:])
		offset += n
	}

	return route
}

// parseSockaddr parses a sockaddr from the message
func parseSockaddr(buf []byte) (net.IP, int) {
	if len(buf) < 4 {
		return nil, 0
	}

	// sockaddr length is first byte
	salen := int(buf[0])
	if salen == 0 {
		salen = 8 // Minimum size, rounded up
	}

	// Round up to alignment
	if salen%8 != 0 {
		salen = (salen + 7) &^ 7
	}

	if salen > len(buf) {
		return nil, salen
	}

	family := buf[1]

	switch family {
	case syscall.AF_INET:
		if len(buf) >= 8 {
			return net.IPv4(buf[4], buf[5], buf[6], buf[7]), salen
		}
	case syscall.AF_INET6:
		if len(buf) >= 24 {
			ip := make(net.IP, 16)
			copy(ip, buf[8:24])
			return ip, salen
		}
	case syscall.AF_LINK:
		// Link-level address, skip
		return nil, salen
	}

	return nil, salen
}

// GetDefaultGateway returns the default gateway
func GetDefaultGateway() (string, error) {
	routes, err := GetRoutes()
	if err != nil {
		return "", err
	}

	for _, r := range routes {
		if r.Destination == "0.0.0.0" || r.Destination == "default" || r.Destination == "::" {
			return r.Gateway, nil
		}
	}

	return "", fmt.Errorf("no default gateway found")
}

// GetDefaultGateway4 returns the IPv4 default gateway
func GetDefaultGateway4() (string, error) {
	routes, err := GetRoutes()
	if err != nil {
		return "", err
	}

	for _, r := range routes {
		if r.Destination == "0.0.0.0" || r.Destination == "default" {
			return r.Gateway, nil
		}
	}

	return "", fmt.Errorf("no IPv4 default gateway found")
}
