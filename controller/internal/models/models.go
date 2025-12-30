// Package models defines data structures for the controller
package models

import "time"

// AgentStatus represents the current state of an agent
type AgentStatus struct {
	ID            string            `json:"id"`
	Hostname      string            `json:"hostname"`
	Version       string            `json:"version"`
	Status        string            `json:"status"` // online, offline, degraded
	LastSeen      time.Time         `json:"last_seen"`
	Uptime        int64             `json:"uptime"`
	PFEnabled     bool              `json:"pf_enabled"`
	PFStates      int64             `json:"pf_states"`
	Interfaces    []InterfaceInfo   `json:"interfaces"`
	CPULoad       float64           `json:"cpu_load"`
	MemoryPercent float64           `json:"memory_percent"`
	MemoryTotal   int64             `json:"memory_total"`
	MemoryUsed    int64             `json:"memory_used"`
	DiskPercent   float64           `json:"disk_percent"`
	ConfigHash    string            `json:"config_hash"`
	Tags          map[string]string `json:"tags,omitempty"`
	Groups        []string          `json:"groups,omitempty"`
	IP            string            `json:"ip,omitempty"`
}

// InterfaceInfo represents network interface status
type InterfaceInfo struct {
	Name      string   `json:"name"`
	Status    string   `json:"status"` // up, down
	MAC       string   `json:"mac"`
	IPv4      []string `json:"ipv4,omitempty"`
	IPv6      []string `json:"ipv6,omitempty"`
	RxBytes   int64    `json:"rx_bytes"`
	TxBytes   int64    `json:"tx_bytes"`
	RxPackets int64    `json:"rx_packets"`
	TxPackets int64    `json:"tx_packets"`
}

// AgentConfig represents configuration to push to an agent
type AgentConfig struct {
	Version     int               `json:"version"`
	Hash        string            `json:"hash"`
	PFRules     string            `json:"pf_rules,omitempty"`
	PFTables    map[string][]string `json:"pf_tables,omitempty"`
	Interfaces  map[string]InterfaceConfig `json:"interfaces,omitempty"`
	Routes      []RouteConfig     `json:"routes,omitempty"`
	Hostname    string            `json:"hostname,omitempty"`
	DNS         *DNSConfig        `json:"dns,omitempty"`
	DHCP        *DHCPConfig       `json:"dhcp,omitempty"`
	NTP         []string          `json:"ntp,omitempty"`
	Tags        map[string]string `json:"tags,omitempty"`
}

// InterfaceConfig defines interface configuration
type InterfaceConfig struct {
	Description string   `json:"description,omitempty"`
	IPv4        string   `json:"ipv4,omitempty"`        // CIDR notation
	IPv4Gateway string   `json:"ipv4_gateway,omitempty"`
	IPv6        string   `json:"ipv6,omitempty"`
	DHCP        bool     `json:"dhcp,omitempty"`
	MTU         int      `json:"mtu,omitempty"`
	Enabled     bool     `json:"enabled"`
}

// RouteConfig defines a route
type RouteConfig struct {
	Destination string `json:"destination"`
	Gateway     string `json:"gateway"`
	Interface   string `json:"interface,omitempty"`
	Priority    int    `json:"priority,omitempty"`
}

// DNSConfig defines DNS resolver configuration
type DNSConfig struct {
	Servers     []string `json:"servers"`
	SearchDomains []string `json:"search_domains,omitempty"`
	LocalZones  []LocalZone `json:"local_zones,omitempty"`
}

// LocalZone defines a local DNS zone
type LocalZone struct {
	Name    string            `json:"name"`
	Type    string            `json:"type"` // static, transparent, redirect
	Records map[string]string `json:"records"`
}

// DHCPConfig defines DHCP server configuration
type DHCPConfig struct {
	Enabled    bool         `json:"enabled"`
	Subnets    []DHCPSubnet `json:"subnets"`
}

// DHCPSubnet defines a DHCP subnet
type DHCPSubnet struct {
	Interface  string   `json:"interface"`
	Network    string   `json:"network"`
	RangeStart string   `json:"range_start"`
	RangeEnd   string   `json:"range_end"`
	Gateway    string   `json:"gateway"`
	DNS        []string `json:"dns"`
	LeaseTime  int      `json:"lease_time"` // seconds
}

// Command represents a command to send to an agent
type Command struct {
	Command  string `json:"command"`
	Timeout  int    `json:"timeout,omitempty"` // seconds
	Async    bool   `json:"async,omitempty"`
}

// CommandResult represents the result of a command
type CommandResult struct {
	Success  bool   `json:"success"`
	Output   string `json:"output"`
	Error    string `json:"error,omitempty"`
	ExitCode int    `json:"exit_code"`
	Duration int64  `json:"duration_ms"`
}

// Event represents a controller event
type Event struct {
	ID        string      `json:"id,omitempty"`
	Type      string      `json:"type"`
	AgentID   string      `json:"agent_id,omitempty"`
	Timestamp time.Time   `json:"timestamp"`
	Severity  string      `json:"severity,omitempty"` // info, warning, error, critical
	Message   string      `json:"message,omitempty"`
	Data      interface{} `json:"data,omitempty"`
}

// Group represents a group of agents
type Group struct {
	Name        string            `json:"name"`
	Description string            `json:"description,omitempty"`
	Tags        map[string]string `json:"tags,omitempty"`      // Agents matching these tags
	AgentIDs    []string          `json:"agent_ids,omitempty"` // Explicit agent list
	Config      *AgentConfig      `json:"config,omitempty"`    // Default config for group
	CreatedAt   time.Time         `json:"created_at"`
	UpdatedAt   time.Time         `json:"updated_at"`
}

// ConfigTemplate represents a reusable configuration template
type ConfigTemplate struct {
	Name        string       `json:"name"`
	Description string       `json:"description,omitempty"`
	Config      AgentConfig  `json:"config"`
	Variables   []Variable   `json:"variables,omitempty"` // Template variables
	CreatedAt   time.Time    `json:"created_at"`
	UpdatedAt   time.Time    `json:"updated_at"`
}

// Variable defines a template variable
type Variable struct {
	Name        string `json:"name"`
	Description string `json:"description,omitempty"`
	Default     string `json:"default,omitempty"`
	Required    bool   `json:"required"`
}

// FleetSummary provides overview statistics
type FleetSummary struct {
	TotalAgents   int     `json:"total_agents"`
	OnlineAgents  int     `json:"online_agents"`
	OfflineAgents int     `json:"offline_agents"`
	DegradedAgents int    `json:"degraded_agents"`
	TotalPFStates int64   `json:"total_pf_states"`
	AvgCPULoad    float64 `json:"avg_cpu_load"`
	AvgMemory     float64 `json:"avg_memory_percent"`
	RecentEvents  int     `json:"recent_events"`
}
