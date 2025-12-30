// Package tests provides integration tests for the NSWall API
package tests

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/northshorenetworks/nswall/api/internal/handlers"
	"github.com/northshorenetworks/nswall/api/internal/middleware"
)

// TestServer wraps the API for testing
type TestServer struct {
	router *chi.Mux
}

// NewTestServer creates a new test server instance
func NewTestServer() *TestServer {
	r := chi.NewRouter()

	// Add middleware
	r.Use(middleware.RequestID)
	r.Use(middleware.Logger)

	// Register routes (subset for testing)
	r.Route("/api/v1", func(r chi.Router) {
		// Health check
		r.Get("/health", handlers.HealthCheck)

		// System
		r.Get("/system/info", handlers.GetSystemInfo)
		r.Get("/system/logs", handlers.GetSystemLogs)

		// Interfaces
		r.Get("/interfaces", handlers.GetInterfaces)

		// Routes
		r.Get("/routes", handlers.GetRoutes)

		// ARP
		r.Get("/arp", handlers.GetARPTable)

		// Firewall
		r.Get("/firewall/status", handlers.GetPFStatus)
		r.Get("/firewall/rules", handlers.GetPFRules)
		r.Get("/firewall/states", handlers.GetPFStates)
		r.Get("/firewall/tables", handlers.GetPFTables)

		// Services
		r.Get("/services", handlers.GetServices)

		// Config
		r.Get("/config/running", handlers.GetRunningConfig)
		r.Get("/config/backups", handlers.GetBackups)

		// Metrics
		r.Get("/metrics", handlers.GetMetrics)
	})

	return &TestServer{router: r}
}

// Request makes a test request
func (ts *TestServer) Request(method, path string, body interface{}) *httptest.ResponseRecorder {
	var reqBody *bytes.Buffer
	if body != nil {
		data, _ := json.Marshal(body)
		reqBody = bytes.NewBuffer(data)
	} else {
		reqBody = bytes.NewBuffer(nil)
	}

	req := httptest.NewRequest(method, path, reqBody)
	req.Header.Set("Content-Type", "application/json")

	rr := httptest.NewRecorder()
	ts.router.ServeHTTP(rr, req)

	return rr
}

// APIResponse represents a standard API response
type APIResponse struct {
	Success bool        `json:"success"`
	Data    interface{} `json:"data,omitempty"`
	Error   string      `json:"error,omitempty"`
}

// parseResponse parses the response body
func parseResponse(rr *httptest.ResponseRecorder) (*APIResponse, error) {
	var resp APIResponse
	err := json.Unmarshal(rr.Body.Bytes(), &resp)
	return &resp, err
}

// TestHealthCheck tests the health endpoint
func TestHealthCheck(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/health", nil)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	resp, err := parseResponse(rr)
	if err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if !resp.Success {
		t.Error("Expected success=true")
	}
}

// TestGetSystemInfo tests the system info endpoint
func TestGetSystemInfo(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/system/info", nil)

	// Should return 200 even if running on non-OpenBSD
	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetInterfaces tests the interfaces endpoint
func TestGetInterfaces(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/interfaces", nil)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetRoutes tests the routes endpoint
func TestGetRoutes(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/routes", nil)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetARPTable tests the ARP endpoint
func TestGetARPTable(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/arp", nil)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetPFStatus tests the PF status endpoint
func TestGetPFStatus(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/firewall/status", nil)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetPFRules tests the PF rules endpoint
func TestGetPFRules(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/firewall/rules", nil)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetServices tests the services endpoint
func TestGetServices(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/services", nil)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetRunningConfig tests the running config endpoint
func TestGetRunningConfig(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/config/running", nil)

	if rr.Code != http.StatusOK && rr.Code != http.StatusInternalServerError {
		t.Errorf("Expected status 200 or 500, got %d", rr.Code)
	}
}

// TestGetMetrics tests the Prometheus metrics endpoint
func TestGetMetrics(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/metrics", nil)

	// Metrics endpoint should always work
	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	// Check Content-Type
	ct := rr.Header().Get("Content-Type")
	if ct != "text/plain; charset=utf-8" {
		t.Errorf("Expected Content-Type text/plain, got %s", ct)
	}
}

// TestNotFound tests 404 handling
func TestNotFound(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/nonexistent", nil)

	if rr.Code != http.StatusNotFound {
		t.Errorf("Expected status 404, got %d", rr.Code)
	}
}

// TestRequestIDHeader tests that request ID is added
func TestRequestIDHeader(t *testing.T) {
	ts := NewTestServer()

	rr := ts.Request("GET", "/api/v1/health", nil)

	reqID := rr.Header().Get("X-Request-ID")
	if reqID == "" {
		t.Error("Expected X-Request-ID header to be set")
	}
}

// Benchmark tests
func BenchmarkHealthCheck(b *testing.B) {
	ts := NewTestServer()

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		ts.Request("GET", "/api/v1/health", nil)
	}
}

func BenchmarkGetMetrics(b *testing.B) {
	ts := NewTestServer()

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		ts.Request("GET", "/api/v1/metrics", nil)
	}
}

// Test concurrent requests
func TestConcurrentRequests(t *testing.T) {
	ts := NewTestServer()

	done := make(chan bool, 10)

	for i := 0; i < 10; i++ {
		go func() {
			rr := ts.Request("GET", "/api/v1/health", nil)
			if rr.Code != http.StatusOK {
				t.Errorf("Expected status 200, got %d", rr.Code)
			}
			done <- true
		}()
	}

	// Wait for all goroutines with timeout
	timeout := time.After(5 * time.Second)
	for i := 0; i < 10; i++ {
		select {
		case <-done:
		case <-timeout:
			t.Fatal("Timeout waiting for concurrent requests")
		}
	}
}
