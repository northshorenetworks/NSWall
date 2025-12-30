//go:build integration
// +build integration

// Package tests provides end-to-end integration tests for the NSWall API
package tests

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"time"

	"github.com/go-chi/chi/v5"
	"nswall-api/internal/handlers"
	"nswall-api/internal/middleware"
	"nswall-api/internal/models"
)

// E2ETestSuite holds the test server and auth token
type E2ETestSuite struct {
	server    *httptest.Server
	authToken string
	client    *http.Client
}

// SetupE2ETestSuite creates a full test server with all routes
func SetupE2ETestSuite(t *testing.T) *E2ETestSuite {
	// Enable dev mode for testing
	os.Setenv("NSWALL_DEV_MODE", "1")

	r := chi.NewRouter()

	// Middleware stack
	r.Use(middleware.RequestID)
	r.Use(middleware.Logger)
	r.Use(middleware.Recoverer)

	// Public routes
	r.Post("/api/v1/auth/login", handlers.Login)
	r.Get("/api/v1/health", handlers.HealthCheck)

	// Protected routes
	r.Group(func(r chi.Router) {
		r.Use(middleware.JWTAuth)

		// System
		r.Get("/api/v1/system/info", handlers.GetSystemInfo)
		r.Get("/api/v1/system/logs", handlers.GetSystemLogs)

		// Interfaces
		r.Get("/api/v1/interfaces", handlers.GetInterfaces)
		r.Get("/api/v1/interfaces/{name}", handlers.GetInterface)

		// Routes
		r.Get("/api/v1/routes", handlers.GetRoutes)
		r.Post("/api/v1/routes", handlers.AddRoute)
		r.Delete("/api/v1/routes/{destination}", handlers.DeleteRoute)

		// ARP
		r.Get("/api/v1/arp", handlers.GetARPTable)
		r.Delete("/api/v1/arp/{ip}", handlers.DeleteARPEntry)
		r.Post("/api/v1/arp/flush", handlers.FlushARP)

		// Firewall
		r.Get("/api/v1/firewall/status", handlers.GetPFStatus)
		r.Get("/api/v1/firewall/rules", handlers.GetPFRules)
		r.Post("/api/v1/firewall/rules", handlers.AddPFRule)
		r.Get("/api/v1/firewall/states", handlers.GetPFStates)
		r.Get("/api/v1/firewall/tables", handlers.GetPFTables)

		// Services
		r.Get("/api/v1/services", handlers.GetServices)
		r.Post("/api/v1/services/{name}/start", handlers.StartService)
		r.Post("/api/v1/services/{name}/stop", handlers.StopService)

		// Config
		r.Get("/api/v1/config/running", handlers.GetRunningConfig)
		r.Get("/api/v1/config/backups", handlers.GetBackups)
		r.Post("/api/v1/config/backup", handlers.CreateBackup)

		// Metrics
		r.Get("/api/v1/metrics", handlers.GetMetrics)
	})

	server := httptest.NewServer(r)

	suite := &E2ETestSuite{
		server: server,
		client: &http.Client{Timeout: 10 * time.Second},
	}

	return suite
}

// Teardown cleans up the test server
func (s *E2ETestSuite) Teardown() {
	s.server.Close()
}

// Request makes an HTTP request to the test server
func (s *E2ETestSuite) Request(method, path string, body interface{}) (*http.Response, error) {
	var reqBody io.Reader
	if body != nil {
		data, _ := json.Marshal(body)
		reqBody = bytes.NewBuffer(data)
	}

	req, err := http.NewRequest(method, s.server.URL+path, reqBody)
	if err != nil {
		return nil, err
	}

	req.Header.Set("Content-Type", "application/json")
	if s.authToken != "" {
		req.Header.Set("Authorization", "Bearer "+s.authToken)
	}

	return s.client.Do(req)
}

// Login authenticates and stores the token
func (s *E2ETestSuite) Login(username, password string) error {
	resp, err := s.Request("POST", "/api/v1/auth/login", map[string]string{
		"username": username,
		"password": password,
	})
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("login failed with status %d", resp.StatusCode)
	}

	var result struct {
		Success bool `json:"success"`
		Data    struct {
			Token string `json:"token"`
		} `json:"data"`
	}

	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return err
	}

	s.authToken = result.Data.Token
	return nil
}

// ParseJSON parses the response body as JSON
func ParseJSON(resp *http.Response, v interface{}) error {
	defer resp.Body.Close()
	return json.NewDecoder(resp.Body).Decode(v)
}

// === E2E Test Cases ===

func TestE2E_HealthCheck(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	resp, err := suite.Request("GET", "/api/v1/health", nil)
	if err != nil {
		t.Fatalf("Request failed: %v", err)
	}

	if resp.StatusCode != http.StatusOK {
		t.Errorf("Expected 200, got %d", resp.StatusCode)
	}

	var result APIResponse
	if err := ParseJSON(resp, &result); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if !result.Success {
		t.Error("Expected success=true")
	}
}

func TestE2E_AuthenticationFlow(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	// Test unauthenticated access to protected route
	resp, err := suite.Request("GET", "/api/v1/system/info", nil)
	if err != nil {
		t.Fatalf("Request failed: %v", err)
	}

	if resp.StatusCode != http.StatusUnauthorized {
		t.Errorf("Expected 401 for unauthenticated request, got %d", resp.StatusCode)
	}
	resp.Body.Close()

	// Login with invalid credentials
	resp, err = suite.Request("POST", "/api/v1/auth/login", map[string]string{
		"username": "invalid",
		"password": "invalid",
	})
	if err != nil {
		t.Fatalf("Request failed: %v", err)
	}
	if resp.StatusCode == http.StatusOK {
		t.Error("Expected login to fail with invalid credentials")
	}
	resp.Body.Close()
}

func TestE2E_SystemEndpoints(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	// Skip auth for dev mode testing
	os.Setenv("NSWALL_DEV_MODE", "1")

	tests := []struct {
		name     string
		method   string
		path     string
		wantCode int
	}{
		{"Health", "GET", "/api/v1/health", http.StatusOK},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			resp, err := suite.Request(tc.method, tc.path, nil)
			if err != nil {
				t.Fatalf("Request failed: %v", err)
			}
			defer resp.Body.Close()

			if resp.StatusCode != tc.wantCode {
				t.Errorf("Expected %d, got %d", tc.wantCode, resp.StatusCode)
			}
		})
	}
}

func TestE2E_FirewallWorkflow(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	// This test would require auth - skip detailed testing for now
	// In a full setup, we would:
	// 1. Login
	// 2. Get current firewall status
	// 3. Add a test rule
	// 4. Verify rule was added
	// 5. Delete the rule
	// 6. Verify rule was removed

	t.Log("Firewall workflow test placeholder - requires full auth setup")
}

func TestE2E_ConcurrentRequests(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	const numRequests = 50
	results := make(chan int, numRequests)
	errors := make(chan error, numRequests)

	for i := 0; i < numRequests; i++ {
		go func() {
			resp, err := suite.Request("GET", "/api/v1/health", nil)
			if err != nil {
				errors <- err
				return
			}
			defer resp.Body.Close()
			results <- resp.StatusCode
		}()
	}

	successCount := 0
	for i := 0; i < numRequests; i++ {
		select {
		case code := <-results:
			if code == http.StatusOK {
				successCount++
			}
		case err := <-errors:
			t.Errorf("Request error: %v", err)
		case <-time.After(30 * time.Second):
			t.Fatal("Timeout waiting for concurrent requests")
		}
	}

	if successCount != numRequests {
		t.Errorf("Expected %d successful requests, got %d", numRequests, successCount)
	}
}

func TestE2E_ResponseHeaders(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	resp, err := suite.Request("GET", "/api/v1/health", nil)
	if err != nil {
		t.Fatalf("Request failed: %v", err)
	}
	defer resp.Body.Close()

	// Check for expected headers
	headers := []string{
		"X-Request-ID",
		"Content-Type",
	}

	for _, h := range headers {
		if resp.Header.Get(h) == "" {
			t.Errorf("Expected header %s to be set", h)
		}
	}
}

func TestE2E_ContentNegotiation(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	resp, err := suite.Request("GET", "/api/v1/health", nil)
	if err != nil {
		t.Fatalf("Request failed: %v", err)
	}
	defer resp.Body.Close()

	ct := resp.Header.Get("Content-Type")
	if ct != "application/json" && ct != "application/json; charset=utf-8" {
		t.Errorf("Expected Content-Type application/json, got %s", ct)
	}
}

func TestE2E_ErrorHandling(t *testing.T) {
	suite := SetupE2ETestSuite(t)
	defer suite.Teardown()

	// Test 404 handling
	resp, err := suite.Request("GET", "/api/v1/nonexistent", nil)
	if err != nil {
		t.Fatalf("Request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusNotFound {
		t.Errorf("Expected 404, got %d", resp.StatusCode)
	}

	// Test method not allowed
	resp, err = suite.Request("DELETE", "/api/v1/health", nil)
	if err != nil {
		t.Fatalf("Request failed: %v", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusMethodNotAllowed && resp.StatusCode != http.StatusNotFound {
		t.Errorf("Expected 405 or 404, got %d", resp.StatusCode)
	}
}

// Benchmark E2E
func BenchmarkE2E_HealthCheck(b *testing.B) {
	suite := &E2ETestSuite{}
	os.Setenv("NSWALL_DEV_MODE", "1")

	r := chi.NewRouter()
	r.Get("/api/v1/health", handlers.HealthCheck)
	suite.server = httptest.NewServer(r)
	suite.client = &http.Client{Timeout: 10 * time.Second}
	defer suite.Teardown()

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		resp, _ := suite.Request("GET", "/api/v1/health", nil)
		if resp != nil {
			resp.Body.Close()
		}
	}
}

func BenchmarkE2E_ConcurrentHealth(b *testing.B) {
	suite := &E2ETestSuite{}
	os.Setenv("NSWALL_DEV_MODE", "1")

	r := chi.NewRouter()
	r.Get("/api/v1/health", handlers.HealthCheck)
	suite.server = httptest.NewServer(r)
	suite.client = &http.Client{Timeout: 10 * time.Second}
	defer suite.Teardown()

	b.ResetTimer()
	b.RunParallel(func(pb *testing.PB) {
		for pb.Next() {
			resp, _ := suite.Request("GET", "/api/v1/health", nil)
			if resp != nil {
				resp.Body.Close()
			}
		}
	})
}
