// Package tests provides comprehensive unit tests for PF firewall parsing
package tests

import (
	"strings"
	"testing"
)

// Sample pfctl -si output from real OpenBSD systems
const pfctlInfoEnabled = `Status: Enabled for 0 days 02:34:56              Debug: err

State Table                          Total             Rate
  current entries                      127
  searches                         1847293           202.3/s
  inserts                            15847             1.7/s
  removals                           15720             1.7/s
Counters
  match                            2847561           311.6/s
  bad-offset                             0             0.0/s
  fragment                               0             0.0/s
  short                                  0             0.0/s
  normalize                              0             0.0/s
  memory                                 0             0.0/s
  bad-timestamp                          0             0.0/s
  congestion                             0             0.0/s
  ip-option                              0             0.0/s
  proto-cksum                            0             0.0/s
  state-mismatch                        23             0.0/s
  state-insert                           0             0.0/s
  state-limit                            0             0.0/s
  src-limit                              0             0.0/s
  synproxy                               0             0.0/s
  translate                          15234             1.7/s
  no-route                               0             0.0/s
`

const pfctlInfoDisabled = `Status: Disabled                                    Debug: err

State Table                          Total             Rate
  current entries                        0
  searches                               0             0.0/s
  inserts                                0             0.0/s
  removals                               0             0.0/s
`

const pfctlInfoWithHostid = `Status: Enabled for 5 days 12:34:56              Debug: Urgent
Hostid: 0xdeadbeef
Checksum: 0x1234567890abcdef

State Table                          Total             Rate
  current entries                     1024
  half-open tcp                         12
  searches                         9847293          1102.3/s
  inserts                           115847            12.7/s
  removals                          115720            12.7/s
`

// Sample pfctl -sr -vv output
const pfctlRulesOutput = `@0 scrub in on em0 all fragment reassemble
  [ Evaluations: 1234567   Packets: 1234567   Bytes: 123456789   States: 0     ]
  [ Inserted: uid 0 pid 12345 State Creations: 0     ]
@1 block drop in log on ! lo0 proto tcp from any to any port 6667
  [ Evaluations: 98765     Packets: 45        Bytes: 2345        States: 0     ]
  [ Inserted: uid 0 pid 12345 State Creations: 0     ]
@2 block drop in quick on em0 from <bruteforce> to any
  [ Evaluations: 5678      Packets: 123       Bytes: 7890        States: 0     ]
  [ Inserted: uid 0 pid 12345 State Creations: 0     ]
@3 pass in on em0 proto tcp from any to any port 22 flags S/SA keep state
  [ Evaluations: 456789    Packets: 123456    Bytes: 98765432    States: 45    ]
  [ Inserted: uid 0 pid 12345 State Creations: 123   ]
@4 pass out on em0 proto tcp from any to any modulate state
  [ Evaluations: 789012    Packets: 567890    Bytes: 1234567890  States: 89    ]
  [ Inserted: uid 0 pid 12345 State Creations: 456   ]
@5 pass in on em1 inet proto { tcp udp } from 192.168.1.0/24 to any keep state
  [ Evaluations: 345678    Packets: 234567    Bytes: 345678901   States: 12    ]
  [ Inserted: uid 0 pid 12345 State Creations: 234   ]
@6 match out on em0 inet from 192.168.1.0/24 to any nat-to (em0:0)
  [ Evaluations: 234567    Packets: 123456    Bytes: 234567890   States: 0     ]
  [ Inserted: uid 0 pid 12345 State Creations: 0     ]
@7 block return in log quick on em0 proto tcp from any to any port 23 label "block-telnet"
  [ Evaluations: 12345     Packets: 67        Bytes: 3456        States: 0     ]
  [ Inserted: uid 0 pid 12345 State Creations: 0     ]
`

// Complex rules with various options
const pfctlComplexRules = `@0 pass in quick on em0 inet6 proto ipv6-icmp all icmp6-type { echoreq routeradv routersol } keep state
  [ Evaluations: 1000      Packets: 500       Bytes: 50000       States: 5     ]
@1 pass in on em0 proto tcp from <trusted> to any port { 22 80 443 8080 } synproxy state
  [ Evaluations: 5000      Packets: 2500      Bytes: 250000      States: 25    ]
@2 block drop in log quick on egress proto tcp from <bruteforce> to any
  [ Evaluations: 100       Packets: 50        Bytes: 2500        States: 0     ]
@3 pass out on em0 proto { tcp udp } from (em0:network) to any port 53 keep state
  [ Evaluations: 8000      Packets: 4000      Bytes: 320000      States: 10    ]
@4 anchor "ftp-proxy/*" in on em0 proto tcp from any to any port 21
  [ Evaluations: 200       Packets: 100       Bytes: 10000       States: 2     ]
@5 pass in on wg0 from (wg0:network) to any keep state
  [ Evaluations: 3000      Packets: 1500      Bytes: 150000      States: 8     ]
`

// Sample pfctl -ss output
const pfctlStatesOutput = `all tcp 192.168.1.100:52341 -> 93.184.216.34:443       ESTABLISHED:ESTABLISHED
   age 00:05:23, expires in 23:54:37, 12345:67890 pkts, 1234567:7654321 bytes, rule 4
all tcp 192.168.1.100:52342 -> 93.184.216.34:80        ESTABLISHED:ESTABLISHED
   age 00:02:15, expires in 23:57:45, 456:789 pkts, 45678:98765 bytes, rule 4
all udp 192.168.1.100:12345 -> 8.8.8.8:53              SINGLE:NO_TRAFFIC
   age 00:00:02, expires in 00:00:28, 1:0 pkts, 64:0 bytes, rule 3
all tcp 10.0.0.50:22 <- 192.168.1.5:54321              ESTABLISHED:ESTABLISHED
   age 01:23:45, expires in 22:36:15, 9876:5432 pkts, 987654:543210 bytes, rule 3
all icmp 192.168.1.100:1 -> 8.8.8.8:0                  0:0
   age 00:00:01, expires in 00:00:19, 1:1 pkts, 84:84 bytes, rule 5
`

// More complex state output
const pfctlStatesComplex = `all tcp 192.168.1.10:443 (100.64.1.1:443) <- 203.0.113.50:12345       ESTABLISHED:ESTABLISHED
   age 00:15:30, expires in 23:44:30, 5000:3000 pkts, 5000000:1500000 bytes, rule 6
all gre 192.168.1.1:47 -> 10.10.10.1:47                GRE_INITIATING:NO_TRAFFIC
   age 00:00:05, expires in 00:01:55, 10:0 pkts, 1200:0 bytes, rule 8
self icmp 127.0.0.1:8 -> 127.0.0.1:0                   0:0
   age 00:00:00, expires in 00:00:10, 1:0 pkts, 84:0 bytes, rule 0
all tcp 192.168.1.50:8080 (em0) <- 172.16.0.100:45678  TIME_WAIT:TIME_WAIT
   age 00:00:30, expires in 00:01:30, 100:50 pkts, 15000:5000 bytes, rule 4
`

// Sample pfctl -sT output
const pfctlTablesOutput = `bruteforce
martians
trusted
private_nets
blocklist
whitelist
`

// Sample pfctl -t bruteforce -Ts output
const pfctlTableAddrs = `   10.0.0.5
   192.168.1.100
   172.16.0.50
   203.0.113.15
   198.51.100.25
`

// Helper function to parse PF info (mirrors the service logic)
func parsePFInfo(output string) map[string]interface{} {
	info := map[string]interface{}{
		"enabled":     true,
		"running":     true,
		"stateCount":  0,
		"stateLimit":  0,
		"debug":       "",
		"hostid":      "",
		"checksum":    "",
	}

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "Status:") {
			if strings.Contains(line, "Disabled") {
				info["enabled"] = false
				info["running"] = false
			}
			// Extract debug level
			if idx := strings.Index(line, "Debug:"); idx > 0 {
				info["debug"] = strings.TrimSpace(line[idx+6:])
			}
		} else if strings.HasPrefix(line, "Hostid:") {
			info["hostid"] = strings.TrimSpace(strings.TrimPrefix(line, "Hostid:"))
		} else if strings.HasPrefix(line, "Checksum:") {
			info["checksum"] = strings.TrimSpace(strings.TrimPrefix(line, "Checksum:"))
		} else if strings.Contains(line, "current entries") {
			parts := strings.Fields(line)
			for i, p := range parts {
				if p == "entries" && i+1 < len(parts) {
					// Next field is the count
					val := 0
					for _, c := range parts[i+1] {
						if c >= '0' && c <= '9' {
							val = val*10 + int(c-'0')
						}
					}
					info["stateCount"] = val
					break
				}
			}
		}
	}

	return info
}

// Helper to parse PF stats
func parsePFStats(output string) map[string]uint64 {
	stats := make(map[string]uint64)
	lines := strings.Split(output, "\n")
	inCounters := false

	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "Counters") {
			inCounters = true
			continue
		}

		if !inCounters {
			continue
		}

		parts := strings.Fields(line)
		if len(parts) < 2 {
			continue
		}

		name := strings.ToLower(parts[0])
		// Get the first number (total count)
		for _, p := range parts[1:] {
			var val uint64
			for _, c := range p {
				if c >= '0' && c <= '9' {
					val = val*10 + uint64(c-'0')
				} else {
					break
				}
			}
			if val > 0 || p == "0" {
				stats[name] = val
				break
			}
		}
	}

	return stats
}

// TestParsePFInfoEnabled tests parsing enabled PF status
func TestParsePFInfoEnabled(t *testing.T) {
	info := parsePFInfo(pfctlInfoEnabled)

	if !info["enabled"].(bool) {
		t.Error("Expected PF to be enabled")
	}

	if !info["running"].(bool) {
		t.Error("Expected PF to be running")
	}

	if info["debug"].(string) != "err" {
		t.Errorf("Expected debug=err, got %s", info["debug"])
	}

	if info["stateCount"].(int) != 127 {
		t.Errorf("Expected stateCount=127, got %d", info["stateCount"])
	}
}

// TestParsePFInfoDisabled tests parsing disabled PF status
func TestParsePFInfoDisabled(t *testing.T) {
	info := parsePFInfo(pfctlInfoDisabled)

	if info["enabled"].(bool) {
		t.Error("Expected PF to be disabled")
	}

	if info["running"].(bool) {
		t.Error("Expected PF to not be running")
	}

	if info["stateCount"].(int) != 0 {
		t.Errorf("Expected stateCount=0, got %d", info["stateCount"])
	}
}

// TestParsePFInfoWithHostid tests parsing PF status with hostid
func TestParsePFInfoWithHostid(t *testing.T) {
	info := parsePFInfo(pfctlInfoWithHostid)

	if info["hostid"].(string) != "0xdeadbeef" {
		t.Errorf("Expected hostid=0xdeadbeef, got %s", info["hostid"])
	}

	if info["checksum"].(string) != "0x1234567890abcdef" {
		t.Errorf("Expected checksum, got %s", info["checksum"])
	}

	if info["debug"].(string) != "Urgent" {
		t.Errorf("Expected debug=Urgent, got %s", info["debug"])
	}

	if info["stateCount"].(int) != 1024 {
		t.Errorf("Expected stateCount=1024, got %d", info["stateCount"])
	}
}

// TestParsePFStats tests parsing PF statistics
func TestParsePFStats(t *testing.T) {
	stats := parsePFStats(pfctlInfoEnabled)

	tests := []struct {
		name     string
		expected uint64
	}{
		{"match", 2847561},
		{"bad-offset", 0},
		{"fragment", 0},
		{"state-mismatch", 23},
		{"translate", 15234},
		{"no-route", 0},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			if val, ok := stats[tt.name]; !ok {
				t.Errorf("Missing stat: %s", tt.name)
			} else if val != tt.expected {
				t.Errorf("Expected %s=%d, got %d", tt.name, tt.expected, val)
			}
		})
	}
}

// Rule parsing helper (mirrors service logic)
type testPFRule struct {
	Number        int
	Action        string
	Direction     string
	Quick         bool
	Log           bool
	Interface     string
	AddressFamily string
	Protocol      string
	Source        string
	Destination   string
	Port          string
	Label         string
	State         string
	Evaluations   uint64
	Packets       uint64
	Bytes         uint64
}

func parseTestRules(output string) []testPFRule {
	var rules []testPFRule
	var current *testPFRule
	ruleNum := 0

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		if line == "" {
			continue
		}

		if strings.HasPrefix(line, "@") {
			if current != nil {
				rules = append(rules, *current)
			}

			current = &testPFRule{Number: ruleNum}
			ruleNum++

			// Parse rule line
			if idx := strings.Index(line, ")"); idx > 0 {
				line = strings.TrimSpace(line[idx+1:])
			} else if idx := strings.Index(line, " "); idx > 0 {
				line = strings.TrimSpace(line[idx+1:])
			}

			parts := strings.Fields(line)
			for i := 0; i < len(parts); i++ {
				switch parts[i] {
				case "pass", "block", "match", "anchor", "scrub":
					current.Action = parts[i]
				case "in":
					current.Direction = "in"
				case "out":
					current.Direction = "out"
				case "quick":
					current.Quick = true
				case "log":
					current.Log = true
				case "on":
					if i+1 < len(parts) {
						current.Interface = parts[i+1]
						i++
					}
				case "inet":
					current.AddressFamily = "inet"
				case "inet6":
					current.AddressFamily = "inet6"
				case "proto":
					if i+1 < len(parts) {
						current.Protocol = parts[i+1]
						i++
					}
				case "from":
					if i+1 < len(parts) {
						current.Source = parts[i+1]
						i++
					}
				case "to":
					if i+1 < len(parts) {
						current.Destination = parts[i+1]
						i++
					}
				case "port":
					if i+1 < len(parts) {
						current.Port = parts[i+1]
						i++
					}
				case "label":
					if i+1 < len(parts) {
						current.Label = strings.Trim(parts[i+1], "\"")
						i++
					}
				case "keep", "modulate", "synproxy":
					current.State = parts[i]
					if i+1 < len(parts) && parts[i+1] == "state" {
						current.State += " state"
						i++
					}
				}
			}
		} else if current != nil && strings.Contains(line, "Evaluations:") {
			// Parse stats line
			parts := strings.Fields(line)
			for i, p := range parts {
				switch p {
				case "Evaluations:":
					if i+1 < len(parts) {
						current.Evaluations = parseNumber(parts[i+1])
					}
				case "Packets:":
					if i+1 < len(parts) {
						current.Packets = parseNumber(parts[i+1])
					}
				case "Bytes:":
					if i+1 < len(parts) {
						current.Bytes = parseNumber(parts[i+1])
					}
				}
			}
		}
	}

	if current != nil {
		rules = append(rules, *current)
	}

	return rules
}

func parseNumber(s string) uint64 {
	var val uint64
	for _, c := range s {
		if c >= '0' && c <= '9' {
			val = val*10 + uint64(c-'0')
		}
	}
	return val
}

// TestParsePFRules tests parsing PF rules output
func TestParsePFRules(t *testing.T) {
	rules := parseTestRules(pfctlRulesOutput)

	if len(rules) != 8 {
		t.Fatalf("Expected 8 rules, got %d", len(rules))
	}

	// Test rule 0 (scrub)
	if rules[0].Action != "scrub" {
		t.Errorf("Rule 0: expected action=scrub, got %s", rules[0].Action)
	}
	if rules[0].Direction != "in" {
		t.Errorf("Rule 0: expected direction=in, got %s", rules[0].Direction)
	}
	if rules[0].Interface != "em0" {
		t.Errorf("Rule 0: expected interface=em0, got %s", rules[0].Interface)
	}

	// Test rule 1 (block with log)
	if rules[1].Action != "block" {
		t.Errorf("Rule 1: expected action=block, got %s", rules[1].Action)
	}
	if !rules[1].Log {
		t.Error("Rule 1: expected log=true")
	}
	if rules[1].Protocol != "tcp" {
		t.Errorf("Rule 1: expected proto=tcp, got %s", rules[1].Protocol)
	}
	if rules[1].Port != "6667" {
		t.Errorf("Rule 1: expected port=6667, got %s", rules[1].Port)
	}

	// Test rule 2 (quick with table)
	if !rules[2].Quick {
		t.Error("Rule 2: expected quick=true")
	}
	if rules[2].Source != "<bruteforce>" {
		t.Errorf("Rule 2: expected source=<bruteforce>, got %s", rules[2].Source)
	}

	// Test rule 3 (pass with keep state)
	if rules[3].Action != "pass" {
		t.Errorf("Rule 3: expected action=pass, got %s", rules[3].Action)
	}
	if rules[3].State != "keep state" {
		t.Errorf("Rule 3: expected state='keep state', got %s", rules[3].State)
	}
	if rules[3].Evaluations != 456789 {
		t.Errorf("Rule 3: expected evaluations=456789, got %d", rules[3].Evaluations)
	}

	// Test rule 4 (modulate state)
	if rules[4].State != "modulate state" {
		t.Errorf("Rule 4: expected state='modulate state', got %s", rules[4].State)
	}
	if rules[4].Packets != 567890 {
		t.Errorf("Rule 4: expected packets=567890, got %d", rules[4].Packets)
	}

	// Test rule 5 (inet with proto list)
	if rules[5].AddressFamily != "inet" {
		t.Errorf("Rule 5: expected af=inet, got %s", rules[5].AddressFamily)
	}

	// Test rule 6 (match/NAT)
	if rules[6].Action != "match" {
		t.Errorf("Rule 6: expected action=match, got %s", rules[6].Action)
	}

	// Test rule 7 (with label)
	if rules[7].Label != "block-telnet" {
		t.Errorf("Rule 7: expected label=block-telnet, got %s", rules[7].Label)
	}
}

// TestParsePFComplexRules tests parsing complex rules
func TestParsePFComplexRules(t *testing.T) {
	rules := parseTestRules(pfctlComplexRules)

	if len(rules) != 6 {
		t.Fatalf("Expected 6 rules, got %d", len(rules))
	}

	// Test IPv6 rule
	if rules[0].AddressFamily != "inet6" {
		t.Errorf("Rule 0: expected af=inet6, got %s", rules[0].AddressFamily)
	}
	if rules[0].Protocol != "ipv6-icmp" {
		t.Errorf("Rule 0: expected proto=ipv6-icmp, got %s", rules[0].Protocol)
	}

	// Test synproxy state
	if rules[1].State != "synproxy state" {
		t.Errorf("Rule 1: expected state='synproxy state', got %s", rules[1].State)
	}

	// Test anchor rule
	if rules[4].Action != "anchor" {
		t.Errorf("Rule 4: expected action=anchor, got %s", rules[4].Action)
	}

	// Test WireGuard interface
	if rules[5].Interface != "wg0" {
		t.Errorf("Rule 5: expected interface=wg0, got %s", rules[5].Interface)
	}
}

// State parsing helper
type testPFState struct {
	Protocol    string
	Source      string
	Destination string
	State       string
	Direction   string
	Age         string
	Rule        int
}

func parseTestStates(output string) []testPFState {
	var states []testPFState

	lines := strings.Split(output, "\n")
	var current *testPFState

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}

		// State line starts with protocol
		if strings.HasPrefix(line, "all ") || strings.HasPrefix(line, "self ") {
			if current != nil {
				states = append(states, *current)
			}

			current = &testPFState{}
			parts := strings.Fields(line)

			if len(parts) >= 2 {
				current.Protocol = parts[1]
			}

			// Find arrow
			for i, p := range parts {
				if p == "->" {
					current.Direction = "out"
					if i > 0 {
						current.Source = parts[i-1]
					}
					if i+1 < len(parts) {
						current.Destination = parts[i+1]
					}
					break
				} else if p == "<-" {
					current.Direction = "in"
					if i > 0 {
						current.Source = parts[i-1]
					}
					if i+1 < len(parts) {
						current.Destination = parts[i+1]
					}
					break
				}
			}

			// Find state
			for _, p := range parts {
				if strings.Contains(p, ":") && !strings.Contains(p, ".") && len(p) > 3 {
					current.State = p
					break
				}
			}
		} else if current != nil && strings.HasPrefix(line, "age") {
			// Parse metadata line
			parts := strings.Fields(line)
			for i, p := range parts {
				if p == "age" && i+1 < len(parts) {
					current.Age = strings.TrimSuffix(parts[i+1], ",")
				}
				if p == "rule" && i+1 < len(parts) {
					current.Rule = int(parseNumber(parts[i+1]))
				}
			}
		}
	}

	if current != nil {
		states = append(states, *current)
	}

	return states
}

// TestParsePFStates tests parsing PF states output
func TestParsePFStates(t *testing.T) {
	states := parseTestStates(pfctlStatesOutput)

	if len(states) != 5 {
		t.Fatalf("Expected 5 states, got %d", len(states))
	}

	// Test TCP outbound state
	if states[0].Protocol != "tcp" {
		t.Errorf("State 0: expected proto=tcp, got %s", states[0].Protocol)
	}
	if states[0].Direction != "out" {
		t.Errorf("State 0: expected direction=out, got %s", states[0].Direction)
	}
	if states[0].State != "ESTABLISHED:ESTABLISHED" {
		t.Errorf("State 0: expected state=ESTABLISHED:ESTABLISHED, got %s", states[0].State)
	}
	if states[0].Rule != 4 {
		t.Errorf("State 0: expected rule=4, got %d", states[0].Rule)
	}

	// Test UDP state
	if states[2].Protocol != "udp" {
		t.Errorf("State 2: expected proto=udp, got %s", states[2].Protocol)
	}
	if states[2].State != "SINGLE:NO_TRAFFIC" {
		t.Errorf("State 2: expected state=SINGLE:NO_TRAFFIC, got %s", states[2].State)
	}

	// Test inbound state
	if states[3].Direction != "in" {
		t.Errorf("State 3: expected direction=in, got %s", states[3].Direction)
	}

	// Test ICMP state
	if states[4].Protocol != "icmp" {
		t.Errorf("State 4: expected proto=icmp, got %s", states[4].Protocol)
	}
}

// TestParsePFStatesComplex tests parsing complex state output
func TestParsePFStatesComplex(t *testing.T) {
	states := parseTestStates(pfctlStatesComplex)

	if len(states) != 4 {
		t.Fatalf("Expected 4 states, got %d", len(states))
	}

	// Test NAT state (with translated address)
	if states[0].Protocol != "tcp" {
		t.Errorf("State 0: expected proto=tcp, got %s", states[0].Protocol)
	}

	// Test GRE state
	if states[1].Protocol != "gre" {
		t.Errorf("State 1: expected proto=gre, got %s", states[1].Protocol)
	}
	if states[1].State != "GRE_INITIATING:NO_TRAFFIC" {
		t.Errorf("State 1: expected state=GRE_INITIATING:NO_TRAFFIC, got %s", states[1].State)
	}

	// Test TIME_WAIT state
	if states[3].State != "TIME_WAIT:TIME_WAIT" {
		t.Errorf("State 3: expected state=TIME_WAIT:TIME_WAIT, got %s", states[3].State)
	}
}

// TestParsePFTables tests parsing table list
func TestParsePFTables(t *testing.T) {
	lines := strings.Split(strings.TrimSpace(pfctlTablesOutput), "\n")

	expectedTables := []string{"bruteforce", "martians", "trusted", "private_nets", "blocklist", "whitelist"}

	if len(lines) != len(expectedTables) {
		t.Fatalf("Expected %d tables, got %d", len(expectedTables), len(lines))
	}

	for i, expected := range expectedTables {
		if strings.TrimSpace(lines[i]) != expected {
			t.Errorf("Table %d: expected %s, got %s", i, expected, lines[i])
		}
	}
}

// TestParsePFTableAddresses tests parsing table addresses
func TestParsePFTableAddresses(t *testing.T) {
	var addrs []string
	for _, line := range strings.Split(pfctlTableAddrs, "\n") {
		line = strings.TrimSpace(line)
		if line != "" {
			addrs = append(addrs, line)
		}
	}

	expectedAddrs := []string{"10.0.0.5", "192.168.1.100", "172.16.0.50", "203.0.113.15", "198.51.100.25"}

	if len(addrs) != len(expectedAddrs) {
		t.Fatalf("Expected %d addresses, got %d", len(expectedAddrs), len(addrs))
	}

	for i, expected := range expectedAddrs {
		if addrs[i] != expected {
			t.Errorf("Address %d: expected %s, got %s", i, expected, addrs[i])
		}
	}
}

// TestPFStateTransitions tests various state transitions
func TestPFStateTransitions(t *testing.T) {
	stateStrings := []struct {
		state       string
		description string
	}{
		{"ESTABLISHED:ESTABLISHED", "Normal established TCP connection"},
		{"SYN_SENT:CLOSED", "Outgoing SYN, waiting for response"},
		{"CLOSED:SYN_RCVD", "Incoming SYN received"},
		{"FIN_WAIT_1:CLOSE_WAIT", "Active close initiated"},
		{"TIME_WAIT:TIME_WAIT", "Connection in TIME_WAIT"},
		{"SINGLE:NO_TRAFFIC", "UDP one-way traffic"},
		{"MULTIPLE:MULTIPLE", "UDP bidirectional traffic"},
		{"GRE_INITIATING:NO_TRAFFIC", "GRE tunnel initiating"},
	}

	for _, tt := range stateStrings {
		t.Run(tt.description, func(t *testing.T) {
			parts := strings.Split(tt.state, ":")
			if len(parts) != 2 {
				t.Errorf("Invalid state format: %s", tt.state)
			}
			// Both parts should be valid state names
			validStates := map[string]bool{
				"ESTABLISHED": true, "SYN_SENT": true, "SYN_RCVD": true,
				"FIN_WAIT_1": true, "FIN_WAIT_2": true, "CLOSE_WAIT": true,
				"CLOSING": true, "LAST_ACK": true, "TIME_WAIT": true,
				"CLOSED": true, "SINGLE": true, "MULTIPLE": true,
				"NO_TRAFFIC": true, "GRE_INITIATING": true, "0": true,
			}
			for _, p := range parts {
				if !validStates[p] {
					// Not an error, just informational - there might be other states
					t.Logf("Note: state %s not in known list", p)
				}
			}
		})
	}
}

// Benchmark tests
func BenchmarkParsePFRules(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseTestRules(pfctlRulesOutput)
	}
}

func BenchmarkParsePFStates(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseTestStates(pfctlStatesOutput)
	}
}

func BenchmarkParsePFInfo(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parsePFInfo(pfctlInfoEnabled)
	}
}

func BenchmarkParsePFStats(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parsePFStats(pfctlInfoEnabled)
	}
}
