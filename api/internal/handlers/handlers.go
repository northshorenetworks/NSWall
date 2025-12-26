// Package handlers provides HTTP handlers for the NSWall API
package handlers

import (
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/northshorenetworks/nswall/api/internal/models"
	"github.com/northshorenetworks/nswall/api/internal/services"
)

// Handler contains all HTTP handlers
type Handler struct {
	System   *services.SystemService
	Network  *services.NetworkService
	Firewall *services.FirewallService
	VPN      *services.VPNService
	HA       *services.HAService
	DHCP     *services.DHCPService
	DNS      *services.DNSService
	Daemon   *services.DaemonService
	Config   *services.ConfigService
	Auth     *services.AuthService
	Version  string
	MockMode bool
}

// NewHandler creates a new Handler
func NewHandler(nshPath, dataDir, version string, mockMode bool) *Handler {
	return &Handler{
		System:   services.NewSystemService(nshPath),
		Network:  services.NewNetworkService(nshPath),
		Firewall: services.NewFirewallService(nshPath),
		VPN:      services.NewVPNService(nshPath),
		HA:       services.NewHAService(nshPath),
		DHCP:     services.NewDHCPService(nshPath),
		DNS:      services.NewDNSService(nshPath),
		Daemon:   services.NewDaemonService(nshPath),
		Config:   services.NewConfigService(nshPath),
		Auth:     services.NewAuthService(dataDir),
		Version:  version,
		MockMode: mockMode,
	}
}

// Response helpers

func writeJSON(w http.ResponseWriter, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	resp := models.Response{Success: true, Data: data}
	json.NewEncoder(w).Encode(resp)
}

func writeError(w http.ResponseWriter, status int, message string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	resp := models.Response{Success: false, Error: message}
	json.NewEncoder(w).Encode(resp)
}

// Root handlers

func (h *Handler) Root(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, map[string]interface{}{
		"name":    "NSWall API",
		"version": h.Version,
		"docs":    "/api/v1",
		"endpoints": map[string]string{
			"system":      "/api/v1/system",
			"interfaces":  "/api/v1/interfaces",
			"routes":      "/api/v1/routes",
			"firewall":    "/api/v1/pf",
			"vpn":         "/api/v1/vpn",
			"ha":          "/api/v1/ha",
			"dhcp":        "/api/v1/dhcp",
			"dns":         "/api/v1/dns",
			"services":    "/api/v1/services",
			"config":      "/api/v1/config",
		},
	})
}

func (h *Handler) Health(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, map[string]interface{}{
		"status":   "healthy",
		"version":  h.Version,
		"mockMode": h.MockMode,
	})
}

// System handlers

func (h *Handler) GetSystemInfo(w http.ResponseWriter, r *http.Request) {
	// Return mock data in mock mode
	if h.MockMode {
		writeJSON(w, map[string]interface{}{
			"hostname": "nswall-mock",
			"os":       "OpenBSD",
			"version":  "7.6",
			"arch":     "amd64",
			"uptime":   "1 day, 2:30",
			"load":     []float64{0.5, 0.4, 0.3},
			"mockMode": true,
		})
		return
	}

	info, err := h.System.GetSystemInfo()
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, info)
}

func (h *Handler) GetVersion(w http.ResponseWriter, r *http.Request) {
	nshVersion, _ := h.System.GetVersion()
	writeJSON(w, map[string]interface{}{
		"api_version": h.Version,
		"nsh_version": nshVersion,
	})
}

func (h *Handler) GetMemory(w http.ResponseWriter, r *http.Request) {
	info, err := h.System.GetMemoryInfo()
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, info)
}

func (h *Handler) GetDisks(w http.ResponseWriter, r *http.Request) {
	disks, err := h.System.GetDiskInfo()
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, disks)
}

func (h *Handler) GetLogs(w http.ResponseWriter, r *http.Request) {
	facility := r.URL.Query().Get("facility")
	lines := 100
	if l := r.URL.Query().Get("lines"); l != "" {
		lines, _ = strconv.Atoi(l)
	}

	logs, err := h.System.GetLogs(facility, lines)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, logs)
}

func (h *Handler) RunCommand(w http.ResponseWriter, r *http.Request) {
	var req models.CommandRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if req.Command == "" {
		writeError(w, http.StatusBadRequest, "Command is required")
		return
	}

	// Block dangerous commands
	dangerous := []string{"reload", "halt", "reboot", "!", "shell", "quit", "exit"}
	cmdLower := strings.ToLower(req.Command)
	for _, d := range dangerous {
		if strings.HasPrefix(cmdLower, d) {
			writeError(w, http.StatusForbidden, "Command not allowed via API")
			return
		}
	}

	resp, err := h.System.RunNshCommand(r.Context(), req.Command, req.Timeout)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, resp)
}

// Interface handlers

func (h *Handler) GetInterfaces(w http.ResponseWriter, r *http.Request) {
	ifaces, err := h.Network.GetInterfaces(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, ifaces)
}

func (h *Handler) GetInterface(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	iface, err := h.Network.GetInterface(r.Context(), name)
	if err != nil {
		writeError(w, http.StatusNotFound, err.Error())
		return
	}
	writeJSON(w, iface)
}

func (h *Handler) ConfigureInterface(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	var config models.InterfaceConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.Network.ConfigureInterface(r.Context(), name, &config); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "configured"})
}

// Route handlers

func (h *Handler) GetRoutes(w http.ResponseWriter, r *http.Request) {
	routes, err := h.Network.GetRoutes(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, routes)
}

func (h *Handler) AddRoute(w http.ResponseWriter, r *http.Request) {
	var route models.RouteConfig
	if err := json.NewDecoder(r.Body).Decode(&route); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.Network.AddRoute(r.Context(), &route); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "added"})
}

func (h *Handler) DeleteRoute(w http.ResponseWriter, r *http.Request) {
	dest := chi.URLParam(r, "destination")
	if err := h.Network.DeleteRoute(r.Context(), dest); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "deleted"})
}

// ARP handlers

func (h *Handler) GetArpTable(w http.ResponseWriter, r *http.Request) {
	entries, err := h.Network.GetArpTable(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, entries)
}

func (h *Handler) DeleteArpEntry(w http.ResponseWriter, r *http.Request) {
	ip := chi.URLParam(r, "ip")
	if err := h.Network.DeleteArpEntry(r.Context(), ip); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "deleted"})
}

func (h *Handler) FlushArpTable(w http.ResponseWriter, r *http.Request) {
	if err := h.Network.FlushArpTable(r.Context()); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "flushed"})
}

// Firewall handlers

func (h *Handler) GetPFInfo(w http.ResponseWriter, r *http.Request) {
	info, err := h.Firewall.GetInfo(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, info)
}

func (h *Handler) GetPFStats(w http.ResponseWriter, r *http.Request) {
	stats, err := h.Firewall.GetStats(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, stats)
}

func (h *Handler) GetPFRules(w http.ResponseWriter, r *http.Request) {
	rules, err := h.Firewall.GetRules(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, rules)
}

func (h *Handler) GetPFStates(w http.ResponseWriter, r *http.Request) {
	states, err := h.Firewall.GetStates(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, states)
}

func (h *Handler) KillPFStates(w http.ResponseWriter, r *http.Request) {
	src := r.URL.Query().Get("src")
	dst := r.URL.Query().Get("dst")
	iface := r.URL.Query().Get("interface")

	count, err := h.Firewall.KillStates(r.Context(), src, dst, iface)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]int{"killed": count})
}

func (h *Handler) GetPFTables(w http.ResponseWriter, r *http.Request) {
	tables, err := h.Firewall.GetTables(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, tables)
}

func (h *Handler) GetPFTable(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	table, err := h.Firewall.GetTable(r.Context(), name)
	if err != nil {
		writeError(w, http.StatusNotFound, err.Error())
		return
	}
	writeJSON(w, table)
}

func (h *Handler) AddPFTableAddress(w http.ResponseWriter, r *http.Request) {
	tableName := chi.URLParam(r, "name")

	var req struct {
		Address string `json:"address"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.Firewall.AddTableAddress(r.Context(), tableName, req.Address); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "added"})
}

func (h *Handler) DeletePFTableAddress(w http.ResponseWriter, r *http.Request) {
	tableName := chi.URLParam(r, "name")
	address := chi.URLParam(r, "address")

	if err := h.Firewall.DeleteTableAddress(r.Context(), tableName, address); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "deleted"})
}

func (h *Handler) FlushPFTable(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	if err := h.Firewall.FlushTable(r.Context(), name); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "flushed"})
}

func (h *Handler) EnablePF(w http.ResponseWriter, r *http.Request) {
	if err := h.Firewall.Enable(r.Context()); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "enabled"})
}

func (h *Handler) DisablePF(w http.ResponseWriter, r *http.Request) {
	if err := h.Firewall.Disable(r.Context()); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "disabled"})
}

func (h *Handler) ReloadPF(w http.ResponseWriter, r *http.Request) {
	if err := h.Firewall.Reload(r.Context()); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "reloaded"})
}

// VPN handlers

func (h *Handler) GetWireGuardInterfaces(w http.ResponseWriter, r *http.Request) {
	ifaces, err := h.VPN.GetWireGuardInterfaces(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, ifaces)
}

func (h *Handler) GetWireGuardInterface(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	iface, err := h.VPN.GetWireGuardInterface(r.Context(), name)
	if err != nil {
		writeError(w, http.StatusNotFound, err.Error())
		return
	}
	writeJSON(w, iface)
}

func (h *Handler) CreateWireGuardInterface(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Name string `json:"name"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.VPN.CreateWireGuardInterface(r.Context(), req.Name); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "created"})
}

func (h *Handler) ConfigureWireGuard(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	var config models.WireGuardConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.VPN.ConfigureWireGuard(r.Context(), name, &config); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "configured"})
}

func (h *Handler) AddWireGuardPeer(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	var peer models.WireGuardPeerConfig
	if err := json.NewDecoder(r.Body).Decode(&peer); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.VPN.AddWireGuardPeer(r.Context(), name, &peer); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "added"})
}

func (h *Handler) DeleteWireGuardPeer(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	publicKey := chi.URLParam(r, "publicKey")

	if err := h.VPN.DeleteWireGuardPeer(r.Context(), name, publicKey); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "deleted"})
}

func (h *Handler) GenerateWireGuardKey(w http.ResponseWriter, r *http.Request) {
	privateKey, publicKey, err := h.VPN.GenerateWireGuardKey(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{
		"private_key": privateKey,
		"public_key":  publicKey,
	})
}

func (h *Handler) GetIKESAs(w http.ResponseWriter, r *http.Request) {
	sas, err := h.VPN.GetIKESAs(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, sas)
}

func (h *Handler) GetIPSecSAs(w http.ResponseWriter, r *http.Request) {
	sas, err := h.VPN.GetIPSecSAs(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, sas)
}

// HA handlers

func (h *Handler) GetCARPStatus(w http.ResponseWriter, r *http.Request) {
	carp, err := h.HA.GetCARPInterfaces(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, carp)
}

func (h *Handler) GetCARPInterface(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	carp, err := h.HA.GetCARPStatus(r.Context(), name)
	if err != nil {
		writeError(w, http.StatusNotFound, err.Error())
		return
	}
	writeJSON(w, carp)
}

func (h *Handler) ConfigureCARPInterface(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	var config models.CARPConfig
	if err := json.NewDecoder(r.Body).Decode(&config); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.HA.ConfigureCARPInterface(r.Context(), name, &config); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "configured"})
}

func (h *Handler) GetPFSync(w http.ResponseWriter, r *http.Request) {
	info, err := h.HA.GetPFSyncInfo(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, info)
}

// DHCP handlers

func (h *Handler) GetDHCPLeases(w http.ResponseWriter, r *http.Request) {
	leases, err := h.DHCP.GetLeases(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, leases)
}

func (h *Handler) GetDHCPSubnets(w http.ResponseWriter, r *http.Request) {
	subnets, err := h.DHCP.GetSubnets(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, subnets)
}

// DNS handlers

func (h *Handler) GetDNSConfig(w http.ResponseWriter, r *http.Request) {
	config, err := h.DNS.GetResolverConfig(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, config)
}

func (h *Handler) FlushDNSCache(w http.ResponseWriter, r *http.Request) {
	if err := h.DNS.FlushDNSCache(r.Context()); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "flushed"})
}

// Service handlers

func (h *Handler) GetServices(w http.ResponseWriter, r *http.Request) {
	svcs, err := h.Daemon.GetServices(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, svcs)
}

func (h *Handler) GetService(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	svc, err := h.Daemon.GetService(r.Context(), name)
	if err != nil {
		writeError(w, http.StatusNotFound, err.Error())
		return
	}
	writeJSON(w, svc)
}

func (h *Handler) ServiceAction(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")

	var action models.ServiceAction
	if err := json.NewDecoder(r.Body).Decode(&action); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	var err error
	switch action.Action {
	case "start":
		err = h.Daemon.StartService(r.Context(), name)
	case "stop":
		err = h.Daemon.StopService(r.Context(), name)
	case "restart":
		err = h.Daemon.RestartService(r.Context(), name)
	case "reload":
		err = h.Daemon.ReloadService(r.Context(), name)
	case "enable":
		err = h.Daemon.EnableService(r.Context(), name)
	case "disable":
		err = h.Daemon.DisableService(r.Context(), name)
	default:
		writeError(w, http.StatusBadRequest, "Invalid action")
		return
	}

	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": action.Action + "ed"})
}

// Config handlers

func (h *Handler) GetRunningConfig(w http.ResponseWriter, r *http.Request) {
	config, err := h.Config.GetRunningConfig(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, config)
}

func (h *Handler) GetStartupConfig(w http.ResponseWriter, r *http.Request) {
	config, err := h.Config.GetStartupConfig(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, config)
}

func (h *Handler) GetConfigDiff(w http.ResponseWriter, r *http.Request) {
	diff, err := h.Config.GetConfigDiff(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, diff)
}

func (h *Handler) SaveConfig(w http.ResponseWriter, r *http.Request) {
	if err := h.Config.SaveConfig(r.Context()); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "saved"})
}

func (h *Handler) BackupConfig(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Name string `json:"name"`
	}
	json.NewDecoder(r.Body).Decode(&req)

	path, err := h.Config.BackupConfig(r.Context(), req.Name)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "backed up", "path": path})
}

func (h *Handler) ListBackups(w http.ResponseWriter, r *http.Request) {
	backups, err := h.Config.ListBackups(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, backups)
}

func (h *Handler) RestoreBackup(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	if err := h.Config.RestoreBackup(r.Context(), name); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "restored"})
}

func (h *Handler) DeleteBackup(w http.ResponseWriter, r *http.Request) {
	name := chi.URLParam(r, "name")
	if err := h.Config.DeleteBackup(r.Context(), name); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "deleted"})
}

func (h *Handler) ValidateConfig(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Config string `json:"config"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	valid, errors, err := h.Config.ValidateConfig(r.Context(), req.Config)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]interface{}{
		"valid":  valid,
		"errors": errors,
	})
}

func (h *Handler) ApplyConfig(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Config string `json:"config"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.Config.ApplyConfig(r.Context(), req.Config); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "applied"})
}

// Auth handlers

func (h *Handler) Login(w http.ResponseWriter, r *http.Request) {
	var req models.LoginRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	session, err := h.Auth.Login(r.Context(), req.Username, req.Password)
	if err != nil {
		writeError(w, http.StatusUnauthorized, err.Error())
		return
	}

	writeJSON(w, session)
}

func (h *Handler) Logout(w http.ResponseWriter, r *http.Request) {
	token := r.Header.Get("Authorization")
	token = strings.TrimPrefix(token, "Bearer ")

	h.Auth.Logout(r.Context(), token)
	writeJSON(w, map[string]string{"status": "logged out"})
}

func (h *Handler) GetUsers(w http.ResponseWriter, r *http.Request) {
	users, err := h.Auth.GetUsers(r.Context())
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, users)
}

func (h *Handler) CreateUser(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Username string `json:"username"`
		Password string `json:"password"`
		Role     string `json:"role"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeError(w, http.StatusBadRequest, "Invalid JSON request")
		return
	}

	if err := h.Auth.CreateUser(r.Context(), req.Username, req.Password, req.Role); err != nil {
		writeError(w, http.StatusBadRequest, err.Error())
		return
	}

	writeJSON(w, map[string]string{"status": "created"})
}

func (h *Handler) DeleteUser(w http.ResponseWriter, r *http.Request) {
	username := chi.URLParam(r, "username")
	if err := h.Auth.DeleteUser(r.Context(), username); err != nil {
		writeError(w, http.StatusNotFound, err.Error())
		return
	}
	writeJSON(w, map[string]string{"status": "deleted"})
}

func (h *Handler) CreateAPIKey(w http.ResponseWriter, r *http.Request) {
	username := chi.URLParam(r, "username")

	var req struct {
		Name   string `json:"name"`
		Expiry string `json:"expiry"`
	}
	json.NewDecoder(r.Body).Decode(&req)

	var expiry time.Duration
	if req.Expiry != "" {
		expiry, _ = time.ParseDuration(req.Expiry)
	}

	key, err := h.Auth.CreateAPIKey(r.Context(), username, req.Name, expiry)
	if err != nil {
		writeError(w, http.StatusBadRequest, err.Error())
		return
	}

	writeJSON(w, map[string]string{"api_key": key})
}
