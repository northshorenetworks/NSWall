# NSWall REST API

Comprehensive REST API for NSWall/NSH network appliance automation.

## Features

- **Full System Management** - Status, memory, disks, logs
- **Network Interfaces** - List, configure, VLANs
- **Routing** - Routes, ARP tables
- **PF Firewall** - Rules, states, tables, enable/disable
- **VPN** - WireGuard interfaces/peers, IKE/IPsec SAs
- **High Availability** - CARP status, pfsync
- **DHCP/DNS** - Leases, subnets, resolver config
- **Services** - Start/stop/restart all 30+ supported daemons
- **Configuration** - Running/startup config, backup/restore, diff
- **Authentication** - API keys, sessions, RBAC

## Quick Start

```bash
# Build
cd api
make build

# Run (no auth)
./nswall-api -listen 0.0.0.0:8080

# Run with API key
./nswall-api -listen 0.0.0.0:8080 -apikey your-secret-key

# Run with full authentication
./nswall-api -listen 0.0.0.0:8080 -auth
```

## Command Line Options

| Option | Default | Description |
|--------|---------|-------------|
| `-listen` | `127.0.0.1:8080` | API listen address |
| `-nsh` | `/usr/local/bin/nsh` | Path to nsh binary |
| `-data` | `/var/db/nswall-api` | Data directory for API state |
| `-apikey` | (none) | Static API key for authentication |
| `-auth` | `false` | Enable full authentication with RBAC |
| `-cors` | (none) | Allowed CORS origins (comma-separated) |
| `-rate-limit` | `100` | Rate limit per minute (0 to disable) |

## API Endpoints

### System

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/system` | System information |
| GET | `/api/v1/system/version` | API and NSH versions |
| GET | `/api/v1/system/memory` | Memory usage |
| GET | `/api/v1/system/disks` | Disk usage |
| GET | `/api/v1/system/logs` | System logs |
| POST | `/api/v1/command` | Execute nsh command |

### Interfaces

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/interfaces` | List all interfaces |
| GET | `/api/v1/interfaces/{name}` | Get interface details |
| PUT | `/api/v1/interfaces/{name}` | Configure interface |

### Routing

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/routes` | Get routing table |
| POST | `/api/v1/routes` | Add a route |
| DELETE | `/api/v1/routes/{destination}` | Delete a route |

### ARP

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/arp` | Get ARP table |
| DELETE | `/api/v1/arp/{ip}` | Delete ARP entry |
| POST | `/api/v1/arp/flush` | Flush ARP table |

### PF Firewall

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/pf` | PF status and info |
| GET | `/api/v1/pf/stats` | PF statistics |
| GET | `/api/v1/pf/rules` | List all rules |
| GET | `/api/v1/pf/states` | List all states |
| DELETE | `/api/v1/pf/states` | Kill states (query params: src, dst, interface) |
| POST | `/api/v1/pf/enable` | Enable PF |
| POST | `/api/v1/pf/disable` | Disable PF |
| POST | `/api/v1/pf/reload` | Reload PF rules |
| GET | `/api/v1/pf/tables` | List all tables |
| GET | `/api/v1/pf/tables/{name}` | Get table entries |
| POST | `/api/v1/pf/tables/{name}` | Add address to table |
| DELETE | `/api/v1/pf/tables/{name}/{address}` | Remove address |
| POST | `/api/v1/pf/tables/{name}/flush` | Flush table |

### VPN

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/vpn/wireguard` | List WireGuard interfaces |
| POST | `/api/v1/vpn/wireguard` | Create WireGuard interface |
| GET | `/api/v1/vpn/wireguard/{name}` | Get WireGuard interface |
| PUT | `/api/v1/vpn/wireguard/{name}` | Configure WireGuard |
| POST | `/api/v1/vpn/wireguard/{name}/peers` | Add peer |
| DELETE | `/api/v1/vpn/wireguard/{name}/peers/{key}` | Remove peer |
| POST | `/api/v1/vpn/wireguard/keygen` | Generate key pair |
| GET | `/api/v1/vpn/ike/sas` | List IKE SAs |
| GET | `/api/v1/vpn/ipsec/sas` | List IPsec SAs |

### High Availability

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/ha/carp` | List CARP interfaces |
| GET | `/api/v1/ha/carp/{name}` | Get CARP status |
| PUT | `/api/v1/ha/carp/{name}` | Configure CARP |
| GET | `/api/v1/ha/pfsync` | Get pfsync status |

### DHCP

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dhcp/leases` | List DHCP leases |
| GET | `/api/v1/dhcp/subnets` | List DHCP subnets |

### DNS

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dns` | Get DNS configuration |
| POST | `/api/v1/dns/flush` | Flush DNS cache |

### Services

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/services` | List all services |
| GET | `/api/v1/services/{name}` | Get service status |
| POST | `/api/v1/services/{name}` | Control service (action: start/stop/restart/reload/enable/disable) |

### Configuration

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/config` | Get running config |
| GET | `/api/v1/config/running` | Get running config |
| GET | `/api/v1/config/startup` | Get startup config |
| GET | `/api/v1/config/diff` | Compare running vs startup |
| POST | `/api/v1/config/save` | Save running to startup |
| POST | `/api/v1/config/validate` | Validate config snippet |
| POST | `/api/v1/config/apply` | Apply config snippet |
| GET | `/api/v1/config/backups` | List backups |
| POST | `/api/v1/config/backups` | Create backup |
| POST | `/api/v1/config/backups/{name}/restore` | Restore backup |
| DELETE | `/api/v1/config/backups/{name}` | Delete backup |

### Authentication (when -auth enabled)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login (returns session token) |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/users` | List users (admin only) |
| POST | `/api/v1/auth/users` | Create user (admin only) |
| DELETE | `/api/v1/auth/users/{username}` | Delete user (admin only) |
| POST | `/api/v1/auth/users/{username}/apikey` | Create API key |

## Authentication

### Simple API Key

```bash
# Header
curl -H "X-API-Key: your-key" http://localhost:8080/api/v1/system

# Query parameter
curl "http://localhost:8080/api/v1/system?api_key=your-key"
```

### Full Authentication

When running with `-auth`, users can:

1. Login to get a session token:
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -d '{"username":"admin","password":"admin"}'
```

2. Use the token:
```bash
curl -H "Authorization: Bearer <token>" http://localhost:8080/api/v1/system
```

3. Or create API keys for automation:
```bash
curl -X POST -H "Authorization: Bearer <token>" \
  http://localhost:8080/api/v1/auth/users/admin/apikey \
  -d '{"name":"ansible"}'
```

### Roles

- **admin** - Full access to all endpoints
- **operator** - Read/write access to config, firewall, services
- **viewer** - Read-only access

## Examples

### Get System Info

```bash
curl http://localhost:8080/api/v1/system
```

Response:
```json
{
  "success": true,
  "data": {
    "hostname": "nswall",
    "os": "OpenBSD",
    "version": "7.8",
    "uptime_seconds": 86400,
    "uptime_human": "1d 0h 0m",
    "load_average": [0.5, 0.3, 0.2]
  }
}
```

### List Interfaces

```bash
curl http://localhost:8080/api/v1/interfaces
```

### Add Route

```bash
curl -X POST http://localhost:8080/api/v1/routes \
  -H "Content-Type: application/json" \
  -d '{"destination":"10.0.0.0/8","gateway":"192.168.1.1"}'
```

### Control Service

```bash
# Restart ospfd
curl -X POST http://localhost:8080/api/v1/services/ospfd \
  -H "Content-Type: application/json" \
  -d '{"action":"restart"}'
```

### Add IP to PF Table

```bash
curl -X POST http://localhost:8080/api/v1/pf/tables/bruteforce \
  -H "Content-Type: application/json" \
  -d '{"address":"10.1.2.3"}'
```

### WireGuard Peer

```bash
curl -X POST http://localhost:8080/api/v1/vpn/wireguard/wg0/peers \
  -H "Content-Type: application/json" \
  -d '{
    "public_key": "abc123...",
    "endpoint": "203.0.113.1:51820",
    "allowed_ips": ["10.0.0.2/32"],
    "persistent_keepalive": 25
  }'
```

## OpenBSD Service

Install the rc.d script:

```bash
doas make install
doas rcctl enable nswall_api
doas rcctl set nswall_api flags "-listen 0.0.0.0:8080 -apikey secret"
doas rcctl start nswall_api
```

## Integration

### Ansible

```yaml
- name: Get interfaces
  uri:
    url: "http://{{ nswall_host }}:8080/api/v1/interfaces"
    headers:
      X-API-Key: "{{ nswall_api_key }}"
  register: interfaces

- name: Restart ospfd
  uri:
    url: "http://{{ nswall_host }}:8080/api/v1/services/ospfd"
    method: POST
    headers:
      X-API-Key: "{{ nswall_api_key }}"
    body_format: json
    body:
      action: restart
```

### Terraform

Use the HTTP provider or a custom provider with this API.

## License

BSD License - See LICENSE file
