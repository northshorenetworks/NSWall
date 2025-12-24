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
	// PF state count
	output, err := exec.Command("pfctl", "-si").Output()
	if err != nil {
		return
	}

	lines := strings.Split(string(output), "\n")
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
	}

	// PF enabled status
	if strings.Contains(string(output), "Status: Enabled") {
		sb.WriteString("# HELP nswall_pf_enabled PF enabled status\n")
		sb.WriteString("# TYPE nswall_pf_enabled gauge\n")
		sb.WriteString("nswall_pf_enabled 1\n\n")
	} else {
		sb.WriteString("# HELP nswall_pf_enabled PF enabled status\n")
		sb.WriteString("# TYPE nswall_pf_enabled gauge\n")
		sb.WriteString("nswall_pf_enabled 0\n\n")
	}
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
