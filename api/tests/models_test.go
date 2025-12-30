// Package tests provides unit tests for API models
package tests

import (
	"encoding/json"
	"testing"
	"time"

	"nswall-api/internal/models"
)

// TestSystemInfoModel tests SystemInfo serialization
func TestSystemInfoModel(t *testing.T) {
	info := models.SystemInfo{
		Hostname:    "nswall",
		Version:     "7.8",
		Kernel:      "GENERIC.MP",
		Architecture: "amd64",
		Uptime:      86400,
		LoadAverage: []float64{0.5, 0.3, 0.2},
	}

	data, err := json.Marshal(info)
	if err != nil {
		t.Fatalf("Failed to marshal SystemInfo: %v", err)
	}

	var decoded models.SystemInfo
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal SystemInfo: %v", err)
	}

	if decoded.Hostname != info.Hostname {
		t.Errorf("Hostname mismatch: %s vs %s", decoded.Hostname, info.Hostname)
	}

	if decoded.Uptime != info.Uptime {
		t.Errorf("Uptime mismatch: %d vs %d", decoded.Uptime, info.Uptime)
	}
}

// TestInterfaceModel tests Interface serialization
func TestInterfaceModel(t *testing.T) {
	iface := models.Interface{
		Name:        "em0",
		Description: "Intel Pro/1000",
		Status:      "up",
		MACAddress:  "00:11:22:33:44:55",
		MTU:         1500,
		Speed:       "1000baseT",
		Flags:       []string{"UP", "BROADCAST", "RUNNING"},
		IPv4Addresses: []models.IPAddress{
			{Address: "192.168.1.1", Netmask: "255.255.255.0", CIDR: "192.168.1.1/24"},
		},
	}

	data, err := json.Marshal(iface)
	if err != nil {
		t.Fatalf("Failed to marshal Interface: %v", err)
	}

	var decoded models.Interface
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal Interface: %v", err)
	}

	if decoded.Name != iface.Name {
		t.Errorf("Name mismatch: %s vs %s", decoded.Name, iface.Name)
	}

	if len(decoded.IPv4Addresses) != 1 {
		t.Errorf("Expected 1 IPv4 address, got %d", len(decoded.IPv4Addresses))
	}
}

// TestRouteModel tests Route serialization
func TestRouteModel(t *testing.T) {
	route := models.Route{
		Destination: "0.0.0.0/0",
		Gateway:     "192.168.1.1",
		Flags:       "UGS",
		Interface:   "em0",
		Priority:    8,
	}

	data, err := json.Marshal(route)
	if err != nil {
		t.Fatalf("Failed to marshal Route: %v", err)
	}

	var decoded models.Route
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal Route: %v", err)
	}

	if decoded.Destination != route.Destination {
		t.Errorf("Destination mismatch: %s vs %s", decoded.Destination, route.Destination)
	}
}

// TestPFRuleModel tests PFRule serialization
func TestPFRuleModel(t *testing.T) {
	rule := models.PFRule{
		Number:    1,
		Action:    "pass",
		Direction: "in",
		Quick:     true,
		Interface: "em0",
		Protocol:  "tcp",
		Source:    "any",
		Dest:      "any",
		Port:      "22",
		Label:     "allow ssh",
	}

	data, err := json.Marshal(rule)
	if err != nil {
		t.Fatalf("Failed to marshal PFRule: %v", err)
	}

	var decoded models.PFRule
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal PFRule: %v", err)
	}

	if decoded.Action != rule.Action {
		t.Errorf("Action mismatch: %s vs %s", decoded.Action, rule.Action)
	}

	if !decoded.Quick {
		t.Error("Quick flag should be true")
	}
}

// TestPFStateModel tests PFState serialization
func TestPFStateModel(t *testing.T) {
	state := models.PFState{
		ID:        "abc123",
		Protocol:  "tcp",
		Direction: "in",
		Source:    "192.168.1.100:45678",
		Dest:      "10.0.0.1:443",
		State:     "ESTABLISHED:ESTABLISHED",
		Age:       300,
		Expires:   3600,
		Packets:   100,
		Bytes:     50000,
	}

	data, err := json.Marshal(state)
	if err != nil {
		t.Fatalf("Failed to marshal PFState: %v", err)
	}

	var decoded models.PFState
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal PFState: %v", err)
	}

	if decoded.Protocol != state.Protocol {
		t.Errorf("Protocol mismatch: %s vs %s", decoded.Protocol, state.Protocol)
	}

	if decoded.Bytes != state.Bytes {
		t.Errorf("Bytes mismatch: %d vs %d", decoded.Bytes, state.Bytes)
	}
}

// TestServiceModel tests Service serialization
func TestServiceModel(t *testing.T) {
	service := models.Service{
		Name:    "sshd",
		Status:  "running",
		Enabled: true,
		PID:     1234,
	}

	data, err := json.Marshal(service)
	if err != nil {
		t.Fatalf("Failed to marshal Service: %v", err)
	}

	var decoded models.Service
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal Service: %v", err)
	}

	if decoded.Name != service.Name {
		t.Errorf("Name mismatch: %s vs %s", decoded.Name, service.Name)
	}

	if decoded.PID != service.PID {
		t.Errorf("PID mismatch: %d vs %d", decoded.PID, service.PID)
	}
}

// TestWireGuardInterfaceModel tests WireGuardInterface serialization
func TestWireGuardInterfaceModel(t *testing.T) {
	wg := models.WireGuardInterface{
		Name:       "wg0",
		PublicKey:  "abcdef123456...",
		ListenPort: 51820,
		Peers: []models.WireGuardPeer{
			{
				PublicKey:  "peer-key-123...",
				AllowedIPs: []string{"10.0.0.2/32"},
				Endpoint:   "203.0.113.1:51820",
			},
		},
	}

	data, err := json.Marshal(wg)
	if err != nil {
		t.Fatalf("Failed to marshal WireGuardInterface: %v", err)
	}

	var decoded models.WireGuardInterface
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal WireGuardInterface: %v", err)
	}

	if decoded.ListenPort != wg.ListenPort {
		t.Errorf("ListenPort mismatch: %d vs %d", decoded.ListenPort, wg.ListenPort)
	}

	if len(decoded.Peers) != 1 {
		t.Errorf("Expected 1 peer, got %d", len(decoded.Peers))
	}
}

// TestCARPStatusModel tests CARPStatus serialization
func TestCARPStatusModel(t *testing.T) {
	carp := models.CARPStatus{
		Interface: "carp0",
		VirtualIP: "192.168.1.254",
		VHID:      1,
		State:     "MASTER",
		AdvSkew:   0,
		AdvBase:   1,
	}

	data, err := json.Marshal(carp)
	if err != nil {
		t.Fatalf("Failed to marshal CARPStatus: %v", err)
	}

	var decoded models.CARPStatus
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal CARPStatus: %v", err)
	}

	if decoded.State != carp.State {
		t.Errorf("State mismatch: %s vs %s", decoded.State, carp.State)
	}
}

// TestDHCPLeaseModel tests DHCPLease serialization
func TestDHCPLeaseModel(t *testing.T) {
	lease := models.DHCPLease{
		IPAddress:  "192.168.1.100",
		MACAddress: "00:11:22:33:44:55",
		Hostname:   "client1",
		LeaseStart: time.Now(),
		LeaseEnd:   time.Now().Add(24 * time.Hour),
	}

	data, err := json.Marshal(lease)
	if err != nil {
		t.Fatalf("Failed to marshal DHCPLease: %v", err)
	}

	var decoded models.DHCPLease
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal DHCPLease: %v", err)
	}

	if decoded.IPAddress != lease.IPAddress {
		t.Errorf("IPAddress mismatch: %s vs %s", decoded.IPAddress, lease.IPAddress)
	}
}

// TestLogEntryModel tests LogEntry serialization
func TestLogEntryModel(t *testing.T) {
	entry := models.LogEntry{
		Timestamp: time.Now(),
		Facility:  "daemon",
		Severity:  "info",
		Program:   "sshd",
		Message:   "Accepted publickey for root from 192.168.1.1",
	}

	data, err := json.Marshal(entry)
	if err != nil {
		t.Fatalf("Failed to marshal LogEntry: %v", err)
	}

	var decoded models.LogEntry
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal LogEntry: %v", err)
	}

	if decoded.Program != entry.Program {
		t.Errorf("Program mismatch: %s vs %s", decoded.Program, entry.Program)
	}
}

// TestUserModel tests User serialization (sensitive data handling)
func TestUserModel(t *testing.T) {
	user := models.User{
		Username: "admin",
		Role:     "admin",
		Email:    "admin@example.com",
	}

	data, err := json.Marshal(user)
	if err != nil {
		t.Fatalf("Failed to marshal User: %v", err)
	}

	// Check that password hash is not in JSON output
	if string(data) != "" {
		// Password should be omitted from JSON
		var result map[string]interface{}
		json.Unmarshal(data, &result)
		if _, ok := result["password_hash"]; ok {
			t.Error("Password hash should not be serialized")
		}
	}
}

// TestAPIResponseModel tests APIResponse serialization
func TestAPIResponseModel(t *testing.T) {
	resp := models.APIResponse{
		Success: true,
		Data:    map[string]string{"status": "ok"},
	}

	data, err := json.Marshal(resp)
	if err != nil {
		t.Fatalf("Failed to marshal APIResponse: %v", err)
	}

	var decoded models.APIResponse
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal APIResponse: %v", err)
	}

	if !decoded.Success {
		t.Error("Expected success=true")
	}
}

// TestAPIResponseWithError tests error response serialization
func TestAPIResponseWithError(t *testing.T) {
	resp := models.APIResponse{
		Success: false,
		Error:   "Something went wrong",
	}

	data, err := json.Marshal(resp)
	if err != nil {
		t.Fatalf("Failed to marshal APIResponse: %v", err)
	}

	var decoded models.APIResponse
	if err := json.Unmarshal(data, &decoded); err != nil {
		t.Fatalf("Failed to unmarshal APIResponse: %v", err)
	}

	if decoded.Success {
		t.Error("Expected success=false")
	}

	if decoded.Error != "Something went wrong" {
		t.Errorf("Error message mismatch: %s", decoded.Error)
	}
}
