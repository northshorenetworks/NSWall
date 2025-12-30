---
layout: default
title: Documentation - NSWall
---

# Documentation

Guides, examples, and resources for NSWall.

---

## Configuration Examples

### Basic Firewall

Protect internal network with NAT:

```
! Enable packet filter
pf enable

! Configure interfaces
interface em0
  description "WAN"
  ip address dhcp
  no shutdown

interface em1
  description "LAN"
  ip address 192.168.1.1/24
  no shutdown

! Add NAT rule
pf add nat on em0 from 192.168.1.0/24 to any -> (em0)

! Add basic filter rules
pf add filter pass out on em0 keep state
pf add filter pass in on em1 from 192.168.1.0/24
pf add filter block in on em0

pf reload
write memory
```

### BGP Router

Full BGP peering configuration:

```
! Configure interfaces
interface em0
  ip address 192.0.2.1/30
  no shutdown

interface em1
  ip address 10.0.0.1/24
  no shutdown

! Enable BGP
bgp enable
bgp edit

! In editor, add:
AS 65001
router-id 192.0.2.1

neighbor 192.0.2.2 {
    remote-as 65002
    announce all
}

network 10.0.0.0/24

! Save and reload
bgp reload
write memory
```

### High Availability Pair

CARP failover with state sync:

**Primary Router:**
```
! CARP virtual IP
interface carp0
  carp vhid 1 pass secret123
  carp advbase 1
  carp advskew 0
  ip address 192.168.1.1/24
  no shutdown

! State sync
interface pfsync0
  syncdev em2
  syncpeer 10.0.0.2
  no shutdown

! Enable PF with sync
pf enable
write memory
```

**Secondary Router:**
```
interface carp0
  carp vhid 1 pass secret123
  carp advbase 1
  carp advskew 100
  ip address 192.168.1.1/24
  no shutdown

interface pfsync0
  syncdev em2
  syncpeer 10.0.0.1
  no shutdown

pf enable
write memory
```

### IKEv2 VPN Site-to-Site

```
! Configure tunnel endpoint
interface em0
  ip address 198.51.100.1/24
  no shutdown

! Enable IKEv2
iked enable
iked edit

! In editor:
ikev2 "site-to-site" passive esp \
    from 198.51.100.1 to 203.0.113.1 \
    local 198.51.100.1 peer 203.0.113.1 \
    childsa enc aes-256-gcm \
    psk "shared-secret-key"

flow esp in from 10.2.0.0/24 to 10.1.0.0/24 peer 203.0.113.1
flow esp out from 10.1.0.0/24 to 10.2.0.0/24 peer 203.0.113.1

! Reload
iked reload
write memory
```

### DNS Resolver with DNSSEC

```
! Enable unbound DNS resolver
unbound enable
unbound edit

! In editor:
server:
    interface: 192.168.1.1
    access-control: 192.168.1.0/24 allow
    hide-identity: yes
    hide-version: yes
    auto-trust-anchor-file: "/var/unbound/db/root.key"
    val-log-level: 1

forward-zone:
    name: "."
    forward-addr: 1.1.1.1
    forward-addr: 8.8.8.8

! Reload
unbound reload
write memory
```

---

## Architecture

### Directory Structure

```
/
├── conf/               # Persistent configuration
│   ├── nssh.conf      # Main configuration
│   └── pf.conf        # PF rules
├── var/
│   └── run/           # Runtime files
│       ├── nssh_pf.conf
│       ├── bgpd.conf
│       ├── ospfd.conf
│       └── ...
└── usr/
    └── bin/
        └── nssh       # CLI binary
```

### Configuration Flow

1. User enters commands in CLI
2. Changes stored in running configuration
3. `write memory` saves to `/conf/nssh.conf`
4. Daemon configs written to `/var/run/`
5. Daemons reload via control programs

---

## Troubleshooting

### Debug Mode

Run nssh with verbose output:
```bash
nssh -v
```

### Check Service Status

```
nswall# show version
nswall# show users
nswall# show kernel pf
```

### View Logs

```bash
# System messages
tail -f /var/log/messages

# PF log
tcpdump -n -e -ttt -i pflog0

# Daemon-specific logs
tail -f /var/log/daemon
```

### Common Issues

**PF rules not loading:**
```
nswall# pf show info
nswall# pf show rules
```

**BGP peer not establishing:**
```
nswall# show bgp neighbor 192.0.2.2
nswall# show bgp summary
```

**CARP not failing over:**
```
nswall# show kernel carp
nswall# show interface carp0
```

---

## Best Practices

### Security

1. Change default enable password immediately
2. Use key-based SSH authentication
3. Limit management access by IP
4. Keep OpenBSD updated
5. Review PF rules regularly

### Performance

1. Use hardware with AES-NI for VPN
2. Size RAM appropriately for state tables
3. Use pflow for traffic analysis
4. Monitor with SNMP

### High Availability

1. Use dedicated sync interface for pfsync
2. Test failover regularly
3. Keep configurations synchronized
4. Document CARP VHID assignments

---

## Resources

- [OpenBSD PF FAQ](https://www.openbsd.org/faq/pf/)
- [OpenBGPD](https://www.openbgpd.org/)
- [OpenOSPFD](https://man.openbsd.org/ospfd)
- [OpenIKED](https://man.openbsd.org/iked)
- [Unbound](https://man.openbsd.org/unbound)
