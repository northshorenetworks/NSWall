package services

import (
	"bufio"
	"context"
	"fmt"
	"os/exec"
	"regexp"
	"strconv"
	"strings"

	"github.com/northshorenetworks/nswall/api/internal/models"
)

// VPNService handles VPN operations (WireGuard, IKE)
type VPNService struct {
	nshPath string
}

// NewVPNService creates a new VPNService
func NewVPNService(nshPath string) *VPNService {
	return &VPNService{nshPath: nshPath}
}

// GetWireGuardInterfaces returns all WireGuard interfaces
func (v *VPNService) GetWireGuardInterfaces(ctx context.Context) ([]models.WireGuardInterface, error) {
	// Find all wg interfaces
	output, err := exec.CommandContext(ctx, "ifconfig", "-a").Output()
	if err != nil {
		return nil, err
	}

	var wgInterfaces []string
	lines := strings.Split(string(output), "\n")
	for _, line := range lines {
		if strings.HasPrefix(line, "wg") && strings.Contains(line, ":") {
			name := strings.Split(line, ":")[0]
			wgInterfaces = append(wgInterfaces, name)
		}
	}

	var interfaces []models.WireGuardInterface
	for _, name := range wgInterfaces {
		iface, err := v.GetWireGuardInterface(ctx, name)
		if err == nil {
			interfaces = append(interfaces, *iface)
		}
	}

	return interfaces, nil
}

// GetWireGuardInterface returns a specific WireGuard interface
func (v *VPNService) GetWireGuardInterface(ctx context.Context, name string) (*models.WireGuardInterface, error) {
	output, err := exec.CommandContext(ctx, "ifconfig", name).Output()
	if err != nil {
		return nil, fmt.Errorf("interface not found: %s", name)
	}

	iface := &models.WireGuardInterface{
		Name: name,
	}

	lines := strings.Split(string(output), "\n")
	var currentPeer *models.WireGuardPeer

	for _, line := range lines {
		line = strings.TrimSpace(line)

		if strings.HasPrefix(line, "wgpubkey") {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				iface.PublicKey = parts[1]
			}
		} else if strings.HasPrefix(line, "wgport") {
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				iface.ListenPort, _ = strconv.Atoi(parts[1])
			}
		} else if strings.HasPrefix(line, "wgpeer") {
			// Save previous peer
			if currentPeer != nil {
				iface.Peers = append(iface.Peers, *currentPeer)
			}

			currentPeer = &models.WireGuardPeer{}
			parts := strings.Fields(line)
			if len(parts) >= 2 {
				currentPeer.PublicKey = parts[1]
			}
		} else if currentPeer != nil {
			if strings.HasPrefix(line, "wgendpoint") {
				parts := strings.Fields(line)
				if len(parts) >= 2 {
					currentPeer.Endpoint = parts[1]
				}
			} else if strings.HasPrefix(line, "wgaip") {
				parts := strings.Fields(line)
				if len(parts) >= 2 {
					currentPeer.AllowedIPs = append(currentPeer.AllowedIPs, parts[1])
				}
			} else if strings.HasPrefix(line, "wgpka") {
				parts := strings.Fields(line)
				if len(parts) >= 2 {
					currentPeer.PersistentKeepalive, _ = strconv.Atoi(parts[1])
				}
			}
		}
	}

	// Save last peer
	if currentPeer != nil {
		iface.Peers = append(iface.Peers, *currentPeer)
	}

	return iface, nil
}

// CreateWireGuardInterface creates a new WireGuard interface
func (v *VPNService) CreateWireGuardInterface(ctx context.Context, name string) error {
	cmd := exec.CommandContext(ctx, "ifconfig", name, "create")
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to create interface: %s", string(output))
	}
	return nil
}

// ConfigureWireGuard configures a WireGuard interface
func (v *VPNService) ConfigureWireGuard(ctx context.Context, name string, config *models.WireGuardConfig) error {
	if config.ListenPort > 0 {
		cmd := exec.CommandContext(ctx, "ifconfig", name, "wgport", strconv.Itoa(config.ListenPort))
		if output, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("failed to set port: %s", string(output))
		}
	}

	if config.PrivateKey != "" {
		cmd := exec.CommandContext(ctx, "ifconfig", name, "wgkey", config.PrivateKey)
		if output, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("failed to set key: %s", string(output))
		}
	}

	return nil
}

// AddWireGuardPeer adds a peer to a WireGuard interface
func (v *VPNService) AddWireGuardPeer(ctx context.Context, name string, peer *models.WireGuardPeerConfig) error {
	// Add peer
	cmd := exec.CommandContext(ctx, "ifconfig", name, "wgpeer", peer.PublicKey)
	if output, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("failed to add peer: %s", string(output))
	}

	// Set endpoint
	if peer.Endpoint != "" {
		cmd = exec.CommandContext(ctx, "ifconfig", name, "wgpeer", peer.PublicKey, "wgendpoint", peer.Endpoint)
		if output, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("failed to set endpoint: %s", string(output))
		}
	}

	// Set allowed IPs
	for _, aip := range peer.AllowedIPs {
		cmd = exec.CommandContext(ctx, "ifconfig", name, "wgpeer", peer.PublicKey, "wgaip", aip)
		if output, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("failed to set allowed IP: %s", string(output))
		}
	}

	// Set preshared key
	if peer.PresharedKey != "" {
		cmd = exec.CommandContext(ctx, "ifconfig", name, "wgpeer", peer.PublicKey, "wgpsk", peer.PresharedKey)
		if output, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("failed to set preshared key: %s", string(output))
		}
	}

	// Set persistent keepalive
	if peer.PersistentKeepalive > 0 {
		cmd = exec.CommandContext(ctx, "ifconfig", name, "wgpeer", peer.PublicKey, "wgpka", strconv.Itoa(peer.PersistentKeepalive))
		if output, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("failed to set keepalive: %s", string(output))
		}
	}

	return nil
}

// DeleteWireGuardPeer removes a peer from a WireGuard interface
func (v *VPNService) DeleteWireGuardPeer(ctx context.Context, name, publicKey string) error {
	cmd := exec.CommandContext(ctx, "ifconfig", name, "-wgpeer", publicKey)
	output, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("failed to delete peer: %s", string(output))
	}
	return nil
}

// GenerateWireGuardKey generates a new WireGuard private key
func (v *VPNService) GenerateWireGuardKey(ctx context.Context) (privateKey, publicKey string, err error) {
	// Generate private key
	privCmd := exec.CommandContext(ctx, "openssl", "rand", "-base64", "32")
	privOutput, err := privCmd.Output()
	if err != nil {
		return "", "", fmt.Errorf("failed to generate key: %v", err)
	}
	privateKey = strings.TrimSpace(string(privOutput))

	// The public key would be derived on interface creation
	// For now return just the private key
	return privateKey, "", nil
}

// GetIKESAs returns IKE Security Associations
func (v *VPNService) GetIKESAs(ctx context.Context) ([]models.IKESa, error) {
	output, err := exec.CommandContext(ctx, "ikectl", "show", "sa").Output()
	if err != nil {
		// Try iked status instead
		output, err = exec.CommandContext(ctx, "rcctl", "check", "iked").Output()
		if err != nil {
			return nil, fmt.Errorf("iked not running or not installed")
		}
	}

	return parseIKESAs(string(output)), nil
}

// GetIPSecSAs returns IPsec Security Associations
func (v *VPNService) GetIPSecSAs(ctx context.Context) ([]models.IPSecSa, error) {
	output, err := exec.CommandContext(ctx, "ipsecctl", "-sa").Output()
	if err != nil {
		return nil, err
	}

	return parseIPSecSAs(string(output)), nil
}

// GetIPSecFlows returns IPsec flows
func (v *VPNService) GetIPSecFlows(ctx context.Context) (string, error) {
	output, err := exec.CommandContext(ctx, "ipsecctl", "-sF").Output()
	if err != nil {
		return "", err
	}
	return string(output), nil
}

// Helper functions

func parseIKESAs(output string) []models.IKESa {
	var sas []models.IKESa

	scanner := bufio.NewScanner(strings.NewReader(output))
	var current *models.IKESa

	for scanner.Scan() {
		line := scanner.Text()
		line = strings.TrimSpace(line)

		if line == "" {
			continue
		}

		// Look for SA header lines
		if strings.HasPrefix(line, "ikesa") || strings.Contains(line, "ESTABLISHED") || strings.Contains(line, "ACTIVE") {
			if current != nil {
				sas = append(sas, *current)
			}

			current = &models.IKESa{}

			// Parse the SA line
			parts := strings.Fields(line)
			for i, p := range parts {
				if strings.HasPrefix(p, "ikesa") {
					continue
				}
				if current.Name == "" && !strings.Contains(p, ":") && !strings.Contains(p, "=") {
					current.Name = p
				}
				if p == "id" && i+1 < len(parts) {
					current.ID, _ = strconv.Atoi(parts[i+1])
				}
			}

			if strings.Contains(line, "ESTABLISHED") {
				current.Status = "ESTABLISHED"
			} else if strings.Contains(line, "ACTIVE") {
				current.Status = "ACTIVE"
			}
		} else if current != nil {
			// Parse additional lines
			if strings.Contains(line, "local") {
				re := regexp.MustCompile(`local\s+(\S+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.LocalAddr = matches[1]
				}
			}
			if strings.Contains(line, "remote") {
				re := regexp.MustCompile(`remote\s+(\S+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.RemoteAddr = matches[1]
				}
			}
			if strings.Contains(line, "srcid") {
				re := regexp.MustCompile(`srcid\s+(\S+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.LocalID = matches[1]
				}
			}
			if strings.Contains(line, "dstid") {
				re := regexp.MustCompile(`dstid\s+(\S+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.RemoteID = matches[1]
				}
			}
		}
	}

	if current != nil {
		sas = append(sas, *current)
	}

	return sas
}

func parseIPSecSAs(output string) []models.IPSecSa {
	var sas []models.IPSecSa

	scanner := bufio.NewScanner(strings.NewReader(output))

	for scanner.Scan() {
		line := scanner.Text()
		line = strings.TrimSpace(line)

		if line == "" || strings.HasPrefix(line, "Flows:") {
			continue
		}

		// Format: esp from 10.0.0.1 to 10.0.0.2 spi 0x12345678 enc aes-256-gcm
		parts := strings.Fields(line)
		if len(parts) < 6 {
			continue
		}

		sa := models.IPSecSa{
			Protocol: parts[0],
		}

		for i, p := range parts {
			if p == "from" && i+1 < len(parts) {
				sa.Source = parts[i+1]
			}
			if p == "to" && i+1 < len(parts) {
				sa.Dest = parts[i+1]
			}
			if p == "spi" && i+1 < len(parts) {
				sa.SPI = parts[i+1]
			}
			if p == "enc" || p == "auth" {
				if i+1 < len(parts) {
					sa.Transform = p + " " + parts[i+1]
				}
			}
		}

		if sa.Protocol != "" && sa.Source != "" {
			sas = append(sas, sa)
		}
	}

	return sas
}
