// Package tests provides unit tests for fleet handler endpoints
package tests

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/northshorenetworks/nswall/api/internal/handlers"
	"github.com/northshorenetworks/nswall/api/internal/services"
)

// TestFleetHandlerCreation tests FleetHandler initialization
func TestFleetHandlerCreation(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)

	if h == nil {
		t.Fatal("Expected FleetHandler to be created")
	}

	if h.GetFleet() == nil {
		t.Error("Expected FleetHandler to have FleetService")
	}
}

// TestFleetGetMode tests GET /fleet/mode endpoint
func TestFleetGetMode(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)

	req := httptest.NewRequest("GET", "/api/v1/fleet/mode", nil)
	rr := httptest.NewRecorder()

	h.GetMode(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	var resp struct {
		Success bool `json:"success"`
		Data    struct {
			Mode    string `json:"mode"`
			AgentID string `json:"agent_id"`
		} `json:"data"`
	}

	if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if !resp.Success {
		t.Error("Expected success=true")
	}

	if resp.Data.Mode != "standalone" {
		t.Errorf("Expected mode=standalone, got %s", resp.Data.Mode)
	}

	if resp.Data.AgentID == "" {
		t.Error("Expected agent_id to be set")
	}
}

// TestFleetSetMode tests PUT /fleet/mode endpoint
func TestFleetSetMode(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)

	tests := []struct {
		name       string
		body       map[string]string
		expectCode int
	}{
		{
			name:       "Set to manager",
			body:       map[string]string{"mode": "manager"},
			expectCode: http.StatusOK,
		},
		{
			name:       "Set to standalone",
			body:       map[string]string{"mode": "standalone"},
			expectCode: http.StatusOK,
		},
		{
			name: "Set to agent with config",
			body: map[string]string{
				"mode":        "agent",
				"manager_url": "https://manager.example.com:8443",
				"api_key":     "test-key",
			},
			expectCode: http.StatusOK,
		},
		{
			name:       "Agent mode without manager_url",
			body:       map[string]string{"mode": "agent"},
			expectCode: http.StatusBadRequest,
		},
		{
			name:       "Invalid mode",
			body:       map[string]string{"mode": "invalid"},
			expectCode: http.StatusBadRequest,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			body, _ := json.Marshal(tt.body)
			req := httptest.NewRequest("PUT", "/api/v1/fleet/mode", bytes.NewReader(body))
			req.Header.Set("Content-Type", "application/json")
			rr := httptest.NewRecorder()

			h.SetMode(rr, req)

			if rr.Code != tt.expectCode {
				t.Errorf("Expected status %d, got %d: %s", tt.expectCode, rr.Code, rr.Body.String())
			}
		})
	}
}

// TestFleetGetAgents tests GET /fleet/agents endpoint
func TestFleetGetAgents(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)

	// Set manager mode first
	h.GetFleet().SetMode(services.ModeManager, "", "")

	// Register some agents
	h.GetFleet().RegisterAgent(&services.AgentInfo{
		ID:       "agent-1",
		Hostname: "fw-1",
		Status:   services.StatusOnline,
	})
	h.GetFleet().RegisterAgent(&services.AgentInfo{
		ID:       "agent-2",
		Hostname: "fw-2",
		Status:   services.StatusOnline,
	})

	req := httptest.NewRequest("GET", "/api/v1/fleet/agents", nil)
	rr := httptest.NewRecorder()

	h.GetAgents(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	var resp struct {
		Success bool                  `json:"success"`
		Data    []services.AgentInfo `json:"data"`
	}

	if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if !resp.Success {
		t.Error("Expected success=true")
	}

	if len(resp.Data) != 2 {
		t.Errorf("Expected 2 agents, got %d", len(resp.Data))
	}
}

// TestFleetGetAgent tests GET /fleet/agent endpoint
func TestFleetGetAgent(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	// Register an agent
	h.GetFleet().RegisterAgent(&services.AgentInfo{
		ID:       "test-agent",
		Hostname: "test-fw",
		Version:  "2.0.0",
	})

	tests := []struct {
		name       string
		agentID    string
		expectCode int
	}{
		{"Existing agent", "test-agent", http.StatusOK},
		{"Non-existent agent", "missing", http.StatusNotFound},
		{"No ID provided", "", http.StatusBadRequest},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			url := "/api/v1/fleet/agent"
			if tt.agentID != "" {
				url += "?id=" + tt.agentID
			}

			req := httptest.NewRequest("GET", url, nil)
			rr := httptest.NewRecorder()

			h.GetAgent(rr, req)

			if rr.Code != tt.expectCode {
				t.Errorf("Expected status %d, got %d", tt.expectCode, rr.Code)
			}
		})
	}
}

// TestFleetHeartbeat tests POST /fleet/heartbeat endpoint
func TestFleetHeartbeat(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	agent := services.AgentInfo{
		ID:        "new-agent",
		Hostname:  "new-fw",
		Version:   "2.0.0",
		Status:    services.StatusOnline,
		PFEnabled: true,
		PFStates:  500,
	}

	body, _ := json.Marshal(agent)
	req := httptest.NewRequest("POST", "/api/v1/fleet/heartbeat", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	rr := httptest.NewRecorder()

	h.Heartbeat(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	var resp struct {
		Success       bool                  `json:"success"`
		PendingConfig *services.AgentConfig `json:"pending_config"`
	}

	if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if !resp.Success {
		t.Error("Expected success=true")
	}

	// Verify agent was registered
	registered := h.GetFleet().GetAgent("new-agent")
	if registered == nil {
		t.Error("Expected agent to be registered")
	}
}

// TestFleetHeartbeatWithPendingConfig tests heartbeat returns pending config
func TestFleetHeartbeatWithPendingConfig(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	// Set pending config
	h.GetFleet().SetPendingConfig("config-agent", &services.AgentConfig{
		PFRules: "pass all",
		Version: 1,
	})

	agent := services.AgentInfo{
		ID:       "config-agent",
		Hostname: "config-fw",
	}

	body, _ := json.Marshal(agent)
	req := httptest.NewRequest("POST", "/api/v1/fleet/heartbeat", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	rr := httptest.NewRecorder()

	h.Heartbeat(rr, req)

	var resp struct {
		Success       bool                  `json:"success"`
		PendingConfig *services.AgentConfig `json:"pending_config"`
	}

	if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if resp.PendingConfig == nil {
		t.Fatal("Expected pending_config to be returned")
	}

	if resp.PendingConfig.PFRules != "pass all" {
		t.Errorf("Expected PFRules='pass all', got %s", resp.PendingConfig.PFRules)
	}
}

// TestFleetPushConfig tests POST /fleet/push-config endpoint
func TestFleetPushConfig(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	// Register an agent
	h.GetFleet().RegisterAgent(&services.AgentInfo{
		ID:       "push-agent",
		Hostname: "push-fw",
		Status:   services.StatusOnline,
	})

	pushReq := struct {
		AgentID string               `json:"agent_id"`
		Config  services.AgentConfig `json:"config"`
	}{
		AgentID: "push-agent",
		Config: services.AgentConfig{
			PFRules: "block all",
			Version: 2,
		},
	}

	body, _ := json.Marshal(pushReq)
	req := httptest.NewRequest("POST", "/api/v1/fleet/push-config", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	rr := httptest.NewRecorder()

	h.PushConfig(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d: %s", rr.Code, rr.Body.String())
	}
}

// TestFleetPushConfigNoAgentID tests push config without agent_id
func TestFleetPushConfigNoAgentID(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)

	pushReq := struct {
		Config services.AgentConfig `json:"config"`
	}{
		Config: services.AgentConfig{
			PFRules: "block all",
		},
	}

	body, _ := json.Marshal(pushReq)
	req := httptest.NewRequest("POST", "/api/v1/fleet/push-config", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	rr := httptest.NewRecorder()

	h.PushConfig(rr, req)

	if rr.Code != http.StatusBadRequest {
		t.Errorf("Expected status 400, got %d", rr.Code)
	}
}

// TestFleetReceiveEvent tests POST /fleet/events endpoint
func TestFleetReceiveEvent(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	event := services.AgentEvent{
		ID:       "evt-1",
		AgentID:  "agent-1",
		Type:     "config_applied",
		Severity: "info",
		Message:  "Configuration applied successfully",
	}

	body, _ := json.Marshal(event)
	req := httptest.NewRequest("POST", "/api/v1/fleet/events", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	rr := httptest.NewRecorder()

	h.ReceiveEvent(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	// Verify event was stored
	events := h.GetFleet().GetEvents(10, "", "")
	if len(events) == 0 {
		t.Error("Expected event to be stored")
	}
}

// TestFleetGetEvents tests GET /fleet/events endpoint
func TestFleetGetEvents(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	// Add some events
	h.GetFleet().ReceiveEvent(services.AgentEvent{
		ID:       "evt-1",
		AgentID:  "agent-1",
		Type:     "heartbeat",
		Severity: "info",
	})
	h.GetFleet().ReceiveEvent(services.AgentEvent{
		ID:       "evt-2",
		AgentID:  "agent-2",
		Type:     "config_failed",
		Severity: "error",
	})

	tests := []struct {
		name       string
		query      string
		expectCode int
	}{
		{"Default", "", http.StatusOK},
		{"With limit", "?limit=1", http.StatusOK},
		{"Filter by agent", "?agent_id=agent-1", http.StatusOK},
		{"Filter by type", "?type=config_failed", http.StatusOK},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			req := httptest.NewRequest("GET", "/api/v1/fleet/events"+tt.query, nil)
			rr := httptest.NewRecorder()

			h.GetEvents(rr, req)

			if rr.Code != tt.expectCode {
				t.Errorf("Expected status %d, got %d", tt.expectCode, rr.Code)
			}
		})
	}
}

// TestFleetGetSummary tests GET /fleet/summary endpoint
func TestFleetGetSummary(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	req := httptest.NewRequest("GET", "/api/v1/fleet/summary", nil)
	rr := httptest.NewRecorder()

	h.GetFleetSummary(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	var resp struct {
		Success bool                   `json:"success"`
		Data    map[string]interface{} `json:"data"`
	}

	if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if !resp.Success {
		t.Error("Expected success=true")
	}

	// Check expected fields
	expectedFields := []string{"total_agents", "online", "offline", "degraded", "total_states"}
	for _, field := range expectedFields {
		if _, ok := resp.Data[field]; !ok {
			t.Errorf("Expected field %s in summary", field)
		}
	}
}

// TestFleetBroadcastConfig tests POST /fleet/broadcast-config endpoint
func TestFleetBroadcastConfig(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)
	h.GetFleet().SetMode(services.ModeManager, "", "")

	// Register some online agents
	for i := 0; i < 3; i++ {
		h.GetFleet().RegisterAgent(&services.AgentInfo{
			ID:     string(rune('a' + i)),
			Status: services.StatusOnline,
		})
	}

	config := services.AgentConfig{
		PFRules: "pass all",
		Version: 1,
	}

	body, _ := json.Marshal(config)
	req := httptest.NewRequest("POST", "/api/v1/fleet/broadcast-config", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	rr := httptest.NewRecorder()

	h.BroadcastConfig(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("Expected status 200, got %d", rr.Code)
	}

	var resp struct {
		Success bool `json:"success"`
		Data    struct {
			QueuedAgents int `json:"queued_agents"`
			TotalAgents  int `json:"total_agents"`
		} `json:"data"`
	}

	if err := json.Unmarshal(rr.Body.Bytes(), &resp); err != nil {
		t.Fatalf("Failed to parse response: %v", err)
	}

	if resp.Data.QueuedAgents != 3 {
		t.Errorf("Expected 3 queued agents, got %d", resp.Data.QueuedAgents)
	}
}

// TestFleetDeleteAgent tests DELETE /fleet/agent endpoint
func TestFleetDeleteAgent(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)

	tests := []struct {
		name       string
		agentID    string
		expectCode int
	}{
		{"With ID", "agent-1", http.StatusOK},
		{"No ID", "", http.StatusBadRequest},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			url := "/api/v1/fleet/agent"
			if tt.agentID != "" {
				url += "?id=" + tt.agentID
			}

			req := httptest.NewRequest("DELETE", url, nil)
			rr := httptest.NewRecorder()

			h.DeleteAgent(rr, req)

			if rr.Code != tt.expectCode {
				t.Errorf("Expected status %d, got %d", tt.expectCode, rr.Code)
			}
		})
	}
}

// TestFleetInvalidJSON tests endpoints with invalid JSON
func TestFleetInvalidJSON(t *testing.T) {
	tmpDir := t.TempDir()
	h := handlers.NewFleetHandler(tmpDir)

	endpoints := []struct {
		method  string
		path    string
		handler http.HandlerFunc
	}{
		{"PUT", "/api/v1/fleet/mode", h.SetMode},
		{"POST", "/api/v1/fleet/heartbeat", h.Heartbeat},
		{"POST", "/api/v1/fleet/push-config", h.PushConfig},
		{"POST", "/api/v1/fleet/events", h.ReceiveEvent},
		{"POST", "/api/v1/fleet/broadcast-config", h.BroadcastConfig},
	}

	for _, ep := range endpoints {
		t.Run(ep.path, func(t *testing.T) {
			req := httptest.NewRequest(ep.method, ep.path, bytes.NewReader([]byte("invalid json")))
			req.Header.Set("Content-Type", "application/json")
			rr := httptest.NewRecorder()

			ep.handler(rr, req)

			if rr.Code != http.StatusBadRequest {
				t.Errorf("Expected status 400 for invalid JSON, got %d", rr.Code)
			}
		})
	}
}
