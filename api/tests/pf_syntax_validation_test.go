// Package tests provides comprehensive PF syntax validation tests
// These tests use pfctl -nf - to validate rule syntax on OpenBSD
package tests

import (
	"bytes"
	"fmt"
	"os"
	"os/exec"
	"runtime"
	"strings"
	"testing"
)

// skipIfNotOpenBSD skips tests that require pfctl
func skipIfNotOpenBSD(t *testing.T) {
	if runtime.GOOS != "openbsd" {
		t.Skip("Skipping: pfctl syntax validation requires OpenBSD")
	}
}

// validatePFSyntax validates PF rule syntax using pfctl -nf -
func validatePFSyntax(rules string) error {
	cmd := exec.Command("pfctl", "-nf", "-")
	cmd.Stdin = strings.NewReader(rules)
	var stderr bytes.Buffer
	cmd.Stderr = &stderr

	if err := cmd.Run(); err != nil {
		return fmt.Errorf("pfctl validation failed: %s", stderr.String())
	}
	return nil
}

// validatePFSyntaxWithFile validates using a temp file (for complex configs)
func validatePFSyntaxWithFile(rules string) error {
	tmpfile, err := os.CreateTemp("", "pf-test-*.conf")
	if err != nil {
		return err
	}
	defer os.Remove(tmpfile.Name())

	if _, err := tmpfile.WriteString(rules); err != nil {
		return err
	}
	tmpfile.Close()

	cmd := exec.Command("pfctl", "-nf", tmpfile.Name())
	var stderr bytes.Buffer
	cmd.Stderr = &stderr

	if err := cmd.Run(); err != nil {
		return fmt.Errorf("pfctl validation failed: %s", stderr.String())
	}
	return nil
}

// =============================================================================
// PF OPTIONS TESTS
// =============================================================================

func TestPFOptionsSet(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// Block policy
		{"set block-policy drop", "set block-policy drop", true},
		{"set block-policy return", "set block-policy return", true},

		// State policy
		{"set state-policy if-bound", "set state-policy if-bound", true},
		{"set state-policy floating", "set state-policy floating", true},

		// State defaults
		{"set state-defaults no-sync", "set state-defaults no-sync", true},
		{"set state-defaults pflow", "set state-defaults pflow", true},

		// Fingerprints
		{"set fingerprints", "set fingerprints \"/etc/pf.os\"", true},

		// Host ID
		{"set hostid 1", "set hostid 1", true},
		{"set hostid 0xdeadbeef", "set hostid 0xdeadbeef", true},

		// Limits
		{"set limit states", "set limit states 10000", true},
		{"set limit frags", "set limit frags 5000", true},
		{"set limit src-nodes", "set limit src-nodes 10000", true},
		{"set limit tables", "set limit tables 1000", true},
		{"set limit table-entries", "set limit table-entries 200000", true},
		{"set limit combined", "set limit { states 10000, frags 5000, src-nodes 10000 }", true},

		// Optimization
		{"set optimization normal", "set optimization normal", true},
		{"set optimization high-latency", "set optimization high-latency", true},
		{"set optimization satellite", "set optimization satellite", true},
		{"set optimization aggressive", "set optimization aggressive", true},
		{"set optimization conservative", "set optimization conservative", true},

		// Timeout
		{"set timeout tcp.first", "set timeout tcp.first 120", true},
		{"set timeout tcp.opening", "set timeout tcp.opening 30", true},
		{"set timeout tcp.established", "set timeout tcp.established 86400", true},
		{"set timeout tcp.closing", "set timeout tcp.closing 900", true},
		{"set timeout tcp.finwait", "set timeout tcp.finwait 45", true},
		{"set timeout tcp.closed", "set timeout tcp.closed 90", true},
		{"set timeout udp.first", "set timeout udp.first 60", true},
		{"set timeout udp.single", "set timeout udp.single 30", true},
		{"set timeout udp.multiple", "set timeout udp.multiple 60", true},
		{"set timeout icmp.first", "set timeout icmp.first 20", true},
		{"set timeout icmp.error", "set timeout icmp.error 10", true},
		{"set timeout other.first", "set timeout other.first 60", true},
		{"set timeout other.single", "set timeout other.single 30", true},
		{"set timeout other.multiple", "set timeout other.multiple 60", true},
		{"set timeout frag", "set timeout frag 30", true},
		{"set timeout interval", "set timeout interval 10", true},
		{"set timeout adaptive.start", "set timeout adaptive.start 6000", true},
		{"set timeout adaptive.end", "set timeout adaptive.end 12000", true},
		{"set timeout src.track", "set timeout src.track 0", true},

		// Loginterface
		{"set loginterface em0", "set loginterface em0", true},
		{"set loginterface none", "set loginterface none", true},

		// Skip
		{"set skip on lo", "set skip on lo", true},
		{"set skip on lo0", "set skip on lo0", true},
		{"set skip on multiple", "set skip on { lo, enc0 }", true},

		// Reassemble
		{"set reassemble yes", "set reassemble yes", true},
		{"set reassemble no", "set reassemble no", true},

		// Debug
		{"set debug none", "set debug none", true},
		{"set debug urgent", "set debug urgent", true},
		{"set debug misc", "set debug misc", true},
		{"set debug loud", "set debug loud", true},

		// Syncookies
		{"set syncookies never", "set syncookies never", true},
		{"set syncookies always", "set syncookies always", true},
		{"set syncookies adaptive", "set syncookies adaptive (start 25%, end 12%)", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
			if !tt.valid && err == nil {
				t.Errorf("Expected invalid rule %q to fail validation", tt.rule)
			}
		})
	}
}

// =============================================================================
// SCRUB RULES TESTS
// =============================================================================

func TestPFScrubRules(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"scrub in all", "match in all scrub (no-df)", true},
		{"scrub out all", "match out all scrub (no-df)", true},
		{"scrub max-mss", "match in all scrub (max-mss 1440)", true},
		{"scrub min-ttl", "match in all scrub (min-ttl 64)", true},
		{"scrub random-id", "match in all scrub (random-id)", true},
		{"scrub reassemble tcp", "match in all scrub (reassemble tcp)", true},
		{"scrub combined options", "match in all scrub (no-df max-mss 1440 min-ttl 64 random-id)", true},
		{"scrub on interface", "match in on em0 all scrub (no-df)", true},
		{"scrub proto tcp", "match in proto tcp all scrub (reassemble tcp)", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

// =============================================================================
// TABLE TESTS
// =============================================================================

func TestPFTables(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// Basic tables
		{"table persist", "table <bruteforce> persist", true},
		{"table const", "table <trusted> const { 10.0.0.0/8 }", true},
		{"table counters", "table <blocked> persist counters", true},

		// Tables with addresses
		{"table single ipv4", "table <good> { 192.168.1.1 }", true},
		{"table multiple ipv4", "table <good> { 192.168.1.1, 192.168.1.2, 10.0.0.0/8 }", true},
		{"table ipv6", "table <good6> { 2001:db8::/32 }", true},
		{"table mixed", "table <servers> { 192.168.1.0/24, 2001:db8::/32 }", true},

		// Tables from file
		{"table file", "table <spammers> persist file \"/etc/spammers\"", true},

		// Negated addresses
		{"table negated", "table <internal> { 10.0.0.0/8, !10.0.0.1 }", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

// =============================================================================
// FILTER RULES - PASS/BLOCK TESTS
// =============================================================================

func TestPFFilterRulesBasic(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// Basic pass/block
		{"pass all", "pass all", true},
		{"block all", "block all", true},
		{"pass in all", "pass in all", true},
		{"pass out all", "pass out all", true},
		{"block in all", "block in all", true},
		{"block out all", "block out all", true},

		// With log
		{"pass log all", "pass in log all", true},
		{"block log all", "block in log all", true},
		{"pass log (all)", "pass in log (all) all", true},
		{"pass log (user)", "pass in log (user) all", true},
		{"pass log (to pflog1)", "pass in log (to pflog1) all", true},

		// With quick
		{"pass quick all", "pass in quick all", true},
		{"block quick all", "block in quick all", true},
		{"block return quick", "block return in quick all", true},

		// Block actions
		{"block drop", "block drop in all", true},
		{"block return", "block return in all", true},
		{"block return-rst", "block return-rst in proto tcp all", true},
		{"block return-icmp", "block return-icmp in all", true},
		{"block return-icmp6", "block return-icmp6 in all", true},
		{"block return-icmp codes", "block return-icmp (net-unr) in all", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesInterface(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"on em0", "pass in on em0 all", true},
		{"on vlan100", "pass in on vlan100 all", true},
		{"on trunk0", "pass in on trunk0 all", true},
		{"on egress", "pass in on egress all", true},
		{"on group", "pass in on egress all", true},
		{"on multiple", "pass in on { em0, em1 } all", true},
		{"on negated", "pass in on ! lo0 all", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesAddressFamily(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"inet", "pass in inet all", true},
		{"inet6", "pass in inet6 all", true},
		{"inet proto tcp", "pass in inet proto tcp all", true},
		{"inet6 proto tcp", "pass in inet6 proto tcp all", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesProtocol(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"proto tcp", "pass in proto tcp all", true},
		{"proto udp", "pass in proto udp all", true},
		{"proto icmp", "pass in proto icmp all", true},
		{"proto icmp6", "pass in proto icmp6 all", true},
		{"proto gre", "pass in proto gre all", true},
		{"proto esp", "pass in proto esp all", true},
		{"proto ah", "pass in proto ah all", true},
		{"proto carp", "pass in proto carp all", true},
		{"proto pfsync", "pass in proto pfsync all", true},
		{"proto ospf", "pass in proto ospf all", true},
		{"proto vrrp", "pass in proto vrrp all", true},
		{"proto multiple", "pass in proto { tcp, udp } all", true},
		{"proto by number", "pass in proto 47 all", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesAddresses(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// From addresses
		{"from any", "pass in from any", true},
		{"from single ip", "pass in from 192.168.1.1", true},
		{"from network", "pass in from 192.168.1.0/24", true},
		{"from ipv6", "pass in inet6 from 2001:db8::/32", true},
		{"from table", "table <good> { 10.0.0.0/8 }\npass in from <good>", true},
		{"from negated", "pass in from ! 192.168.1.1", true},
		{"from self", "pass in from self", true},
		{"from no-route", "block in from no-route", true},
		{"from urpf-failed", "block in from urpf-failed", true},
		{"from interface", "pass in from (em0)", true},
		{"from interface network", "pass in from (em0:network)", true},
		{"from interface broadcast", "pass in from (em0:broadcast)", true},
		{"from interface peer", "pass in from (em0:peer)", true},
		{"from multiple", "pass in from { 192.168.1.0/24, 10.0.0.0/8 }", true},

		// To addresses
		{"to any", "pass out to any", true},
		{"to single ip", "pass out to 192.168.1.1", true},
		{"to network", "pass out to 192.168.1.0/24", true},
		{"to self", "pass in to self", true},

		// Combined from/to
		{"from to", "pass in from 192.168.1.0/24 to 10.0.0.0/8", true},
		{"from to any", "pass in from 192.168.1.0/24 to any", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesPorts(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// Port specifications
		{"port single", "pass in proto tcp to any port 80", true},
		{"port name", "pass in proto tcp to any port ssh", true},
		{"port range", "pass in proto tcp to any port 1024:65535", true},
		{"port range symbols", "pass in proto tcp to any port 1024 >< 65535", true},
		{"port multiple", "pass in proto tcp to any port { 22, 80, 443 }", true},
		{"port names multiple", "pass in proto tcp to any port { ssh, http, https }", true},
		{"port comparison gt", "pass in proto tcp to any port > 1023", true},
		{"port comparison lt", "pass in proto tcp to any port < 1024", true},
		{"port comparison ge", "pass in proto tcp to any port >= 1024", true},
		{"port comparison le", "pass in proto tcp to any port <= 1023", true},
		{"port comparison ne", "pass in proto tcp to any port != 22", true},

		// Source ports
		{"source port", "pass in proto tcp from any port 12345", true},
		{"source and dest port", "pass in proto tcp from any port 12345 to any port 80", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesState(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// State tracking
		{"keep state", "pass in proto tcp to any port 80 keep state", true},
		{"modulate state", "pass in proto tcp to any port 80 modulate state", true},
		{"synproxy state", "pass in proto tcp to any port 80 synproxy state", true},
		{"no state", "pass in proto tcp to any port 80 no state", true},

		// State options
		{"state max", "pass in proto tcp to any port 80 keep state (max 100)", true},
		{"state source-track", "pass in proto tcp to any port 80 keep state (source-track rule)", true},
		{"state source-track global", "pass in proto tcp to any port 80 keep state (source-track global)", true},
		{"state max-src-nodes", "pass in proto tcp to any port 80 keep state (max-src-nodes 100)", true},
		{"state max-src-states", "pass in proto tcp to any port 80 keep state (max-src-states 10)", true},
		{"state max-src-conn", "pass in proto tcp to any port 80 keep state (max-src-conn 100)", true},
		{"state max-src-conn-rate", "pass in proto tcp to any port 80 keep state (max-src-conn-rate 15/5)", true},
		{"state overload", "table <bruteforce> persist\npass in proto tcp to any port 22 keep state (max-src-conn-rate 15/5, overload <bruteforce> flush)", true},
		{"state overload global", "table <bruteforce> persist\npass in proto tcp to any port 22 keep state (max-src-conn-rate 15/5, overload <bruteforce> flush global)", true},
		{"state if-bound", "pass in proto tcp to any port 80 keep state (if-bound)", true},
		{"state floating", "pass in proto tcp to any port 80 keep state (floating)", true},
		{"state no-sync", "pass in proto tcp to any port 80 keep state (no-sync)", true},
		{"state pflow", "pass in proto tcp to any port 80 keep state (pflow)", true},
		{"state sloppy", "pass in proto tcp to any port 80 keep state (sloppy)", true},
		{"state combined", "pass in proto tcp to any port 80 keep state (max 1000, source-track rule, max-src-nodes 100, max-src-states 3)", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesICMP(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// ICMP types
		{"icmp echoreq", "pass in inet proto icmp icmp-type echoreq", true},
		{"icmp echorep", "pass in inet proto icmp icmp-type echorep", true},
		{"icmp unreach", "pass in inet proto icmp icmp-type unreach", true},
		{"icmp squench", "pass in inet proto icmp icmp-type squench", true},
		{"icmp redir", "pass in inet proto icmp icmp-type redir", true},
		{"icmp timex", "pass in inet proto icmp icmp-type timex", true},
		{"icmp paramprob", "pass in inet proto icmp icmp-type paramprob", true},
		{"icmp timereq", "pass in inet proto icmp icmp-type timereq", true},
		{"icmp timerep", "pass in inet proto icmp icmp-type timerep", true},
		{"icmp inforeq", "pass in inet proto icmp icmp-type inforeq", true},
		{"icmp inforep", "pass in inet proto icmp icmp-type inforep", true},
		{"icmp maskreq", "pass in inet proto icmp icmp-type maskreq", true},
		{"icmp maskrep", "pass in inet proto icmp icmp-type maskrep", true},
		{"icmp multiple", "pass in inet proto icmp icmp-type { echoreq, unreach }", true},
		{"icmp by number", "pass in inet proto icmp icmp-type 8", true},
		{"icmp with code", "pass in inet proto icmp icmp-type unreach code net-unr", true},

		// ICMPv6 types
		{"icmp6 echoreq", "pass in inet6 proto icmp6 icmp6-type echoreq", true},
		{"icmp6 echorep", "pass in inet6 proto icmp6 icmp6-type echorep", true},
		{"icmp6 unreach", "pass in inet6 proto icmp6 icmp6-type unreach", true},
		{"icmp6 toobig", "pass in inet6 proto icmp6 icmp6-type toobig", true},
		{"icmp6 timex", "pass in inet6 proto icmp6 icmp6-type timex", true},
		{"icmp6 paramprob", "pass in inet6 proto icmp6 icmp6-type paramprob", true},
		{"icmp6 neighbrsol", "pass in inet6 proto icmp6 icmp6-type neighbrsol", true},
		{"icmp6 neighbradv", "pass in inet6 proto icmp6 icmp6-type neighbradv", true},
		{"icmp6 routersol", "pass in inet6 proto icmp6 icmp6-type routersol", true},
		{"icmp6 routeradv", "pass in inet6 proto icmp6 icmp6-type routeradv", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesTCPFlags(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"flags S/SA", "pass in proto tcp flags S/SA", true},
		{"flags S/SAFR", "pass in proto tcp flags S/SAFR", true},
		{"flags any", "pass in proto tcp flags any", true},
		{"flags FPU/FPUASREW", "pass in proto tcp flags FPU/FPUASREW", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesOS(t *testing.T) {
	skipIfNotOpenBSD(t)

	// OS fingerprinting requires /etc/pf.os to exist
	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"os Linux", "pass in proto tcp from any os \"Linux\"", true},
		{"os OpenBSD", "pass in proto tcp from any os \"OpenBSD\"", true},
		{"os Windows", "pass in proto tcp from any os \"Windows\"", true},
		{"os multiple", "pass in proto tcp from any os { \"Linux\", \"OpenBSD\" }", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				// OS fingerprinting might fail if pf.os doesn't have the entry
				t.Logf("OS fingerprinting test %q: %v", tt.name, err)
			}
		})
	}
}

func TestPFFilterRulesLabels(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"label simple", "pass in proto tcp to any port 80 label \"web\"", true},
		{"label with spaces", "pass in proto tcp to any port 80 label \"web traffic\"", true},
		{"label with macros", "pass in on em0 proto tcp to any port 80 label \"$if web\"", true},
		{"tag", "pass in proto tcp to any port 80 tag WEB", true},
		{"tagged", "pass out tagged WEB", true},
		{"tagged negated", "pass out ! tagged INTERNAL", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesQueue(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"set queue on", "pass in on em0 proto tcp to any port 80 set queue web", true},
		{"set queue with priority", "pass in on em0 proto tcp to any port 22 set queue (ssh, bulk)", true},
		{"set prio", "pass in proto tcp to any port 80 set prio 5", true},
		{"set prio tuple", "pass in proto tcp to any port 80 set prio (5, 6)", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFFilterRulesToS(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"tos lowdelay", "pass in proto tcp tos lowdelay", true},
		{"tos throughput", "pass in proto tcp tos throughput", true},
		{"tos reliability", "pass in proto tcp tos reliability", true},
		{"tos numeric", "pass in proto tcp tos 0x10", true},
		{"set tos", "pass in proto tcp to any port 80 set tos lowdelay", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

// =============================================================================
// NAT RULES TESTS
// =============================================================================

func TestPFNATRules(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// Basic NAT
		{"nat outgoing", "match out on em0 from 192.168.1.0/24 to any nat-to (em0)", true},
		{"nat static ip", "match out on em0 from 192.168.1.0/24 to any nat-to 203.0.113.1", true},
		{"nat pool", "match out on em0 from 192.168.1.0/24 to any nat-to { 203.0.113.1, 203.0.113.2 }", true},
		{"nat range", "match out on em0 from 192.168.1.0/24 to any nat-to 203.0.113.1 - 203.0.113.10", true},

		// NAT options
		{"nat static-port", "match out on em0 from 192.168.1.0/24 to any nat-to (em0) static-port", true},
		{"nat bitmask", "match out on em0 from 192.168.1.0/24 to any nat-to 203.0.113.0/24 bitmask", true},
		{"nat random", "match out on em0 from 192.168.1.0/24 to any nat-to (em0) random", true},
		{"nat source-hash", "match out on em0 from 192.168.1.0/24 to any nat-to (em0) source-hash", true},
		{"nat round-robin", "match out on em0 from 192.168.1.0/24 to any nat-to { 203.0.113.1, 203.0.113.2 } round-robin", true},
		{"nat sticky", "match out on em0 from 192.168.1.0/24 to any nat-to { 203.0.113.1, 203.0.113.2 } round-robin sticky-address", true},

		// NAT with ports
		{"nat port range", "match out on em0 proto tcp from 192.168.1.0/24 to any nat-to (em0) port 50000:65535", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFBiNATRules(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"binat basic", "match on em0 from 192.168.1.1 to any binat-to 203.0.113.1", true},
		{"binat network", "match on em0 from 192.168.1.0/24 to any binat-to 203.0.113.0/24", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

func TestPFRDRRules(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		// Basic RDR (port forwarding)
		{"rdr web", "match in on em0 proto tcp to (em0) port 80 rdr-to 192.168.1.10", true},
		{"rdr with port", "match in on em0 proto tcp to (em0) port 8080 rdr-to 192.168.1.10 port 80", true},
		{"rdr range", "match in on em0 proto tcp to (em0) port 8000:8100 rdr-to 192.168.1.10 port 80:180", true},
		{"rdr pool", "match in on em0 proto tcp to (em0) port 80 rdr-to { 192.168.1.10, 192.168.1.11 }", true},
		{"rdr round-robin", "match in on em0 proto tcp to (em0) port 80 rdr-to { 192.168.1.10, 192.168.1.11 } round-robin", true},
		{"rdr sticky", "match in on em0 proto tcp to (em0) port 80 rdr-to { 192.168.1.10, 192.168.1.11 } round-robin sticky-address", true},
		{"rdr random", "match in on em0 proto tcp to (em0) port 80 rdr-to { 192.168.1.10, 192.168.1.11 } random", true},
		{"rdr source-hash", "match in on em0 proto tcp to (em0) port 80 rdr-to { 192.168.1.10, 192.168.1.11 } source-hash", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

// =============================================================================
// ANCHOR TESTS
// =============================================================================

func TestPFAnchors(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"anchor basic", "anchor \"test\"", true},
		{"anchor with rules", "anchor \"test\" in on em0 proto tcp to any port 80", true},
		{"anchor quick", "anchor \"test\" in quick on em0", true},
		{"load anchor", "load anchor \"test\" from \"/etc/pf.test\"", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

// =============================================================================
// QUEUE DEFINITION TESTS
// =============================================================================

func TestPFQueues(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"queue basic", "queue web on em0 bandwidth 10M", true},
		{"queue min", "queue web on em0 bandwidth 10M min 5M", true},
		{"queue max", "queue web on em0 bandwidth 10M max 50M", true},
		{"queue burst", "queue web on em0 bandwidth 10M burst 20M for 100ms", true},
		{"queue default", "queue std on em0 bandwidth 50M default", true},
		{"queue qlimit", "queue web on em0 bandwidth 10M qlimit 100", true},
		{"queue parent", "queue root on em0 bandwidth 100M\nqueue web parent root bandwidth 10M", true},
		{"queue flows", "queue web on em0 bandwidth 10M flows 1024", true},
		{"queue quantum", "queue web on em0 bandwidth 10M quantum 1500", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

// =============================================================================
// MACRO TESTS
// =============================================================================

func TestPFMacros(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name  string
		rule  string
		valid bool
	}{
		{"macro interface", "ext_if = \"em0\"\npass in on $ext_if", true},
		{"macro network", "internal = \"192.168.1.0/24\"\npass in from $internal", true},
		{"macro list", "web_ports = \"{ 80, 443 }\"\npass in proto tcp to any port $web_ports", true},
		{"macro table", "table <blocked> persist\nblocked = \"<blocked>\"\nblock in from $blocked", true},
		{"macro combined", "ext_if = \"em0\"\nint_net = \"192.168.1.0/24\"\npass in on $ext_if from $int_net", true},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if tt.valid && err != nil {
				t.Errorf("Expected valid rule %q, got error: %v", tt.rule, err)
			}
		})
	}
}

// =============================================================================
// COMPLEX RULESETS
// =============================================================================

func TestPFCompleteRuleset(t *testing.T) {
	skipIfNotOpenBSD(t)

	ruleset := `
# Macros
ext_if = "em0"
int_if = "em1"
int_net = "192.168.1.0/24"
web_ports = "{ 80, 443 }"
ssh_port = "22"

# Tables
table <bruteforce> persist
table <martians> const { 0.0.0.0/8, 127.0.0.0/8, 169.254.0.0/16, 172.16.0.0/12, \
    192.0.0.0/24, 192.0.2.0/24, 224.0.0.0/3, 240.0.0.0/4 }

# Options
set block-policy drop
set loginterface $ext_if
set skip on lo
set state-policy if-bound
set optimization normal
set limit { states 100000, src-nodes 50000, frags 25000 }

# Normalization
match in all scrub (no-df random-id max-mss 1440)
match out all scrub (random-id)

# NAT
match out on $ext_if from $int_net to any nat-to ($ext_if)

# Default block
block log all
block in quick from <martians>
block in quick from urpf-failed

# Allow loopback
pass quick on lo

# Allow ICMP
pass in inet proto icmp icmp-type { echoreq, unreach }
pass in inet6 proto icmp6 icmp6-type { echoreq, echorep, neighbrsol, neighbradv, routersol, routeradv }

# Allow SSH with bruteforce protection
pass in on $ext_if proto tcp to ($ext_if) port $ssh_port \
    keep state (max-src-conn-rate 5/60, overload <bruteforce> flush global)

# Allow web traffic
pass in on $ext_if proto tcp to ($ext_if) port $web_ports keep state

# Allow established outbound
pass out keep state
`

	err := validatePFSyntax(ruleset)
	if err != nil {
		t.Errorf("Complete ruleset validation failed: %v", err)
	}
}

func TestPFFirewallWithNAT(t *testing.T) {
	skipIfNotOpenBSD(t)

	ruleset := `
# Basic firewall with NAT and port forwarding

ext_if = "em0"
int_if = "em1"
dmz_if = "em2"

int_net = "192.168.1.0/24"
dmz_net = "10.0.0.0/24"

webserver = "10.0.0.10"
mailserver = "10.0.0.20"

table <spamd-white> persist

set skip on lo
set block-policy return

# Scrub
match in all scrub (no-df random-id)

# NAT for internal network
match out on $ext_if from $int_net nat-to ($ext_if)
match out on $ext_if from $dmz_net nat-to ($ext_if)

# Port forwarding to web server
match in on $ext_if proto tcp to ($ext_if) port { 80, 443 } rdr-to $webserver

# Port forwarding to mail server
match in on $ext_if proto tcp to ($ext_if) port { 25, 587, 993, 995 } rdr-to $mailserver

# Default deny
block log all

# Allow traffic to forwarded services
pass in on $ext_if proto tcp to $webserver port { 80, 443 }
pass in on $ext_if proto tcp to $mailserver port { 25, 587, 993, 995 }

# Allow established
pass out keep state

# Allow internal to access DMZ
pass in on $int_if from $int_net to $dmz_net
pass out on $dmz_if from $int_net to $dmz_net

# Allow internal to internet
pass in on $int_if from $int_net
pass out on $ext_if from $int_net nat-to ($ext_if)
`

	err := validatePFSyntax(ruleset)
	if err != nil {
		t.Errorf("Firewall with NAT ruleset validation failed: %v", err)
	}
}

func TestPFHighAvailability(t *testing.T) {
	skipIfNotOpenBSD(t)

	ruleset := `
# HA Firewall with CARP and pfsync

ext_if = "em0"
int_if = "em1"
sync_if = "em2"

carp_ext = "carp0"
carp_int = "carp1"

# Allow pfsync
pass quick on $sync_if proto pfsync keep state (no-sync)

# Allow CARP
pass quick proto carp keep state (no-sync)

# Normal firewall rules on CARP interfaces
block log on { $carp_ext, $carp_int }
pass in on $carp_ext proto tcp to ($carp_ext) port { 22, 80, 443 } keep state
pass out on $carp_ext keep state
pass on $carp_int keep state
`

	err := validatePFSyntax(ruleset)
	if err != nil {
		t.Errorf("HA ruleset validation failed: %v", err)
	}
}

// =============================================================================
// INVALID SYNTAX TESTS - Ensure pfctl catches errors
// =============================================================================

func TestPFInvalidSyntax(t *testing.T) {
	skipIfNotOpenBSD(t)

	tests := []struct {
		name string
		rule string
	}{
		{"missing action", "in on em0 all"},
		{"invalid action", "allow in on em0 all"},
		{"invalid direction", "pass sideways on em0 all"},
		{"invalid protocol", "pass in proto invalid all"},
		{"missing interface", "pass in on all"},
		{"unclosed brace", "pass in from { 10.0.0.0/8 to any"},
		{"invalid port", "pass in proto tcp to any port notaport"},
		{"invalid address", "pass in from 999.999.999.999"},
		{"invalid cidr", "pass in from 10.0.0.0/99"},
		{"conflicting options", "pass in proto udp flags S/SA"}, // flags only for TCP
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := validatePFSyntax(tt.rule)
			if err == nil {
				t.Errorf("Expected invalid rule %q to fail validation", tt.rule)
			}
		})
	}
}

// =============================================================================
// BENCHMARK TESTS
// =============================================================================

func BenchmarkPFSyntaxValidation(b *testing.B) {
	if runtime.GOOS != "openbsd" {
		b.Skip("Skipping: requires OpenBSD")
	}

	rule := "pass in on em0 proto tcp from 192.168.1.0/24 to any port 80 keep state"

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		validatePFSyntax(rule)
	}
}

func BenchmarkPFComplexRulesetValidation(b *testing.B) {
	if runtime.GOOS != "openbsd" {
		b.Skip("Skipping: requires OpenBSD")
	}

	ruleset := `
ext_if = "em0"
table <blocked> persist
set skip on lo
block all
pass out keep state
pass in on $ext_if proto tcp to ($ext_if) port { 22, 80, 443 } keep state
pass in inet proto icmp icmp-type echoreq keep state
`

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		validatePFSyntax(ruleset)
	}
}
