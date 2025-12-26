package handlers

import (
	"encoding/json"
	"net/http"
	"strconv"

	"github.com/go-chi/chi/v5"
	"github.com/northshorenetworks/nswall/api/internal/services"
)

// SetupHandler handles initial setup wizard endpoints
type SetupHandler struct {
	setupService *services.SetupService
	devMode      bool
}

// NewSetupHandler creates a new setup handler
func NewSetupHandler(setupService *services.SetupService, devMode bool) *SetupHandler {
	return &SetupHandler{
		setupService: setupService,
		devMode:      devMode,
	}
}

// GetSetupStatus returns the current setup status
func (h *SetupHandler) GetSetupStatus(w http.ResponseWriter, r *http.Request) {
	status := h.setupService.GetStatus()

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data: map[string]interface{}{
			"completed":     status.Completed,
			"current_step":  status.CurrentStep,
			"total_steps":   status.TotalSteps,
			"version":       status.Version,
			"completed_at":  status.CompletedAt,
		},
	})
}

// GetSetupConfig returns the current setup configuration
func (h *SetupHandler) GetSetupConfig(w http.ResponseWriter, r *http.Request) {
	if h.setupService.IsSetupComplete() {
		respondError(w, http.StatusForbidden, "Setup already completed")
		return
	}

	config := h.setupService.GetConfig()

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data:    config,
	})
}

// GetInterfaces returns available network interfaces for setup
func (h *SetupHandler) GetInterfaces(w http.ResponseWriter, r *http.Request) {
	var interfaces []services.InterfaceInfo
	var err error

	if h.devMode {
		// Return mock interfaces for development
		interfaces = []services.InterfaceInfo{
			{
				Name:        "em0",
				Description: "Intel PRO/1000 Network Connection",
				MAC:         "00:0c:29:ab:cd:ef",
				Status:      "up",
				IPv4:        []string{"192.168.1.1"},
				MediaType:   "Ethernet autoselect (1000baseT full-duplex)",
			},
			{
				Name:        "em1",
				Description: "Intel PRO/1000 Network Connection",
				MAC:         "00:0c:29:ab:cd:f0",
				Status:      "up",
				IPv4:        []string{"10.0.0.1"},
				MediaType:   "Ethernet autoselect (1000baseT full-duplex)",
			},
			{
				Name:        "em2",
				Description: "Intel PRO/1000 Network Connection",
				MAC:         "00:0c:29:ab:cd:f1",
				Status:      "down",
				MediaType:   "Ethernet autoselect",
			},
			{
				Name:   "vio0",
				Description: "VirtIO Network Device",
				MAC:    "52:54:00:12:34:56",
				Status: "up",
				IPv4:   []string{"172.16.0.1"},
			},
		}
	} else {
		interfaces, err = h.setupService.GetAvailableInterfaces()
		if err != nil {
			respondError(w, http.StatusInternalServerError, "Failed to get interfaces: "+err.Error())
			return
		}
	}

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data:    interfaces,
	})
}

// GetTimezones returns available timezones
func (h *SetupHandler) GetTimezones(w http.ResponseWriter, r *http.Request) {
	timezones := h.setupService.GetAvailableTimezones()

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data:    timezones,
	})
}

// ValidateStep validates a setup step without saving
func (h *SetupHandler) ValidateStep(w http.ResponseWriter, r *http.Request) {
	if h.setupService.IsSetupComplete() {
		respondError(w, http.StatusForbidden, "Setup already completed")
		return
	}

	stepStr := chi.URLParam(r, "step")
	step, err := strconv.Atoi(stepStr)
	if err != nil {
		respondError(w, http.StatusBadRequest, "Invalid step number")
		return
	}

	var config services.SetupConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		respondError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if err := h.setupService.ValidateStep(step, config); err != nil {
		respondJSON(w, http.StatusOK, APIResponse{
			Success: false,
			Error:   err.Error(),
		})
		return
	}

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data: map[string]interface{}{
			"valid": true,
		},
	})
}

// SaveStep saves a setup step
func (h *SetupHandler) SaveStep(w http.ResponseWriter, r *http.Request) {
	if h.setupService.IsSetupComplete() {
		respondError(w, http.StatusForbidden, "Setup already completed")
		return
	}

	stepStr := chi.URLParam(r, "step")
	step, err := strconv.Atoi(stepStr)
	if err != nil {
		respondError(w, http.StatusBadRequest, "Invalid step number")
		return
	}

	var config services.SetupConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		respondError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if err := h.setupService.SaveStep(step, config); err != nil {
		respondError(w, http.StatusBadRequest, err.Error())
		return
	}

	status := h.setupService.GetStatus()

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data: map[string]interface{}{
			"step":         step,
			"current_step": status.CurrentStep,
			"saved":        true,
		},
	})
}

// CompleteSetup finalizes the setup process
func (h *SetupHandler) CompleteSetup(w http.ResponseWriter, r *http.Request) {
	if h.setupService.IsSetupComplete() {
		respondError(w, http.StatusForbidden, "Setup already completed")
		return
	}

	// In dev mode, skip actual system configuration
	if h.devMode {
		respondJSON(w, http.StatusOK, APIResponse{
			Success: true,
			Data: map[string]interface{}{
				"completed": true,
				"message":   "Setup completed (dev mode - no system changes applied)",
			},
		})
		return
	}

	if err := h.setupService.CompleteSetup(); err != nil {
		respondError(w, http.StatusInternalServerError, "Failed to complete setup: "+err.Error())
		return
	}

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data: map[string]interface{}{
			"completed": true,
			"message":   "Setup completed successfully. Please log in with your new admin account.",
		},
	})
}

// GenerateSetupToken generates a one-time setup token
func (h *SetupHandler) GenerateSetupToken(w http.ResponseWriter, r *http.Request) {
	if h.setupService.IsSetupComplete() {
		respondError(w, http.StatusForbidden, "Setup already completed")
		return
	}

	token, err := h.setupService.GenerateSetupToken()
	if err != nil {
		respondError(w, http.StatusInternalServerError, "Failed to generate token: "+err.Error())
		return
	}

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data: map[string]interface{}{
			"token": token,
		},
	})
}

// ResetSetup resets the setup (for development/testing)
func (h *SetupHandler) ResetSetup(w http.ResponseWriter, r *http.Request) {
	if !h.devMode {
		respondError(w, http.StatusForbidden, "Reset only available in dev mode")
		return
	}

	if err := h.setupService.ResetSetup(); err != nil {
		respondError(w, http.StatusInternalServerError, "Failed to reset setup: "+err.Error())
		return
	}

	respondJSON(w, http.StatusOK, APIResponse{
		Success: true,
		Data: map[string]interface{}{
			"reset": true,
		},
	})
}

// SetupRequired middleware checks if setup is required
func (h *SetupHandler) SetupRequired(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// Allow setup endpoints through
		if isSetupEndpoint(r.URL.Path) {
			next.ServeHTTP(w, r)
			return
		}

		// If setup is not complete, redirect to setup
		if !h.setupService.IsSetupComplete() {
			respondJSON(w, http.StatusServiceUnavailable, APIResponse{
				Success: false,
				Error:   "Initial setup required",
				Data: map[string]interface{}{
					"setup_required": true,
					"redirect":       "/setup",
				},
			})
			return
		}

		next.ServeHTTP(w, r)
	})
}

func isSetupEndpoint(path string) bool {
	setupPaths := []string{
		"/api/v1/setup",
		"/api/v1/health",
		"/setup",
	}
	for _, p := range setupPaths {
		if len(path) >= len(p) && path[:len(p)] == p {
			return true
		}
	}
	return false
}
