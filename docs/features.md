---
layout: default
title: Features - NSWall
---

# NSWall Features

NSWall integrates numerous OpenBSD base system services into a unified, easy-to-use network appliance framework.

---

## Firewall & Security

### Packet Filter (PF)
OpenBSD's renowned stateful packet filter with:
- Stateful inspection
- NAT/PAT/BINAT
- Traffic shaping (ALTQ)
- Address tables
- Anchors for modular rules
- Real-time state monitoring

```
nswall(config)# pf enable
nswall(config)# pf add filter pass in on em0 proto tcp to port { 22 80 443 }
nswall(config)# pf add nat on egress from 10.0.0.0/8 to any -> (egress)
nswall(config)# pf reload
```

### IPSec VPN
Site-to-site and remote access VPN:
- IKEv1 via isakmpd
- IKEv2 via iked (recommended)
- ESP/AH protocols
- Perfect Forward Secrecy

```
nswall(config)# iked enable
nswall(config)# iked edit
```

---

## Routing

### BGP (Border Gateway Protocol)
Full BGP4 implementation via OpenBGPD:
- eBGP and iBGP
- Route reflector support
- Communities and extended communities
- Route filtering with IRR

```
nswall# show bgp summary
nswall# show bgp neighbor 192.0.2.1
nswall(config)# bgp enable
```

### OSPF (Open Shortest Path First)
Link-state routing via OpenOSPFD:
- OSPFv2 for IPv4
- Area configuration
- Stub and NSSA areas
- Virtual links

```
nswall# show ospf neighbor
nswall# show ospf database
nswall(config)# ospf enable
```

### RIP (Routing Information Protocol)
Distance-vector routing for simple networks:
- RIPv2 support
- Split horizon
- Route poisoning

---

## High Availability

### CARP (Common Address Redundancy Protocol)
Virtual IP failover:
- Master/backup election
- Preemption control
- Multiple VHID support
- Interface tracking

```
nswall(config)# interface carp0
nswall(config-if)# carp vhid 1 pass secret
nswall(config-if)# ip address 192.168.1.1/24
```

### pfsync
Firewall state synchronization:
- Real-time state replication
- Bulk state transfer
- Secure peer communication

```
nswall(config)# interface pfsync0
nswall(config-if)# syncdev em1
nswall(config-if)# syncpeer 10.0.0.2
```

---

## Network Services

### DNS Resolver (unbound)
DNSSEC-validating recursive resolver:
- DNS-over-TLS support
- Local zone management
- DNS blackholing
- Cache management

```
nswall(config)# unbound enable
nswall(config)# unbound flush
nswall(config)# unbound stats
```

### DHCP Server
Dynamic host configuration:
- Address pools
- Fixed leases
- Option configuration
- Relay support

```
nswall(config)# dhcp enable
nswall# show dhcp leases
```

### HTTP Server (httpd)
Native web server for management UI:
- TLS support
- FastCGI backend
- Virtual hosts
- Access control

```
nswall(config)# httpd enable
nswall(config)# httpd edit
```

### Mail (smtpd)
OpenSMTPD for system alerts:
- Local delivery
- External relay
- TLS encryption

```
nswall(config)# smtpd enable
nswall(config)# smtpd pause
nswall(config)# smtpd resume
```

### NTP
Time synchronization:
- Multiple server support
- Drift correction
- Constraint validation

```
nswall(config)# ntp enable
```

### SNMP
Network monitoring:
- SNMPv2c/v3 support
- Trap generation
- Custom OIDs

```
nswall(config)# snmp enable
nswall(config)# snmp trap send test
```

---

## IPv6 Support

### Router Advertisement (rad)
IPv6 SLAAC support:
- Prefix advertisement
- DNS server (RDNSS)
- Default router lifetime

```
nswall(config)# rad enable
nswall(config)# rad edit
```

---

## Load Balancing

### relayd
Application-level load balancing:
- Layer 7 inspection
- SSL termination
- Health checks
- Session persistence

```
nswall(config)# relay enable
nswall# show relay status
```

---

## Interface Support

### Physical Interfaces
- Ethernet (em, re, vr, etc.)
- Wireless (802.11)
- Serial

### Virtual Interfaces
- VLANs
- Bridges
- Tunnels (GIF, GRE)
- Trunks (LACP/failover)
- CARP
- pfsync

### Interface Configuration
```
nswall(config)# interface em0
nswall(config-if)# ip address 192.168.1.1/24
nswall(config-if)# mtu 1500
nswall(config-if)# description "LAN Interface"
nswall(config-if)# no shutdown
```

---

## Management

### CLI Features
- Tab completion
- Command history
- Context-sensitive help
- Configuration modes

### Configuration Management
- Running vs startup config
- Configuration backup
- Rollback support

```
nswall# show running-config
nswall# show startup-config
nswall# write memory
```

### Monitoring
```
nswall# show interface em0
nswall# show route
nswall# show arp
nswall# show kernel ip
nswall# show kernel tcp
```

---

## Security Features

- Privilege separation
- Secure defaults
- No unnecessary services
- Regular security updates via OpenBSD
