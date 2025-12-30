// Package tests provides unit tests for the setup wizard
package tests

import (
	"testing"

	"github.com/northshorenetworks/nswall/api/internal/services"
)

func TestSetupServiceCreation(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)
	if svc == nil {
		t.Fatal("Expected SetupService to be created")
	}
}

func TestSetupInitialStatus(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	status := svc.GetStatus()
	if status.Completed {
		t.Error("Expected setup not completed initially")
	}
	if status.CurrentStep != 1 {
		t.Errorf("Expected current step 1, got %d", status.CurrentStep)
	}
	if status.TotalSteps != 5 {
		t.Errorf("Expected 5 total steps, got %d", status.TotalSteps)
	}
}

func TestSetupDefaultConfig(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	config := svc.GetConfig()
	if config.APIPort != 8443 {
		t.Errorf("Expected default API port 8443, got %d", config.APIPort)
	}
	if !config.EnableHTTPS {
		t.Error("Expected HTTPS enabled by default")
	}
	if config.Timezone != "UTC" {
		t.Errorf("Expected default timezone UTC, got %s", config.Timezone)
	}
}

func TestSetupValidateStep1_Interface(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	tests := []struct {
		name    string
		config  services.SetupConfig
		wantErr bool
	}{
		{
			name:    "empty interface",
			config:  services.SetupConfig{},
			wantErr: true,
		},
		{
			name: "valid DHCP config",
			config: services.SetupConfig{
				ManagementInterface: "em0",
				UseDHCP:             true,
			},
			wantErr: false,
		},
		{
			name: "static IP without address",
			config: services.SetupConfig{
				ManagementInterface: "em0",
				UseDHCP:             false,
			},
			wantErr: true,
		},
		{
			name: "static IP with address",
			config: services.SetupConfig{
				ManagementInterface: "em0",
				UseDHCP:             false,
				ManagementIP:        "192.168.1.1",
				ManagementNetmask:   "255.255.255.0",
			},
			wantErr: false,
		},
		{
			name: "invalid IP address",
			config: services.SetupConfig{
				ManagementInterface: "em0",
				UseDHCP:             false,
				ManagementIP:        "invalid",
				ManagementNetmask:   "255.255.255.0",
			},
			wantErr: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := svc.ValidateStep(1, tt.config)
			if (err != nil) != tt.wantErr {
				t.Errorf("ValidateStep() error = %v, wantErr %v", err, tt.wantErr)
			}
		})
	}
}

func TestSetupValidateStep2_AdminAccount(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	tests := []struct {
		name    string
		config  services.SetupConfig
		wantErr bool
	}{
		{
			name:    "empty username",
			config:  services.SetupConfig{AdminPassword: "Password123"},
			wantErr: true,
		},
		{
			name:    "short username",
			config:  services.SetupConfig{AdminUsername: "ab", AdminPassword: "Password123"},
			wantErr: true,
		},
		{
			name:    "empty password",
			config:  services.SetupConfig{AdminUsername: "admin"},
			wantErr: true,
		},
		{
			name:    "short password",
			config:  services.SetupConfig{AdminUsername: "admin", AdminPassword: "Pass1"},
			wantErr: true,
		},
		{
			name:    "weak password - no uppercase",
			config:  services.SetupConfig{AdminUsername: "admin", AdminPassword: "password123"},
			wantErr: true,
		},
		{
			name:    "weak password - no lowercase",
			config:  services.SetupConfig{AdminUsername: "admin", AdminPassword: "PASSWORD123"},
			wantErr: true,
		},
		{
			name:    "weak password - no number",
			config:  services.SetupConfig{AdminUsername: "admin", AdminPassword: "PasswordABC"},
			wantErr: true,
		},
		{
			name:    "valid credentials",
			config:  services.SetupConfig{AdminUsername: "admin", AdminPassword: "Password123"},
			wantErr: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := svc.ValidateStep(2, tt.config)
			if (err != nil) != tt.wantErr {
				t.Errorf("ValidateStep() error = %v, wantErr %v", err, tt.wantErr)
			}
		})
	}
}

func TestSetupValidateStep3_APISettings(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	tests := []struct {
		name    string
		config  services.SetupConfig
		wantErr bool
	}{
		{
			name:    "empty hostname",
			config:  services.SetupConfig{APIPort: 8443},
			wantErr: true,
		},
		{
			name:    "invalid port - zero",
			config:  services.SetupConfig{Hostname: "nswall", APIPort: 0},
			wantErr: true,
		},
		{
			name:    "invalid port - too high",
			config:  services.SetupConfig{Hostname: "nswall", APIPort: 70000},
			wantErr: true,
		},
		{
			name:    "valid config",
			config:  services.SetupConfig{Hostname: "nswall", APIPort: 8443},
			wantErr: false,
		},
		{
			name:    "valid config - port 443",
			config:  services.SetupConfig{Hostname: "nswall", APIPort: 443},
			wantErr: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := svc.ValidateStep(3, tt.config)
			if (err != nil) != tt.wantErr {
				t.Errorf("ValidateStep() error = %v, wantErr %v", err, tt.wantErr)
			}
		})
	}
}

func TestSetupValidateStep4_DNS(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	tests := []struct {
		name    string
		config  services.SetupConfig
		wantErr bool
	}{
		{
			name:    "no DNS servers",
			config:  services.SetupConfig{DNSServers: []string{}},
			wantErr: true,
		},
		{
			name:    "invalid DNS server",
			config:  services.SetupConfig{DNSServers: []string{"invalid"}},
			wantErr: true,
		},
		{
			name:    "valid single DNS",
			config:  services.SetupConfig{DNSServers: []string{"1.1.1.1"}},
			wantErr: false,
		},
		{
			name:    "valid multiple DNS",
			config:  services.SetupConfig{DNSServers: []string{"1.1.1.1", "8.8.8.8"}},
			wantErr: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			err := svc.ValidateStep(4, tt.config)
			if (err != nil) != tt.wantErr {
				t.Errorf("ValidateStep() error = %v, wantErr %v", err, tt.wantErr)
			}
		})
	}
}

func TestSetupSaveStep(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	// Save step 1
	config := services.SetupConfig{
		ManagementInterface: "em0",
		UseDHCP:             true,
	}

	err := svc.SaveStep(1, config)
	if err != nil {
		t.Fatalf("Failed to save step 1: %v", err)
	}

	// Check status updated
	status := svc.GetStatus()
	if status.CurrentStep != 2 {
		t.Errorf("Expected current step 2 after saving step 1, got %d", status.CurrentStep)
	}

	// Check config saved
	savedConfig := svc.GetConfig()
	if savedConfig.ManagementInterface != "em0" {
		t.Errorf("Expected interface em0, got %s", savedConfig.ManagementInterface)
	}
}

func TestSetupGetTimezones(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	timezones := svc.GetAvailableTimezones()
	if len(timezones) == 0 {
		t.Error("Expected at least one timezone")
	}

	// Check UTC is present
	found := false
	for _, tz := range timezones {
		if tz == "UTC" {
			found = true
			break
		}
	}
	if !found {
		t.Error("Expected UTC in timezones list")
	}
}

func TestSetupGenerateToken(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	token, err := svc.GenerateSetupToken()
	if err != nil {
		t.Fatalf("Failed to generate token: %v", err)
	}

	if len(token) != 64 { // 32 bytes = 64 hex chars
		t.Errorf("Expected 64-char token, got %d chars", len(token))
	}

	// Validate token
	if !svc.ValidateSetupToken(token) {
		t.Error("Generated token should be valid")
	}

	// Invalid token should fail
	if svc.ValidateSetupToken("invalid") {
		t.Error("Invalid token should not validate")
	}
}

func TestSetupReset(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	// Save some steps
	svc.SaveStep(1, services.SetupConfig{
		ManagementInterface: "em0",
		UseDHCP:             true,
	})

	// Reset
	err := svc.ResetSetup()
	if err != nil {
		t.Fatalf("Failed to reset setup: %v", err)
	}

	// Check status is reset
	status := svc.GetStatus()
	if status.CurrentStep != 1 {
		t.Errorf("Expected step 1 after reset, got %d", status.CurrentStep)
	}
	if status.Completed {
		t.Error("Expected not completed after reset")
	}
}

func TestSetupIsComplete(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	if svc.IsSetupComplete() {
		t.Error("Setup should not be complete initially")
	}
}

func TestSetupGetInterfaces(t *testing.T) {
	tempDir := t.TempDir()
	svc := services.NewSetupService(tempDir)

	// This will use the Go fallback on non-OpenBSD systems
	interfaces, err := svc.GetAvailableInterfaces()
	if err != nil {
		// Not an error on non-OpenBSD systems, just might return empty
		t.Logf("GetAvailableInterfaces returned error (expected on non-OpenBSD): %v", err)
	}

	// Just check it doesn't panic
	_ = interfaces
}

// Benchmark tests
func BenchmarkSetupValidateStep(b *testing.B) {
	tempDir := b.TempDir()
	svc := services.NewSetupService(tempDir)

	config := services.SetupConfig{
		ManagementInterface: "em0",
		UseDHCP:             true,
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.ValidateStep(1, config)
	}
}

func BenchmarkSetupSaveStep(b *testing.B) {
	tempDir := b.TempDir()
	svc := services.NewSetupService(tempDir)

	config := services.SetupConfig{
		ManagementInterface: "em0",
		UseDHCP:             true,
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.SaveStep(1, config)
	}
}
