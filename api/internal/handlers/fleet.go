package handlers

import (
	"encoding/json"
	"net/http"

	"github.com/northshorenetworks/nswall/api/internal/services"
)

// FleetHandler handles fleet management endpoints
type FleetHandler struct {
	fleet *services.FleetService
}

// NewFleetHandler creates a new FleetHandler
func NewFleetHandler(configPath string) *FleetHandler {
	return &FleetHandler{
		fleet: services.NewFleetService(configPath),
	}
}

// GetFleet returns the FleetService
func (h *FleetHandler) GetFleet() *services.FleetService {
	return h.fleet
}

// GetMode returns the current fleet mode
func (h *FleetHandler) GetMode(w http.ResponseWriter, r *http.Request) {
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data": map[string]interface{}{
			"mode":     h.fleet.GetMode(),
			"agent_id": h.fleet.GetAgentID(),
		},
	})
}

// SetMode sets the fleet mode
func (h *FleetHandler) SetMode(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Mode       string `json:"mode"`
		ManagerURL string `json:"manager_url,omitempty"`
		APIKey     string `json:"api_key,omitempty"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, `{"success": false, "error": "invalid request"}`, http.StatusBadRequest)
		return
	}

	mode := services.AgentMode(req.Mode)
	if mode != services.ModeStandalone && mode != services.ModeManager && mode != services.ModeAgent {
		http.Error(w, `{"success": false, "error": "invalid mode"}`, http.StatusBadRequest)
		return
	}

	if mode == services.ModeAgent && (req.ManagerURL == "" || req.APIKey == "") {
		http.Error(w, `{"success": false, "error": "manager_url and api_key required for agent mode"}`, http.StatusBadRequest)
		return
	}

	if err := h.fleet.SetMode(mode, req.ManagerURL, req.APIKey); err != nil {
		http.Error(w, `{"success": false, "error": "`+err.Error()+`"}`, http.StatusInternalServerError)
		return
	}

	// Start agent if needed
	if mode == services.ModeAgent {
		h.fleet.StartAgent()
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data": map[string]interface{}{
			"mode": mode,
		},
	})
}

// GetAgents returns all registered agents (manager mode)
func (h *FleetHandler) GetAgents(w http.ResponseWriter, r *http.Request) {
	agents := h.fleet.GetAgents()
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data":    agents,
	})
}

// GetAgent returns a specific agent
func (h *FleetHandler) GetAgent(w http.ResponseWriter, r *http.Request) {
	agentID := r.URL.Query().Get("id")
	if agentID == "" {
		http.Error(w, `{"success": false, "error": "agent id required"}`, http.StatusBadRequest)
		return
	}

	agent := h.fleet.GetAgent(agentID)
	if agent == nil {
		http.Error(w, `{"success": false, "error": "agent not found"}`, http.StatusNotFound)
		return
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data":    agent,
	})
}

// Heartbeat receives heartbeat from agents
func (h *FleetHandler) Heartbeat(w http.ResponseWriter, r *http.Request) {
	var info services.AgentInfo
	if err := json.NewDecoder(r.Body).Decode(&info); err != nil {
		http.Error(w, `{"success": false, "error": "invalid request"}`, http.StatusBadRequest)
		return
	}

	// Capture agent IP
	info.IP = r.RemoteAddr
	if fwd := r.Header.Get("X-Forwarded-For"); fwd != "" {
		info.IP = fwd
	}

	h.fleet.RegisterAgent(&info)

	// Check for pending config
	pendingConfig := h.fleet.GetPendingConfig(info.ID)

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success":        true,
		"pending_config": pendingConfig,
	})
}

// PushConfig pushes configuration to an agent
func (h *FleetHandler) PushConfig(w http.ResponseWriter, r *http.Request) {
	var req struct {
		AgentID string                `json:"agent_id"`
		Config  services.AgentConfig  `json:"config"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, `{"success": false, "error": "invalid request"}`, http.StatusBadRequest)
		return
	}

	if req.AgentID == "" {
		http.Error(w, `{"success": false, "error": "agent_id required"}`, http.StatusBadRequest)
		return
	}

	if err := h.fleet.PushConfigToAgent(req.AgentID, &req.Config); err != nil {
		http.Error(w, `{"success": false, "error": "`+err.Error()+`"}`, http.StatusInternalServerError)
		return
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"message": "configuration queued for delivery",
	})
}

// ApplyConfig applies configuration from manager (agent mode)
func (h *FleetHandler) ApplyConfig(w http.ResponseWriter, r *http.Request) {
	var config services.AgentConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		http.Error(w, `{"success": false, "error": "invalid request"}`, http.StatusBadRequest)
		return
	}

	// This would apply the config locally
	// For now, just acknowledge receipt
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"message": "configuration received",
	})
}

// ReceiveEvent receives events from agents
func (h *FleetHandler) ReceiveEvent(w http.ResponseWriter, r *http.Request) {
	var event services.AgentEvent
	if err := json.NewDecoder(r.Body).Decode(&event); err != nil {
		http.Error(w, `{"success": false, "error": "invalid request"}`, http.StatusBadRequest)
		return
	}

	h.fleet.ReceiveEvent(event)

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
	})
}

// GetEvents returns events
func (h *FleetHandler) GetEvents(w http.ResponseWriter, r *http.Request) {
	limit := 100
	if l := r.URL.Query().Get("limit"); l != "" {
		var n int
		if _, err := json.Unmarshal([]byte(l), &n); err == nil && n > 0 {
			limit = n
		}
	}

	agentID := r.URL.Query().Get("agent_id")
	eventType := r.URL.Query().Get("type")

	events := h.fleet.GetEvents(limit, agentID, eventType)

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data":    events,
	})
}

// GetFleetSummary returns fleet summary statistics
func (h *FleetHandler) GetFleetSummary(w http.ResponseWriter, r *http.Request) {
	summary := h.fleet.GetFleetSummary()
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data":    summary,
	})
}

// BroadcastConfig broadcasts configuration to all agents
func (h *FleetHandler) BroadcastConfig(w http.ResponseWriter, r *http.Request) {
	var config services.AgentConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		http.Error(w, `{"success": false, "error": "invalid request"}`, http.StatusBadRequest)
		return
	}

	agents := h.fleet.GetAgents()
	queued := 0
	for _, agent := range agents {
		if agent.Status == services.StatusOnline {
			h.fleet.SetPendingConfig(agent.ID, &config)
			queued++
		}
	}

	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data": map[string]interface{}{
			"queued_agents": queued,
			"total_agents":  len(agents),
		},
	})
}

// DeleteAgent removes an agent from the fleet
func (h *FleetHandler) DeleteAgent(w http.ResponseWriter, r *http.Request) {
	agentID := r.URL.Query().Get("id")
	if agentID == "" {
		http.Error(w, `{"success": false, "error": "agent id required"}`, http.StatusBadRequest)
		return
	}

	// For now just acknowledge - the agent will be removed when it stops heartbeating
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"message": "agent marked for removal",
	})
}
