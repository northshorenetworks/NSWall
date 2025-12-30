// NSWall SNMP Agent
// Provides SNMP monitoring for NSWall firewall/router
package main

import (
	"flag"
	"fmt"
	"log"
	"net"
	"os"
	"os/exec"
	"os/signal"
	"strconv"
	"strings"
	"syscall"
	"time"

	"github.com/gosnmp/gosnmp"
)

var version = "2.0.0"

// OID definitions - using enterprises.59999 (example private enterprise number)
// In production, register with IANA for an actual enterprise number
const (
	// Base OIDs
	oidBase       = ".1.3.6.1.4.1.59999"
	oidSystem     = oidBase + ".1"
	oidInterfaces = oidBase + ".2"
	oidFirewall   = oidBase + ".3"
	oidVPN        = oidBase + ".4"
	oidHA         = oidBase + ".5"

	// System OIDs
	oidSysVersion   = oidSystem + ".1.0"
	oidSysHostname  = oidSystem + ".2.0"
	oidSysUptime    = oidSystem + ".3.0"
	oidSysLoadAvg1  = oidSystem + ".4.0"
	oidSysLoadAvg5  = oidSystem + ".5.0"
	oidSysLoadAvg15 = oidSystem + ".6.0"
	oidSysMemTotal  = oidSystem + ".7.0"
	oidSysMemFree   = oidSystem + ".8.0"

	// Firewall OIDs
	oidPfEnabled     = oidFirewall + ".1.0"
	oidPfStates      = oidFirewall + ".2.0"
	oidPfStateLimit  = oidFirewall + ".3.0"
	oidPfSrcNodes    = oidFirewall + ".4.0"
	oidPfRules       = oidFirewall + ".5.0"
	oidPfBytesIn     = oidFirewall + ".6.0"
	oidPfBytesOut    = oidFirewall + ".7.0"
	oidPfPacketsIn   = oidFirewall + ".8.0"
	oidPfPacketsOut  = oidFirewall + ".9.0"

	// HA OIDs
	oidCarpMaster    = oidHA + ".1.0"
	oidCarpBackup    = oidHA + ".2.0"
	oidPfsyncEnabled = oidHA + ".3.0"
)

// SNMPAgent holds the agent state
type SNMPAgent struct {
	community string
	port      int
	listener  *gosnmp.TrapListener
}

// NewSNMPAgent creates a new SNMP agent
func NewSNMPAgent(community string, port int) *SNMPAgent {
	return &SNMPAgent{
		community: community,
		port:      port,
	}
}

// getSystemInfo retrieves system information
func (a *SNMPAgent) getSystemInfo() map[string]interface{} {
	info := make(map[string]interface{})

	// Hostname
	hostname, _ := os.Hostname()
	info["hostname"] = hostname

	// OpenBSD version
	if out, err := exec.Command("uname", "-r").Output(); err == nil {
		info["version"] = strings.TrimSpace(string(out))
	}

	// Uptime
	if out, err := exec.Command("sysctl", "-n", "kern.boottime").Output(); err == nil {
		// Parse boottime
		info["uptime"] = parseUptime(string(out))
	}

	// Load average
	if out, err := exec.Command("sysctl", "-n", "vm.loadavg").Output(); err == nil {
		parts := strings.Fields(string(out))
		if len(parts) >= 3 {
			info["load1"], _ = strconv.ParseFloat(parts[0], 64)
			info["load5"], _ = strconv.ParseFloat(parts[1], 64)
			info["load15"], _ = strconv.ParseFloat(parts[2], 64)
		}
	}

	// Memory
	if out, err := exec.Command("sysctl", "-n", "hw.physmem").Output(); err == nil {
		mem, _ := strconv.ParseUint(strings.TrimSpace(string(out)), 10, 64)
		info["memtotal"] = mem
	}

	return info
}

func parseUptime(boottime string) int64 {
	// OpenBSD sysctl kern.boottime returns epoch seconds
	parts := strings.Fields(boottime)
	if len(parts) > 0 {
		sec, _ := strconv.ParseInt(parts[0], 10, 64)
		return time.Now().Unix() - sec
	}
	return 0
}

// getPFInfo retrieves PF firewall information
func (a *SNMPAgent) getPFInfo() map[string]interface{} {
	info := make(map[string]interface{})

	// pfctl -si for interface stats
	if out, err := exec.Command("pfctl", "-si").Output(); err == nil {
		lines := strings.Split(string(out), "\n")
		for _, line := range lines {
			line = strings.TrimSpace(line)
			if strings.HasPrefix(line, "Status:") {
				if strings.Contains(line, "Enabled") {
					info["enabled"] = 1
				} else {
					info["enabled"] = 0
				}
			}
		}
	}

	// pfctl -ss for state count
	if out, err := exec.Command("pfctl", "-ss").Output(); err == nil {
		lines := strings.Split(string(out), "\n")
		count := 0
		for _, line := range lines {
			if strings.TrimSpace(line) != "" {
				count++
			}
		}
		info["states"] = count
	}

	// pfctl -sm for limits
	if out, err := exec.Command("pfctl", "-sm").Output(); err == nil {
		lines := strings.Split(string(out), "\n")
		for _, line := range lines {
			if strings.Contains(line, "states") {
				parts := strings.Fields(line)
				if len(parts) >= 4 {
					limit, _ := strconv.ParseUint(parts[3], 10, 64)
					info["statelimit"] = limit
				}
			}
			if strings.Contains(line, "src-nodes") {
				parts := strings.Fields(line)
				if len(parts) >= 4 {
					nodes, _ := strconv.ParseUint(parts[3], 10, 64)
					info["srcnodes"] = nodes
				}
			}
		}
	}

	// pfctl -sr for rule count
	if out, err := exec.Command("pfctl", "-sr").Output(); err == nil {
		lines := strings.Split(string(out), "\n")
		count := 0
		for _, line := range lines {
			if strings.TrimSpace(line) != "" && !strings.HasPrefix(line, "#") {
				count++
			}
		}
		info["rules"] = count
	}

	// pfctl -vsi for byte/packet counters
	if out, err := exec.Command("pfctl", "-vsi").Output(); err == nil {
		lines := strings.Split(string(out), "\n")
		for _, line := range lines {
			line = strings.TrimSpace(line)
			if strings.HasPrefix(line, "Bytes In") {
				parts := strings.Fields(line)
				if len(parts) >= 3 {
					bytes, _ := strconv.ParseUint(parts[2], 10, 64)
					info["bytesin"] = bytes
				}
			}
			if strings.HasPrefix(line, "Bytes Out") {
				parts := strings.Fields(line)
				if len(parts) >= 3 {
					bytes, _ := strconv.ParseUint(parts[2], 10, 64)
					info["bytesout"] = bytes
				}
			}
			if strings.HasPrefix(line, "Packets In") {
				parts := strings.Fields(line)
				if len(parts) >= 3 {
					packets, _ := strconv.ParseUint(parts[2], 10, 64)
					info["packetsin"] = packets
				}
			}
			if strings.HasPrefix(line, "Packets Out") {
				parts := strings.Fields(line)
				if len(parts) >= 3 {
					packets, _ := strconv.ParseUint(parts[2], 10, 64)
					info["packetsout"] = packets
				}
			}
		}
	}

	return info
}

// getCARPInfo retrieves CARP HA information
func (a *SNMPAgent) getCARPInfo() map[string]interface{} {
	info := make(map[string]interface{})
	info["master"] = 0
	info["backup"] = 0

	if out, err := exec.Command("ifconfig", "-a").Output(); err == nil {
		lines := strings.Split(string(out), "\n")
		for _, line := range lines {
			if strings.Contains(line, "carp:") {
				if strings.Contains(line, "MASTER") {
					info["master"] = info["master"].(int) + 1
				}
				if strings.Contains(line, "BACKUP") {
					info["backup"] = info["backup"].(int) + 1
				}
			}
		}
	}

	// Check pfsync
	if out, err := exec.Command("ifconfig", "pfsync0").Output(); err == nil {
		if strings.Contains(string(out), "UP") {
			info["pfsync"] = 1
		} else {
			info["pfsync"] = 0
		}
	} else {
		info["pfsync"] = 0
	}

	return info
}

// handleRequest handles incoming SNMP requests
func (a *SNMPAgent) handleRequest(packet *gosnmp.SnmpPacket, addr *net.UDPAddr) (*gosnmp.SnmpPacket, error) {
	// Check community string
	if packet.Community != a.community {
		return nil, fmt.Errorf("invalid community string")
	}

	response := &gosnmp.SnmpPacket{
		Version:   packet.Version,
		Community: packet.Community,
		PDUType:   gosnmp.GetResponse,
		RequestID: packet.RequestID,
	}

	sysInfo := a.getSystemInfo()
	pfInfo := a.getPFInfo()
	carpInfo := a.getCARPInfo()

	for _, pdu := range packet.Variables {
		var responsePDU gosnmp.SnmpPDU
		responsePDU.Name = pdu.Name

		switch pdu.Name {
		// System OIDs
		case oidSysVersion:
			responsePDU.Type = gosnmp.OctetString
			responsePDU.Value = sysInfo["version"]
		case oidSysHostname:
			responsePDU.Type = gosnmp.OctetString
			responsePDU.Value = sysInfo["hostname"]
		case oidSysUptime:
			responsePDU.Type = gosnmp.TimeTicks
			if uptime, ok := sysInfo["uptime"].(int64); ok {
				responsePDU.Value = uint32(uptime * 100) // TimeTicks are in 1/100ths of second
			}
		case oidSysLoadAvg1:
			responsePDU.Type = gosnmp.OctetString
			if load, ok := sysInfo["load1"].(float64); ok {
				responsePDU.Value = fmt.Sprintf("%.2f", load)
			}
		case oidSysLoadAvg5:
			responsePDU.Type = gosnmp.OctetString
			if load, ok := sysInfo["load5"].(float64); ok {
				responsePDU.Value = fmt.Sprintf("%.2f", load)
			}
		case oidSysLoadAvg15:
			responsePDU.Type = gosnmp.OctetString
			if load, ok := sysInfo["load15"].(float64); ok {
				responsePDU.Value = fmt.Sprintf("%.2f", load)
			}
		case oidSysMemTotal:
			responsePDU.Type = gosnmp.Counter64
			if mem, ok := sysInfo["memtotal"].(uint64); ok {
				responsePDU.Value = mem
			}

		// Firewall OIDs
		case oidPfEnabled:
			responsePDU.Type = gosnmp.Integer
			if enabled, ok := pfInfo["enabled"].(int); ok {
				responsePDU.Value = enabled
			}
		case oidPfStates:
			responsePDU.Type = gosnmp.Gauge32
			if states, ok := pfInfo["states"].(int); ok {
				responsePDU.Value = uint32(states)
			}
		case oidPfStateLimit:
			responsePDU.Type = gosnmp.Gauge32
			if limit, ok := pfInfo["statelimit"].(uint64); ok {
				responsePDU.Value = uint32(limit)
			}
		case oidPfRules:
			responsePDU.Type = gosnmp.Gauge32
			if rules, ok := pfInfo["rules"].(int); ok {
				responsePDU.Value = uint32(rules)
			}
		case oidPfBytesIn:
			responsePDU.Type = gosnmp.Counter64
			if bytes, ok := pfInfo["bytesin"].(uint64); ok {
				responsePDU.Value = bytes
			}
		case oidPfBytesOut:
			responsePDU.Type = gosnmp.Counter64
			if bytes, ok := pfInfo["bytesout"].(uint64); ok {
				responsePDU.Value = bytes
			}
		case oidPfPacketsIn:
			responsePDU.Type = gosnmp.Counter64
			if packets, ok := pfInfo["packetsin"].(uint64); ok {
				responsePDU.Value = packets
			}
		case oidPfPacketsOut:
			responsePDU.Type = gosnmp.Counter64
			if packets, ok := pfInfo["packetsout"].(uint64); ok {
				responsePDU.Value = packets
			}

		// HA OIDs
		case oidCarpMaster:
			responsePDU.Type = gosnmp.Integer
			if master, ok := carpInfo["master"].(int); ok {
				responsePDU.Value = master
			}
		case oidCarpBackup:
			responsePDU.Type = gosnmp.Integer
			if backup, ok := carpInfo["backup"].(int); ok {
				responsePDU.Value = backup
			}
		case oidPfsyncEnabled:
			responsePDU.Type = gosnmp.Integer
			if pfsync, ok := carpInfo["pfsync"].(int); ok {
				responsePDU.Value = pfsync
			}

		default:
			responsePDU.Type = gosnmp.NoSuchObject
			responsePDU.Value = nil
		}

		response.Variables = append(response.Variables, responsePDU)
	}

	return response, nil
}

// Start starts the SNMP agent
func (a *SNMPAgent) Start() error {
	addr := fmt.Sprintf("0.0.0.0:%d", a.port)
	log.Printf("Starting NSWall SNMP agent on %s", addr)

	// Create UDP listener
	udpAddr, err := net.ResolveUDPAddr("udp", addr)
	if err != nil {
		return fmt.Errorf("failed to resolve address: %v", err)
	}

	conn, err := net.ListenUDP("udp", udpAddr)
	if err != nil {
		return fmt.Errorf("failed to listen: %v", err)
	}
	defer conn.Close()

	buf := make([]byte, 65535)

	for {
		n, remoteAddr, err := conn.ReadFromUDP(buf)
		if err != nil {
			log.Printf("Read error: %v", err)
			continue
		}

		// Parse SNMP packet
		packet := &gosnmp.SnmpPacket{}
		remainder, err := packet.UnmarshalMsg(buf[:n])
		if err != nil {
			log.Printf("Failed to parse SNMP packet: %v", err)
			continue
		}
		_ = remainder

		// Handle request
		response, err := a.handleRequest(packet, remoteAddr)
		if err != nil {
			log.Printf("Request handling error: %v", err)
			continue
		}

		// Send response
		responseBytes, err := response.MarshalMsg()
		if err != nil {
			log.Printf("Failed to marshal response: %v", err)
			continue
		}

		_, err = conn.WriteToUDP(responseBytes, remoteAddr)
		if err != nil {
			log.Printf("Failed to send response: %v", err)
		}
	}
}

func main() {
	var (
		port      = flag.Int("port", 161, "SNMP port")
		community = flag.String("community", "public", "SNMP community string")
		showVer   = flag.Bool("version", false, "Show version")
	)
	flag.Parse()

	if *showVer {
		fmt.Printf("nswall-snmpd version %s\n", version)
		os.Exit(0)
	}

	agent := NewSNMPAgent(*community, *port)

	// Handle signals for graceful shutdown
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)

	go func() {
		<-sigChan
		log.Println("Shutting down SNMP agent...")
		os.Exit(0)
	}()

	if err := agent.Start(); err != nil {
		log.Fatalf("SNMP agent error: %v", err)
	}
}
