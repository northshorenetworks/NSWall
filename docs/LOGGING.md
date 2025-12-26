# NSWall Logging Strategy

## Overview

NSWall implements a comprehensive logging strategy that covers application logging, security audit logging, and integration with external logging systems.

## Log Types

### 1. Application Logs

Standard application logs for debugging and operational monitoring.

**Location:** `/var/log/nswall/api.log` (configurable)

**Format:** JSON (structured) or text

```json
{
  "timestamp": "2024-12-25T10:30:00Z",
  "level": "INFO",
  "message": "Request completed",
  "component": "api",
  "request_id": "abc123",
  "method": "GET",
  "path": "/api/v1/interfaces",
  "status_code": 200,
  "duration_ms": 45.2
}
```

**Log Levels:**
- `DEBUG` - Detailed debugging information
- `INFO` - General operational information
- `WARN` - Warning conditions
- `ERROR` - Error conditions
- `FATAL` - Critical errors that cause shutdown

### 2. Audit Logs

Security-focused logs for compliance and forensics.

**Location:** `/var/log/nswall/audit.log`

**Retention:** 90 days (configurable)

**Events logged:**
- Authentication (login, logout, failures)
- User management (create, modify, delete)
- Configuration changes
- Firewall rule changes
- Service start/stop
- System commands
- HA failover events

```json
{
  "timestamp": "2024-12-25T10:30:00Z",
  "event_type": "CONFIG_CHANGED",
  "severity": "info",
  "username": "admin",
  "client_ip": "192.168.1.100",
  "request_id": "abc123",
  "resource": "pf.conf",
  "action": "modify",
  "result": "success",
  "message": "User admin modified pf.conf",
  "old_value": "...",
  "new_value": "..."
}
```

### 3. Access Logs

HTTP request logs in combined log format.

**Location:** `/var/log/nswall/access.log`

**Format:**
```
192.168.1.100 GET /api/v1/interfaces 200 1234 45ms
```

### 4. System Logs

OpenBSD daemon logs via syslog.

**Facility:** `daemon`
**Location:** `/var/log/daemon`

## Configuration

### API Server Configuration

```yaml
# /etc/nswall/api.yaml
logging:
  level: info          # debug, info, warn, error
  format: json         # json or text
  output: /var/log/nswall/api.log
  syslog: localhost:514

audit:
  enabled: true
  file: /var/log/nswall/audit.log
  max_size: 100        # MB
  max_age: 90          # days
  syslog: true
```

### Environment Variables

```bash
NSWALL_LOG_LEVEL=debug
NSWALL_LOG_FORMAT=json
NSWALL_LOG_OUTPUT=/var/log/nswall/api.log
NSWALL_AUDIT_ENABLED=true
```

## Log Rotation

### newsyslog.conf

Add to `/etc/newsyslog.conf`:

```
/var/log/nswall/api.log    640  7  1000  *  Z  /var/run/nswall-api.pid
/var/log/nswall/audit.log  640  90 1000  *  Z
/var/log/nswall/access.log 640  7  1000  *  Z  /var/run/nswall-api.pid
```

### Manual Rotation

```bash
# Rotate logs
newsyslog -f /etc/newsyslog.conf

# Or send SIGHUP to API server
pkill -HUP nswall-api
```

## Centralized Logging

### Syslog Integration

NSWall can forward logs to a remote syslog server:

```yaml
logging:
  syslog: loghost.example.com:514
```

### ELK Stack Integration

For Elasticsearch/Logstash/Kibana:

1. Configure Filebeat on NSWall:

```yaml
# /etc/filebeat/filebeat.yml
filebeat.inputs:
  - type: log
    paths:
      - /var/log/nswall/*.log
    json.keys_under_root: true
    json.add_error_key: true

output.elasticsearch:
  hosts: ["elk.example.com:9200"]
  index: "nswall-%{+yyyy.MM.dd}"
```

2. Or use Logstash with syslog input:

```ruby
input {
  syslog {
    port => 514
    type => "nswall"
  }
}

filter {
  if [type] == "nswall" {
    json {
      source => "message"
    }
  }
}

output {
  elasticsearch {
    hosts => ["localhost:9200"]
    index => "nswall-%{+YYYY.MM.dd}"
  }
}
```

### Grafana Loki

For Grafana Loki integration:

```yaml
# /etc/promtail/config.yml
clients:
  - url: http://loki.example.com:3100/loki/api/v1/push

scrape_configs:
  - job_name: nswall
    static_configs:
      - targets:
          - localhost
        labels:
          job: nswall
          __path__: /var/log/nswall/*.log
```

## Prometheus Metrics

Log-related metrics are exposed at `/api/v1/metrics`:

```
# HELP nswall_http_requests_total Total HTTP requests
# TYPE nswall_http_requests_total counter
nswall_http_requests_total{method="GET",path="/api/v1/interfaces",status="200"} 1234

# HELP nswall_http_request_duration_seconds HTTP request latency
# TYPE nswall_http_request_duration_seconds histogram
nswall_http_request_duration_seconds_bucket{le="0.1"} 900

# HELP nswall_audit_events_total Total audit events
# TYPE nswall_audit_events_total counter
nswall_audit_events_total{event_type="LOGIN",result="success"} 100
```

## Best Practices

### Security
- Audit logs should be immutable (append-only)
- Restrict access to log files (chmod 640)
- Consider log signing for forensic integrity
- Forward logs to a remote system for tamper-resistance

### Performance
- Use async logging in production
- Avoid DEBUG level in production
- Configure appropriate log rotation
- Monitor log disk usage

### Compliance
- Retain audit logs per regulatory requirements
- Include all authentication events
- Log configuration changes with before/after values
- Timestamp all entries in UTC

## Troubleshooting

### Enable Debug Logging

```bash
# Temporarily enable debug
export NSWALL_LOG_LEVEL=debug
rcctl restart nswall_api
```

### View Live Logs

```bash
# Follow API logs
tail -f /var/log/nswall/api.log | jq .

# Follow audit logs
tail -f /var/log/nswall/audit.log | jq .

# Filter by level
tail -f /var/log/nswall/api.log | jq 'select(.level == "ERROR")'
```

### Search Logs

```bash
# Find authentication failures
grep LOGIN_FAILED /var/log/nswall/audit.log | jq .

# Find errors in last hour
jq 'select(.level == "ERROR")' /var/log/nswall/api.log | \
  jq 'select(.timestamp > (now - 3600 | todate))'
```

## API Endpoints

### Get Recent Logs

```bash
# System logs
curl http://localhost:8080/api/v1/system/logs?lines=100

# Filtered by facility
curl http://localhost:8080/api/v1/system/logs?facility=daemon&lines=50
```

### Get Audit Events

```bash
# Recent audit events
curl http://localhost:8080/api/v1/audit/events?limit=100

# Filter by type
curl http://localhost:8080/api/v1/audit/events?type=LOGIN_FAILED&limit=50
```
