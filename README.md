# NSWall 2.0

## Enterprise Network Appliance Framework for OpenBSD

---

**Based on [NSH](https://github.com/yellowman/nsh) by Chris Cappuccio <chris@nmedia.net>**

NSWall transforms OpenBSD into an enterprise-ready network appliance with a Junos/IOS-like CLI, REST API, Web UI, and full automation support.

---

## What's New in 2.0

- **REST API**: Full-featured API with 70+ endpoints for automation
- **Web Dashboard**: Vue.js-based management interface
- **Ansible Collection**: Infrastructure as Code support
- **Terraform Provider**: Cloud-style provisioning
- **Prometheus Metrics**: Native observability integration
- **SNMP Agent**: Traditional NMS monitoring
- **OpenAPI Spec**: Complete API documentation

---

## Features

### CLI
- **Familiar Interface**: Cisco IOS / Juniper Junos style commands
- **30+ Daemons**: bgpd, ospfd, eigrpd, pf, iked, relayd, and more
- **WireGuard VPN**: Native wg interface support
- **High Availability**: CARP and pfsync integration
- **OpenBSD Security**: Built on the most secure OS foundation

### REST API
- System info, logs, and command execution
- Interface and routing management
- PF firewall rules, states, and tables
- WireGuard and IKE/IPsec VPN
- DHCP leases and DNS
- Service management for 30+ daemons
- Configuration backup/restore
- Authentication with RBAC

### Monitoring
- Prometheus metrics endpoint (`/api/v1/metrics`)
- SNMP v2c agent with custom MIB
- Real-time system statistics
- PF firewall counters
- CARP/pfsync HA status

### Automation
- Ansible Collection (`nswall.network`)
- Terraform Provider (`nswall`)
- Full OpenAPI 3.1 specification
- RESTful JSON API

## Supported Daemons

bgpd, dhcpd, dhcpleased, dhcrelay, dvmrpd, eigrpd, ftp-proxy, ifstated, inetd,
iked, ipsecctl, ldapd, ldpd, npppd, ntpd, ospfd, ospf6d, pf, rad, relayd,
resolvd, ripd, sasyncd, slaacd, smtpd, snmpd, sshd, tftpd, tftp-proxy.

---

## Quick Install

### Full Installation (OpenBSD)

```shell
# Install dependencies
pkg_add sqlite3 go node

# Clone repository
git clone https://github.com/northshorenetworks/NSWall.git
cd NSWall

# Build and install CLI
make
doas make install

# Build and install API server
cd api
make
doas make install
cd ..

# Build and install Web UI
cd webui
npm install
npm run build
cd ..

# Build and install SNMP agent (optional)
cd snmpd
make
doas make install
cd ..
```

### Enable Services

```shell
# Enable API server
doas rcctl enable nswall_api
doas rcctl start nswall_api

# Enable SNMP agent (optional)
doas rcctl enable nswall_snmpd
doas rcctl start nswall_snmpd
```

### Set as Login Shell

```shell
doas sh -c 'echo /usr/local/bin/nsh >> /etc/shells'
chsh -s /usr/local/bin/nsh
```

---

## Usage

```
$ nsh
nswall> enable
Password:

nswall# show interface
nswall# show route
nswall# show running-config

nswall# configure
nswall(config)# hostname myrouter
nswall(config)# interface em0
nswall(config-em0)# ip 10.0.0.1/24
nswall(config-em0)# description "WAN uplink"
nswall(config-em0)# exit
nswall(config)# write-config
```

---

## System Integration

To have NSWall manage your system configuration at boot:

```shell
cd scripts/shell
doas sh ./rc.local-nsh-openbsd-integrate.sh
```

This will:
- Backup existing /etc configuration
- Import settings into NSWall format
- Configure system to load NSWall config at boot

---

## REST API

The API server provides 70+ endpoints for automation:

```shell
# Health check
curl http://localhost:8080/api/v1/health

# Get system info
curl http://localhost:8080/api/v1/system/info

# List interfaces
curl http://localhost:8080/api/v1/interfaces

# Get PF rules
curl http://localhost:8080/api/v1/firewall/rules

# Prometheus metrics
curl http://localhost:8080/api/v1/metrics
```

See `api/openapi.yaml` for full API documentation.

---

## Web UI

Access the web dashboard at `http://localhost:8080/` after starting the API server.

Features:
- System dashboard with real-time stats
- Interface management
- Routing and ARP tables
- PF firewall rules and states
- VPN status (WireGuard, IKE/IPsec)
- Service management
- Configuration backup/restore
- Log viewer

---

## Ansible

Install the Ansible collection:

```shell
ansible-galaxy collection install ./ansible
```

Example playbook:

```yaml
- hosts: firewalls
  collections:
    - nswall.network
  tasks:
    - name: Gather facts
      nswall_facts:
        gather_subset:
          - system
          - interfaces
          - firewall

    - name: Ensure sshd is running
      nswall_service:
        name: sshd
        state: started
        enabled: true

    - name: Block IP in PF table
      nswall_pf_table:
        table: blocklist
        addresses:
          - 10.0.0.100
        state: present
```

---

## Terraform

Configure the provider:

```hcl
terraform {
  required_providers {
    nswall = {
      source = "northshorenetworks/nswall"
    }
  }
}

provider "nswall" {
  host     = "https://nswall.example.com:8080"
  api_key  = var.nswall_api_key
}

resource "nswall_route" "internal" {
  destination = "10.0.0.0/8"
  gateway     = "192.168.1.1"
}
```

---

## SNMP Monitoring

Query NSWall via SNMP:

```shell
# Get hostname
snmpget -v2c -c public localhost .1.3.6.1.4.1.59999.1.2.0

# Get PF state count
snmpget -v2c -c public localhost .1.3.6.1.4.1.59999.3.2.0

# Walk all NSWall OIDs
snmpwalk -v2c -c public localhost .1.3.6.1.4.1.59999
```

See `snmpd/NSWALL-MIB.txt` for full MIB.

---

## Documentation

- **Manual**: `man nsh` or type `manual <topic>` within the shell
- **API Spec**: `api/openapi.yaml`
- **Upstream NSH**: https://github.com/yellowman/nsh
- **NSH Website**: http://www.nmedia.net/nsh/

### Presentations

- [BSDCAN 2024: Supporting Business IT with OpenBSD and NSH](https://www.youtube.com/watch?v=9T9-v5NLjXk)
- [EurobsdCon 2022: NSH for Network Administrators](https://www.youtube.com/watch?v=WMKxIHaWaG0)

---

## License

BSD License - See [COPYRIGHT](COPYRIGHT)

NSWall is based on NSH, freely licensed in the BSD style by Chris Cappuccio.

---

## Contributing

- GitHub: https://github.com/northshorenetworks/NSWall
- Upstream NSH: https://github.com/yellowman/nsh
- NSH Mailing List: nsh@lists.deschutesdigital.com

## CI/CD

GitHub Actions for automated builds:
- OpenBSD 7.8 and 7.6 (x86_64)
- OpenBSD 7.8 (ARM64)
- Automated release packages
