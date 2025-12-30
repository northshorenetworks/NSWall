// Package tests provides unit tests for fleet management functionality
package tests

import (
	"os"
	"path/filepath"
	"testing"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/services"
)

// TestFleetServiceCreation tests FleetService initialization
func TestFleetServiceCreation(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)

	if svc == nil {
		t.Fatal("Expected FleetService to be created")
	}

	// Should have an agent ID
	if svc.GetAgentID() == "" {
		t.Error("Expected agent ID to be generated")
	}
}

// TestFleetServiceAgentIDPersistence tests that agent ID is persisted
func TestFleetServiceAgentIDPersistence(t *testing.T) {
	tmpDir := t.TempDir()

	// Create first instance
	svc1 := services.NewFleetService(tmpDir)
	id1 := svc1.GetAgentID()

	// Create second instance with same config path
	svc2 := services.NewFleetService(tmpDir)
	id2 := svc2.GetAgentID()

	if id1 != id2 {
		t.Errorf("Expected agent IDs to match: %s != %s", id1, id2)
	}

	// Verify file was created
	idFile := filepath.Join(tmpDir, ".agent_id")
	if _, err := os.Stat(idFile); os.IsNotExist(err) {
		t.Error("Expected .agent_id file to be created")
	}
}

// TestFleetServiceDefaultMode tests default mode is standalone
func TestFleetServiceDefaultMode(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)

	if svc.GetMode() != services.ModeStandalone {
		t.Errorf("Expected default mode to be standalone, got %s", svc.GetMode())
	}
}

// TestFleetServiceSetMode tests mode setting
func TestFleetServiceSetMode(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)

	tests := []struct {
		mode       services.AgentMode
		managerURL string
		apiKey     string
	}{
		{services.ModeManager, "", ""},
		{services.ModeAgent, "https://manager.example.com:8443", "test-api-key"},
		{services.ModeStandalone, "", ""},
	}

	for _, tt := range tests {
		t.Run(string(tt.mode), func(t *testing.T) {
			err := svc.SetMode(tt.mode, tt.managerURL, tt.apiKey)
			if err != nil {
				t.Errorf("SetMode failed: %v", err)
			}

			if svc.GetMode() != tt.mode {
				t.Errorf("Expected mode %s, got %s", tt.mode, svc.GetMode())
			}
		})
	}
}

// TestFleetServiceModeConfigPersistence tests mode config is persisted
func TestFleetServiceModeConfigPersistence(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)

	err := svc.SetMode(services.ModeManager, "", "")
	if err != nil {
		t.Fatalf("SetMode failed: %v", err)
	}

	// Verify file was created
	configFile := filepath.Join(tmpDir, "fleet.json")
	if _, err := os.Stat(configFile); os.IsNotExist(err) {
		t.Error("Expected fleet.json file to be created")
	}
}

// TestFleetServiceRegisterAgent tests agent registration
func TestFleetServiceRegisterAgent(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	agent := &services.AgentInfo{
		ID:        "test-agent-001",
		Hostname:  "firewall-1",
		Version:   "2.0.0",
		Status:    services.StatusOnline,
		PFEnabled: true,
		PFStates:  1234,
	}

	svc.RegisterAgent(agent)

	// Retrieve agent
	retrieved := svc.GetAgent("test-agent-001")
	if retrieved == nil {
		t.Fatal("Expected agent to be registered")
	}

	if retrieved.Hostname != "firewall-1" {
		t.Errorf("Expected hostname firewall-1, got %s", retrieved.Hostname)
	}

	if retrieved.PFStates != 1234 {
		t.Errorf("Expected 1234 states, got %d", retrieved.PFStates)
	}
}

// TestFleetServiceGetAgents tests getting all agents
func TestFleetServiceGetAgents(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Register multiple agents
	agents := []*services.AgentInfo{
		{ID: "agent-1", Hostname: "fw-1", Status: services.StatusOnline},
		{ID: "agent-2", Hostname: "fw-2", Status: services.StatusOnline},
		{ID: "agent-3", Hostname: "fw-3", Status: services.StatusDegraded},
	}

	for _, agent := range agents {
		svc.RegisterAgent(agent)
	}

	retrieved := svc.GetAgents()
	if len(retrieved) != 3 {
		t.Errorf("Expected 3 agents, got %d", len(retrieved))
	}
}

// TestFleetServiceAgentStatusTimeout tests agent offline detection
func TestFleetServiceAgentStatusTimeout(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Register agent with old LastSeen
	agent := &services.AgentInfo{
		ID:       "old-agent",
		Hostname: "old-fw",
		Status:   services.StatusOnline,
		LastSeen: time.Now().Add(-5 * time.Minute), // 5 minutes ago
	}
	svc.RegisterAgent(agent)

	// GetAgents should mark it as offline
	agents := svc.GetAgents()
	if len(agents) != 1 {
		t.Fatal("Expected 1 agent")
	}

	if agents[0].Status != services.StatusOffline {
		t.Errorf("Expected status offline for stale agent, got %s", agents[0].Status)
	}
}

// TestFleetServicePendingConfig tests pending config management
func TestFleetServicePendingConfig(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	config := &services.AgentConfig{
		PFRules: "pass all",
		Version: 1,
	}

	svc.SetPendingConfig("agent-1", config)

	// Get pending config (should clear it)
	retrieved := svc.GetPendingConfig("agent-1")
	if retrieved == nil {
		t.Fatal("Expected pending config")
	}

	if retrieved.PFRules != "pass all" {
		t.Errorf("Expected PF rules 'pass all', got %s", retrieved.PFRules)
	}

	// Second get should return nil (config was consumed)
	retrieved2 := svc.GetPendingConfig("agent-1")
	if retrieved2 != nil {
		t.Error("Expected pending config to be cleared after retrieval")
	}
}

// TestFleetServiceEvents tests event management
func TestFleetServiceEvents(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Receive events
	events := []services.AgentEvent{
		{ID: "evt-1", AgentID: "agent-1", Type: "config_applied", Severity: "info", Message: "Config applied"},
		{ID: "evt-2", AgentID: "agent-1", Type: "heartbeat", Severity: "info", Message: "Heartbeat"},
		{ID: "evt-3", AgentID: "agent-2", Type: "config_failed", Severity: "error", Message: "Failed"},
	}

	for _, event := range events {
		svc.ReceiveEvent(event)
	}

	// Get all events
	retrieved := svc.GetEvents(10, "", "")
	if len(retrieved) != 3 {
		t.Errorf("Expected 3 events, got %d", len(retrieved))
	}

	// Filter by agent
	agentEvents := svc.GetEvents(10, "agent-1", "")
	if len(agentEvents) != 2 {
		t.Errorf("Expected 2 events for agent-1, got %d", len(agentEvents))
	}

	// Filter by type
	failedEvents := svc.GetEvents(10, "", "config_failed")
	if len(failedEvents) != 1 {
		t.Errorf("Expected 1 config_failed event, got %d", len(failedEvents))
	}
}

// TestFleetServiceEventLimit tests event limit
func TestFleetServiceEventLimit(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Add many events
	for i := 0; i < 100; i++ {
		svc.ReceiveEvent(services.AgentEvent{
			ID:      "evt-" + string(rune(i)),
			AgentID: "agent-1",
			Type:    "test",
			Message: "Test event",
		})
	}

	// Get with limit
	retrieved := svc.GetEvents(5, "", "")
	if len(retrieved) != 5 {
		t.Errorf("Expected 5 events with limit, got %d", len(retrieved))
	}
}

// TestFleetServiceSummary tests fleet summary statistics
func TestFleetServiceSummary(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Register agents with different statuses
	agents := []*services.AgentInfo{
		{ID: "agent-1", Status: services.StatusOnline, PFStates: 1000, LastSeen: time.Now()},
		{ID: "agent-2", Status: services.StatusOnline, PFStates: 2000, LastSeen: time.Now()},
		{ID: "agent-3", Status: services.StatusDegraded, PFStates: 500, LastSeen: time.Now()},
	}

	for _, agent := range agents {
		svc.RegisterAgent(agent)
	}

	summary := svc.GetFleetSummary()

	if summary["total_agents"].(int) != 3 {
		t.Errorf("Expected 3 total agents, got %d", summary["total_agents"])
	}

	if summary["online"].(int) != 2 {
		t.Errorf("Expected 2 online agents, got %d", summary["online"])
	}

	if summary["degraded"].(int) != 1 {
		t.Errorf("Expected 1 degraded agent, got %d", summary["degraded"])
	}

	if summary["total_states"].(int64) != 3500 {
		t.Errorf("Expected 3500 total states, got %d", summary["total_states"])
	}
}

// TestFleetServiceAgentNotFound tests getting non-existent agent
func TestFleetServiceAgentNotFound(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)

	agent := svc.GetAgent("non-existent")
	if agent != nil {
		t.Error("Expected nil for non-existent agent")
	}
}

// TestFleetServiceAgentUpdate tests updating an existing agent
func TestFleetServiceAgentUpdate(t *testing.T) {
	tmpDir := t.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Register initial agent
	svc.RegisterAgent(&services.AgentInfo{
		ID:       "agent-1",
		Hostname: "fw-1",
		PFStates: 100,
	})

	// Update agent
	svc.RegisterAgent(&services.AgentInfo{
		ID:       "agent-1",
		Hostname: "fw-1-updated",
		PFStates: 200,
	})

	// Should still have 1 agent
	agents := svc.GetAgents()
	if len(agents) != 1 {
		t.Errorf("Expected 1 agent after update, got %d", len(agents))
	}

	// Should have updated values
	agent := svc.GetAgent("agent-1")
	if agent.Hostname != "fw-1-updated" {
		t.Errorf("Expected hostname fw-1-updated, got %s", agent.Hostname)
	}
	if agent.PFStates != 200 {
		t.Errorf("Expected 200 states, got %d", agent.PFStates)
	}
}

// TestAgentModeConstants tests mode constant values
func TestAgentModeConstants(t *testing.T) {
	if services.ModeStandalone != "standalone" {
		t.Errorf("Expected ModeStandalone to be 'standalone', got %s", services.ModeStandalone)
	}
	if services.ModeManager != "manager" {
		t.Errorf("Expected ModeManager to be 'manager', got %s", services.ModeManager)
	}
	if services.ModeAgent != "agent" {
		t.Errorf("Expected ModeAgent to be 'agent', got %s", services.ModeAgent)
	}
}

// TestAgentStatusConstants tests status constant values
func TestAgentStatusConstants(t *testing.T) {
	if services.StatusOnline != "online" {
		t.Errorf("Expected StatusOnline to be 'online', got %s", services.StatusOnline)
	}
	if services.StatusOffline != "offline" {
		t.Errorf("Expected StatusOffline to be 'offline', got %s", services.StatusOffline)
	}
	if services.StatusDegraded != "degraded" {
		t.Errorf("Expected StatusDegraded to be 'degraded', got %s", services.StatusDegraded)
	}
	if services.StatusMaintenance != "maintenance" {
		t.Errorf("Expected StatusMaintenance to be 'maintenance', got %s", services.StatusMaintenance)
	}
}

// BenchmarkRegisterAgent benchmarks agent registration
func BenchmarkRegisterAgent(b *testing.B) {
	tmpDir := b.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	agent := &services.AgentInfo{
		ID:       "bench-agent",
		Hostname: "bench-fw",
		Status:   services.StatusOnline,
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.RegisterAgent(agent)
	}
}

// BenchmarkGetAgents benchmarks getting agents
func BenchmarkGetAgents(b *testing.B) {
	tmpDir := b.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Register 100 agents
	for i := 0; i < 100; i++ {
		svc.RegisterAgent(&services.AgentInfo{
			ID:       string(rune(i)),
			Hostname: "fw",
			Status:   services.StatusOnline,
			LastSeen: time.Now(),
		})
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		_ = svc.GetAgents()
	}
}

// BenchmarkGetFleetSummary benchmarks fleet summary
func BenchmarkGetFleetSummary(b *testing.B) {
	tmpDir := b.TempDir()
	svc := services.NewFleetService(tmpDir)
	svc.SetMode(services.ModeManager, "", "")

	// Register 100 agents
	for i := 0; i < 100; i++ {
		svc.RegisterAgent(&services.AgentInfo{
			ID:       string(rune(i)),
			Hostname: "fw",
			Status:   services.StatusOnline,
			PFStates: int64(i * 100),
			LastSeen: time.Now(),
		})
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		_ = svc.GetFleetSummary()
	}
}
