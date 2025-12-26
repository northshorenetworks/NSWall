// Package tests provides comprehensive BGP daemon validation tests
// Tests bgpd configuration syntax and bgpctl output parsing
package tests

import (
	"fmt"
	"os"
	"os/exec"
	"regexp"
	"runtime"
	"strconv"
	"strings"
	"testing"
)

// =============================================================================
// BGPD CONFIGURATION SYNTAX VALIDATION
// Uses bgpd -n to validate configuration syntax
// =============================================================================

func skipIfNotOpenBSD(t *testing.T) {
	if runtime.GOOS != "openbsd" {
		t.Skip("Skipping: requires OpenBSD")
	}
}

// validateBGPDConfig validates bgpd.conf syntax using bgpd -nf
func validateBGPDConfig(config string) error {
	tmpfile, err := os.CreateTemp("", "bgpd-test-*.conf")
	if err != nil {
		return err
	}
	defer os.Remove(tmpfile.Name())

	if _, err := tmpfile.WriteString(config); err != nil {
		return err
	}
	tmpfile.Close()

	cmd := exec.Command("bgpd", "-nf", tmpfile.Name())
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("bgpd validation failed: %s", string(output))
	}
	return nil
}

func TestBGPDGlobalConfig(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "basic AS and router-id",
			config: `
AS 65001
router-id 192.168.1.1
`,
			valid: true,
		},
		{
			name: "holdtime configuration",
			config: `
AS 65001
router-id 192.168.1.1
holdtime 90
holdtime min 30
`,
			valid: true,
		},
		{
			name: "fib-update option",
			config: `
AS 65001
router-id 192.168.1.1
fib-update yes
`,
			valid: true,
		},
		{
			name: "fib-priority",
			config: `
AS 65001
router-id 192.168.1.1
fib-priority 48
`,
			valid: true,
		},
		{
			name: "route-collector",
			config: `
AS 65001
router-id 192.168.1.1
route-collector yes
`,
			valid: true,
		},
		{
			name: "log updates",
			config: `
AS 65001
router-id 192.168.1.1
log updates
`,
			valid: true,
		},
		{
			name: "transparent-as",
			config: `
AS 65001
router-id 192.168.1.1
transparent-as yes
`,
			valid: true,
		},
		{
			name: "listen on specific address",
			config: `
AS 65001
router-id 192.168.1.1
listen on 192.168.1.1
`,
			valid: true,
		},
		{
			name: "listen on multiple addresses",
			config: `
AS 65001
router-id 192.168.1.1
listen on 192.168.1.1
listen on 10.0.0.1
listen on ::1
`,
			valid: true,
		},
		{
			name: "socket settings",
			config: `
AS 65001
router-id 192.168.1.1
socket "/var/run/bgpd.sock"
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateBGPDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
			if !tt.valid && err == nil {
				t.Errorf("Expected invalid config to fail validation")
			}
		})
	}
}

func TestBGPDNeighborConfig(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "basic eBGP neighbor",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	descr "Upstream Provider"
}
`,
			valid: true,
		},
		{
			name: "iBGP neighbor",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 192.168.1.2 {
	remote-as 65001
	descr "Internal peer"
}
`,
			valid: true,
		},
		{
			name: "neighbor with multihop",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	multihop 3
}
`,
			valid: true,
		},
		{
			name: "neighbor with passive mode",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	passive
}
`,
			valid: true,
		},
		{
			name: "neighbor with local-address",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	local-address 192.168.1.1
}
`,
			valid: true,
		},
		{
			name: "neighbor with holdtime",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	holdtime 180
	holdtime min 60
}
`,
			valid: true,
		},
		{
			name: "neighbor with authentication",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	tcp md5sig password "secretkey"
}
`,
			valid: true,
		},
		{
			name: "neighbor with TTL security",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	ttl-security yes
}
`,
			valid: true,
		},
		{
			name: "neighbor with announce options",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	announce IPv4 unicast
	announce IPv6 unicast
}
`,
			valid: true,
		},
		{
			name: "neighbor with max-prefix",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	max-prefix 10000 restart 30
}
`,
			valid: true,
		},
		{
			name: "IPv6 neighbor",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 2001:db8::2 {
	remote-as 65002
	announce IPv6 unicast
}
`,
			valid: true,
		},
		{
			name: "neighbor with RDE options",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	rde evaluate all
}
`,
			valid: true,
		},
		{
			name: "neighbor with softreconfig",
			config: `
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.2 {
	remote-as 65002
	softreconfig in yes
}
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateBGPDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
		})
	}
}

func TestBGPDGroupConfig(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "basic group",
			config: `
AS 65001
router-id 192.168.1.1

group "upstreams" {
	remote-as 65002

	neighbor 10.0.0.2
	neighbor 10.0.0.3
}
`,
			valid: true,
		},
		{
			name: "group with shared options",
			config: `
AS 65001
router-id 192.168.1.1

group "peers" {
	remote-as 65002
	holdtime 90
	announce IPv4 unicast
	announce IPv6 unicast

	neighbor 10.0.0.2 {
		descr "Peer 1"
	}
	neighbor 10.0.0.3 {
		descr "Peer 2"
	}
}
`,
			valid: true,
		},
		{
			name: "iBGP group with route-reflector",
			config: `
AS 65001
router-id 192.168.1.1

group "route-reflector-clients" {
	remote-as 65001
	rde route-reflector

	neighbor 192.168.1.2
	neighbor 192.168.1.3
	neighbor 192.168.1.4
}
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateBGPDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
		})
	}
}

func TestBGPDNetworkConfig(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "announce network",
			config: `
AS 65001
router-id 192.168.1.1

network 10.0.0.0/8
network 192.168.0.0/16
`,
			valid: true,
		},
		{
			name: "announce IPv6 network",
			config: `
AS 65001
router-id 192.168.1.1

network 2001:db8::/32
`,
			valid: true,
		},
		{
			name: "network with set options",
			config: `
AS 65001
router-id 192.168.1.1

network 10.0.0.0/8 set {
	localpref 200
	community 65001:100
}
`,
			valid: true,
		},
		{
			name: "network with static",
			config: `
AS 65001
router-id 192.168.1.1

network static
`,
			valid: true,
		},
		{
			name: "network with connected",
			config: `
AS 65001
router-id 192.168.1.1

network connected
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateBGPDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
		})
	}
}

func TestBGPDFilterConfig(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "prefix-set",
			config: `
AS 65001
router-id 192.168.1.1

prefix-set "bogons" {
	0.0.0.0/8 prefixlen >= 8
	10.0.0.0/8 prefixlen >= 8
	127.0.0.0/8 prefixlen >= 8
	169.254.0.0/16 prefixlen >= 16
	172.16.0.0/12 prefixlen >= 12
	192.0.2.0/24 prefixlen >= 24
	192.168.0.0/16 prefixlen >= 16
	224.0.0.0/3 prefixlen >= 3
}
`,
			valid: true,
		},
		{
			name: "as-set",
			config: `
AS 65001
router-id 192.168.1.1

as-set "customers" {
	65100, 65101, 65102, 65103
}
`,
			valid: true,
		},
		{
			name: "filter rule deny",
			config: `
AS 65001
router-id 192.168.1.1

deny from any prefix 0.0.0.0/0 prefixlen > 24
`,
			valid: true,
		},
		{
			name: "filter rule allow",
			config: `
AS 65001
router-id 192.168.1.1

allow from any
`,
			valid: true,
		},
		{
			name: "filter with community match",
			config: `
AS 65001
router-id 192.168.1.1

deny from any community 65001:666
`,
			valid: true,
		},
		{
			name: "filter with AS path match",
			config: `
AS 65001
router-id 192.168.1.1

deny from any AS 65100
deny from any peer-as 65200
`,
			valid: true,
		},
		{
			name: "filter with set actions",
			config: `
AS 65001
router-id 192.168.1.1

match from any set { localpref 100, prepend-self 2 }
`,
			valid: true,
		},
		{
			name: "complex filter rules",
			config: `
AS 65001
router-id 192.168.1.1

# Deny bogons and too-specific prefixes
deny from any prefix 10.0.0.0/8 prefixlen >= 8
deny from any prefix 192.168.0.0/16 prefixlen >= 16
deny from any prefixlen > 24

# Accept from customers with community tagging
allow from group "customers" set community 65001:100

# Default deny
deny from any
`,
			valid: true,
		},
		{
			name: "filter with large community",
			config: `
AS 65001
router-id 192.168.1.1

match from any set large-community 65001:0:100
`,
			valid: true,
		},
		{
			name: "filter with ext-community",
			config: `
AS 65001
router-id 192.168.1.1

match from any set ext-community rt 65001:100
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateBGPDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
		})
	}
}

func TestBGPDCompleteConfig(t *testing.T) {
	skipIfNotOpenBSD(t)

	config := `
# Complete BGP configuration example
AS 65001
router-id 192.168.1.1
holdtime 90
holdtime min 30
fib-update yes
log updates

# Prefix sets for filtering
prefix-set "bogons" {
	0.0.0.0/8 prefixlen >= 8
	10.0.0.0/8 prefixlen >= 8
	127.0.0.0/8 prefixlen >= 8
	169.254.0.0/16 prefixlen >= 16
	172.16.0.0/12 prefixlen >= 12
	192.0.2.0/24 prefixlen >= 24
	192.168.0.0/16 prefixlen >= 16
	224.0.0.0/3 prefixlen >= 3
}

prefix-set "our-networks" {
	203.0.113.0/24
	2001:db8::/32
}

# Network announcements
network 203.0.113.0/24 set { localpref 200, community 65001:100 }
network 2001:db8::/32

# Upstream providers
group "upstreams" {
	remote-as 65002
	announce IPv4 unicast
	announce IPv6 unicast

	neighbor 10.0.0.1 {
		descr "Primary upstream"
		local-address 10.0.0.2
	}
	neighbor 10.1.0.1 {
		descr "Secondary upstream"
		local-address 10.1.0.2
	}
}

# iBGP mesh
group "ibgp" {
	remote-as 65001
	announce IPv4 unicast
	announce IPv6 unicast

	neighbor 192.168.1.2 {
		descr "Core router 2"
	}
	neighbor 192.168.1.3 {
		descr "Core router 3"
	}
}

# Filtering rules

# Block bogons
deny from any prefix-set "bogons"

# Block too-long prefixes
deny from any prefixlen > 24
deny from any inet6 prefixlen > 48

# Accept from upstreams
allow from group "upstreams"

# Set local-pref for iBGP
match from group "ibgp" set localpref 100

# Allow to upstreams with prepend
allow to group "upstreams" prefix-set "our-networks" set { prepend-self 1 }

# Default policy
deny from any
deny to any
`

	err := validateBGPDConfig(config)
	if err != nil {
		t.Errorf("Complete BGP config validation failed: %v", err)
	}
}

// =============================================================================
// BGPCTL OUTPUT PARSING TESTS
// =============================================================================

const bgpctlShowSummary = `Neighbor                   AS    MsgRcvd    MsgSent  OutQ Up/Down  State/PrfRcvd
10.0.0.1              65002      12345       6789     0 01:23:45   Established
10.0.0.2              65003       9876       5432     0 00:45:30   Established
192.168.1.2           65001       5678       5678     0 12:34:56   Established
10.0.0.3              65004          0          0     0 00:00:00   Idle
10.0.0.4              65005        123        456     5 00:01:23   Active
2001:db8::1           65006       1000       2000     0 02:00:00   Established
`

const bgpctlShowRibMemory = `RDE memory statistics
    319442 IPv4 network entries using 8.1M of memory
     12345 IPv6 network entries using 789.2K of memory
    523456 rib entries using 15.9M of memory
    156789 prefix entries using 10.5M of memory
     98765 BGP path attribute entries using 3.2M of memory
     45678 BGP AS-PATH attribute entries using 890.1K of memory
     12345 BGP COMMUNITY attribute entries using 123.4K of memory
         1 RDE adjacencies using 40 bytes of memory
    234567 tables entries using 7.8M of memory
Rib memory statistics
         4 update sets using 192 bytes of memory
`

const bgpctlShowNeighbor = `BGP neighbor is 10.0.0.1, remote AS 65002
 Description: Primary Upstream Provider
 BGP version 4, remote router-id 10.0.0.1
 BGP state = Established, up for 01:23:45
 Last read 00:00:05, holdtime 90s, keepalive interval 30s
 Neighbor capabilities:
    Multiprotocol extensions: IPv4 unicast, IPv6 unicast
    Route Refresh
    Graceful Restart
    4-byte AS numbers

 Message statistics:
                    Sent       Rcvd
    Opens              2          2
    Notifications      0          0
    Updates        12345       6789
    Keepalives      5678       5678
    Route Refresh      0          0
    Total          18025      12469

 Update statistics:
                    Sent       Rcvd
    Updates        12345       6789
    Withdraws        123        456
    End-of-Rib        2          2

 Prefixes:
    IPv4 unicast:    1234 received, 567 sent
    IPv6 unicast:     456 received, 123 sent
`

const bgpctlShowRib = `flags: * = Valid, > = Selected, I = via IBGP, A = Announced,
       S = Stale, E = Error
origin validation state: N = not-found, V = valid, ! = invalid
aspa validation state: ? = unknown, V = valid, ! = invalid
origin: i = IGP, e = EGP, ? = Incomplete

flags  vs destination          gateway          lpref   med aspath origin
*>      V  10.0.0.0/8           10.0.0.1           100     0 65002 65100 i
*>      V  172.16.0.0/12        10.0.0.1           100    10 65002 65200 65300 i
*>I     V  192.168.1.0/24       192.168.1.2        100     0 i
*>      V  203.0.113.0/24       0.0.0.0            200     0 i
*  A    N  10.1.0.0/16          10.0.0.2           100   100 65003 65400 ?
*>      V  2001:db8::/32        2001:db8::1        100     0 65006 i
`

// BGPNeighborSummary represents parsed neighbor summary
type BGPNeighborSummary struct {
	Address      string
	AS           int
	MsgRcvd      int
	MsgSent      int
	OutQ         int
	UpDown       string
	State        string
	PrefixesRcvd int
}

// BGPRoute represents a parsed BGP route
type BGPRoute struct {
	Flags       string
	Valid       bool
	Selected    bool
	IBGP        bool
	Announced   bool
	Validation  string
	Destination string
	Gateway     string
	LocalPref   int
	MED         int
	ASPath      string
	Origin      string
}

func parseBGPSummary(output string) []BGPNeighborSummary {
	var neighbors []BGPNeighborSummary

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		// Skip header line
		if strings.HasPrefix(line, "Neighbor") || line == "" {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 7 {
			continue
		}

		neighbor := BGPNeighborSummary{
			Address: fields[0],
		}
		neighbor.AS, _ = strconv.Atoi(fields[1])
		neighbor.MsgRcvd, _ = strconv.Atoi(fields[2])
		neighbor.MsgSent, _ = strconv.Atoi(fields[3])
		neighbor.OutQ, _ = strconv.Atoi(fields[4])
		neighbor.UpDown = fields[5]
		neighbor.State = fields[6]

		// Check if state is "Established" with prefix count
		if neighbor.State == "Established" && len(fields) > 7 {
			// Prefix count might be in state field or next field
		}

		neighbors = append(neighbors, neighbor)
	}

	return neighbors
}

func parseBGPRoutes(output string) []BGPRoute {
	var routes []BGPRoute

	lines := strings.Split(output, "\n")
	inRoutes := false

	for _, line := range lines {
		if strings.HasPrefix(line, "flags ") {
			inRoutes = true
			continue
		}
		if !inRoutes || line == "" {
			continue
		}

		// Parse route line
		// Format: flags  vs destination          gateway          lpref   med aspath origin
		if len(line) < 60 {
			continue
		}

		route := BGPRoute{}

		// Parse flags (first few characters)
		flagsPart := line[:8]
		route.Flags = strings.TrimSpace(flagsPart)
		route.Valid = strings.Contains(route.Flags, "*")
		route.Selected = strings.Contains(route.Flags, ">")
		route.IBGP = strings.Contains(route.Flags, "I")
		route.Announced = strings.Contains(route.Flags, "A")

		// Parse validation state
		if len(line) > 8 {
			route.Validation = string(line[8])
		}

		// Parse remaining fields
		fields := strings.Fields(line[10:])
		if len(fields) >= 4 {
			route.Destination = fields[0]
			route.Gateway = fields[1]
			route.LocalPref, _ = strconv.Atoi(fields[2])
			route.MED, _ = strconv.Atoi(fields[3])

			// AS path is remaining fields before origin
			if len(fields) > 5 {
				route.ASPath = strings.Join(fields[4:len(fields)-1], " ")
				route.Origin = fields[len(fields)-1]
			}
		}

		routes = append(routes, route)
	}

	return routes
}

func parseBGPRibMemory(output string) map[string]int {
	stats := make(map[string]int)

	re := regexp.MustCompile(`(\d+)\s+(IPv[46]|rib|prefix|BGP|RDE|tables|update)`)

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		matches := re.FindStringSubmatch(line)
		if len(matches) > 2 {
			count, _ := strconv.Atoi(matches[1])
			key := strings.ToLower(matches[2])
			stats[key] = count
		}
	}

	return stats
}

func TestParseBGPSummary(t *testing.T) {
	neighbors := parseBGPSummary(bgpctlShowSummary)

	if len(neighbors) != 6 {
		t.Errorf("Expected 6 neighbors, got %d", len(neighbors))
	}

	// Check first neighbor
	if neighbors[0].Address != "10.0.0.1" {
		t.Errorf("Expected address 10.0.0.1, got %s", neighbors[0].Address)
	}
	if neighbors[0].AS != 65002 {
		t.Errorf("Expected AS 65002, got %d", neighbors[0].AS)
	}
	if neighbors[0].MsgRcvd != 12345 {
		t.Errorf("Expected MsgRcvd 12345, got %d", neighbors[0].MsgRcvd)
	}
	if neighbors[0].State != "Established" {
		t.Errorf("Expected state Established, got %s", neighbors[0].State)
	}

	// Check idle neighbor
	if neighbors[3].State != "Idle" {
		t.Errorf("Expected state Idle for neighbor 4, got %s", neighbors[3].State)
	}

	// Check active neighbor
	if neighbors[4].State != "Active" {
		t.Errorf("Expected state Active for neighbor 5, got %s", neighbors[4].State)
	}
	if neighbors[4].OutQ != 5 {
		t.Errorf("Expected OutQ 5, got %d", neighbors[4].OutQ)
	}

	// Check IPv6 neighbor
	if neighbors[5].Address != "2001:db8::1" {
		t.Errorf("Expected IPv6 address, got %s", neighbors[5].Address)
	}
}

func TestParseBGPRoutes(t *testing.T) {
	routes := parseBGPRoutes(bgpctlShowRib)

	if len(routes) < 5 {
		t.Errorf("Expected at least 5 routes, got %d", len(routes))
	}

	// Check first route
	found := false
	for _, r := range routes {
		if r.Destination == "10.0.0.0/8" {
			found = true
			if !r.Valid {
				t.Error("Expected route to be valid")
			}
			if !r.Selected {
				t.Error("Expected route to be selected")
			}
			if r.LocalPref != 100 {
				t.Errorf("Expected localpref 100, got %d", r.LocalPref)
			}
		}
	}
	if !found {
		t.Error("Did not find expected route 10.0.0.0/8")
	}

	// Check iBGP route
	for _, r := range routes {
		if r.Destination == "192.168.1.0/24" {
			if !r.IBGP {
				t.Error("Expected iBGP flag for internal route")
			}
		}
	}
}

func TestParseBGPRibMemory(t *testing.T) {
	stats := parseBGPRibMemory(bgpctlShowRibMemory)

	if stats["ipv4"] != 319442 {
		t.Errorf("Expected 319442 IPv4 entries, got %d", stats["ipv4"])
	}
	if stats["ipv6"] != 12345 {
		t.Errorf("Expected 12345 IPv6 entries, got %d", stats["ipv6"])
	}
}

// =============================================================================
// BENCHMARK TESTS
// =============================================================================

func BenchmarkParseBGPSummary(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseBGPSummary(bgpctlShowSummary)
	}
}

func BenchmarkParseBGPRoutes(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseBGPRoutes(bgpctlShowRib)
	}
}

func BenchmarkParseLargeRoutingTable(b *testing.B) {
	// Generate large routing table
	var builder strings.Builder
	builder.WriteString("flags: * = Valid, > = Selected\n\n")
	builder.WriteString("flags  vs destination          gateway          lpref   med aspath origin\n")

	for i := 0; i < 100000; i++ {
		builder.WriteString(fmt.Sprintf("*>      V  %d.%d.%d.0/24       10.0.0.1           100     0 65002 %d i\n",
			(i/65536)%256, (i/256)%256, i%256, 65100+i%1000))
	}
	largeTable := builder.String()

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		parseBGPRoutes(largeTable)
	}
}
