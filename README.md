# NSWall 2.0

**Network Appliance Framework for OpenBSD**

NSWall transforms OpenBSD into a Junos/IOS-like network device with a familiar CLI interface. It provides a unified configuration shell for managing interfaces, routing, firewall rules, and network services.

## Features

- **Familiar CLI**: Cisco IOS / Juniper Junos style command interface
- **Unified Configuration**: Single interface for interfaces, routing, PF firewall
- **OpenBSD Native**: Leverages bgpd, ospfd, pf, CARP, and other OpenBSD daemons
- **High Availability**: Built-in support for CARP failover and pfsync state sync
- **Security First**: Built on OpenBSD's security-focused foundation

## Requirements

- OpenBSD 7.5 or later (7.8 recommended)
- amd64 or arm64 architecture
- Base system only (no additional packages required for core functionality)

## Quick Install

### From Source (Recommended for Development)

```bash
# Clone the repository
git clone https://github.com/northshorenetworks/NSWall.git
cd NSWall

# Build
./build.sh build

# Install (as root)
doas ./build.sh install
```

### Create Package for Distribution

```bash
./build.sh package
# Creates: nswall-2.0.0-amd64.tgz
```

## Usage

### Run NSWall Shell

```bash
# Run directly
nswall

# Or set as login shell for a user
chsh -s /usr/local/bin/nswall admin
```

### Basic Commands

```
nswall> enable                    # Enter privileged mode
Password:

nswall# show interface            # Show interfaces
nswall# show route                # Show routing table
nswall# show running-config       # Show current configuration

nswall# configure terminal        # Enter configuration mode
nswall(config)# hostname myrouter
nswall(config)# interface em0
nswall(config-if)# ip 10.0.0.1/24
nswall(config-if)# description WAN Interface
nswall(config-if)# exit
nswall(config)# write-config      # Save configuration
```

### Interface Configuration

```
nswall(config)# interface em0
nswall(config-if)# ip 192.168.1.1/24
nswall(config-if)# ip 192.168.1.2/24 alias    # Secondary IP
nswall(config-if)# mtu 9000
nswall(config-if)# description "Uplink to ISP"
nswall(config-if)# no shutdown
```

### Firewall (PF) Configuration

```
nswall# pf enable
nswall# pf edit                   # Edit pf.conf directly
nswall# pf reload                 # Reload rules
nswall# show pf rules             # Show active rules
nswall# show pf states            # Show state table
```

### Routing

```
nswall(config)# route add default 192.168.1.254
nswall(config)# route add 10.0.0.0/8 172.16.0.1

# BGP (using OpenBSD bgpd)
nswall# bgp enable
nswall# bgp edit                  # Edit bgpd.conf

# OSPF (using OpenBSD ospfd)
nswall# ospf enable
nswall# ospf edit                 # Edit ospfd.conf
```

### High Availability (CARP)

```
nswall(config)# interface carp0
nswall(config-if)# vhid 1
nswall(config-if)# advskew 100
nswall(config-if)# cpass "secretpassword"
nswall(config-if)# carpdev em0
nswall(config-if)# ip 10.0.0.254/24
```

## File Locations

| Path | Description |
|------|-------------|
| `/usr/local/bin/nswall` | Main CLI binary |
| `/etc/nswall/nswall.conf` | Main configuration file |
| `/var/run/nswall/` | Runtime state files |
| `/usr/local/share/nswall/` | Support scripts and modules |
| `/usr/local/share/examples/nswall/` | Example configurations |

## Development

### Directory Structure

```
NSWall/
├── nssh/              # Core shell source code (C)
│   ├── commands.c     # Command handlers
│   ├── if.c          # Interface configuration
│   ├── conf.c        # Configuration management
│   ├── pf/           # PF firewall integration
│   └── ...
├── pkg/              # OpenBSD package files
│   ├── Makefile      # Port Makefile
│   ├── pkg-descr     # Package description
│   └── files/        # Support files
├── build.sh          # Local build script
└── README.md         # This file
```

### Building for Development

```bash
# Build only (no install)
./build.sh build

# Clean build artifacts
./build.sh clean

# Build and install
doas ./build.sh install
```

### Creating an OpenBSD Port

The `pkg/` directory contains a standard OpenBSD port structure:

```bash
# Copy to ports tree
cp -r pkg/ /usr/ports/net/nswall/

# Build port
cd /usr/ports/net/nswall
make build
make install
```

## Roadmap

### Phase 1: Core Shell (Current)
- [x] CLI shell with IOS-like syntax
- [x] Interface configuration
- [x] PF firewall integration
- [x] Routing configuration
- [x] OpenBSD package structure

### Phase 2: API & Automation
- [ ] REST API for configuration
- [ ] Candidate config / commit model
- [ ] Configuration rollback
- [ ] Ansible module

### Phase 3: Enterprise Features
- [ ] RPKI integration with rpki-client
- [ ] WireGuard VPN module
- [ ] Prometheus metrics exporter
- [ ] Web UI (optional)

## License

ISC License - See [LICENSE](LICENSE)

## Contributing

Contributions welcome! Please read the contributing guidelines before submitting PRs.

## History

NSWall was originally developed circa 2010-2012 as an OpenBSD-based network appliance platform. Version 2.0 modernizes the project for current OpenBSD releases with a focus on API-first design and package-based deployment.
