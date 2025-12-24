// Package models defines data structures for the NSWall API
package models

import "time"

// Response is a standard API response wrapper
type Response struct {
	Success bool        `json:"success"`
	Data    interface{} `json:"data,omitempty"`
	Error   string      `json:"error,omitempty"`
	Meta    *Meta       `json:"meta,omitempty"`
}

// Meta contains response metadata
type Meta struct {
	Total    int    `json:"total,omitempty"`
	Page     int    `json:"page,omitempty"`
	PageSize int    `json:"page_size,omitempty"`
	Version  string `json:"version,omitempty"`
}

// SystemInfo contains system status information
type SystemInfo struct {
	Hostname    string    `json:"hostname"`
	OS          string    `json:"os"`
	Version     string    `json:"version"`
	Arch        string    `json:"arch"`
	Uptime      int64     `json:"uptime_seconds"`
	UptimeHuman string    `json:"uptime_human"`
	BootTime    time.Time `json:"boot_time"`
	Time        time.Time `json:"time"`
	Timezone    string    `json:"timezone"`
	LoadAvg     []float64 `json:"load_average"`
	CPUCount    int       `json:"cpu_count"`
}

// MemoryInfo contains memory usage information
type MemoryInfo struct {
	Total     uint64  `json:"total_bytes"`
	Used      uint64  `json:"used_bytes"`
	Free      uint64  `json:"free_bytes"`
	Cached    uint64  `json:"cached_bytes"`
	UsedPct   float64 `json:"used_percent"`
	SwapTotal uint64  `json:"swap_total_bytes"`
	SwapUsed  uint64  `json:"swap_used_bytes"`
}

// DiskInfo contains disk usage information
type DiskInfo struct {
	Filesystem string  `json:"filesystem"`
	Mount      string  `json:"mount"`
	Total      uint64  `json:"total_bytes"`
	Used       uint64  `json:"used_bytes"`
	Available  uint64  `json:"available_bytes"`
	UsedPct    float64 `json:"used_percent"`
}

// Interface represents a network interface
type Interface struct {
	Name        string   `json:"name"`
	Description string   `json:"description,omitempty"`
	Status      string   `json:"status"`
	AdminStatus string   `json:"admin_status"`
	MAC         string   `json:"mac_address,omitempty"`
	MTU         int      `json:"mtu"`
	Speed       string   `json:"speed,omitempty"`
	Duplex      string   `json:"duplex,omitempty"`
	IPv4        []IPAddr `json:"ipv4_addresses,omitempty"`
	IPv6        []IPAddr `json:"ipv6_addresses,omitempty"`
	Flags       []string `json:"flags,omitempty"`
	Group       string   `json:"group,omitempty"`
	Parent      string   `json:"parent,omitempty"`
	VlanID      int      `json:"vlan_id,omitempty"`
	Statistics  *InterfaceStats `json:"statistics,omitempty"`
}

// IPAddr represents an IP address with prefix
type IPAddr struct {
	Address string `json:"address"`
	Prefix  int    `json:"prefix"`
	CIDR    string `json:"cidr"`
}

// InterfaceStats contains interface statistics
type InterfaceStats struct {
	RxBytes   uint64 `json:"rx_bytes"`
	TxBytes   uint64 `json:"tx_bytes"`
	RxPackets uint64 `json:"rx_packets"`
	TxPackets uint64 `json:"tx_packets"`
	RxErrors  uint64 `json:"rx_errors"`
	TxErrors  uint64 `json:"tx_errors"`
	RxDropped uint64 `json:"rx_dropped"`
	TxDropped uint64 `json:"tx_dropped"`
	Collisions uint64 `json:"collisions"`
}

// InterfaceConfig is used to configure an interface
type InterfaceConfig struct {
	Description string   `json:"description,omitempty"`
	IPv4        []string `json:"ipv4_addresses,omitempty"`
	IPv6        []string `json:"ipv6_addresses,omitempty"`
	MTU         int      `json:"mtu,omitempty"`
	AdminUp     *bool    `json:"admin_up,omitempty"`
	VlanID      int      `json:"vlan_id,omitempty"`
	Parent      string   `json:"parent,omitempty"`
}

// Route represents a routing table entry
type Route struct {
	Destination string `json:"destination"`
	Gateway     string `json:"gateway,omitempty"`
	Interface   string `json:"interface,omitempty"`
	Flags       string `json:"flags"`
	Priority    int    `json:"priority,omitempty"`
	Label       string `json:"label,omitempty"`
	Source      string `json:"source,omitempty"`
	Expire      int    `json:"expire,omitempty"`
}

// RouteConfig is used to add/modify routes
type RouteConfig struct {
	Destination string `json:"destination"`
	Gateway     string `json:"gateway,omitempty"`
	Interface   string `json:"interface,omitempty"`
	Priority    int    `json:"priority,omitempty"`
	Label       string `json:"label,omitempty"`
}

// ARPEntry represents an ARP table entry
type ARPEntry struct {
	IPAddress  string `json:"ip_address"`
	MACAddress string `json:"mac_address"`
	Interface  string `json:"interface"`
	Flags      string `json:"flags"`
	Expire     string `json:"expire,omitempty"`
}

// PFInfo contains PF status information
type PFInfo struct {
	Enabled     bool   `json:"enabled"`
	Running     bool   `json:"running"`
	Debug       string `json:"debug"`
	Hostid      string `json:"hostid,omitempty"`
	Checksum    string `json:"checksum,omitempty"`
	Since       string `json:"since,omitempty"`
	StateCount  int    `json:"state_count"`
	StateLimit  int    `json:"state_limit"`
	SrcNodes    int    `json:"src_nodes"`
	FragCount   int    `json:"frag_count"`
}

// PFStats contains PF statistics
type PFStats struct {
	Match           uint64 `json:"match"`
	BadOffset       uint64 `json:"bad_offset"`
	Fragment        uint64 `json:"fragment"`
	Short           uint64 `json:"short"`
	Normalize       uint64 `json:"normalize"`
	Memory          uint64 `json:"memory"`
	BadTimestamp    uint64 `json:"bad_timestamp"`
	CongestionDrop  uint64 `json:"congestion"`
	IPOption        uint64 `json:"ip_option"`
	ProtoCksum      uint64 `json:"proto_cksum"`
	StateMismatch   uint64 `json:"state_mismatch"`
	StateInsert     uint64 `json:"state_insert"`
	StateLimit      uint64 `json:"state_limit"`
	SrcLimit        uint64 `json:"src_limit"`
	SynProxy        uint64 `json:"synproxy"`
	Translate       uint64 `json:"translate"`
	NoRoute         uint64 `json:"no_route"`
}

// PFRule represents a PF firewall rule
type PFRule struct {
	Number      int    `json:"number"`
	Action      string `json:"action"`
	Direction   string `json:"direction"`
	Log         bool   `json:"log"`
	Quick       bool   `json:"quick"`
	Interface   string `json:"interface,omitempty"`
	AddressFamily string `json:"af,omitempty"`
	Protocol    string `json:"protocol,omitempty"`
	Source      string `json:"source,omitempty"`
	Destination string `json:"destination,omitempty"`
	Port        string `json:"port,omitempty"`
	State       string `json:"state,omitempty"`
	Label       string `json:"label,omitempty"`
	Evaluations uint64 `json:"evaluations"`
	Packets     uint64 `json:"packets"`
	Bytes       uint64 `json:"bytes"`
	Raw         string `json:"raw"`
}

// PFState represents a PF state entry
type PFState struct {
	ID          string `json:"id"`
	Direction   string `json:"direction"`
	Protocol    string `json:"protocol"`
	Source      string `json:"source"`
	Destination string `json:"destination"`
	Gateway     string `json:"gateway,omitempty"`
	State       string `json:"state"`
	Age         string `json:"age"`
	Expire      string `json:"expire"`
	Packets     []uint64 `json:"packets"`
	Bytes       []uint64 `json:"bytes"`
	Rule        int    `json:"rule"`
	Anchor      string `json:"anchor,omitempty"`
	Interface   string `json:"interface,omitempty"`
}

// PFTable represents a PF table
type PFTable struct {
	Name      string    `json:"name"`
	Addresses []string  `json:"addresses"`
	Count     int       `json:"count"`
	Flags     string    `json:"flags,omitempty"`
}

// PFTableEntry represents an entry in a PF table
type PFTableEntry struct {
	Address   string    `json:"address"`
	Cleared   time.Time `json:"cleared,omitempty"`
	Packets   []uint64  `json:"packets,omitempty"`
	Bytes     []uint64  `json:"bytes,omitempty"`
}

// Service represents a system service/daemon
type Service struct {
	Name        string `json:"name"`
	Description string `json:"description,omitempty"`
	Status      string `json:"status"`
	Enabled     bool   `json:"enabled"`
	PID         int    `json:"pid,omitempty"`
	Uptime      string `json:"uptime,omitempty"`
}

// ServiceAction is used to control services
type ServiceAction struct {
	Action string `json:"action"` // start, stop, restart, reload, enable, disable
}

// DHCPLease represents a DHCP lease
type DHCPLease struct {
	IPAddress   string    `json:"ip_address"`
	MACAddress  string    `json:"mac_address"`
	Hostname    string    `json:"hostname,omitempty"`
	Interface   string    `json:"interface"`
	Start       time.Time `json:"start"`
	End         time.Time `json:"end"`
	State       string    `json:"state"`
}

// DHCPRange represents a DHCP pool range
type DHCPRange struct {
	Start string `json:"start"`
	End   string `json:"end"`
}

// DHCPSubnet represents DHCP subnet configuration
type DHCPSubnet struct {
	Network     string      `json:"network"`
	Netmask     string      `json:"netmask"`
	Range       *DHCPRange  `json:"range,omitempty"`
	Router      string      `json:"router,omitempty"`
	DNS         []string    `json:"dns,omitempty"`
	Domain      string      `json:"domain,omitempty"`
	LeaseTime   int         `json:"lease_time,omitempty"`
}

// WireGuardInterface represents a WireGuard interface
type WireGuardInterface struct {
	Name       string           `json:"name"`
	PublicKey  string           `json:"public_key"`
	ListenPort int              `json:"listen_port,omitempty"`
	FwMark     int              `json:"fwmark,omitempty"`
	Peers      []WireGuardPeer  `json:"peers"`
}

// WireGuardPeer represents a WireGuard peer
type WireGuardPeer struct {
	PublicKey           string   `json:"public_key"`
	Endpoint            string   `json:"endpoint,omitempty"`
	AllowedIPs          []string `json:"allowed_ips"`
	LatestHandshake     string   `json:"latest_handshake,omitempty"`
	TransferRx          uint64   `json:"transfer_rx"`
	TransferTx          uint64   `json:"transfer_tx"`
	PersistentKeepalive int      `json:"persistent_keepalive,omitempty"`
}

// WireGuardConfig is used to configure WireGuard
type WireGuardConfig struct {
	ListenPort int    `json:"listen_port,omitempty"`
	PrivateKey string `json:"private_key,omitempty"`
}

// WireGuardPeerConfig is used to add/modify WireGuard peers
type WireGuardPeerConfig struct {
	PublicKey           string   `json:"public_key"`
	Endpoint            string   `json:"endpoint,omitempty"`
	AllowedIPs          []string `json:"allowed_ips"`
	PresharedKey        string   `json:"preshared_key,omitempty"`
	PersistentKeepalive int      `json:"persistent_keepalive,omitempty"`
}

// IKESa represents an IKE Security Association
type IKESa struct {
	Name        string `json:"name"`
	ID          int    `json:"id"`
	Status      string `json:"status"`
	LocalAddr   string `json:"local_addr"`
	RemoteAddr  string `json:"remote_addr"`
	LocalID     string `json:"local_id,omitempty"`
	RemoteID    string `json:"remote_id,omitempty"`
	Initiator   bool   `json:"initiator"`
	Established string `json:"established,omitempty"`
	Rekey       string `json:"rekey,omitempty"`
}

// IPSecSa represents an IPsec Security Association
type IPSecSa struct {
	SPI        string `json:"spi"`
	Protocol   string `json:"protocol"`
	Source     string `json:"source"`
	Dest       string `json:"destination"`
	Transform  string `json:"transform"`
	Direction  string `json:"direction"`
	State      string `json:"state"`
}

// CARPInterface represents a CARP interface
type CARPInterface struct {
	Interface  string `json:"interface"`
	VHID       int    `json:"vhid"`
	State      string `json:"state"`
	AdvBase    int    `json:"advbase"`
	AdvSkew    int    `json:"advskew"`
	VirtualIP  string `json:"virtual_ip"`
	Master     string `json:"master,omitempty"`
}

// CARPConfig is used to configure CARP
type CARPConfig struct {
	VHID     int    `json:"vhid"`
	AdvBase  int    `json:"advbase,omitempty"`
	AdvSkew  int    `json:"advskew,omitempty"`
	Pass     string `json:"pass,omitempty"`
	VirtualIP string `json:"virtual_ip"`
}

// PFSyncInfo represents pfsync status
type PFSyncInfo struct {
	Interface string `json:"interface"`
	SyncPeer  string `json:"sync_peer,omitempty"`
	SyncIf    string `json:"sync_if,omitempty"`
	MaxUpd    int    `json:"maxupd,omitempty"`
	Defer     bool   `json:"defer"`
	Stats     *PFSyncStats `json:"stats,omitempty"`
}

// PFSyncStats contains pfsync statistics
type PFSyncStats struct {
	SentInserts    uint64 `json:"sent_inserts"`
	SentUpdates    uint64 `json:"sent_updates"`
	SentDeletes    uint64 `json:"sent_deletes"`
	ReceivedInserts uint64 `json:"received_inserts"`
	ReceivedUpdates uint64 `json:"received_updates"`
	ReceivedDeletes uint64 `json:"received_deletes"`
	Errors         uint64 `json:"errors"`
}

// Config represents NSWall configuration
type Config struct {
	Raw     string            `json:"raw,omitempty"`
	Parsed  map[string]interface{} `json:"parsed,omitempty"`
}

// ConfigDiff represents a configuration difference
type ConfigDiff struct {
	Running string `json:"running"`
	Startup string `json:"startup"`
	Diff    string `json:"diff"`
	Same    bool   `json:"same"`
}

// CommandRequest is a request to execute an nsh command
type CommandRequest struct {
	Command string `json:"command"`
	Timeout int    `json:"timeout,omitempty"`
}

// CommandResponse is the response from an nsh command
type CommandResponse struct {
	Command  string `json:"command"`
	Output   string `json:"output"`
	ExitCode int    `json:"exit_code"`
	Duration string `json:"duration,omitempty"`
}

// User represents an API user
type User struct {
	Username string   `json:"username"`
	Role     string   `json:"role"`
	Enabled  bool     `json:"enabled"`
	APIKeys  []string `json:"api_keys,omitempty"`
}

// Session represents an authenticated session
type Session struct {
	Token     string    `json:"token"`
	Username  string    `json:"username"`
	Role      string    `json:"role"`
	CreatedAt time.Time `json:"created_at"`
	ExpiresAt time.Time `json:"expires_at"`
}

// LoginRequest is used for authentication
type LoginRequest struct {
	Username string `json:"username"`
	Password string `json:"password"`
}

// LogEntry represents a log entry
type LogEntry struct {
	Timestamp time.Time `json:"timestamp"`
	Facility  string    `json:"facility"`
	Level     string    `json:"level"`
	Message   string    `json:"message"`
	Host      string    `json:"host,omitempty"`
	Program   string    `json:"program,omitempty"`
}
