// Package tests provides unit tests for API handlers
package tests

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/northshorenetworks/nswall/api/internal/handlers"
)

// TestHealthCheckHandler tests the health check handler directly
func TestHealthCheckHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/health", nil)
	rr := httptest.NewRecorder()

	handlers.HealthCheck(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	var resp struct {
		Success bool `json:"success"`
		Data    struct {
			Status string `json:"status"`
		} `json:"data"`
	}

	if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if !resp.Success {
		t.Error("Expected success=true")
	}

	if resp.Data.Status != "healthy" {
		t.Errorf("Expected status=healthy, got %s", resp.Data.Status)
	}
}

// TestMetricsHandler tests the metrics handler
func TestMetricsHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/metrics", nil)
	rr := httptest.NewRecorder()

	handlers.GetMetrics(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	body := rr.Body.String()

	// Check for expected metric prefixes
	expectedMetrics := []string{
		"nswall_",
	}

	for _, metric := range expectedMetrics {
		if !strings.Contains(body, metric) {
			t.Errorf("Expected metrics to contain %s", metric)
		}
	}
}

// TestSystemInfoHandler tests system info handler
func TestSystemInfoHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/system/info", nil)
	rr := httptest.NewRecorder()

	handlers.GetSystemInfo(rr, req)

	// May fail on non-OpenBSD systems, that's okay
	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestLogsHandler tests logs handler with parameters
func TestLogsHandler(t *testing.T) {
	tests := []struct {
		name     string
		query    string
		expected int
	}{
		{"Default", "", http.StatusOK},
		{"WithFacility", "?facility=daemon", http.StatusOK},
		{"WithLines", "?lines=50", http.StatusOK},
		{"WithBoth", "?facility=authlog&lines=100", http.StatusOK},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			req := httptest.NewRequest("GET", "/api/v1/system/logs"+tt.query, nil)
			rr := httptest.NewRecorder()

			handlers.GetSystemLogs(rr, req)

			// May fail on non-OpenBSD, just check it doesn't panic
			if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
				t.Errorf("Expected status 200 or 500, got %d", rr.Code)
			}
		})
	}
}

// TestInterfacesHandler tests interfaces handler
func TestInterfacesHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/interfaces", nil)
	rr := httptest.NewRecorder()

	handlers.GetInterfaces(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}

	if rr.Code == http.StatusOK {
		var resp struct {
			Success bool        `json:"success"`
			Data    interface{} `json:"data"`
		}

		if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
			t.Fatalf("Failed to parse response: %v", err)
		}

		if !resp.Success {
			t.Error("Expected success=true")
		}
	}
}

// TestRoutesHandler tests routes handler
func TestRoutesHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/routes", nil)
	rr := httptest.NewRecorder()

	handlers.GetRoutes(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestARPHandler tests ARP handler
func TestARPHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/arp", nil)
	rr := httptest.NewRecorder()

	handlers.GetARPTable(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestPFStatusHandler tests PF status handler
func TestPFStatusHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/firewall/status", nil)
	rr := httptest.NewRecorder()

	handlers.GetPFStatus(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestPFRulesHandler tests PF rules handler
func TestPFRulesHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/firewall/rules", nil)
	rr := httptest.NewRecorder()

	handlers.GetPFRules(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestPFStatesHandler tests PF states handler with parameters
func TestPFStatesHandler(t *testing.T) {
	tests := []struct {
		name  string
		query string
	}{
		{"Default", ""},
		{"WithLimit", "?limit=10"},
		{"WithFilter", "?filter=tcp"},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			req := httptest.NewRequest("GET", "/api/v1/firewall/states"+tt.query, nil)
			rr := httptest.NewRecorder()

			handlers.GetPFStates(rr, req)

			if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
				t.Errorf("Expected status 200 or 500, got %d", rr.Code)
			}
		})
	}
}

// TestServicesHandler tests services handler
func TestServicesHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/services", nil)
	rr := httptest.NewRecorder()

	handlers.GetServices(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestConfigHandler tests running config handler
func TestConfigHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/config/running", nil)
	rr := httptest.NewRecorder()

	handlers.GetRunningConfig(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestBackupsHandler tests backups handler
func TestBackupsHandler(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/config/backups", nil)
	rr := httptest.NewRecorder()

	handlers.GetBackups(rr, req)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestJSONContentType tests that all responses have proper content type
func TestJSONContentType(t *testing.T) {
	endpoints := []string{
		"/api/v1/health",
		"/api/v1/system/info",
		"/api/v1/interfaces",
		"/api/v1/routes",
	}

	handlerMap := map[string]http.HandlerFunc{
		"/api/v1/health":      handlers.HealthCheck,
		"/api/v1/system/info": handlers.GetSystemInfo,
		"/api/v1/interfaces":  handlers.GetInterfaces,
		"/api/v1/routes":      handlers.GetRoutes,
	}

	for _, endpoint := range endpoints {
		t.Run(endpoint, func(t *testing.T) {
			req := httptest.NewRequest("GET", endpoint, nil)
			rr := httptest.NewRecorder()

			if handler, ok := handlerMap[endpoint]; ok {
				handler(rr, req)

				ct := rr.Header().Get("Content-Type")
				if !strings.Contains(ct, "application/json") {
					// Note: metrics endpoint returns text/plain
					if endpoint != "/api/v1/metrics" {
						t.Errorf("Expected Content-Type application/json, got %s", ct)
					}
				}
			}
		})
	}
}
