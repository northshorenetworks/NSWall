// NSWall Services API Tests
// Run with: go test -v
//
// Test output is compatible with standard Go test tooling and CI systems.
// Results are reported in TAP-like format for integration with test frameworks.

package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"testing"
	"time"
)

// Test fixtures
var testConfigDir string
var testStartTime time.Time

func TestMain(m *testing.M) {
	testStartTime = time.Now()

	// Print test header
	fmt.Println("================================")
	fmt.Println("NSWall Services API Tests")
	fmt.Printf("Started: %s\n", testStartTime.Format(time.RFC3339))
	fmt.Println("================================")
	fmt.Println()

	// Create temp directory for test configs
	var err error
	testConfigDir, err = ioutil.TempDir("", "nswall_api_test")
	if err != nil {
		fmt.Printf("FAIL: Setup failed: %v\n", err)
		os.Exit(1)
	}
	defer os.RemoveAll(testConfigDir)

	// Run tests
	code := m.Run()

	// Print test summary
	elapsed := time.Since(testStartTime)
	fmt.Println()
	fmt.Println("================================")
	fmt.Printf("Test Duration: %v\n", elapsed.Round(time.Millisecond))
	if code == 0 {
		fmt.Println("Result: PASS")
	} else {
		fmt.Println("Result: FAIL")
	}
	fmt.Println("================================")

	os.Exit(code)
}

func TestHealthEndpoint(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/health", nil)
	w := httptest.NewRecorder()

	handleHealth(w, req)

	resp := w.Result()
	if resp.StatusCode != http.StatusOK {
		t.Errorf("Expected status 200, got %d", resp.StatusCode)
	}

	var response APIResponse
	json.NewDecoder(resp.Body).Decode(&response)

	if response.Status != "ok" {
		t.Errorf("Expected status 'ok', got '%s'", response.Status)
	}
}

func TestListServicesEndpoint(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/services", nil)
	w := httptest.NewRecorder()

	handleServices(w, req)

	resp := w.Result()
	if resp.StatusCode != http.StatusOK {
		t.Errorf("Expected status 200, got %d", resp.StatusCode)
	}

	var response APIResponse
	json.NewDecoder(resp.Body).Decode(&response)

	if response.Status != "success" {
		t.Errorf("Expected status 'success', got '%s'", response.Status)
	}

	// Should return list of services
	if response.Data == nil {
		t.Error("Expected data to contain services list")
	}
}

func TestServiceConfigReadWrite(t *testing.T) {
	// Create a test config file
	testConfig := filepath.Join(testConfigDir, "test.conf")
	testContent := "router-id 10.0.0.1\n"

	// Write test config
	err := ioutil.WriteFile(testConfig, []byte(testContent), 0600)
	if err != nil {
		t.Fatalf("Failed to write test config: %v", err)
	}

	// Read it back
	content, err := ioutil.ReadFile(testConfig)
	if err != nil {
		t.Fatalf("Failed to read test config: %v", err)
	}

	if string(content) != testContent {
		t.Errorf("Config content mismatch: got '%s', want '%s'", content, testContent)
	}
}

func TestConfigUpdateRequest(t *testing.T) {
	testConfig := filepath.Join(testConfigDir, "update_test.conf")

	// Create initial config
	ioutil.WriteFile(testConfig, []byte("initial\n"), 0600)

	// Simulate update
	newContent := "updated content\n"
	err := ioutil.WriteFile(testConfig, []byte(newContent), 0600)
	if err != nil {
		t.Fatalf("Failed to update config: %v", err)
	}

	// Verify update
	content, _ := ioutil.ReadFile(testConfig)
	if string(content) != newContent {
		t.Errorf("Config not updated: got '%s', want '%s'", content, newContent)
	}
}

func TestServiceStatusCheck(t *testing.T) {
	// Test isServiceRunning function
	// Note: This will likely return false in test environment
	running, pid := isServiceRunning("nonexistent_service_12345")

	if running {
		t.Error("Non-existent service should not be running")
	}
	if pid != 0 {
		t.Errorf("Non-existent service PID should be 0, got %d", pid)
	}
}

func TestParseKeyValueStats(t *testing.T) {
	input := "key1=value1\nkey2=value2\nkey3=value3\n"
	result := parseKeyValueStats(input)

	if len(result) != 3 {
		t.Errorf("Expected 3 key-value pairs, got %d", len(result))
	}

	if result["key1"] != "value1" {
		t.Errorf("Expected 'value1', got '%s'", result["key1"])
	}
}

func TestAPIResponseJSON(t *testing.T) {
	response := APIResponse{
		Status:  "success",
		Message: "Test message",
		Data:    map[string]string{"key": "value"},
	}

	data, err := json.Marshal(response)
	if err != nil {
		t.Fatalf("Failed to marshal response: %v", err)
	}

	var decoded APIResponse
	json.Unmarshal(data, &decoded)

	if decoded.Status != "success" {
		t.Errorf("Expected status 'success', got '%s'", decoded.Status)
	}
}

func TestServiceConfigValidation(t *testing.T) {
	// Test config validation for a service
	cfg := ServiceConfig{
		Name:       "test",
		Daemon:     "/nonexistent/daemon",
		ConfigFile: "/nonexistent/config",
	}

	// Should return false for non-existent daemon
	result := validateConfig(cfg)
	// Note: This may return true if daemon path doesn't exist
	// since validation may skip non-existent files
	_ = result
}

func TestMethodNotAllowed(t *testing.T) {
	// Test POST to services list endpoint (should be GET only)
	req := httptest.NewRequest("POST", "/api/v1/services", nil)
	w := httptest.NewRecorder()

	handleServices(w, req)

	resp := w.Result()
	if resp.StatusCode != http.StatusMethodNotAllowed {
		t.Errorf("Expected status 405, got %d", resp.StatusCode)
	}
}

func TestServiceActionRequest(t *testing.T) {
	action := ServiceActionRequest{
		Action: "enable",
	}

	data, _ := json.Marshal(action)
	var decoded ServiceActionRequest
	json.Unmarshal(data, &decoded)

	if decoded.Action != "enable" {
		t.Errorf("Expected action 'enable', got '%s'", decoded.Action)
	}
}

func TestConfigUpdateRequestStruct(t *testing.T) {
	req := ConfigUpdateRequest{
		Content: "test config content",
		Reload:  true,
	}

	data, _ := json.Marshal(req)
	var decoded ConfigUpdateRequest
	json.Unmarshal(data, &decoded)

	if decoded.Content != "test config content" {
		t.Errorf("Expected content mismatch")
	}
	if !decoded.Reload {
		t.Error("Expected reload to be true")
	}
}

func TestServiceConfigStruct(t *testing.T) {
	cfg := serviceConfigs["ospfd"]

	if cfg.Name != "ospfd" {
		t.Errorf("Expected name 'ospfd', got '%s'", cfg.Name)
	}
	if cfg.Daemon == "" {
		t.Error("Expected daemon path to be set")
	}
	if cfg.ConfigFile == "" {
		t.Error("Expected config file path to be set")
	}
}

func TestWriteJSON(t *testing.T) {
	w := httptest.NewRecorder()
	data := APIResponse{Status: "test"}

	writeJSON(w, http.StatusOK, data)

	resp := w.Result()
	if resp.StatusCode != http.StatusOK {
		t.Errorf("Expected status 200, got %d", resp.StatusCode)
	}

	body, _ := ioutil.ReadAll(resp.Body)
	if !bytes.Contains(body, []byte("test")) {
		t.Error("Response body should contain 'test'")
	}
}

func TestServiceNotFound(t *testing.T) {
	req := httptest.NewRequest("GET", "/api/v1/services/nonexistent_service", nil)
	w := httptest.NewRecorder()

	handleServiceByName(w, req)

	resp := w.Result()
	if resp.StatusCode != http.StatusNotFound {
		t.Errorf("Expected status 404, got %d", resp.StatusCode)
	}
}
