# NSWall Services REST API

RESTful API for managing OpenBSD system services on NSWall.

## Endpoints

### Health Check
```
GET /api/v1/health
```

### List All Services
```
GET /api/v1/services
```
Returns status of all configured services.

### Service CRUD Operations

#### Get Service Details
```
GET /api/v1/services/{name}
```
Returns service configuration, status, and current config content.

#### Delete/Disable Service
```
DELETE /api/v1/services/{name}
```
Stops and disables the service.

### Configuration Management

#### Get Configuration
```
GET /api/v1/services/{name}/config
```
Returns the current configuration file content.

#### Update Configuration
```
PUT /api/v1/services/{name}/config
Content-Type: application/json

{
  "content": "configuration content here",
  "reload": true
}
```

#### Create Default Configuration
```
POST /api/v1/services/{name}/config
```
Creates configuration from the default template.

### Service Status

```
GET /api/v1/services/{name}/status
```
Returns running state, PID, and config validation status.

### Service Actions

```
POST /api/v1/services/{name}/action
Content-Type: application/json

{
  "action": "enable|disable|reload|restart"
}
```

### Service Statistics

```
GET /api/v1/services/{name}/stats
```
Returns service-specific statistics (where available).

## Supported Services

| Service | Description |
|---------|-------------|
| unbound | DNS resolver with DNSSEC |
| httpd | OpenBSD HTTP server |
| iked | IKEv2 VPN daemon |
| rad | IPv6 Router Advertisement |
| smtpd | OpenSMTPD mail server |
| acme | Let's Encrypt certificates |
| ldpd | MPLS Label Distribution |
| pflogd | PF logging daemon |
| eigrpd | EIGRP routing protocol |
| ospfd | OSPF routing protocol |
| bgpd | BGP routing protocol |
| ripd | RIP routing protocol |
| dhcpd | DHCP server |
| ntpd | NTP time daemon |
| sshd | SSH server |
| relayd | Relay/load balancer |
| snmpd | SNMP daemon |

## Example Usage

### Enable OSPF
```bash
curl -X POST http://localhost:8081/api/v1/services/ospfd/action \
  -H "Content-Type: application/json" \
  -d '{"action": "enable"}'
```

### Update BGP Configuration
```bash
curl -X PUT http://localhost:8081/api/v1/services/bgpd/config \
  -H "Content-Type: application/json" \
  -d '{"content": "AS 65000\nrouter-id 10.0.0.1\n", "reload": true}'
```

### Get Service Status
```bash
curl http://localhost:8081/api/v1/services/unbound/status
```

## Running

```bash
cd api/services
go build -o nswall-services-api
./nswall-services-api
```

The API listens on port 8081 by default. Set `NSWALL_API_PORT` environment variable to change.
