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

// FirewallService handles PF firewall operations
type FirewallService struct {
	nshPath string
}

// NewFirewallService creates a new FirewallService
func NewFirewallService(nshPath string) *FirewallService {
	return &FirewallService{nshPath: nshPath}
}

// GetInfo returns PF status information
func (f *FirewallService) GetInfo(ctx context.Context) (*models.PFInfo, error) {
	output, err := exec.CommandContext(ctx, "pfctl", "-si").Output()
	if err != nil {
		// PF might be disabled
		return &models.PFInfo{
			Enabled: false,
			Running: false,
		}, nil
	}

	info := &models.PFInfo{
		Enabled: true,
		Running: true,
	}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "Status:") {
			if strings.Contains(line, "Disabled") {
				info.Enabled = false
				info.Running = false
			}
		} else if strings.HasPrefix(line, "Debug:") {
			info.Debug = strings.TrimPrefix(line, "Debug:")
			info.Debug = strings.TrimSpace(info.Debug)
		} else if strings.HasPrefix(line, "Hostid:") {
			info.Hostid = strings.TrimPrefix(line, "Hostid:")
			info.Hostid = strings.TrimSpace(info.Hostid)
		} else if strings.HasPrefix(line, "Checksum:") {
			info.Checksum = strings.TrimPrefix(line, "Checksum:")
			info.Checksum = strings.TrimSpace(info.Checksum)
		} else if strings.Contains(line, "current entries") {
			re := regexp.MustCompile(`(\d+)/(\d+)`)
			if matches := re.FindStringSubmatch(line); len(matches) > 2 {
				info.StateCount, _ = strconv.Atoi(matches[1])
				info.StateLimit, _ = strconv.Atoi(matches[2])
			}
		} else if strings.Contains(line, "source-track") {
			re := regexp.MustCompile(`(\d+)`)
			if matches := re.FindStringSubmatch(line); len(matches) > 0 {
				info.SrcNodes, _ = strconv.Atoi(matches[0])
			}
		}
	}

	return info, nil
}

// GetStats returns PF statistics
func (f *FirewallService) GetStats(ctx context.Context) (*models.PFStats, error) {
	output, err := exec.CommandContext(ctx, "pfctl", "-si").Output()
	if err != nil {
		return nil, err
	}

	stats := &models.PFStats{}

	lines := strings.Split(string(output), "\n")
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

		value, _ := strconv.ParseUint(parts[len(parts)-1], 10, 64)
		name := strings.ToLower(parts[0])

		switch name {
		case "match":
			stats.Match = value
		case "bad-offset":
			stats.BadOffset = value
		case "fragment":
			stats.Fragment = value
		case "short":
			stats.Short = value
		case "normalize":
			stats.Normalize = value
		case "memory":
			stats.Memory = value
		case "bad-timestamp":
			stats.BadTimestamp = value
		case "congestion":
			stats.CongestionDrop = value
		case "ip-option":
			stats.IPOption = value
		case "proto-cksum":
			stats.ProtoCksum = value
		case "state-mismatch":
			stats.StateMismatch = value
		case "state-insert":
			stats.StateInsert = value
		case "state-limit":
			stats.StateLimit = value
		case "src-limit":
			stats.SrcLimit = value
		case "synproxy":
			stats.SynProxy = value
		case "translate":
			stats.Translate = value
		case "no-route":
			stats.NoRoute = value
		}
	}

	return stats, nil
}

// GetRules returns PF rules
func (f *FirewallService) GetRules(ctx context.Context) ([]models.PFRule, error) {
	output, err := exec.CommandContext(ctx, "pfctl", "-sr", "-vv").Output()
	if err != nil {
		return nil, err
	}

	return parseRules(string(output)), nil
}

// GetStates returns PF states
func (f *FirewallService) GetStates(ctx context.Context) ([]models.PFState, error) {
	output, err := exec.CommandContext(ctx, "pfctl", "-ss", "-v").Output()
	if err != nil {
		return nil, err
	}

	return parseStates(string(output)), nil
}

// KillStates kills states matching the given criteria
func (f *FirewallService) KillStates(ctx context.Context, src, dst, iface string) (int, error) {
	args := []string{"-k"}

	if src != "" {
		args = append(args, src)
	} else {
		args = append(args, "0.0.0.0/0")
	}

	if dst != "" {
		args = append(args, "-k", dst)
	}

	if iface != "" {
		args = append(args, "-i", iface)
	}

	cmd := exec.CommandContext(ctx, "pfctl", args...)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return 0, fmt.Errorf("failed to kill states: %s", string(output))
	}

	// Parse "killed X states"
	re := regexp.MustCompile(`killed (\d+) states`)
	if matches := re.FindStringSubmatch(string(output)); len(matches) > 1 {
		count, _ := strconv.Atoi(matches[1])
		return count, nil
	}

	return 0, nil
}

// GetTables returns PF tables
func (f *FirewallService) GetTables(ctx context.Context) ([]models.PFTable, error) {
	output, err := exec.CommandContext(ctx, "pfctl", "-sT").Output()
	if err != nil {
		return nil, err
	}

	var tables []models.PFTable
	lines := strings.Split(string(output), "\n")

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}

		table := models.PFTable{Name: line}

		// Get addresses for this table
		addrOutput, err := exec.CommandContext(ctx, "pfctl", "-t", line, "-Ts").Output()
		if err == nil {
			addrs := strings.Split(string(addrOutput), "\n")
			for _, addr := range addrs {
				addr = strings.TrimSpace(addr)
				if addr != "" {
					table.Addresses = append(table.Addresses, addr)
				}
			}
			table.Count = len(table.Addresses)
		}

		tables = append(tables, table)
	}

	return tables, nil
}

// GetTable returns a specific PF table
func (f *FirewallService) GetTable(ctx context.Context, name string) (*models.PFTable, error) {
	output, err := exec.CommandContext(ctx, "pfctl", "-t", name, "-Ts").Output()
	if err != nil {
		return nil, fmt.Errorf("table not found: %s", name)
	}

	table := &models.PFTable{Name: name}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line != "" {
			table.Addresses = append(table.Addresses, line)
		}
	}
	table.Count = len(table.Addresses)

	return table, nil
}

// AddTableAddress adds an address to a PF table
func (f *FirewallService) AddTableAddress(ctx context.Context, table, address string) error {
	cmd := exec.CommandContext(ctx, "pfctl", "-t", table, "-T", "add", address)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to add address: %s", string(output))
	}
	return nil
}

// DeleteTableAddress removes an address from a PF table
func (f *FirewallService) DeleteTableAddress(ctx context.Context, table, address string) error {
	cmd := exec.CommandContext(ctx, "pfctl", "-t", table, "-T", "delete", address)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to delete address: %s", string(output))
	}
	return nil
}

// FlushTable flushes all addresses from a PF table
func (f *FirewallService) FlushTable(ctx context.Context, table string) error {
	cmd := exec.CommandContext(ctx, "pfctl", "-t", table, "-T", "flush")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to flush table: %s", string(output))
	}
	return nil
}

// Enable enables PF
func (f *FirewallService) Enable(ctx context.Context) error {
	cmd := exec.CommandContext(ctx, "pfctl", "-e")
	_, err := cmd.CombinedOutput()
	return err
}

// Disable disables PF
func (f *FirewallService) Disable(ctx context.Context) error {
	cmd := exec.CommandContext(ctx, "pfctl", "-d")
	_, err := cmd.CombinedOutput()
	return err
}

// Reload reloads PF rules from configuration
func (f *FirewallService) Reload(ctx context.Context) error {
	cmd := exec.CommandContext(ctx, "pfctl", "-f", "/etc/pf.conf")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to reload rules: %s", string(output))
	}
	return nil
}

// Helper functions

func parseRules(output string) []models.PFRule {
	var rules []models.PFRule
	var current *models.PFRule

	scanner := bufio.NewScanner(strings.NewReader(output))
	ruleNum := 0

	for scanner.Scan() {
		line := scanner.Text()

		if line == "" {
			continue
		}

		// Rule line starts with @ for rule number
		if strings.HasPrefix(line, "@") {
			if current != nil {
				rules = append(rules, *current)
			}

			current = &models.PFRule{
				Number: ruleNum,
				Raw:    line,
			}
			ruleNum++

			// Parse the rule
			parseRuleLine(line, current)
		} else if current != nil && strings.HasPrefix(line, "  [") {
			// Statistics line: [ Evaluations: 123 Packets: 456 Bytes: 789 ]
			re := regexp.MustCompile(`Evaluations:\s*(\d+).*Packets:\s*(\d+).*Bytes:\s*(\d+)`)
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

func parseRuleLine(line string, rule *models.PFRule) {
	// Remove rule number prefix
	if idx := strings.Index(line, ")"); idx > 0 {
		line = strings.TrimSpace(line[idx+1:])
	}

	parts := strings.Fields(line)
	if len(parts) == 0 {
		return
	}

	i := 0

	// Action
	if i < len(parts) {
		switch parts[i] {
		case "pass", "block", "match":
			rule.Action = parts[i]
			i++
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
		case "quick":
			rule.Quick = true
			i++
		case "on":
			if i+1 < len(parts) {
				rule.Interface = parts[i+1]
				i += 2
			} else {
				i++
			}
		case "inet":
			rule.AddressFamily = "inet"
			i++
		case "inet6":
			rule.AddressFamily = "inet6"
			i++
		case "proto":
			if i+1 < len(parts) {
				rule.Protocol = parts[i+1]
				i += 2
			} else {
				i++
			}
		case "from":
			if i+1 < len(parts) {
				rule.Source = parts[i+1]
				i += 2
				// Check for port
				if i < len(parts) && parts[i] == "port" {
					if i+1 < len(parts) {
						rule.Source += " port " + parts[i+1]
						i += 2
					}
				}
			} else {
				i++
			}
		case "to":
			if i+1 < len(parts) {
				rule.Destination = parts[i+1]
				i += 2
				// Check for port
				if i < len(parts) && parts[i] == "port" {
					if i+1 < len(parts) {
						rule.Port = parts[i+1]
						i += 2
					}
				}
			} else {
				i++
			}
		case "label":
			if i+1 < len(parts) {
				rule.Label = strings.Trim(parts[i+1], "\"")
				i += 2
			} else {
				i++
			}
		case "keep", "modulate", "synproxy":
			rule.State = parts[i]
			if i+1 < len(parts) && parts[i+1] == "state" {
				rule.State += " state"
				i += 2
			} else {
				i++
			}
		default:
			i++
		}
	}
}

func parseStates(output string) []models.PFState {
	var states []models.PFState

	lines := strings.Split(output, "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}

		state := parseStateLine(line)
		if state != nil {
			states = append(states, *state)
		}
	}

	return states
}

func parseStateLine(line string) *models.PFState {
	// Format: proto src -> dst state [age expire rule]
	// Example: tcp 192.168.1.1:12345 -> 10.0.0.1:80 ESTABLISHED:ESTABLISHED

	parts := strings.Fields(line)
	if len(parts) < 5 {
		return nil
	}

	state := &models.PFState{
		Protocol: parts[0],
	}

	// Find the -> separator
	arrowIdx := -1
	for i, p := range parts {
		if p == "->" {
			arrowIdx = i
			break
		}
	}

	if arrowIdx < 0 || arrowIdx < 2 {
		return nil
	}

	state.Source = parts[arrowIdx-1]
	if arrowIdx+1 < len(parts) {
		state.Destination = parts[arrowIdx+1]
	}

	// Parse state and other fields
	for i := arrowIdx + 2; i < len(parts); i++ {
		p := parts[i]

		if strings.Contains(p, ":") && !strings.Contains(p, ".") {
			// State like ESTABLISHED:ESTABLISHED
			state.State = p
		} else if strings.HasPrefix(p, "age") || strings.HasSuffix(p, "s") {
			if state.Age == "" {
				state.Age = p
			} else {
				state.Expire = p
			}
		} else if strings.HasPrefix(p, "rule") {
			if i+1 < len(parts) {
				state.Rule, _ = strconv.Atoi(parts[i+1])
				i++
			}
		}
	}

	return state
}
