// Package handlers provides SSE streaming endpoints for live data
package handlers

import (
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"strconv"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/openbsd"
)

// StreamingData represents a single SSE event
type StreamingData struct {
	Type      string      `json:"type"`
	Timestamp int64       `json:"timestamp"`
	Data      interface{} `json:"data"`
}

// StreamInterfaces streams interface statistics in real-time
func (h *Handler) StreamInterfaces(w http.ResponseWriter, r *http.Request) {
	h.setupSSE(w)

	// Get interval from query param (default 1s)
	interval := 1 * time.Second
	if i := r.URL.Query().Get("interval"); i != "" {
		if secs, err := strconv.Atoi(i); err == nil && secs > 0 {
			interval = time.Duration(secs) * time.Second
		}
	}

	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	ctx := r.Context()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			ifaces, err := openbsd.GetInterfaces()
			if err != nil {
				h.sendSSE(w, "error", map[string]string{"error": err.Error()})
				continue
			}

			h.sendSSE(w, "interfaces", ifaces)
		}
	}
}

// StreamPFStatus streams PF firewall status in real-time
func (h *Handler) StreamPFStatus(w http.ResponseWriter, r *http.Request) {
	h.setupSSE(w)

	interval := 1 * time.Second
	if i := r.URL.Query().Get("interval"); i != "" {
		if secs, err := strconv.Atoi(i); err == nil && secs > 0 {
			interval = time.Duration(secs) * time.Second
		}
	}

	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	ctx := r.Context()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			pf, err := openbsd.OpenPF()
			if err != nil {
				h.sendSSE(w, "error", map[string]string{"error": err.Error()})
				continue
			}

			status, err := pf.GetStatus()
			pf.Close()

			if err != nil {
				h.sendSSE(w, "error", map[string]string{"error": err.Error()})
				continue
			}

			h.sendSSE(w, "pf_status", status)
		}
	}
}

// StreamSystem streams system metrics (CPU, memory, load)
func (h *Handler) StreamSystem(w http.ResponseWriter, r *http.Request) {
	h.setupSSE(w)

	interval := 1 * time.Second
	if i := r.URL.Query().Get("interval"); i != "" {
		if secs, err := strconv.Atoi(i); err == nil && secs > 0 {
			interval = time.Duration(secs) * time.Second
		}
	}

	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	ctx := r.Context()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			data := make(map[string]interface{})

			// Load average
			if load, err := openbsd.GetLoadAvg(); err == nil {
				data["load"] = load
			}

			// Memory
			if mem, err := openbsd.GetPhysMem(); err == nil {
				data["memory_total"] = mem
			}

			if vm, err := openbsd.GetVMStats(); err == nil {
				data["vm_stats"] = vm
			}

			// Uptime
			if boot, err := openbsd.GetBootTime(); err == nil {
				data["uptime_seconds"] = int64(time.Since(boot).Seconds())
			}

			h.sendSSE(w, "system", data)
		}
	}
}

// StreamPFStates streams PF state count (for state table graphs)
func (h *Handler) StreamPFStates(w http.ResponseWriter, r *http.Request) {
	h.setupSSE(w)

	interval := 1 * time.Second
	if i := r.URL.Query().Get("interval"); i != "" {
		if secs, err := strconv.Atoi(i); err == nil && secs > 0 {
			interval = time.Duration(secs) * time.Second
		}
	}

	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	ctx := r.Context()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			pf, err := openbsd.OpenPF()
			if err != nil {
				h.sendSSE(w, "error", map[string]string{"error": err.Error()})
				continue
			}

			status, err := pf.GetStatus()
			pf.Close()

			if err != nil {
				continue
			}

			data := map[string]interface{}{
				"states":          status.States,
				"src_nodes":       status.SrcNodes,
				"state_inserts":   status.StateInserts,
				"state_removals":  status.StateRemovals,
				"state_searches":  status.StateSearches,
				"bytes_in":        status.Bytes[0],
				"bytes_out":       status.Bytes[1],
				"packets_in":      status.Packets[0],
				"packets_out":     status.Packets[1],
			}

			h.sendSSE(w, "pf_states", data)
		}
	}
}

// StreamAll streams all metrics combined
func (h *Handler) StreamAll(w http.ResponseWriter, r *http.Request) {
	h.setupSSE(w)

	interval := 2 * time.Second
	if i := r.URL.Query().Get("interval"); i != "" {
		if secs, err := strconv.Atoi(i); err == nil && secs > 0 {
			interval = time.Duration(secs) * time.Second
		}
	}

	ticker := time.NewTicker(interval)
	defer ticker.Stop()

	ctx := r.Context()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			data := make(map[string]interface{})

			// Interfaces
			if ifaces, err := openbsd.GetInterfaces(); err == nil {
				data["interfaces"] = ifaces
			}

			// PF Status
			if pf, err := openbsd.OpenPF(); err == nil {
				if status, err := pf.GetStatus(); err == nil {
					data["pf"] = status
				}
				pf.Close()
			}

			// System
			if load, err := openbsd.GetLoadAvg(); err == nil {
				data["load"] = load
			}

			if boot, err := openbsd.GetBootTime(); err == nil {
				data["uptime"] = int64(time.Since(boot).Seconds())
			}

			h.sendSSE(w, "all", data)
		}
	}
}

// setupSSE configures the response for SSE streaming
func (h *Handler) setupSSE(w http.ResponseWriter) {
	w.Header().Set("Content-Type", "text/event-stream")
	w.Header().Set("Cache-Control", "no-cache")
	w.Header().Set("Connection", "keep-alive")
	w.Header().Set("Access-Control-Allow-Origin", "*")

	// Flush headers immediately
	if f, ok := w.(http.Flusher); ok {
		f.Flush()
	}
}

// sendSSE sends a single SSE event
func (h *Handler) sendSSE(w http.ResponseWriter, eventType string, data interface{}) {
	event := StreamingData{
		Type:      eventType,
		Timestamp: time.Now().UnixMilli(),
		Data:      data,
	}

	jsonData, err := json.Marshal(event)
	if err != nil {
		return
	}

	fmt.Fprintf(w, "event: %s\n", eventType)
	fmt.Fprintf(w, "data: %s\n\n", jsonData)

	if f, ok := w.(http.Flusher); ok {
		f.Flush()
	}
}

// Helper to add context timeout for streaming
func withStreamTimeout(ctx context.Context, timeout time.Duration) (context.Context, context.CancelFunc) {
	if timeout <= 0 {
		timeout = 5 * time.Minute // Default max stream time
	}
	return context.WithTimeout(ctx, timeout)
}
