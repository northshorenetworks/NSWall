// Package tests provides exhaustive tests for PF parsing functions
package tests

import (
	"regexp"
	"strconv"
	"strings"
	"testing"
)

// =============================================================================
// PFCTL -SR OUTPUT PARSING TESTS
// Testing parseRules and parseRuleLine equivalents
// =============================================================================

// Sample pfctl -sr -vv outputs from real OpenBSD systems
const pfctlRulesVerbose = `@0 scrub in on em0 all fragment reassemble
  [ Evaluations: 1847293       Packets: 1847293       Bytes: 234567890     States: 0     ]
  [ Inserted: uid 0 pid 1 State Creations: 0     ]
@1 scrub out on em0 all random-id fragment reassemble
  [ Evaluations: 923456        Packets: 923456        Bytes: 123456789     States: 0     ]
  [ Inserted: uid 0 pid 1 State Creations: 0     ]
@2 block drop in log on ! lo0 proto tcp from any to any port 0
  [ Evaluations: 5123          Packets: 0             Bytes: 0              States: 0     ]
  [ Inserted: uid 0 pid 1 State Creations: 0     ]
@3 block drop in log all
  [ Evaluations: 234567        Packets: 1234          Bytes: 56789          States: 0     ]
  [ Inserted: uid 0 pid 1 State Creations: 0     ]
@4 pass in quick on em0 inet proto tcp from any to 192.168.1.1 port = 22 flags S/SA keep state
  [ Evaluations: 12345         Packets: 67890         Bytes: 9876543        States: 5     ]
  [ Inserted: uid 0 pid 1 State Creations: 127   ]
@5 pass out on em0 all flags S/SA keep state
  [ Evaluations: 456789        Packets: 345678        Bytes: 123456789      States: 45    ]
  [ Inserted: uid 0 pid 1 State Creations: 8234  ]
@6 pass in on em0 inet proto icmp all icmp-type echoreq keep state
  [ Evaluations: 1234          Packets: 567           Bytes: 34567          States: 2     ]
  [ Inserted: uid 0 pid 1 State Creations: 23    ]
@7 pass in on em0 inet6 proto ipv6-icmp all icmp6-type echoreq keep state
  [ Evaluations: 456           Packets: 123           Bytes: 12345          States: 1     ]
  [ Inserted: uid 0 pid 1 State Creations: 5     ]
@8 block return-rst in on em0 proto tcp from <bruteforce> to any
  [ Evaluations: 234           Packets: 12            Bytes: 720            States: 0     ]
  [ Inserted: uid 0 pid 1 State Creations: 0     ]
@9 pass in on em0 proto tcp from any to any port = 80 synproxy state
  [ Evaluations: 5678          Packets: 23456         Bytes: 12345678       States: 23    ]
  [ Inserted: uid 0 pid 1 State Creations: 456   ]
@10 pass in on em0 proto tcp from any to any port = 443 modulate state
  [ Evaluations: 6789          Packets: 34567         Bytes: 23456789       States: 34    ]
  [ Inserted: uid 0 pid 1 State Creations: 567   ]
`

const pfctlRulesWithAnchors = `@0 anchor "ftp-proxy/*" all
@1 anchor "tftp-proxy/*" all
@2 pass in on em0 inet proto tcp from any to 192.168.1.1 port = 21 divert-to 127.0.0.1 port 8021
  [ Evaluations: 123           Packets: 456           Bytes: 12345          States: 2     ]
@3 anchor "relayd/*" all
`

const pfctlRulesNAT = `@0 match out on em0 inet from 192.168.1.0/24 to any nat-to (em0:0) round-robin
  [ Evaluations: 456789        Packets: 234567        Bytes: 123456789      States: 0     ]
@1 match in on em0 inet proto tcp from any to (em0:0) port = 80 rdr-to 192.168.1.10 port 80
  [ Evaluations: 12345         Packets: 6789          Bytes: 1234567        States: 0     ]
@2 match in on em0 inet proto tcp from any to (em0:0) port = 443 rdr-to 192.168.1.10 port 443
  [ Evaluations: 23456         Packets: 7890          Bytes: 2345678        States: 0     ]
`

const pfctlRulesComplex = `@0 pass in quick on em0 inet proto tcp from <trusted> to (em0:0) port = 22 flags S/SA keep state (max-src-conn 100, max-src-conn-rate 15/5, overload <bruteforce> flush global) label "ssh-trusted"
  [ Evaluations: 1234          Packets: 5678          Bytes: 234567         States: 12    ]
@1 pass in on em0 inet proto tcp from any to (em0:0) port 80 >< 90 flags S/SA keep state label "web-range"
  [ Evaluations: 567           Packets: 234           Bytes: 12345          States: 3     ]
@2 pass in log on egress inet proto tcp from any to any port { 22 80 443 } keep state
  [ Evaluations: 12345         Packets: 67890         Bytes: 3456789        States: 45    ]
@3 block return log (all) quick on em0 inet proto tcp from <blocked> to any port = 25
  [ Evaluations: 234           Packets: 56            Bytes: 2345           States: 0     ]
`

// PFRuleData represents parsed rule data for testing
type PFRuleData struct {
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
	State         string
	Label         string
	Evaluations   uint64
	Packets       uint64
	Bytes         uint64
}

// parseTestRules parses pfctl -sr -vv output for testing
func parseTestRules(output string) []PFRuleData {
	var rules []PFRuleData
	var current *PFRuleData

	lines := strings.Split(output, "\n")

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}

		if strings.HasPrefix(line, "@") {
			if current != nil {
				rules = append(rules, *current)
			}

			current = &PFRuleData{}
			parseTestRuleLine(line, current)
		} else if current != nil && strings.HasPrefix(line, "[") {
			// Statistics line
			re := regexp.MustCompile(`Evaluations:\s*(\d+)\s+Packets:\s*(\d+)\s+Bytes:\s*(\d+)`)
			if matches := re.FindStringSubmatch(line); len(matches) > 3 {
				current.Evaluations, _ = strconv.ParseUint(matches[1], 10, 64)
				current.Packets, _ = strconv.ParseUint(matches[2], 10, 64)
				current.Bytes, _ = strconv.ParseUint(matches[3], 10, 64)
			}
		}
	}

	if current != nil {
		rules = append(rules, *current)
	}

	return rules
}

// parseTestRuleLine parses a single rule line for testing
func parseTestRuleLine(line string, rule *PFRuleData) {
	// Extract rule number
	if idx := strings.Index(line, " "); idx > 0 {
		numStr := strings.TrimPrefix(line[:idx], "@")
		rule.Number, _ = strconv.Atoi(numStr)
		line = strings.TrimSpace(line[idx+1:])
	}

	parts := strings.Fields(line)
	if len(parts) == 0 {
		return
	}

	i := 0

	// Action (may include block type like "block drop" or "block return-rst")
	if i < len(parts) {
		switch parts[i] {
		case "pass":
			rule.Action = "pass"
			i++
		case "block":
			rule.Action = "block"
			i++
			// Check for block type
			if i < len(parts) {
				switch parts[i] {
				case "drop", "return", "return-rst", "return-icmp", "return-icmp6":
					rule.Action += " " + parts[i]
					i++
				}
			}
		case "match":
			rule.Action = "match"
			i++
		case "scrub":
			rule.Action = "scrub"
			i++
		case "anchor":
			rule.Action = "anchor"
			i++
			return // Anchors have special format
		}
	}

	// Parse remaining tokens
	for i < len(parts) {
		switch parts[i] {
		case "in":
			rule.Direction = "in"
			i++
		case "out":
			rule.Direction = "out"
			i++
		case "log":
			rule.Log = true
			i++
			// Skip log options like (all)
			if i < len(parts) && strings.HasPrefix(parts[i], "(") {
				for i < len(parts) && !strings.HasSuffix(parts[i], ")") {
					i++
				}
				i++
			}
		case "quick":
			rule.Quick = true
			i++
		case "on":
			i++
			if i < len(parts) {
				// Handle negation
				if parts[i] == "!" {
					i++
					if i < len(parts) {
						rule.Interface = "!" + parts[i]
						i++
					}
				} else {
					rule.Interface = parts[i]
					i++
				}
			}
		case "inet":
			rule.AddressFamily = "inet"
			i++
		case "inet6":
			rule.AddressFamily = "inet6"
			i++
		case "proto":
			i++
			if i < len(parts) {
				rule.Protocol = parts[i]
				i++
			}
		case "from":
			i++
			if i < len(parts) {
				rule.Source = parts[i]
				i++
				// Handle port after from
				if i < len(parts) && parts[i] == "port" {
					i++
					if i < len(parts) {
						rule.Source += " port " + parts[i]
						i++
					}
				}
			}
		case "to":
			i++
			if i < len(parts) {
				rule.Destination = parts[i]
				i++
				// Handle port after to
				if i < len(parts) && parts[i] == "port" {
					i++
					if i < len(parts) {
						// Handle port with operator (=, ><, etc.)
						portStr := parts[i]
						i++
						// If operator is separate token
						if i < len(parts) && (portStr == "=" || portStr == ">" || portStr == "<" || portStr == "><") {
							portStr += " " + parts[i]
							i++
						}
						rule.Port = portStr
					}
				}
			}
		case "flags":
			i++
			if i < len(parts) {
				i++ // Skip flags value
			}
		case "keep", "modulate", "synproxy":
			rule.State = parts[i]
			i++
			if i < len(parts) && parts[i] == "state" {
				rule.State += " state"
				i++
			}
		case "label":
			i++
			if i < len(parts) {
				rule.Label = strings.Trim(parts[i], "\"")
				i++
			}
		case "all":
			i++
		default:
			// Handle state options in parentheses
			if strings.HasPrefix(parts[i], "(") {
				depth := 1
				for i < len(parts) && depth > 0 {
					if strings.HasSuffix(parts[i], ")") {
						depth--
					}
					i++
				}
			} else {
				i++
			}
		}
	}
}

func TestParseRulesVerbose(t *testing.T) {
	rules := parseTestRules(pfctlRulesVerbose)

	if len(rules) != 11 {
		t.Errorf("Expected 11 rules, got %d", len(rules))
	}

	// Test first rule (scrub)
	if rules[0].Number != 0 {
		t.Errorf("Rule 0: expected number 0, got %d", rules[0].Number)
	}
	if rules[0].Action != "scrub" {
		t.Errorf("Rule 0: expected action scrub, got %s", rules[0].Action)
	}
	if rules[0].Direction != "in" {
		t.Errorf("Rule 0: expected direction in, got %s", rules[0].Direction)
	}

	// Test block rule
	if rules[3].Action != "block drop" {
		t.Errorf("Rule 3: expected 'block drop', got %s", rules[3].Action)
	}
	if !rules[3].Log {
		t.Errorf("Rule 3: expected log=true")
	}
	if rules[3].Packets != 1234 {
		t.Errorf("Rule 3: expected packets=1234, got %d", rules[3].Packets)
	}

	// Test pass with quick and keep state
	if rules[4].Number != 4 {
		t.Errorf("Rule 4: expected number 4, got %d", rules[4].Number)
	}
	if !rules[4].Quick {
		t.Errorf("Rule 4: expected quick=true")
	}
	if rules[4].Protocol != "tcp" {
		t.Errorf("Rule 4: expected protocol tcp, got %s", rules[4].Protocol)
	}
	if rules[4].State != "keep state" {
		t.Errorf("Rule 4: expected 'keep state', got %s", rules[4].State)
	}

	// Test synproxy state
	if rules[9].State != "synproxy state" {
		t.Errorf("Rule 9: expected 'synproxy state', got %s", rules[9].State)
	}

	// Test modulate state
	if rules[10].State != "modulate state" {
		t.Errorf("Rule 10: expected 'modulate state', got %s", rules[10].State)
	}
}

func TestParseRulesWithAnchors(t *testing.T) {
	rules := parseTestRules(pfctlRulesWithAnchors)

	if len(rules) != 4 {
		t.Errorf("Expected 4 rules, got %d", len(rules))
	}

	// First three should be anchors
	if rules[0].Action != "anchor" {
		t.Errorf("Rule 0: expected action anchor, got %s", rules[0].Action)
	}
	if rules[1].Action != "anchor" {
		t.Errorf("Rule 1: expected action anchor, got %s", rules[1].Action)
	}

	// Rule 2 should be a pass rule
	if rules[2].Action != "pass" {
		t.Errorf("Rule 2: expected action pass, got %s", rules[2].Action)
	}
}

func TestParseRulesNAT(t *testing.T) {
	rules := parseTestRules(pfctlRulesNAT)

	if len(rules) != 3 {
		t.Errorf("Expected 3 rules, got %d", len(rules))
	}

	// All should be match rules
	for i, r := range rules {
		if r.Action != "match" {
			t.Errorf("Rule %d: expected action match, got %s", i, r.Action)
		}
	}

	// Check NAT rule
	if rules[0].Direction != "out" {
		t.Errorf("Rule 0: expected direction out, got %s", rules[0].Direction)
	}
	if rules[0].Source != "192.168.1.0/24" {
		t.Errorf("Rule 0: expected source 192.168.1.0/24, got %s", rules[0].Source)
	}

	// Check RDR rules
	if rules[1].Direction != "in" {
		t.Errorf("Rule 1: expected direction in, got %s", rules[1].Direction)
	}
}

func TestParseRulesComplex(t *testing.T) {
	rules := parseTestRules(pfctlRulesComplex)

	if len(rules) != 4 {
		t.Errorf("Expected 4 rules, got %d", len(rules))
	}

	// Rule with label
	if rules[0].Label != "ssh-trusted" {
		t.Errorf("Rule 0: expected label 'ssh-trusted', got '%s'", rules[0].Label)
	}
	if !rules[0].Quick {
		t.Errorf("Rule 0: expected quick=true")
	}
	if rules[0].Source != "<trusted>" {
		t.Errorf("Rule 0: expected source '<trusted>', got '%s'", rules[0].Source)
	}

	// Rule with port range
	if rules[1].Label != "web-range" {
		t.Errorf("Rule 1: expected label 'web-range', got '%s'", rules[1].Label)
	}

	// Block return rule
	if rules[3].Action != "block return" {
		t.Errorf("Rule 3: expected 'block return', got %s", rules[3].Action)
	}
	if !rules[3].Log {
		t.Errorf("Rule 3: expected log=true")
	}
	if !rules[3].Quick {
		t.Errorf("Rule 3: expected quick=true")
	}
}

// =============================================================================
// PFCTL -SS OUTPUT PARSING TESTS
// Testing parseStates and parseStateLine equivalents
// =============================================================================

const pfctlStatesOutput = `all tcp 192.168.1.100:45678 -> 93.184.216.34:443       ESTABLISHED:ESTABLISHED
   age 00:05:23, expires in 23:54:37, 12345:6789 pkts, 1234567:789012 bytes, rule 5
all tcp 192.168.1.100:45679 -> 93.184.216.34:80        ESTABLISHED:ESTABLISHED
   age 00:02:15, expires in 23:57:45, 567:234 pkts, 45678:12345 bytes, rule 5
all udp 192.168.1.100:12345 -> 8.8.8.8:53              MULTIPLE:SINGLE
   age 00:00:03, expires in 00:00:57, 2:1 pkts, 120:80 bytes, rule 7
all icmp 192.168.1.100:1234 -> 8.8.8.8:0               0:0
   age 00:00:01, expires in 00:00:19, 1:0 pkts, 84:0 bytes, rule 6
all gre 192.168.1.100 -> 203.0.113.1                   MULTIPLE:MULTIPLE
   age 00:15:34, expires in 00:44:26, 5678:4567 pkts, 567890:456789 bytes, rule 8
all esp 192.168.1.100 -> 203.0.113.1                   NO_TRAFFIC:NO_TRAFFIC
   age 00:10:00, expires in 00:50:00, 0:0 pkts, 0:0 bytes, rule 9
all tcp 192.168.1.100:22 <- 10.0.0.50:54321            ESTABLISHED:ESTABLISHED
   age 01:23:45, expires in 22:36:15, 98765:87654 pkts, 9876543:8765432 bytes, rule 4
all tcp 192.168.1.100:80 (192.168.1.1:80) <- 10.0.0.100:56789       TIME_WAIT:TIME_WAIT
   age 00:00:30, expires in 00:01:30, 100:50 pkts, 10000:5000 bytes, rule 10
`

const pfctlStatesVerbose = `all tcp 192.168.1.100:45678 (em0) -> 93.184.216.34:443 (em0)       ESTABLISHED:ESTABLISHED
   age 00:05:23, expires in 23:54:37, 12345:6789 pkts, 1234567:789012 bytes, rule 5
   id: 0123456789abcdef creatorid: 00000001 gateway: 192.168.1.1
all tcp 192.168.1.100:45679 (em0) -> 93.184.216.34:80 (em0)        FIN_WAIT_2:FIN_WAIT_2
   age 00:02:15, expires in 00:00:45, 567:234 pkts, 45678:12345 bytes, rule 5
   id: 0123456789abcdf0 creatorid: 00000001
all tcp 192.168.1.100:22 (em1) <- 10.0.0.50:54321 (em0)            SYN_SENT:CLOSED
   age 00:00:01, expires in 00:00:29, 1:0 pkts, 60:0 bytes, rule 4
   id: 0123456789abcdf1 creatorid: 00000001
`

// PFStateData represents parsed state data for testing
type PFStateData struct {
	Protocol    string
	Direction   string
	Source      string
	Destination string
	Gateway     string
	State       string
	Age         string
	Expires     string
	PacketsIn   uint64
	PacketsOut  uint64
	BytesIn     uint64
	BytesOut    uint64
	Rule        int
	ID          string
	Interface   string
}

// parseTestStates parses pfctl -ss output for testing
func parseTestStates(output string) []PFStateData {
	var states []PFStateData
	var current *PFStateData

	lines := strings.Split(output, "\n")

	for _, line := range lines {
		if line == "" {
			continue
		}

		// Main state line starts with "all" or interface name
		if strings.HasPrefix(line, "all ") || (len(line) > 0 && line[0] != ' ') {
			if current != nil {
				states = append(states, *current)
			}
			current = parseTestStateLine(line)
		} else if current != nil && strings.HasPrefix(line, "   ") {
			// Extended info line
			parseTestStateExtended(strings.TrimSpace(line), current)
		}
	}

	if current != nil {
		states = append(states, *current)
	}

	return states
}

func parseTestStateLine(line string) *PFStateData {
	state := &PFStateData{}

	// Remove leading "all "
	if strings.HasPrefix(line, "all ") {
		line = line[4:]
	}

	parts := strings.Fields(line)
	if len(parts) < 4 {
		return nil
	}

	state.Protocol = parts[0]

	// Find arrow direction
	arrowIdx := -1
	for i, p := range parts {
		if p == "->" || p == "<-" {
			arrowIdx = i
			if p == "->" {
				state.Direction = "out"
			} else {
				state.Direction = "in"
			}
			break
		}
	}

	if arrowIdx < 1 || arrowIdx >= len(parts)-1 {
		return nil
	}

	// Source is before arrow
	state.Source = parts[arrowIdx-1]
	// Strip interface info like (em0)
	if idx := strings.Index(state.Source, "("); idx > 0 {
		state.Interface = strings.Trim(state.Source[idx:], "()")
		state.Source = state.Source[:idx]
	}

	// Destination is after arrow
	state.Destination = parts[arrowIdx+1]
	// Strip interface info
	if idx := strings.Index(state.Destination, "("); idx > 0 {
		state.Destination = state.Destination[:idx]
	}
	// Handle NAT'd addresses like (192.168.1.1:80)
	if arrowIdx+2 < len(parts) && strings.HasPrefix(parts[arrowIdx+2], "(") {
		// This is the original destination before NAT
	}

	// State is last field
	for i := arrowIdx + 2; i < len(parts); i++ {
		if strings.Contains(parts[i], ":") && !strings.Contains(parts[i], ".") {
			state.State = parts[i]
			break
		}
	}

	return state
}

func parseTestStateExtended(line string, state *PFStateData) {
	// Parse age, expires, packets, bytes, rule
	if strings.HasPrefix(line, "age ") {
		// Parse: age 00:05:23, expires in 23:54:37, 12345:6789 pkts, 1234567:789012 bytes, rule 5
		parts := strings.Split(line, ", ")
		for _, p := range parts {
			p = strings.TrimSpace(p)
			if strings.HasPrefix(p, "age ") {
				state.Age = strings.TrimPrefix(p, "age ")
			} else if strings.HasPrefix(p, "expires in ") {
				state.Expires = strings.TrimPrefix(p, "expires in ")
			} else if strings.HasSuffix(p, " pkts") {
				pktStr := strings.TrimSuffix(p, " pkts")
				if idx := strings.Index(pktStr, ":"); idx > 0 {
					state.PacketsOut, _ = strconv.ParseUint(pktStr[:idx], 10, 64)
					state.PacketsIn, _ = strconv.ParseUint(pktStr[idx+1:], 10, 64)
				}
			} else if strings.HasSuffix(p, " bytes") {
				byteStr := strings.TrimSuffix(p, " bytes")
				if idx := strings.Index(byteStr, ":"); idx > 0 {
					state.BytesOut, _ = strconv.ParseUint(byteStr[:idx], 10, 64)
					state.BytesIn, _ = strconv.ParseUint(byteStr[idx+1:], 10, 64)
				}
			} else if strings.HasPrefix(p, "rule ") {
				state.Rule, _ = strconv.Atoi(strings.TrimPrefix(p, "rule "))
			}
		}
	} else if strings.HasPrefix(line, "id: ") {
		// Parse: id: 0123456789abcdef creatorid: 00000001 gateway: 192.168.1.1
		parts := strings.Fields(line)
		for i := 0; i < len(parts)-1; i += 2 {
			key := strings.TrimSuffix(parts[i], ":")
			val := parts[i+1]
			switch key {
			case "id":
				state.ID = val
			case "gateway":
				state.Gateway = val
			}
		}
	}
}

func TestParseStates(t *testing.T) {
	states := parseTestStates(pfctlStatesOutput)

	if len(states) != 8 {
		t.Errorf("Expected 8 states, got %d", len(states))
	}

	// Test first TCP state
	if states[0].Protocol != "tcp" {
		t.Errorf("State 0: expected protocol tcp, got %s", states[0].Protocol)
	}
	if states[0].Source != "192.168.1.100:45678" {
		t.Errorf("State 0: expected source 192.168.1.100:45678, got %s", states[0].Source)
	}
	if states[0].Destination != "93.184.216.34:443" {
		t.Errorf("State 0: expected dest 93.184.216.34:443, got %s", states[0].Destination)
	}
	if states[0].State != "ESTABLISHED:ESTABLISHED" {
		t.Errorf("State 0: expected state ESTABLISHED:ESTABLISHED, got %s", states[0].State)
	}
	if states[0].Age != "00:05:23" {
		t.Errorf("State 0: expected age 00:05:23, got %s", states[0].Age)
	}
	if states[0].PacketsOut != 12345 {
		t.Errorf("State 0: expected packetsOut 12345, got %d", states[0].PacketsOut)
	}
	if states[0].Rule != 5 {
		t.Errorf("State 0: expected rule 5, got %d", states[0].Rule)
	}

	// Test UDP state
	if states[2].Protocol != "udp" {
		t.Errorf("State 2: expected protocol udp, got %s", states[2].Protocol)
	}
	if states[2].State != "MULTIPLE:SINGLE" {
		t.Errorf("State 2: expected state MULTIPLE:SINGLE, got %s", states[2].State)
	}

	// Test ICMP state
	if states[3].Protocol != "icmp" {
		t.Errorf("State 3: expected protocol icmp, got %s", states[3].Protocol)
	}

	// Test GRE state (no ports)
	if states[4].Protocol != "gre" {
		t.Errorf("State 4: expected protocol gre, got %s", states[4].Protocol)
	}
	if states[4].Source != "192.168.1.100" {
		t.Errorf("State 4: expected source 192.168.1.100, got %s", states[4].Source)
	}

	// Test ESP state
	if states[5].Protocol != "esp" {
		t.Errorf("State 5: expected protocol esp, got %s", states[5].Protocol)
	}
	if states[5].State != "NO_TRAFFIC:NO_TRAFFIC" {
		t.Errorf("State 5: expected state NO_TRAFFIC:NO_TRAFFIC, got %s", states[5].State)
	}

	// Test inbound state (<-)
	if states[6].Direction != "in" {
		t.Errorf("State 6: expected direction in, got %s", states[6].Direction)
	}
}

func TestParseStatesVerbose(t *testing.T) {
	states := parseTestStates(pfctlStatesVerbose)

	if len(states) != 3 {
		t.Errorf("Expected 3 states, got %d", len(states))
	}

	// Check ID and gateway
	if states[0].ID != "0123456789abcdef" {
		t.Errorf("State 0: expected ID 0123456789abcdef, got %s", states[0].ID)
	}
	if states[0].Gateway != "192.168.1.1" {
		t.Errorf("State 0: expected gateway 192.168.1.1, got %s", states[0].Gateway)
	}

	// Check different state types
	if states[1].State != "FIN_WAIT_2:FIN_WAIT_2" {
		t.Errorf("State 1: expected state FIN_WAIT_2:FIN_WAIT_2, got %s", states[1].State)
	}
	if states[2].State != "SYN_SENT:CLOSED" {
		t.Errorf("State 2: expected state SYN_SENT:CLOSED, got %s", states[2].State)
	}
}

// =============================================================================
// PFCTL -ST OUTPUT PARSING TESTS
// =============================================================================

const pfctlTablesOutput = `bruteforce
blocked
trusted
martians
spamd-white`

const pfctlTableShowOutput = `   10.0.0.1
   10.0.0.2
   10.0.0.0/24
   !10.0.0.100
   192.168.1.0/24
   2001:db8::/32`

const pfctlTableShowVerboseOutput = `   10.0.0.1
	Cleared:     Thu Jan  1 00:00:00 1970
	In/Block:    [ Packets: 0               Bytes: 0               ]
	In/Pass:     [ Packets: 0               Bytes: 0               ]
	Out/Block:   [ Packets: 0               Bytes: 0               ]
	Out/Pass:    [ Packets: 0               Bytes: 0               ]
   10.0.0.2
	Cleared:     Thu Jan  1 00:00:00 1970
	In/Block:    [ Packets: 123             Bytes: 12345           ]
	In/Pass:     [ Packets: 456             Bytes: 45678           ]
	Out/Block:   [ Packets: 78              Bytes: 7890            ]
	Out/Pass:    [ Packets: 901             Bytes: 90123           ]`

type PFTableData struct {
	Name      string
	Addresses []string
}

type PFTableEntry struct {
	Address      string
	InBlockPkts  uint64
	InBlockBytes uint64
	InPassPkts   uint64
	InPassBytes  uint64
	OutBlockPkts uint64
	OutBlockBytes uint64
	OutPassPkts  uint64
	OutPassBytes uint64
}

func parseTestTables(output string) []string {
	var tables []string
	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line != "" {
			tables = append(tables, line)
		}
	}
	return tables
}

func parseTestTableAddresses(output string) []string {
	var addresses []string
	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line != "" && !strings.HasPrefix(line, "Cleared:") &&
			!strings.HasPrefix(line, "In/") && !strings.HasPrefix(line, "Out/") {
			addresses = append(addresses, line)
		}
	}
	return addresses
}

func TestParseTables(t *testing.T) {
	tables := parseTestTables(pfctlTablesOutput)

	expected := []string{"bruteforce", "blocked", "trusted", "martians", "spamd-white"}
	if len(tables) != len(expected) {
		t.Errorf("Expected %d tables, got %d", len(expected), len(tables))
	}

	for i, exp := range expected {
		if tables[i] != exp {
			t.Errorf("Table %d: expected %s, got %s", i, exp, tables[i])
		}
	}
}

func TestParseTableAddresses(t *testing.T) {
	addresses := parseTestTableAddresses(pfctlTableShowOutput)

	expected := []string{"10.0.0.1", "10.0.0.2", "10.0.0.0/24", "!10.0.0.100", "192.168.1.0/24", "2001:db8::/32"}
	if len(addresses) != len(expected) {
		t.Errorf("Expected %d addresses, got %d", len(expected), len(addresses))
	}

	for i, exp := range expected {
		if addresses[i] != exp {
			t.Errorf("Address %d: expected %s, got %s", i, exp, addresses[i])
		}
	}
}

// =============================================================================
// EDGE CASES AND STRESS TESTS
// =============================================================================

func TestParseEmptyOutput(t *testing.T) {
	rules := parseTestRules("")
	if len(rules) != 0 {
		t.Errorf("Expected 0 rules from empty output, got %d", len(rules))
	}

	states := parseTestStates("")
	if len(states) != 0 {
		t.Errorf("Expected 0 states from empty output, got %d", len(states))
	}

	tables := parseTestTables("")
	if len(tables) != 0 {
		t.Errorf("Expected 0 tables from empty output, got %d", len(tables))
	}
}

func TestParseMalformedOutput(t *testing.T) {
	// Malformed rule lines
	malformedRules := `@0 this is not a valid rule line
@1
@2 pass in`

	rules := parseTestRules(malformedRules)
	// Should not panic, may return partial data
	if len(rules) < 1 {
		t.Logf("Parsed %d rules from malformed output", len(rules))
	}

	// Malformed state lines
	malformedStates := `all tcp incomplete
   partial info
random garbage`

	states := parseTestStates(malformedStates)
	// Should not panic
	t.Logf("Parsed %d states from malformed output", len(states))
}

func TestParseVeryLongRule(t *testing.T) {
	// Very long rule with many options
	longRule := `@0 pass in log (all) quick on em0 inet proto tcp from <very_long_table_name_with_many_characters> to (em0:0) port { 22 80 443 8080 8443 9000 9001 9002 } flags S/SA keep state (max 10000, source-track rule, max-src-nodes 1000, max-src-states 100, max-src-conn 50, max-src-conn-rate 25/30, overload <another_very_long_table_name> flush global, if-bound, pflow) label "this_is_a_very_long_label_for_a_firewall_rule"
  [ Evaluations: 999999999     Packets: 888888888     Bytes: 777777777777  States: 66666 ]`

	rules := parseTestRules(longRule)
	if len(rules) != 1 {
		t.Errorf("Expected 1 rule, got %d", len(rules))
	}
	if rules[0].Quick != true {
		t.Error("Expected quick=true for long rule")
	}
	if rules[0].Log != true {
		t.Error("Expected log=true for long rule")
	}
}

func TestParseIPv6Addresses(t *testing.T) {
	ipv6Rules := `@0 pass in inet6 proto tcp from 2001:db8::1 to 2001:db8::2 port = 80
@1 pass in inet6 proto tcp from 2001:db8::/32 to any
@2 pass in inet6 proto ipv6-icmp icmp6-type { 128 129 133 134 135 136 }`

	rules := parseTestRules(ipv6Rules)
	if len(rules) != 3 {
		t.Errorf("Expected 3 IPv6 rules, got %d", len(rules))
	}

	for _, r := range rules {
		if r.AddressFamily != "inet6" {
			t.Errorf("Expected inet6 address family, got %s", r.AddressFamily)
		}
	}
}

// =============================================================================
// BENCHMARK TESTS
// =============================================================================

func BenchmarkParseRules(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseTestRules(pfctlRulesVerbose)
	}
}

func BenchmarkParseStates(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseTestStates(pfctlStatesOutput)
	}
}

func BenchmarkParseTables(b *testing.B) {
	for i := 0; i < b.N; i++ {
		parseTestTables(pfctlTablesOutput)
	}
}

func BenchmarkParseLargeStateTable(b *testing.B) {
	// Generate a large state table
	var builder strings.Builder
	for i := 0; i < 10000; i++ {
		builder.WriteString(fmt.Sprintf("all tcp 192.168.1.%d:%d -> 10.0.0.%d:%d       ESTABLISHED:ESTABLISHED\n",
			i%256, 10000+i, i%256, 80+i%100))
		builder.WriteString(fmt.Sprintf("   age 00:%02d:%02d, expires in 23:%02d:%02d, %d:%d pkts, %d:%d bytes, rule 5\n",
			i%60, i%60, i%60, i%60, i*100, i*50, i*10000, i*5000))
	}
	largeOutput := builder.String()

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		parseTestStates(largeOutput)
	}
}
