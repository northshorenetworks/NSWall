# NSWall API Server

REST API for NSWall/NSH network appliance automation.

## Building

```bash
# Install Go
pkg_add go

# Build
cd api
make build

# Install
doas make install
```

## Running

```bash
# Start manually
nswall-api -listen 127.0.0.1:8080

# With API key authentication
nswall-api -listen 127.0.0.1:8080 -apikey "your-secret-key"

# As a service
doas rcctl enable nswall_api
doas rcctl start nswall_api
```

## API Endpoints

### System

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check |
| GET | `/api/v1/status` | System status |
| GET | `/api/v1/version` | Version info |

### Network

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/interfaces` | List all interfaces |
| GET | `/api/v1/interfaces/{name}` | Show specific interface |
| GET | `/api/v1/routes` | Show routing table |
| GET | `/api/v1/arp` | Show ARP table |

### Configuration

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/config` | Get running config |
| POST | `/api/v1/command` | Execute nsh command |

### Firewall (PF)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/pf/rules` | Show PF rules |
| GET | `/api/v1/pf/states` | Show PF states |
| GET | `/api/v1/pf/info` | Show PF info |

## Examples

```bash
# Get interfaces
curl http://localhost:8080/api/v1/interfaces

# Get routes
curl http://localhost:8080/api/v1/routes

# Execute command
curl -X POST http://localhost:8080/api/v1/command \
  -H "Content-Type: application/json" \
  -d '{"command": "show interface em0"}'

# With API key
curl -H "X-API-Key: your-secret-key" http://localhost:8080/api/v1/status
```

## Configuration

Edit `/etc/rc.d/nswall_api` to change defaults:

```bash
daemon_flags="-listen 0.0.0.0:8443 -apikey mysecret"
```

## Security

- By default, listens only on localhost (127.0.0.1)
- Use `-apikey` flag for authentication
- Dangerous commands (reload, halt, shell) are blocked
- Run as unprivileged `_nswall` user
