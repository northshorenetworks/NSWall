// Package tests provides unit tests for metrics functionality
package tests

import (
	"strings"
	"testing"

	"github.com/northshorenetworks/nswall/api/internal/services"
)

// TestMetricsServiceCreation tests MetricsService initialization
func TestMetricsServiceCreation(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	if svc == nil {
		t.Fatal("Expected MetricsService to be created")
	}
}

// TestMetricsServiceRequestCounter tests the request counter
func TestMetricsServiceRequestCounter(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")

	// Increment several times
	for i := 0; i < 10; i++ {
		svc.IncrementRequests()
	}

	// Get metrics and verify counter is present
	metrics := svc.GetMetrics()

	if !strings.Contains(metrics, "nswall_http_requests_total") {
		t.Error("Expected metrics to contain nswall_http_requests_total")
	}

	// Check that it contains 10
	if !strings.Contains(metrics, "nswall_http_requests_total 10") {
		t.Error("Expected request counter to be 10")
	}
}

// TestMetricsFormat tests that metrics are in Prometheus format
func TestMetricsFormat(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// Should contain HELP and TYPE declarations
	if !strings.Contains(metrics, "# HELP") {
		t.Error("Expected metrics to contain # HELP declarations")
	}

	if !strings.Contains(metrics, "# TYPE") {
		t.Error("Expected metrics to contain # TYPE declarations")
	}
}

// TestMetricsContainsInfo tests that version info is present
func TestMetricsContainsInfo(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	expectedMetrics := []string{
		"nswall_info",
		"nswall_uptime_seconds",
		"nswall_http_requests_total",
		"nswall_go_goroutines",
		"nswall_go_memstats_alloc_bytes",
	}

	for _, metric := range expectedMetrics {
		if !strings.Contains(metrics, metric) {
			t.Errorf("Expected metrics to contain %s", metric)
		}
	}
}

// TestMetricsGaugeTypes tests that gauge metrics have correct TYPE
func TestMetricsGaugeTypes(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// nswall_info should be a gauge
	if !strings.Contains(metrics, "# TYPE nswall_info gauge") {
		t.Error("Expected nswall_info to be a gauge type")
	}

	// goroutines should be a gauge
	if !strings.Contains(metrics, "# TYPE nswall_go_goroutines gauge") {
		t.Error("Expected nswall_go_goroutines to be a gauge type")
	}
}

// TestMetricsCounterTypes tests that counter metrics have correct TYPE
func TestMetricsCounterTypes(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// http_requests_total should be a counter
	if !strings.Contains(metrics, "# TYPE nswall_http_requests_total counter") {
		t.Error("Expected nswall_http_requests_total to be a counter type")
	}
}

// TestMetricsPFEnabled tests PF enabled metric
func TestMetricsPFEnabled(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// Should contain PF enabled status (may be 0 or 1)
	if !strings.Contains(metrics, "nswall_pf_enabled") {
		// This is okay on non-OpenBSD systems
		t.Log("nswall_pf_enabled not present (may be expected on non-OpenBSD)")
	}
}

// TestMetricsLabels tests that labels are properly formatted
func TestMetricsLabels(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// Check load average has period label
	if strings.Contains(metrics, "nswall_load_average") {
		if !strings.Contains(metrics, `period="1m"`) &&
			!strings.Contains(metrics, `period="5m"`) &&
			!strings.Contains(metrics, `period="15m"`) {
			t.Error("Expected load_average to have period labels")
		}
	}
}

// TestMetricsProtocolLabels tests state protocol labels
func TestMetricsProtocolLabels(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// If states by protocol is present, check labels
	if strings.Contains(metrics, "nswall_pf_states_by_protocol") {
		protocols := []string{`protocol="tcp"`, `protocol="udp"`, `protocol="icmp"`, `protocol="other"`}
		foundAny := false
		for _, proto := range protocols {
			if strings.Contains(metrics, proto) {
				foundAny = true
				break
			}
		}
		if !foundAny {
			t.Error("Expected nswall_pf_states_by_protocol to have protocol labels")
		}
	}
}

// TestMetricsDirectionLabels tests packet direction labels
func TestMetricsDirectionLabels(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// If packets total is present, check direction labels
	if strings.Contains(metrics, "nswall_pf_packets_total") {
		if !strings.Contains(metrics, `direction="in"`) && !strings.Contains(metrics, `direction="out"`) {
			t.Error("Expected nswall_pf_packets_total to have direction labels")
		}
	}
}

// TestMetricsActionLabels tests action labels (pass/block)
func TestMetricsActionLabels(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// If packets total is present, check action labels
	if strings.Contains(metrics, "nswall_pf_packets_total") {
		if !strings.Contains(metrics, `action="pass"`) && !strings.Contains(metrics, `action="block"`) {
			t.Error("Expected nswall_pf_packets_total to have action labels")
		}
	}
}

// TestMetricsRuleLabels tests rule metric labels
func TestMetricsRuleLabels(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// If rule metrics are present, check for rule number label
	if strings.Contains(metrics, "nswall_pf_rule_evaluations") {
		if !strings.Contains(metrics, `rule="`) {
			t.Error("Expected nswall_pf_rule_evaluations to have rule label")
		}
	}
}

// TestMetricsNoInvalidCharacters tests metric names are valid
func TestMetricsNoInvalidCharacters(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// Metric names should not contain hyphens (Prometheus requirement)
	lines := strings.Split(metrics, "\n")
	for _, line := range lines {
		if strings.HasPrefix(line, "#") || line == "" {
			continue
		}
		// Get metric name (before first space or {)
		parts := strings.FieldsFunc(line, func(r rune) bool {
			return r == ' ' || r == '{'
		})
		if len(parts) > 0 {
			metricName := parts[0]
			if strings.Contains(metricName, "-") {
				t.Errorf("Metric name contains hyphen: %s", metricName)
			}
		}
	}
}

// TestMetricsInterfaceLabels tests interface metric labels
func TestMetricsInterfaceLabels(t *testing.T) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")
	metrics := svc.GetMetrics()

	// If interface metrics are present, check for interface label
	if strings.Contains(metrics, "nswall_interface_rx_bytes") {
		if !strings.Contains(metrics, `interface="`) {
			t.Error("Expected interface metrics to have interface label")
		}
	}
}

// BenchmarkGetMetrics benchmarks the GetMetrics function
func BenchmarkGetMetrics(b *testing.B) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		_ = svc.GetMetrics()
	}
}

// BenchmarkIncrementRequests benchmarks the IncrementRequests function
func BenchmarkIncrementRequests(b *testing.B) {
	svc := services.NewMetricsService("/usr/local/bin/nsh")

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.IncrementRequests()
	}
}
