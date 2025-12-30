---
layout: default
title: CLI Reference - NSWall
---

# CLI Reference

Complete command reference for NSWall's Junos/IOS-style CLI.

---

## Command Modes

| Mode | Prompt | Description |
|------|--------|-------------|
| User | `nswall>` | Basic monitoring commands |
| Enable | `nswall#` | Privileged commands |
| Configure | `nswall(config)#` | Global configuration |
| Interface | `nswall(config-if)#` | Interface configuration |

---

## Mode Navigation

```
nswall> enable                  # Enter privileged mode
nswall# configure terminal      # Enter configuration mode
nswall(config)# interface em0   # Enter interface config
nswall(config-if)# exit         # Return to config mode
nswall(config)# end             # Return to enable mode
nswall# disable                 # Return to user mode
nswall> quit                    # Exit CLI
```

---

## Global Commands

### Show Commands

```
show hostname           # Display system hostname
show version            # Display software version
show users              # Display logged-in users
show running-config     # Display active configuration
show startup-config     # Display saved configuration
show interface [name]   # Display interface status
show route [prefix]     # Display routing table
show arp                # Display ARP table
show monitor            # Monitor routing changes
```

### Kernel Statistics

```
show kernel ip          # IP statistics
show kernel tcp         # TCP statistics
show kernel udp         # UDP statistics
show kernel icmp        # ICMP statistics
show kernel igmp        # IGMP statistics
show kernel route       # Routing statistics
show kernel carp        # CARP statistics
show kernel pf          # Packet filter statistics
show kernel mbuf        # Memory buffer statistics
show kernel ah          # AH (IPSec) statistics
show kernel esp         # ESP (IPSec) statistics
```

---

## Network Commands

### Diagnostics

```
ping <host>                     # ICMP ping
ping6 <host>                    # IPv6 ping
traceroute <host>               # Trace route
ssh <user>@<host>               # SSH connection
telnet <host> [port]            # Telnet connection
tcpdump [options]               # Packet capture (enable mode)
```

### Interface Configuration

```
interface <name>                # Enter interface config
  ip address <addr>/<prefix>    # Set IP address
  ip address dhcp               # Enable DHCP client
  no ip address <addr>          # Remove IP address
  mtu <size>                    # Set MTU
  metric <value>                # Set routing metric
  description <text>            # Set description
  shutdown                      # Disable interface
  no shutdown                   # Enable interface
  group <name>                  # Add to interface group
  lladdr <mac>                  # Set MAC address
```

### VLAN Configuration

```
interface vlan<id>
  vlan <id>                     # Set VLAN ID
  vlandev <interface>           # Set parent interface
```

### Trunk/LAG Configuration

```
interface trunk<id>
  trunkport <interface>         # Add port to trunk
  trunkproto <protocol>         # Set trunk protocol
                                # (failover, loadbalance, etc.)
```

---

## Routing

### Static Routes

```
route add <dest>/<prefix> <gateway>     # Add route
route delete <dest>/<prefix>            # Delete route
route flush                             # Flush routes
```

### BGP

```
bgp enable                      # Start BGP daemon
bgp disable                     # Stop BGP daemon
bgp edit                        # Edit configuration
bgp reload                      # Reload configuration
bgp neighbor <peer> up          # Bring up neighbor
bgp neighbor <peer> down        # Bring down neighbor
bgp network add <prefix>        # Announce network
bgp network delete <prefix>     # Withdraw network

show bgp summary                # BGP summary
show bgp neighbor <peer>        # Neighbor details
show bgp rib                    # Routing information base
show bgp announced              # Announced networks
```

### OSPF

```
ospf enable                     # Start OSPF daemon
ospf disable                    # Stop OSPF daemon
ospf edit                       # Edit configuration
ospf reload                     # Reload configuration
ospf fib couple                 # Couple FIB
ospf fib decouple               # Decouple FIB

show ospf summary               # OSPF summary
show ospf neighbor              # OSPF neighbors
show ospf database              # Link state database
show ospf interfaces            # OSPF interfaces
show ospf rib                   # OSPF RIB
show ospf fib                   # OSPF FIB
```

### RIP

```
rip enable                      # Start RIP daemon
rip disable                     # Stop RIP daemon
rip edit                        # Edit configuration
rip reload                      # Reload configuration

show rip neighbor               # RIP neighbors
show rip rib                    # RIP RIB
```

---

## Packet Filter (PF)

```
pf enable                       # Enable packet filter
pf disable                      # Disable packet filter
pf reload                       # Reload rules

pf add filter <rule>            # Add filter rule
pf add nat <rule>               # Add NAT rule
pf add binat <rule>             # Add BINAT rule

pf show rules                   # Show filter rules
pf show nat                     # Show NAT rules
pf show states                  # Show state table
pf show info                    # Show PF info
pf show all                     # Show all PF info

flush pf all                    # Flush all PF state
flush pf states                 # Flush state table
flush pf nat                    # Flush NAT rules
flush pf filter                 # Flush filter rules
```

---

## VPN

### IPSec (isakmpd)

```
ipsec enable                    # Start IKEv1 daemon
ipsec disable                   # Stop IKEv1 daemon
ipsec edit                      # Edit configuration
ipsec reload                    # Reload flows
```

### IKEv2 (iked)

```
iked enable                     # Start IKEv2 daemon
iked disable                    # Stop IKEv2 daemon
iked edit                       # Edit configuration
iked reload                     # Reload configuration
iked reset                      # Reset all SAs

show sadb                       # Show SA database
```

---

## High Availability

### CARP

```
interface carp<n>
  carp vhid <id>                # Set virtual host ID
  carp pass <password>          # Set password
  carp advbase <seconds>        # Advertisement base
  carp advskew <value>          # Advertisement skew
  carp carpdev <interface>      # Set CARP device
  carp state master             # Force master
  carp state backup             # Force backup
```

### pfsync

```
interface pfsync0
  syncdev <interface>           # Sync interface
  syncpeer <address>            # Sync peer
  maxupd <count>                # Max updates
```

---

## Network Services

### DNS Resolver (unbound)

```
unbound enable                  # Start DNS resolver
unbound disable                 # Stop DNS resolver
unbound edit                    # Edit configuration
unbound reload                  # Reload configuration
unbound flush                   # Flush DNS cache
unbound stats                   # Show statistics
```

### DHCP Server

```
dhcp enable                     # Start DHCP server
dhcp disable                    # Stop DHCP server
dhcp edit                       # Edit configuration

show dhcp leases                # Show DHCP leases
```

### HTTP Server

```
httpd enable                    # Start HTTP server
httpd disable                   # Stop HTTP server
httpd edit                      # Edit configuration
```

### Mail (smtpd)

```
smtpd enable                    # Start mail service
smtpd disable                   # Stop mail service
smtpd edit                      # Edit configuration
smtpd pause                     # Pause mail processing
smtpd resume                    # Resume mail processing
```

### NTP

```
ntp enable                      # Start NTP daemon
ntp disable                     # Stop NTP daemon
ntp edit                        # Edit configuration
```

### SNMP

```
snmp enable                     # Start SNMP daemon
snmp disable                    # Stop SNMP daemon
snmp edit                       # Edit configuration
snmp trap send <oid>            # Send SNMP trap
```

### SSH Server

```
sshd enable                     # Start SSH server
sshd disable                    # Stop SSH server
sshd edit                       # Edit configuration
```

### IPv6 Router Advertisement

```
rad enable                      # Start RA daemon
rad disable                     # Stop RA daemon
rad edit                        # Edit configuration
```

---

## Load Balancing (relayd)

```
relay enable                    # Start relay daemon
relay disable                   # Stop relay daemon
relay edit                      # Edit configuration
relay reload                    # Reload configuration
relay host enable <name>        # Enable host
relay host disable <name>       # Disable host
relay table enable <name>       # Enable table
relay table disable <name>      # Disable table
relay monitor                   # Monitor mode

show relay hosts                # Show host status
show relay redirects            # Show redirects
show relay status               # Show relay status
show relay sessions             # Show active sessions
show relay summary              # Show summary
```

---

## System

### Configuration Management

```
write memory                    # Save configuration
write terminal                  # Display configuration
reload                          # Reboot system
halt                            # Shutdown system
```

### Administration

```
enable                          # Enter privileged mode
enable password                 # Set enable password
hostname <name>                 # Set hostname
```

### ARP

```
arp set <ip> <mac>              # Set static ARP entry
arp delete <ip>                 # Delete ARP entry
flush arp                       # Flush ARP cache
show arp                        # Show ARP table
```

### Flush Commands

```
flush arp                       # Flush ARP cache
flush ip routes                 # Flush IP routes
flush history                   # Flush command history
flush pf all                    # Flush PF state
flush line <tty>                # Flush terminal line
```

---

## Help & Completion

```
?                               # Context-sensitive help
<partial>?                      # Show matching commands
<Tab>                           # Command completion
show ?                          # Show available show commands
```
