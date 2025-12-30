---
layout: default
title: NSWall - OpenBSD Network Appliance Framework
---

# NSWall

**Transform OpenBSD into a professional network appliance with a familiar CLI**

NSWall is an open-source network appliance framework built on OpenBSD that provides a Junos/IOS-like command-line interface for managing firewalls, routers, and network services.

---

## Why NSWall?

| Feature | Description |
|---------|-------------|
| **Familiar CLI** | Junos/IOS-style commands that network engineers already know |
| **OpenBSD Security** | Built on the most secure operating system available |
| **Lightweight** | Runs on embedded hardware with minimal resources |
| **Open Source** | ISC licensed, free to use and modify |

---

## Quick Start

```bash
# Clone the repository
git clone https://github.com/northshorenetworks/NSWall.git
cd NSWall

# Build (requires OpenBSD)
make

# Run the CLI
./nssh/nssh
```

---

## Sample CLI Session

```
nswall> enable
nswall# configure terminal
nswall(config)# interface em0
nswall(config-if)# ip address 192.168.1.1/24
nswall(config-if)# no shutdown
nswall(config-if)# exit
nswall(config)# pf enable
nswall(config)# pf add filter pass in on em0 proto tcp to port 22
nswall(config)# write memory
```

---

## Core Features

### Packet Filter (PF)
- Dynamic rule management
- NAT/PAT configuration
- State tracking and monitoring
- Rule syntax validation

### Routing Protocols
- **BGP** - Full BGP4 support via OpenBGPD
- **OSPF** - Link-state routing via OpenOSPFD
- **RIP** - Distance-vector routing
- **Static Routes** - Manual route configuration

### High Availability
- **CARP** - Common Address Redundancy Protocol
- **pfsync** - Firewall state synchronization
- **Failover** - Automatic failover support

### VPN
- **IPSec** - Site-to-site VPN
- **IKEv2** - Modern VPN with iked
- **OpenVPN** - SSL VPN support

### Network Services
- **DHCP** - Server and relay
- **DNS** - Resolver with DNSSEC (unbound)
- **NTP** - Time synchronization
- **SNMP** - Network monitoring
- **HTTP** - Management web interface

---

## Supported Hardware

NSWall supports various embedded and server platforms:

| Platform | Description |
|----------|-------------|
| PC Engines | APU series boards |
| Soekris | Net4501, Net4801, Net5501 |
| WRAP | PC Engines WRAP boards |
| Commell | LE-564 embedded PC |
| VMware | Virtual machines |
| Generic | Standard x86/amd64 hardware |

---

## Documentation

- [Installation Guide](installation/)
- [Feature Overview](features/)
- [CLI Reference](cli-reference/)
- [Configuration Examples](documentation/)

---

## Contributing

NSWall is open source and welcomes contributions!

1. Fork the repository
2. Create a feature branch
3. Submit a pull request

See our [GitHub repository](https://github.com/northshorenetworks/NSWall) for more details.

---

## License

NSWall is released under the ISC License, a permissive open-source license similar to BSD.

---

## Links

- [GitHub Repository](https://github.com/northshorenetworks/NSWall)
- [Issue Tracker](https://github.com/northshorenetworks/NSWall/issues)
- [OpenBSD](https://www.openbsd.org/)
