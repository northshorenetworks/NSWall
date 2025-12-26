package services

import (
	"fmt"
	"os/exec"
	"runtime"
	"strconv"
	"strings"
	"sync"
	"time"
)

// MetricsService provides Prometheus metrics
type MetricsService struct {
	nshPath     string
	startTime   time.Time
	requestCount int64
	mu          sync.RWMutex
}

// NewMetricsService creates a new MetricsService
func NewMetricsService(nshPath string) *MetricsService {
	return &MetricsService{
		nshPath:   nshPath,
		startTime: time.Now(),
	}
}

// IncrementRequests increments the request counter
func (m *MetricsService) IncrementRequests() {
	m.mu.Lock()
	m.requestCount++
	m.mu.Unlock()
}

// GetMetrics returns Prometheus-formatted metrics
func (m *MetricsService) GetMetrics() string {
	var sb strings.Builder

	// Help and type declarations
	sb.WriteString("# HELP nswall_info NSWall version information\n")
	sb.WriteString("# TYPE nswall_info gauge\n")
	sb.WriteString("nswall_info{version=\"2.0.0\"} 1\n\n")

	// Uptime
	uptime := time.Since(m.startTime).Seconds()
	sb.WriteString("# HELP nswall_uptime_seconds API server uptime in seconds\n")
	sb.WriteString("# TYPE nswall_uptime_seconds counter\n")
	sb.WriteString(fmt.Sprintf("nswall_uptime_seconds %f\n\n", uptime))

	// Request count
	m.mu.RLock()
	requests := m.requestCount
	m.mu.RUnlock()
	sb.WriteString("# HELP nswall_http_requests_total Total HTTP requests\n")
	sb.WriteString("# TYPE nswall_http_requests_total counter\n")
	sb.WriteString(fmt.Sprintf("nswall_http_requests_total %d\n\n", requests))

	// Go runtime metrics
	var memStats runtime.MemStats
	runtime.ReadMemStats(&memStats)

	sb.WriteString("# HELP nswall_go_goroutines Number of goroutines\n")
	sb.WriteString("# TYPE nswall_go_goroutines gauge\n")
	sb.WriteString(fmt.Sprintf("nswall_go_goroutines %d\n\n", runtime.NumGoroutine()))

	sb.WriteString("# HELP nswall_go_memstats_alloc_bytes Bytes allocated and still in use\n")
	sb.WriteString("# TYPE nswall_go_memstats_alloc_bytes gauge\n")
	sb.WriteString(fmt.Sprintf("nswall_go_memstats_alloc_bytes %d\n\n", memStats.Alloc))

	sb.WriteString("# HELP nswall_go_memstats_sys_bytes Bytes obtained from system\n")
	sb.WriteString("# TYPE nswall_go_memstats_sys_bytes gauge\n")
	sb.WriteString(fmt.Sprintf("nswall_go_memstats_sys_bytes %d\n\n", memStats.Sys))

	// System metrics
	m.appendSystemMetrics(&sb)

	// PF metrics
	m.appendPFMetrics(&sb)

	// Interface metrics
	m.appendInterfaceMetrics(&sb)

	return sb.String()
}

func (m *MetricsService) appendSystemMetrics(sb *strings.Builder) {
	// Load average
	loadOutput, err := exec.Command("sysctl", "-n", "vm.loadavg").Output()
	if err == nil {
		fields := strings.Fields(string(loadOutput))
		if len(fields) >= 3 {
			sb.WriteString("# HELP nswall_load_average System load average\n")
			sb.WriteString("# TYPE nswall_load_average gauge\n")
			if load1, err := strconv.ParseFloat(fields[0], 64); err == nil {
				sb.WriteString(fmt.Sprintf("nswall_load_average{period=\"1m\"} %f\n", load1))
			}
			if load5, err := strconv.ParseFloat(fields[1], 64); err == nil {
				sb.WriteString(fmt.Sprintf("nswall_load_average{period=\"5m\"} %f\n", load5))
			}
			if load15, err := strconv.ParseFloat(fields[2], 64); err == nil {
				sb.WriteString(fmt.Sprintf("nswall_load_average{period=\"15m\"} %f\n", load15))
			}
			sb.WriteString("\n")
		}
	}

	// Memory
	physMem, _ := exec.Command("sysctl", "-n", "hw.physmem").Output()
	if len(physMem) > 0 {
		total, _ := strconv.ParseUint(strings.TrimSpace(string(physMem)), 10, 64)
		sb.WriteString("# HELP nswall_memory_total_bytes Total physical memory\n")
		sb.WriteString("# TYPE nswall_memory_total_bytes gauge\n")
		sb.WriteString(fmt.Sprintf("nswall_memory_total_bytes %d\n\n", total))
	}

	// Boot time / system uptime
	bootTime, _ := exec.Command("sysctl", "-n", "kern.boottime").Output()
	if len(bootTime) > 0 {
		// Parse: sec = 1234567890
		str := string(bootTime)
		if idx := strings.Index(str, "sec = "); idx >= 0 {
			rest := str[idx+6:]
			if endIdx := strings.Index(rest, ","); endIdx > 0 {
				rest = rest[:endIdx]
			}
			if sec, err := strconv.ParseInt(strings.TrimSpace(rest), 10, 64); err == nil {
				uptime := time.Now().Unix() - sec
				sb.WriteString("# HELP nswall_system_uptime_seconds System uptime in seconds\n")
				sb.WriteString("# TYPE nswall_system_uptime_seconds counter\n")
				sb.WriteString(fmt.Sprintf("nswall_system_uptime_seconds %d\n\n", uptime))
			}
		}
	}
}

func (m *MetricsService) appendPFMetrics(sb *strings.Builder) {
	// PF info (pfctl -si)
	m.appendPFInfo(sb)

	// PF packet counters
	m.appendPFCounters(sb)

	// PF state breakdown by protocol
	m.appendPFStatesByProtocol(sb)

	// PF per-rule metrics
	m.appendPFRuleMetrics(sb)

	// PF queue metrics (ALTQ/HFSC)
	m.appendPFQueueMetrics(sb)

	// PF table metrics
	m.appendPFTableMetrics(sb)
}

func (m *MetricsService) appendPFInfo(sb *strings.Builder) {
	output, err := exec.Command("pfctl", "-si").Output()
	if err != nil {
		return
	}

	lines := strings.Split(string(output), "\n")

	// PF enabled status
	enabled := 0
	if strings.Contains(string(output), "Status: Enabled") {
		enabled = 1
	}
	sb.WriteString("# HELP nswall_pf_enabled PF enabled status (1=enabled, 0=disabled)\n")
	sb.WriteString("# TYPE nswall_pf_enabled gauge\n")
	sb.WriteString(fmt.Sprintf("nswall_pf_enabled %d\n\n", enabled))

	for _, line := range lines {
		line = strings.TrimSpace(line)

		// State table entries
		if strings.Contains(line, "current entries") {
			fields := strings.Fields(line)
			for i, f := range fields {
				if f == "current" && i > 0 {
					count, _ := strconv.ParseInt(fields[i-1], 10, 64)
					sb.WriteString("# HELP nswall_pf_states Current PF state count\n")
					sb.WriteString("# TYPE nswall_pf_states gauge\n")
					sb.WriteString(fmt.Sprintf("nswall_pf_states %d\n\n", count))
					break
				}
			}
		}

		// State table limit
		if strings.HasPrefix(line, "limit") {
			fields := strings.Fields(line)
			if len(fields) >= 2 {
				if limit, err := strconv.ParseInt(fields[1], 10, 64); err == nil {
					sb.WriteString("# HELP nswall_pf_state_limit Maximum state table entries\n")
					sb.WriteString("# TYPE nswall_pf_state_limit gauge\n")
					sb.WriteString(fmt.Sprintf("nswall_pf_state_limit %d\n\n", limit))
				}
			}
		}

		// Source nodes
		if strings.Contains(line, "source nodes") {
			fields := strings.Fields(line)
			for i, f := range fields {
				if f == "source" && i > 0 {
					count, _ := strconv.ParseInt(fields[i-1], 10, 64)
					sb.WriteString("# HELP nswall_pf_source_nodes Current source tracking nodes\n")
					sb.WriteString("# TYPE nswall_pf_source_nodes gauge\n")
					sb.WriteString(fmt.Sprintf("nswall_pf_source_nodes %d\n\n", count))
					break
				}
			}
		}
	}
}

func (m *MetricsService) appendPFCounters(sb *strings.Builder) {
	// Get verbose stats for packet counters
	output, err := exec.Command("pfctl", "-vsi").Output()
	if err != nil {
		return
	}

	sb.WriteString("# HELP nswall_pf_packets_total Total packets processed by direction and action\n")
	sb.WriteString("# TYPE nswall_pf_packets_total counter\n")
	sb.WriteString("# HELP nswall_pf_bytes_total Total bytes processed by direction and action\n")
	sb.WriteString("# TYPE nswall_pf_bytes_total counter\n")

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		line = strings.TrimSpace(line)

		// Parse packet/byte counters
		// Format varies, look for Passed/Blocked lines
		if strings.Contains(line, "Passed") || strings.Contains(line, "Blocked") {
			direction := "in"
			if strings.Contains(line, "Out") {
				direction = "out"
			}

			fields := strings.Fields(line)
			for i, f := range fields {
				if f == "Passed" && i+1 < len(fields) {
					packets, _ := strconv.ParseInt(fields[i+1], 10, 64)
					sb.WriteString(fmt.Sprintf("nswall_pf_packets_total{direction=\"%s\",action=\"pass\"} %d\n", direction, packets))
					if i+2 < len(fields) {
						bytes, _ := strconv.ParseInt(fields[i+2], 10, 64)
						sb.WriteString(fmt.Sprintf("nswall_pf_bytes_total{direction=\"%s\",action=\"pass\"} %d\n", direction, bytes))
					}
				}
				if f == "Blocked" && i+1 < len(fields) {
					packets, _ := strconv.ParseInt(fields[i+1], 10, 64)
					sb.WriteString(fmt.Sprintf("nswall_pf_packets_total{direction=\"%s\",action=\"block\"} %d\n", direction, packets))
					if i+2 < len(fields) {
						bytes, _ := strconv.ParseInt(fields[i+2], 10, 64)
						sb.WriteString(fmt.Sprintf("nswall_pf_bytes_total{direction=\"%s\",action=\"block\"} %d\n", direction, bytes))
					}
				}
			}
		}

		// Counter section
		if strings.HasPrefix(line, "match") || strings.HasPrefix(line, "bad-offset") ||
			strings.HasPrefix(line, "fragment") || strings.HasPrefix(line, "short") ||
			strings.HasPrefix(line, "normalize") || strings.HasPrefix(line, "memory") {
			fields := strings.Fields(line)
			if len(fields) >= 2 {
				name := strings.ReplaceAll(fields[0], "-", "_")
				if count, err := strconv.ParseInt(fields[1], 10, 64); err == nil {
					sb.WriteString(fmt.Sprintf("nswall_pf_counter_%s %d\n", name, count))
				}
			}
		}
	}
	sb.WriteString("\n")
}

func (m *MetricsService) appendPFStatesByProtocol(sb *strings.Builder) {
	// Get states and count by protocol
	output, err := exec.Command("pfctl", "-ss").Output()
	if err != nil {
		return
	}

	protoCounts := map[string]int64{
		"tcp":   0,
		"udp":   0,
		"icmp":  0,
		"other": 0,
	}

	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		switch {
		case strings.Contains(line, " tcp "):
			protoCounts["tcp"]++
		case strings.Contains(line, " udp "):
			protoCounts["udp"]++
		case strings.Contains(line, " icmp "):
			protoCounts["icmp"]++
		default:
			if strings.TrimSpace(line) != "" && !strings.HasPrefix(line, "No") {
				protoCounts["other"]++
			}
		}
	}

	sb.WriteString("# HELP nswall_pf_states_by_protocol State table entries by protocol\n")
	sb.WriteString("# TYPE nswall_pf_states_by_protocol gauge\n")
	for proto, count := range protoCounts {
		sb.WriteString(fmt.Sprintf("nswall_pf_states_by_protocol{protocol=\"%s\"} %d\n", proto, count))
	}
	sb.WriteString("\n")
}

func (m *MetricsService) appendPFRuleMetrics(sb *strings.Builder) {
	// Get rules with counters (pfctl -vsr)
	output, err := exec.Command("pfctl", "-vsr").Output()
	if err != nil {
		return
	}

	sb.WriteString("# HELP nswall_pf_rule_evaluations Rule evaluation count\n")
	sb.WriteString("# TYPE nswall_pf_rule_evaluations counter\n")
	sb.WriteString("# HELP nswall_pf_rule_packets Packets matched by rule\n")
	sb.WriteString("# TYPE nswall_pf_rule_packets counter\n")
	sb.WriteString("# HELP nswall_pf_rule_bytes Bytes matched by rule\n")
	sb.WriteString("# TYPE nswall_pf_rule_bytes counter\n")
	sb.WriteString("# HELP nswall_pf_rule_states States created by rule\n")
	sb.WriteString("# TYPE nswall_pf_rule_states gauge\n")

	lines := strings.Split(string(output), "\n")
	ruleNum := 0

	for i, line := range lines {
		if line == "" {
			continue
		}

		// Rules start with @
		if strings.HasPrefix(strings.TrimSpace(line), "@") {
			// Extract rule number
			fields := strings.Fields(line)
			if len(fields) > 0 {
				numStr := strings.TrimPrefix(fields[0], "@")
				if num, err := strconv.Atoi(numStr); err == nil {
					ruleNum = num
				}
			}

			// Determine action and direction
			action := "unknown"
			if strings.Contains(line, " pass ") {
				action = "pass"
			} else if strings.Contains(line, " block ") {
				action = "block"
			} else if strings.Contains(line, " match ") {
				action = "match"
			}

			direction := "any"
			if strings.Contains(line, " in ") {
				direction = "in"
			} else if strings.Contains(line, " out ") {
				direction = "out"
			}

			// Extract interface
			iface := "any"
			for j, f := range fields {
				if f == "on" && j+1 < len(fields) {
					iface = fields[j+1]
					break
				}
			}

			labels := fmt.Sprintf("rule=\"%d\",action=\"%s\",direction=\"%s\",interface=\"%s\"",
				ruleNum, action, direction, iface)

			// Look for stats on next line
			if i+1 < len(lines) {
				statsLine := lines[i+1]
				if strings.Contains(statsLine, "Evaluations:") {
					// Parse: [ Evaluations: 123456  Packets: 12345  Bytes: 1234567  States: 12 ]
					statFields := strings.Fields(statsLine)
					for j, f := range statFields {
						switch f {
						case "Evaluations:":
							if j+1 < len(statFields) {
								if val, err := strconv.ParseInt(statFields[j+1], 10, 64); err == nil {
									sb.WriteString(fmt.Sprintf("nswall_pf_rule_evaluations{%s} %d\n", labels, val))
								}
							}
						case "Packets:":
							if j+1 < len(statFields) {
								if val, err := strconv.ParseInt(statFields[j+1], 10, 64); err == nil {
									sb.WriteString(fmt.Sprintf("nswall_pf_rule_packets{%s} %d\n", labels, val))
								}
							}
						case "Bytes:":
							if j+1 < len(statFields) {
								if val, err := strconv.ParseInt(statFields[j+1], 10, 64); err == nil {
									sb.WriteString(fmt.Sprintf("nswall_pf_rule_bytes{%s} %d\n", labels, val))
								}
							}
						case "States:":
							if j+1 < len(statFields) {
								val := strings.TrimSuffix(statFields[j+1], "]")
								if num, err := strconv.ParseInt(val, 10, 64); err == nil {
									sb.WriteString(fmt.Sprintf("nswall_pf_rule_states{%s} %d\n", labels, num))
								}
							}
						}
					}
				}
			}
		}
	}
	sb.WriteString("\n")
}

func (m *MetricsService) appendPFQueueMetrics(sb *strings.Builder) {
	// Get queue stats (pfctl -vsq)
	output, err := exec.Command("pfctl", "-vsq").Output()
	if err != nil || len(output) == 0 {
		return
	}

	sb.WriteString("# HELP nswall_pf_queue_packets Packets through queue\n")
	sb.WriteString("# TYPE nswall_pf_queue_packets counter\n")
	sb.WriteString("# HELP nswall_pf_queue_bytes Bytes through queue\n")
	sb.WriteString("# TYPE nswall_pf_queue_bytes counter\n")
	sb.WriteString("# HELP nswall_pf_queue_dropped Packets dropped by queue\n")
	sb.WriteString("# TYPE nswall_pf_queue_dropped counter\n")
	sb.WriteString("# HELP nswall_pf_queue_length Current queue length\n")
	sb.WriteString("# TYPE nswall_pf_queue_length gauge\n")

	var queueName, queueIface string
	lines := strings.Split(string(output), "\n")

	for _, line := range lines {
		if strings.HasPrefix(line, "queue") {
			fields := strings.Fields(line)
			if len(fields) >= 4 {
				queueName = fields[1]
				for i, f := range fields {
					if f == "on" && i+1 < len(fields) {
						queueIface = fields[i+1]
						break
					}
				}
			}
		}

		if queueName != "" && strings.Contains(line, "pkts:") {
			labels := fmt.Sprintf("queue=\"%s\",interface=\"%s\"", queueName, queueIface)

			fields := strings.Fields(line)
			for i, f := range fields {
				switch f {
				case "pkts:":
					if i+1 < len(fields) {
						if val, err := strconv.ParseInt(fields[i+1], 10, 64); err == nil {
							sb.WriteString(fmt.Sprintf("nswall_pf_queue_packets{%s} %d\n", labels, val))
						}
					}
				case "bytes:":
					if i+1 < len(fields) {
						if val, err := strconv.ParseInt(fields[i+1], 10, 64); err == nil {
							sb.WriteString(fmt.Sprintf("nswall_pf_queue_bytes{%s} %d\n", labels, val))
						}
					}
				case "dropped":
					// Look for dropped pkts after this
					if i+2 < len(fields) && fields[i+1] == "pkts:" {
						if val, err := strconv.ParseInt(fields[i+2], 10, 64); err == nil {
							sb.WriteString(fmt.Sprintf("nswall_pf_queue_dropped{%s} %d\n", labels, val))
						}
					}
				case "qlength:":
					if i+1 < len(fields) {
						// May be fraction like 0/50
						parts := strings.Split(fields[i+1], "/")
						if len(parts) >= 1 {
							if val, err := strconv.ParseInt(parts[0], 10, 64); err == nil {
								sb.WriteString(fmt.Sprintf("nswall_pf_queue_length{%s} %d\n", labels, val))
							}
						}
					}
				}
			}
		}
	}
	sb.WriteString("\n")
}

func (m *MetricsService) appendPFTableMetrics(sb *strings.Builder) {
	// Get table stats (pfctl -vsTables)
	output, err := exec.Command("pfctl", "-vsTables").Output()
	if err != nil || len(output) == 0 {
		return
	}

	sb.WriteString("# HELP nswall_pf_table_addresses Addresses in table\n")
	sb.WriteString("# TYPE nswall_pf_table_addresses gauge\n")
	sb.WriteString("# HELP nswall_pf_table_evaluations Table evaluations\n")
	sb.WriteString("# TYPE nswall_pf_table_evaluations counter\n")
	sb.WriteString("# HELP nswall_pf_table_match Table matches\n")
	sb.WriteString("# TYPE nswall_pf_table_match counter\n")

	var tableName string
	lines := strings.Split(string(output), "\n")

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}

		// Table name line starts with -
		if strings.HasPrefix(line, "-") {
			fields := strings.Fields(line)
			if len(fields) >= 2 {
				tableName = strings.Trim(fields[1], "<>")
			}
			continue
		}

		if tableName != "" && strings.Contains(line, "Addresses:") {
			fields := strings.Fields(line)
			for i, f := range fields {
				if f == "Addresses:" && i+1 < len(fields) {
					if val, err := strconv.ParseInt(fields[i+1], 10, 64); err == nil {
						sb.WriteString(fmt.Sprintf("nswall_pf_table_addresses{table=\"%s\"} %d\n", tableName, val))
					}
				}
			}
		}

		if tableName != "" && strings.Contains(line, "Evaluations:") {
			fields := strings.Fields(line)
			for i, f := range fields {
				switch f {
				case "Evaluations:":
					if i+1 < len(fields) {
						if val, err := strconv.ParseInt(fields[i+1], 10, 64); err == nil {
							sb.WriteString(fmt.Sprintf("nswall_pf_table_evaluations{table=\"%s\"} %d\n", tableName, val))
						}
					}
				case "Match:":
					if i+1 < len(fields) {
						if val, err := strconv.ParseInt(fields[i+1], 10, 64); err == nil {
							sb.WriteString(fmt.Sprintf("nswall_pf_table_match{table=\"%s\"} %d\n", tableName, val))
						}
					}
				}
			}
		}
	}
	sb.WriteString("\n")
}

func (m *MetricsService) appendInterfaceMetrics(sb *strings.Builder) {
	// Get interface statistics
	output, err := exec.Command("netstat", "-ibn").Output()
	if err != nil {
		return
	}

	sb.WriteString("# HELP nswall_interface_rx_bytes Received bytes\n")
	sb.WriteString("# TYPE nswall_interface_rx_bytes counter\n")
	sb.WriteString("# HELP nswall_interface_tx_bytes Transmitted bytes\n")
	sb.WriteString("# TYPE nswall_interface_tx_bytes counter\n")
	sb.WriteString("# HELP nswall_interface_rx_packets Received packets\n")
	sb.WriteString("# TYPE nswall_interface_rx_packets counter\n")
	sb.WriteString("# HELP nswall_interface_tx_packets Transmitted packets\n")
	sb.WriteString("# TYPE nswall_interface_tx_packets counter\n")
	sb.WriteString("# HELP nswall_interface_rx_errors Receive errors\n")
	sb.WriteString("# TYPE nswall_interface_rx_errors counter\n")
	sb.WriteString("# HELP nswall_interface_tx_errors Transmit errors\n")
	sb.WriteString("# TYPE nswall_interface_tx_errors counter\n")

	lines := strings.Split(string(output), "\n")
	seenInterfaces := make(map[string]bool)

	for i, line := range lines {
		if i == 0 || line == "" {
			continue
		}

		fields := strings.Fields(line)
		if len(fields) < 10 {
			continue
		}

		iface := fields[0]
		// Skip duplicate entries (IPv4 vs IPv6 rows)
		if seenInterfaces[iface] {
			continue
		}
		// Skip loopback and special interfaces
		if iface == "lo0" || strings.HasPrefix(iface, "pflog") || strings.HasPrefix(iface, "enc") {
			continue
		}
		seenInterfaces[iface] = true

		// Fields: Name Mtu Network Address Ipkts Ierrs Ibytes Opkts Oerrs Obytes Coll
		rxPackets, _ := strconv.ParseUint(fields[4], 10, 64)
		rxErrors, _ := strconv.ParseUint(fields[5], 10, 64)
		rxBytes, _ := strconv.ParseUint(fields[6], 10, 64)
		txPackets, _ := strconv.ParseUint(fields[7], 10, 64)
		txErrors, _ := strconv.ParseUint(fields[8], 10, 64)
		txBytes, _ := strconv.ParseUint(fields[9], 10, 64)

		sb.WriteString(fmt.Sprintf("nswall_interface_rx_bytes{interface=\"%s\"} %d\n", iface, rxBytes))
		sb.WriteString(fmt.Sprintf("nswall_interface_tx_bytes{interface=\"%s\"} %d\n", iface, txBytes))
		sb.WriteString(fmt.Sprintf("nswall_interface_rx_packets{interface=\"%s\"} %d\n", iface, rxPackets))
		sb.WriteString(fmt.Sprintf("nswall_interface_tx_packets{interface=\"%s\"} %d\n", iface, txPackets))
		sb.WriteString(fmt.Sprintf("nswall_interface_rx_errors{interface=\"%s\"} %d\n", iface, rxErrors))
		sb.WriteString(fmt.Sprintf("nswall_interface_tx_errors{interface=\"%s\"} %d\n", iface, txErrors))
	}
	sb.WriteString("\n")
}
