package services

import (
	"bufio"
	"context"
	"fmt"
	"os"
	"os/exec"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/northshorenetworks/nswall/api/internal/models"
)

// DHCPService handles DHCP operations
type DHCPService struct {
	nshPath string
}

// NewDHCPService creates a new DHCPService
func NewDHCPService(nshPath string) *DHCPService {
	return &DHCPService{nshPath: nshPath}
}

// GetLeases returns DHCP leases
func (d *DHCPService) GetLeases(ctx context.Context) ([]models.DHCPLease, error) {
	// Try dhcpd leases file
	content, err := os.ReadFile("/var/db/dhcpd.leases")
	if err != nil {
		// Try alternate location
		content, err = os.ReadFile("/var/db/dhcpleased/leases")
		if err != nil {
			return nil, fmt.Errorf("no lease file found")
		}
	}

	return parseLeases(string(content)), nil
}

// GetSubnets returns configured DHCP subnets
func (d *DHCPService) GetSubnets(ctx context.Context) ([]models.DHCPSubnet, error) {
	content, err := os.ReadFile("/etc/dhcpd.conf")
	if err != nil {
		return nil, fmt.Errorf("dhcpd.conf not found: %v", err)
	}

	return parseSubnets(string(content)), nil
}

// Helper functions

func parseLeases(content string) []models.DHCPLease {
	var leases []models.DHCPLease
	var current *models.DHCPLease

	scanner := bufio.NewScanner(strings.NewReader(content))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())

		if strings.HasPrefix(line, "lease ") {
			if current != nil {
				leases = append(leases, *current)
			}

			// Parse: lease 192.168.1.100 {
			re := regexp.MustCompile(`lease\s+(\S+)`)
			if matches := re.FindStringSubmatch(line); len(matches) > 1 {
				current = &models.DHCPLease{
					IPAddress: matches[1],
				}
			}
		} else if current != nil {
			if strings.HasPrefix(line, "starts") {
				// Parse: starts 3 2024/01/15 10:30:00;
				current.Start = parseLeaseTime(line)
			} else if strings.HasPrefix(line, "ends") {
				current.End = parseLeaseTime(line)
			} else if strings.HasPrefix(line, "hardware ethernet") {
				// Parse: hardware ethernet 00:11:22:33:44:55;
				re := regexp.MustCompile(`hardware ethernet\s+([0-9a-fA-F:]+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.MACAddress = matches[1]
				}
			} else if strings.HasPrefix(line, "client-hostname") {
				// Parse: client-hostname "myhost";
				re := regexp.MustCompile(`client-hostname\s+"([^"]+)"`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.Hostname = matches[1]
				}
			} else if strings.HasPrefix(line, "binding state") {
				// Parse: binding state active;
				re := regexp.MustCompile(`binding state\s+(\w+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.State = matches[1]
				}
			} else if line == "}" {
				// Determine state based on times if not set
				if current.State == "" {
					if time.Now().Before(current.End) {
						current.State = "active"
					} else {
						current.State = "expired"
					}
				}
			}
		}
	}

	if current != nil {
		leases = append(leases, *current)
	}

	return leases
}

func parseLeaseTime(line string) time.Time {
	// Format: starts 3 2024/01/15 10:30:00;
	re := regexp.MustCompile(`\d+\s+(\d{4}/\d{2}/\d{2}\s+\d{2}:\d{2}:\d{2})`)
	if matches := re.FindStringSubmatch(line); len(matches) > 1 {
		t, _ := time.Parse("2006/01/02 15:04:05", matches[1])
		return t
	}
	return time.Time{}
}

func parseSubnets(content string) []models.DHCPSubnet {
	var subnets []models.DHCPSubnet
	var current *models.DHCPSubnet
	depth := 0

	scanner := bufio.NewScanner(strings.NewReader(content))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())

		// Skip comments
		if strings.HasPrefix(line, "#") {
			continue
		}

		if strings.HasPrefix(line, "subnet ") {
			// Parse: subnet 192.168.1.0 netmask 255.255.255.0 {
			re := regexp.MustCompile(`subnet\s+(\S+)\s+netmask\s+(\S+)`)
			if matches := re.FindStringSubmatch(line); len(matches) > 2 {
				current = &models.DHCPSubnet{
					Network: matches[1],
					Netmask: matches[2],
				}
			}
			depth++
		} else if current != nil {
			if strings.Contains(line, "{") {
				depth++
			}
			if strings.Contains(line, "}") {
				depth--
				if depth == 0 {
					subnets = append(subnets, *current)
					current = nil
				}
			}

			// Parse options
			if strings.HasPrefix(line, "range ") {
				// Parse: range 192.168.1.100 192.168.1.200;
				re := regexp.MustCompile(`range\s+(\S+)\s+(\S+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 2 {
					current.Range = &models.DHCPRange{
						Start: matches[1],
						End:   matches[2],
					}
				}
			} else if strings.HasPrefix(line, "option routers") {
				re := regexp.MustCompile(`option routers\s+(\S+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.Router = strings.TrimSuffix(matches[1], ";")
				}
			} else if strings.HasPrefix(line, "option domain-name-servers") {
				re := regexp.MustCompile(`option domain-name-servers\s+(.+);`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					servers := strings.Split(matches[1], ",")
					for _, s := range servers {
						current.DNS = append(current.DNS, strings.TrimSpace(s))
					}
				}
			} else if strings.HasPrefix(line, "option domain-name") {
				re := regexp.MustCompile(`option domain-name\s+"([^"]+)"`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.Domain = matches[1]
				}
			} else if strings.HasPrefix(line, "default-lease-time") {
				re := regexp.MustCompile(`default-lease-time\s+(\d+)`)
				if matches := re.FindStringSubmatch(line); len(matches) > 1 {
					current.LeaseTime, _ = strconv.Atoi(matches[1])
				}
			}
		}
	}

	return subnets
}

// DNSService handles DNS operations
type DNSService struct {
	nshPath string
}

// NewDNSService creates a new DNSService
func NewDNSService(nshPath string) *DNSService {
	return &DNSService{nshPath: nshPath}
}

// GetResolverConfig returns resolver configuration
func (d *DNSService) GetResolverConfig(ctx context.Context) (map[string]interface{}, error) {
	content, err := os.ReadFile("/etc/resolv.conf")
	if err != nil {
		return nil, err
	}

	config := make(map[string]interface{})
	var nameservers []string
	var search []string

	scanner := bufio.NewScanner(strings.NewReader(string(content)))
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if strings.HasPrefix(line, "#") || line == "" {
			continue
		}

		parts := strings.Fields(line)
		if len(parts) < 2 {
			continue
		}

		switch parts[0] {
		case "nameserver":
			nameservers = append(nameservers, parts[1])
		case "search", "domain":
			search = append(search, parts[1:]...)
		case "options":
			config["options"] = parts[1:]
		}
	}

	config["nameservers"] = nameservers
	config["search"] = search

	return config, nil
}

// SetResolverConfig sets resolver configuration
func (d *DNSService) SetResolverConfig(ctx context.Context, nameservers []string, search []string) error {
	var lines []string

	// Add search domains
	if len(search) > 0 {
		lines = append(lines, "search "+strings.Join(search, " "))
	}

	// Add nameservers
	for _, ns := range nameservers {
		lines = append(lines, "nameserver "+ns)
	}

	content := strings.Join(lines, "\n") + "\n"

	err := os.WriteFile("/etc/resolv.conf", []byte(content), 0644)
	if err != nil {
		return fmt.Errorf("failed to write resolv.conf: %v", err)
	}

	return nil
}

// GetUnboundConfig returns unbound configuration if available
func (d *DNSService) GetUnboundConfig(ctx context.Context) (string, error) {
	content, err := os.ReadFile("/var/unbound/etc/unbound.conf")
	if err != nil {
		return "", fmt.Errorf("unbound not configured: %v", err)
	}
	return string(content), nil
}

// FlushDNSCache flushes the DNS cache
func (d *DNSService) FlushDNSCache(ctx context.Context) error {
	// Try unbound
	cmd := exec.CommandContext(ctx, "unbound-control", "flush_zone", ".")
	if err := cmd.Run(); err != nil {
		// Try resolvd
		cmd = exec.CommandContext(ctx, "pkill", "-HUP", "resolvd")
		if err := cmd.Run(); err != nil {
			return fmt.Errorf("no supported DNS cache found")
		}
	}
	return nil
}
