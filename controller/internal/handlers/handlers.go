// Package handlers provides HTTP handlers for the controller API
package handlers

import (
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/northshorenetworks/nswall/controller/internal/models"
	nc "github.com/northshorenetworks/nswall/controller/internal/nats"
	"github.com/northshorenetworks/nswall/controller/internal/store"
)

// Handler contains all HTTP handlers
type Handler struct {
	store   *store.AgentStore
	nats    *nc.Client
	version string
}

// NewHandler creates a new Handler
func NewHandler(store *store.AgentStore, nats *nc.Client, version string) *Handler {
	return &Handler{
		store:   store,
		nats:    nats,
		version: version,
	}
}

// Response helpers

func writeJSON(w http.ResponseWriter, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": true,
		"data":    data,
	})
}

func writeError(w http.ResponseWriter, status int, message string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"success": false,
		"error":   message,
	})
}

// Health returns controller health status
func (h *Handler) Health(w http.ResponseWriter, r *http.Request) {
	summary := h.store.GetSummary()
	writeJSON(w, map[string]interface{}{
		"status":  "healthy",
		"version": h.version,
		"agents":  summary.TotalAgents,
		"online":  summary.OnlineAgents,
	})
}

// Agent handlers

func (h *Handler) ListAgents(w http.ResponseWriter, r *http.Request) {
	agents := h.store.GetAgents()
	writeJSON(w, agents)
}

func (h *Handler) GetAgent(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	agent := h.store.GetAgent(id)
	if agent == nil {
		writeError(w, http.StatusNotFound, "Agent not found")
		return
	}
	writeJSON(w, agent)
}

func (h *Handler) DeleteAgent(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	h.store.DeleteAgent(id)
	writeJSON(w, map[string]string{"status": "deleted"})
}

func (h *Handler) GetAgentStatus(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")
	agent := h.store.GetAgent(id)
	if agent == nil {
		writeError(w, http.StatusNotFound, "Agent not found")
		return
	}
	writeJSON(w, map[string]interface{}{
		"id":         agent.ID,
		"hostname":   agent.Hostname,
		"status":     agent.Status,
		"last_seen":  agent.LastSeen,
		"pf_enabled": agent.PFEnabled,
		"pf_states":  agent.PFStates,
		"cpu_load":   agent.CPULoad,
		"memory":     agent.MemoryPercent,
	})
}

func (h *Handler) SendCommand(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")

	var cmd models.Command
	if err := json.NewDecoder(r.Body).Decode(&cmd); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	result, err := h.nats.SendCommand(id, &cmd)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, result)
}

func (h *Handler) PushConfig(w http.ResponseWriter, r *http.Request) {
	id := chi.URLParam(r, "id")

	var config models.AgentConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	if err := h.nats.SendConfig(id, &config); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "pushed"})
}

func (h *Handler) GetSummary(w http.ResponseWriter, r *http.Request) {
	summary := h.store.GetSummary()
	writeJSON(w, summary)
}

// Broadcast handlers

func (h *Handler) BroadcastConfig(w http.ResponseWriter, r *http.Request) {
	var config models.AgentConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	if err := h.nats.BroadcastConfig(&config); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	agents := h.store.GetAgents()
	online := 0
	for _, a := range agents {
		if a.Status == "online" {
			online++
		}
	}

	writeJSON(w, map[string]interface{}{
		"status":        "broadcast",
		"online_agents": online,
	})
}

func (h *Handler) BroadcastCommand(w http.ResponseWriter, r *http.Request) {
	var cmd models.Command
	if err := json.NewDecoder(r.Body).Decode(&cmd); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	if err := h.nats.BroadcastCommand(&cmd); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "broadcast"})
}

// Event handlers

func (h *Handler) GetEvents(w http.ResponseWriter, r *http.Request) {
	limit := 100
	if l := r.URL.Query().Get("limit"); l != "" {
		fmt.Sscanf(l, "%d", &limit)
	}

	events, err := h.nats.GetRecentEvents(limit)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, events)
}

// StreamEvents provides Server-Sent Events for real-time updates
func (h *Handler) StreamEvents(w http.ResponseWriter, r *http.Request) {
	flusher, ok := w.(http.Flusher)
	if !ok {
		writeError(w, http.StatusInternalServerError, "Streaming not supported")
		return
	}

	w.Header().Set("Content-Type", "text/event-stream")
	w.Header().Set("Cache-Control", "no-cache")
	w.Header().Set("Connection", "keep-alive")

	eventCh := h.nats.SubscribeEvents()
	defer h.nats.UnsubscribeEvents(eventCh)

	for {
		select {
		case event := <-eventCh:
			data, _ := json.Marshal(event)
			fmt.Fprintf(w, "data: %s\n\n", data)
			flusher.Flush()
		case <-r.Context().Done():
			return
		}
	}
}

// WebSocket provides WebSocket connection for real-time updates
func (h *Handler) WebSocket(w http.ResponseWriter, r *http.Request) {
	// Simplified SSE fallback - full WebSocket would use gorilla/websocket
	h.StreamEvents(w, r)
}

// Template handlers

func (h *Handler) ListTemplates(w http.ResponseWriter, r *http.Request) {
	templates := h.store.GetTemplates()
	writeJSON(w, templates)
}

func (h *Handler) GetTemplate(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	template := h.store.GetTemplate(name)
	if template == nil {
		writeError(w, http.StatusNotFound, "Template not found")
		return
	}
	writeJSON(w, template)
}

func (h *Handler) CreateTemplate(w http.ResponseWriter, r *http.Request) {
	var template models.ConfigTemplate
	if err := json.NewDecoder(r.Body).Decode(&template); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	if template.Name == "" {
		writeError(w, http.StatusBadRequest, "Name is required")
		return
	}

	h.store.SaveTemplate(&template)
	writeJSON(w, map[string]string{"status": "created"})
}

func (h *Handler) UpdateTemplate(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	var template models.ConfigTemplate
	if err := json.NewDecoder(r.Body).Decode(&template); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	template.Name = name
	h.store.SaveTemplate(&template)
	writeJSON(w, map[string]string{"status": "updated"})
}

func (h *Handler) DeleteTemplate(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	h.store.DeleteTemplate(name)
	writeJSON(w, map[string]string{"status": "deleted"})
}

// Group handlers

func (h *Handler) ListGroups(w http.ResponseWriter, r *http.Request) {
	groups := h.store.GetGroups()
	writeJSON(w, groups)
}

func (h *Handler) GetGroup(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	group := h.store.GetGroup(name)
	if group == nil {
		writeError(w, http.StatusNotFound, "Group not found")
		return
	}

	// Include agents in response
	agents := h.store.GetAgentsByGroup(name)
	writeJSON(w, map[string]interface{}{
		"group":  group,
		"agents": agents,
	})
}

func (h *Handler) CreateGroup(w http.ResponseWriter, r *http.Request) {
	var group models.Group
	if err := json.NewDecoder(r.Body).Decode(&group); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	if group.Name == "" {
		writeError(w, http.StatusBadRequest, "Name is required")
		return
	}

	group.CreatedAt = time.Now()
	h.store.SaveGroup(&group)
	writeJSON(w, map[string]string{"status": "created"})
}

func (h *Handler) UpdateGroup(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	var group models.Group
	if err := json.NewDecoder(r.Body).Decode(&group); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid request")
		return
	}

	group.Name = name
	h.store.SaveGroup(&group)
	writeJSON(w, map[string]string{"status": "updated"})
}

func (h *Handler) DeleteGroup(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	h.store.DeleteGroup(name)
	writeJSON(w, map[string]string{"status": "deleted"})
}

func (h *Handler) PushGroupConfig(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	group := h.store.GetGroup(name)
	if group == nil {
		writeError(w, http.StatusNotFound, "Group not found")
		return
	}

	var config models.AgentConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		// Use group's default config if none provided
		if group.Config != nil {
			config = *group.Config
		} else {
			writeError(w, http.StatusBadRequest, "No config provided and group has no default config")
			return
		}
	}

	// Push to all agents in group
	agents := h.store.GetAgentsByGroup(name)
	pushed := 0
	failed := 0

	for _, agent := range agents {
		if err := h.nats.SendConfig(agent.ID, &config); err != nil {
			failed++
		} else {
			pushed++
		}
	}

	writeJSON(w, map[string]interface{}{
		"pushed": pushed,
		"failed": failed,
		"total":  len(agents),
	})
}
