// Package tests provides comprehensive OSPF daemon validation tests
// Tests ospfd configuration syntax and ospfctl output parsing
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
// OSPFD CONFIGURATION SYNTAX VALIDATION
// Uses ospfd -n to validate configuration syntax
// =============================================================================

// validateOSPFDConfig validates ospfd.conf syntax using ospfd -nf
func validateOSPFDConfig(config string) error {
	if runtime.GOOS != "openbsd" {
		return nil // Skip on non-OpenBSD
	}

	tmpfile, err := os.CreateTemp("", "ospfd-test-*.conf")
	if err != nil {
		return err
	}
	defer os.Remove(tmpfile.Name())

	if _, err := tmpfile.WriteString(config); err != nil {
		return err
	}
	tmpfile.Close()

	cmd := exec.Command("ospfd", "-nf", tmpfile.Name())
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("ospfd validation failed: %s", string(output))
	}
	return nil
}

func TestOSPFDGlobalConfig(t *testing.T) {
	if runtime.GOOS != "openbsd" {
		t.Skip("Skipping: requires OpenBSD")
	}

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "basic router-id",
			config: `
router-id 192.168.1.1
`,
			valid: true,
		},
		{
			name: "fib-update option",
			config: `
router-id 192.168.1.1
fib-update yes
`,
			valid: true,
		},
		{
			name: "fib-priority",
			config: `
router-id 192.168.1.1
fib-priority 32
`,
			valid: true,
		},
		{
			name: "rfc1583compat",
			config: `
router-id 192.168.1.1
rfc1583compat yes
`,
			valid: true,
		},
		{
			name: "redistribute connected",
			config: `
router-id 192.168.1.1
redistribute connected
`,
			valid: true,
		},
		{
			name: "redistribute static",
			config: `
router-id 192.168.1.1
redistribute static
`,
			valid: true,
		},
		{
			name: "redistribute with set",
			config: `
router-id 192.168.1.1
redistribute connected set { metric 100, type 2 }
`,
			valid: true,
		},
		{
			name: "redistribute default",
			config: `
router-id 192.168.1.1
redistribute default set metric 10
`,
			valid: true,
		},
		{
			name: "spf-delay and holdtime",
			config: `
router-id 192.168.1.1
spf-delay 1
spf-holdtime 5
`,
			valid: true,
		},
		{
			name: "stub router",
			config: `
router-id 192.168.1.1
stub router yes
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateOSPFDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
		})
	}
}

func TestOSPFDAreaConfig(t *testing.T) {
	if runtime.GOOS != "openbsd" {
		t.Skip("Skipping: requires OpenBSD")
	}

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "basic area",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0
}
`,
			valid: true,
		},
		{
			name: "area with demote",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	demote carp
	interface em0
}
`,
			valid: true,
		},
		{
			name: "stub area",
			config: `
router-id 192.168.1.1

area 0.0.0.1 {
	stub
	interface em0
}
`,
			valid: true,
		},
		{
			name: "stub area no-summary",
			config: `
router-id 192.168.1.1

area 0.0.0.1 {
	stub no-summary
	interface em0
}
`,
			valid: true,
		},
		{
			name: "stub area with default-cost",
			config: `
router-id 192.168.1.1

area 0.0.0.1 {
	stub default-cost 100
	interface em0
}
`,
			valid: true,
		},
		{
			name: "multiple areas",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0
}

area 0.0.0.1 {
	stub
	interface em1
}

area 0.0.0.2 {
	interface em2
}
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateOSPFDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
		})
	}
}

func TestOSPFDInterfaceConfig(t *testing.T) {
	if runtime.GOOS != "openbsd" {
		t.Skip("Skipping: requires OpenBSD")
	}

	tests := []struct {
		name   string
		config string
		valid  bool
	}{
		{
			name: "interface with cost",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		cost 10
	}
}
`,
			valid: true,
		},
		{
			name: "interface with hello-interval",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		hello-interval 10
	}
}
`,
			valid: true,
		},
		{
			name: "interface with dead-interval",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		router-dead-time 40
	}
}
`,
			valid: true,
		},
		{
			name: "interface with retransmit-interval",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		retransmit-interval 5
	}
}
`,
			valid: true,
		},
		{
			name: "interface with transmit-delay",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		transmit-delay 1
	}
}
`,
			valid: true,
		},
		{
			name: "interface with priority",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		router-priority 100
	}
}
`,
			valid: true,
		},
		{
			name: "interface passive",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		passive
	}
}
`,
			valid: true,
		},
		{
			name: "interface type broadcast",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		type broadcast
	}
}
`,
			valid: true,
		},
		{
			name: "interface type point-to-point",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		type p2p
	}
}
`,
			valid: true,
		},
		{
			name: "interface with authentication",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		auth-type simple
		auth-key "secretkey"
	}
}
`,
			valid: true,
		},
		{
			name: "interface with MD5 auth",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		auth-type crypt
		auth-md5 1 "md5secretkey"
	}
}
`,
			valid: true,
		},
		{
			name: "interface with metric",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		metric 100
	}
}
`,
			valid: true,
		},
		{
			name: "interface with all options",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		cost 10
		hello-interval 10
		router-dead-time 40
		retransmit-interval 5
		transmit-delay 1
		router-priority 100
		type broadcast
		auth-type crypt
		auth-md5 1 "secretkey"
	}
}
`,
			valid: true,
		},
		{
			name: "interface with depend on",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		depend on em1
	}
}
`,
			valid: true,
		},
		{
			name: "interface demote",
			config: `
router-id 192.168.1.1

area 0.0.0.0 {
	interface em0 {
		demote carp 10
	}
}
`,
			valid: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validateOSPFDConfig(tt.config)
			if tt.valid && err != nil {
				t.Errorf("Expected valid config, got error: %v", err)
			}
		})
	}
}

func TestOSPFDCompleteConfig(t *testing.T) {
	if runtime.GOOS != "openbsd" {
		t.Skip("Skipping: requires OpenBSD")
	}

	config := `
# Complete OSPF configuration
router-id 192.168.1.1

# Global options
fib-update yes
rfc1583compat yes
spf-delay 1
spf-holdtime 5

# Redistribution
redistribute connected set { metric 100, type 1 }
redistribute static set { metric 200, type 2 }

# Backbone area
area 0.0.0.0 {
	# Core interface with DR election priority
	interface em0 {
		cost 10
		hello-interval 10
		router-dead-time 40
		router-priority 100
		auth-type crypt
		auth-md5 1 "area0key"
	}

	# Backup link
	interface em1 {
		cost 50
		hello-interval 10
		router-dead-time 40
		router-priority 50
		auth-type crypt
		auth-md5 1 "area0key"
	}
}

# Stub area for remote sites
area 0.0.0.1 {
	stub default-cost 100

	interface em2 {
		cost 10
		passive
	}

	interface em3 {
		type p2p
		cost 20
	}
}

# Internal area
area 0.0.0.2 {
	demote carp

	interface em4 {
		cost 10
		depend on em0
	}
}
`

	err := validateOSPFDConfig(config)
	if err != nil {
		t.Errorf("Complete OSPF config validation failed: %v", err)
	}
}

// =============================================================================
// OSPFCTL OUTPUT PARSING TESTS
// =============================================================================

const ospfctlShowNeighbor = `ID              Pri State        DeadTime Address         Iface      Uptime
192.168.1.2     100 Full/DR      00:00:35 192.168.1.2     em0        01:23:45
192.168.1.3      50 Full/BDR     00:00:38 192.168.1.3     em0        01:23:40
192.168.1.4      10 Full/DROther 00:00:32 192.168.1.4     em0        00:45:30
192.168.2.2       1 2-Way        00:00:39 192.168.2.2     em1        00:30:00
192.168.3.2       1 Init         00:00:40 192.168.3.2     em2        00:00:05
192.168.4.2       1 Loading      00:00:36 192.168.4.2     em3        00:00:15
192.168.5.2       1 ExStart      00:00:37 192.168.5.2     em4        00:00:10
`

const ospfctlShowInterface = `Interface   Address         State  HelloTimer Linkstate
em0         192.168.1.1/24  DR     00:00:03   up
em1         192.168.2.1/24  BDR    00:00:07   up
em2         192.168.3.1/24  DROther 00:00:05   up
em3         10.0.0.1/30     p2p    00:00:08   up
lo0         127.0.0.1/8     Loopbk            up
vlan100     192.168.100.1/24 Down              down
`

const ospfctlShowDatabase = `Router Link States (Area 0.0.0.0)

ADV Router       Age  Seq#       Checksum Link cnt
192.168.1.1       234 0x80000123 0x1234   3
192.168.1.2       567 0x80000456 0x5678   4
192.168.1.3       890 0x80000789 0x9abc   2

Network Link States (Area 0.0.0.0)

Link ID          ADV Router       Age  Seq#       Checksum
192.168.1.1      192.168.1.2      345  0x80000234 0x2345

Summary Net Link States (Area 0.0.0.0)

Link ID          ADV Router       Age  Seq#       Checksum
10.0.0.0         192.168.1.1      456  0x80000345 0x3456
172.16.0.0       192.168.1.1      567  0x80000456 0x4567

AS External Link States

Link ID          ADV Router       Age  Seq#       Checksum
0.0.0.0          192.168.1.1      678  0x80000567 0x5678
8.8.8.0          192.168.1.1      789  0x80000678 0x6789
`

const ospfctlShowRib = `Destination          Nexthop          Path Type    Type      Cost     Uptime
192.168.1.0/24       192.168.1.1      Intra-Area   Network   10       01:23:45
192.168.2.0/24       192.168.1.2      Intra-Area   Network   20       00:45:30
10.0.0.0/8           192.168.1.3      Inter-Area   Network   100      00:30:00
0.0.0.0/0            192.168.1.1      Type-2       External  1        00:15:00
8.8.8.0/24           192.168.1.1      Type-1       External  150      00:10:00
`

// OSPFNeighbor represents parsed OSPF neighbor
type OSPFNeighbor struct {
	RouterID   string
	Priority   int
	State      string
	Role       string // DR, BDR, DROther
	DeadTime   string
	Address    string
	Interface  string
	Uptime     string
}

// OSPFInterface represents parsed OSPF interface
type OSPFInterface struct {
	Name      string
	Address   string
	State     string
	HelloTimer string
	LinkState string
}

// OSPFLSA represents parsed OSPF LSA
type OSPFLSA struct {
	Type     string
	LinkID   string
	Router   string
	Age      int
	SeqNum   string
	Checksum string
	LinkCnt  int
}

// OSPFRoute represents parsed OSPF route
type OSPFRoute struct {
	Destination string
	Nexthop     string
	PathType    string
	Type        string
	Cost        int
	Uptime      string
}

func parseOSPFNeighbors(output string) []OSPFNeighbor {
	var neighbors []OSPFNeighbor

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		if strings.HasPrefix(line, "ID") || line == "" {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 7 {
			continue
		}

		neighbor := OSPFNeighbor{
			RouterID:  fields[0],
			DeadTime:  fields[3],
			Address:   fields[4],
			Interface: fields[5],
			Uptime:    fields[6],
		}
		neighbor.Priority, _ = strconv.Atoi(fields[1])

		// Parse state/role (e.g., "Full/DR")
		stateParts := strings.Split(fields[2], "/")
		neighbor.State = stateParts[0]
		if len(stateParts) > 1 {
			neighbor.Role = stateParts[1]
		}

		neighbors = append(neighbors, neighbor)
	}

	return neighbors
}

func parseOSPFInterfaces(output string) []OSPFInterface {
	var interfaces []OSPFInterface

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		if strings.HasPrefix(line, "Interface") || line == "" {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 4 {
			continue
		}

		iface := OSPFInterface{
			Name:      fields[0],
			Address:   fields[1],
			State:     fields[2],
		}

		if len(fields) > 3 {
			iface.HelloTimer = fields[3]
		}
		if len(fields) > 4 {
			iface.LinkState = fields[4]
		}

		interfaces = append(interfaces, iface)
	}

	return interfaces
}

func parseOSPFRoutes(output string) []OSPFRoute {
	var routes []OSPFRoute

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		if strings.HasPrefix(line, "Destination") || line == "" {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 6 {
			continue
		}

		route := OSPFRoute{
			Destination: fields[0],
			Nexthop:     fields[1],
			PathType:    fields[2],
			Type:        fields[3],
			Uptime:      fields[5],
		}
		route.Cost, _ = strconv.Atoi(fields[4])

		routes = append(routes, route)
	}

	return routes
}

func parseOSPFDatabase(output string) map[string]int {
	stats := make(map[string]int)

	// Count LSA types
	re := regexp.MustCompile(`^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s+`)

	currentType := ""
	lines := strings.Split(output, "\n")

	for _, line := range lines {
		if strings.Contains(line, "Router Link States") {
			currentType = "router"
		} else if strings.Contains(line, "Network Link States") {
			currentType = "network"
		} else if strings.Contains(line, "Summary Net Link States") {
			currentType = "summary"
		} else if strings.Contains(line, "AS External Link States") {
			currentType = "external"
		} else if currentType != "" && re.MatchString(strings.TrimSpace(line)) {
			stats[currentType]++
		}
	}

	return stats
}

func TestParseOSPFNeighbors(t *testing.T) {
	neighbors := parseOSPFNeighbors(ospfctlShowNeighbor)

	if len(neighbors) != 7 {
		t.Errorf("Expected 7 neighbors, got %d", len(neighbors))
	}

	// Check DR neighbor
	if neighbors[0].RouterID != "192.168.1.2" {
		t.Errorf("Expected router-id 192.168.1.2, got %s", neighbors[0].RouterID)
	}
	if neighbors[0].State != "Full" {
		t.Errorf("Expected state Full, got %s", neighbors[0].State)
	}
	if neighbors[0].Role != "DR" {
		t.Errorf("Expected role DR, got %s", neighbors[0].Role)
	}
	if neighbors[0].Priority != 100 {
		t.Errorf("Expected priority 100, got %d", neighbors[0].Priority)
	}

	// Check BDR neighbor
	if neighbors[1].Role != "BDR" {
		t.Errorf("Expected role BDR, got %s", neighbors[1].Role)
	}

	// Check 2-Way neighbor
	if neighbors[3].State != "2-Way" {
		t.Errorf("Expected state 2-Way, got %s", neighbors[3].State)
	}

	// Check various states
	states := map[int]string{
		4: "Init",
		5: "Loading",
		6: "ExStart",
	}
	for idx, expected := range states {
		if neighbors[idx].State != expected {
			t.Errorf("Neighbor %d: expected state %s, got %s", idx, expected, neighbors[idx].State)
		}
	}
}

func TestParseOSPFInterfaces(t *testing.T) {
	interfaces := parseOSPFInterfaces(ospfctlShowInterface)

	if len(interfaces) != 6 {
		t.Errorf("Expected 6 interfaces, got %d", len(interfaces))
	}

	// Check DR interface
	if interfaces[0].Name != "em0" {
		t.Errorf("Expected em0, got %s", interfaces[0].Name)
	}
	if interfaces[0].State != "DR" {
		t.Errorf("Expected state DR, got %s", interfaces[0].State)
	}
	if interfaces[0].LinkState != "up" {
		t.Errorf("Expected linkstate up, got %s", interfaces[0].LinkState)
	}

	// Check p2p interface
	if interfaces[3].State != "p2p" {
		t.Errorf("Expected state p2p, got %s", interfaces[3].State)
	}

	// Check down interface
	if interfaces[5].State != "Down" {
		t.Errorf("Expected state Down, got %s", interfaces[5].State)
	}
}

func TestParseOSPFRoutes(t *testing.T) {
	routes := parseOSPFRoutes(ospfctlShowRib)

	if len(routes) != 5 {
		t.Errorf("Expected 5 routes, got %d", len(routes))
	}

	// Check intra-area route
	if routes[0].PathType != "Intra-Area" {
		t.Errorf("Expected Intra-Area, got %s", routes[0].PathType)
	}
	if routes[0].Cost != 10 {
		t.Errorf("Expected cost 10, got %d", routes[0].Cost)
	}

	// Check inter-area route
	if routes[2].PathType != "Inter-Area" {
		t.Errorf("Expected Inter-Area, got %s", routes[2].PathType)
	}

	// Check external routes
	if routes[3].PathType != "Type-2" {
		t.Errorf("Expected Type-2, got %s", routes[3].PathType)
	}
	if routes[4].PathType != "Type-1" {
		t.Errorf("Expected Type-1, got %s", routes[4].PathType)
	}
}

func TestParseOSPFDatabase(t *testing.T) {
	stats := parseOSPFDatabase(ospfctlShowDatabase)

	if stats["router"] != 3 {
		t.Errorf("Expected 3 router LSAs, got %d", stats["router"])
	}
	if stats["network"] != 1 {
		t.Errorf("Expected 1 network LSA, got %d", stats["network"])
	}
	if stats["summary"] != 2 {
		t.Errorf("Expected 2 summary LSAs, got %d", stats["summary"])
	}
	if stats["external"] != 2 {
		t.Errorf("Expected 2 external LSAs, got %d", stats["external"])
	}
}

// =============================================================================
// BENCHMARK TESTS
// =============================================================================

func BenchmarkParseOSPFNeighbors(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseOSPFNeighbors(ospfctlShowNeighbor)
	}
}

func BenchmarkParseOSPFRoutes(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseOSPFRoutes(ospfctlShowRib)
	}
}

func BenchmarkParseLargeOSPFDatabase(b *testing.B) {
	var builder strings.Builder
	builder.WriteString("Router Link States (Area 0.0.0.0)\n\n")
	builder.WriteString("ADV Router       Age  Seq#       Checksum Link cnt\n")

	for i := 0; i < 10000; i++ {
		builder.WriteString(fmt.Sprintf("192.168.%d.%d       %d 0x80000%03x 0x%04x   %d\n",
			(i/256)%256, i%256, i%3600, i%4096, i%65536, i%10))
	}

	largeDB := builder.String()

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		parseOSPFDatabase(largeDB)
	}
}
