# NSWall Controller

Central management server for NSWall fleet using NATS messaging.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    NSWall Controller                         │
│  ┌─────────┐  ┌──────────┐  ┌─────────────┐  ┌───────────┐ │
│  │ WebUI   │──│ REST API │──│ NATS Client │──│ JetStream │ │
│  └─────────┘  └──────────┘  └──────────────┘  └───────────┘ │
└──────────────────────────────┬──────────────────────────────┘
                               │
                        ┌──────┴──────┐
                        │ NATS Server │ (embedded)
                        └──────┬──────┘
              ┌────────────────┼────────────────┐
              │                │                │
       ┌──────┴──────┐  ┌──────┴──────┐  ┌──────┴──────┐
       │ NSWall fw1  │  │ NSWall fw2  │  │ NSWall fw3  │
       └─────────────┘  └─────────────┘  └─────────────┘
```

## Features

- **Embedded NATS Server** - No external dependencies
- **JetStream** - Persistent event storage
- **Real-time Updates** - SSE/WebSocket for live dashboard
- **Fleet Management** - Groups, templates, bulk operations
- **Config Push** - Deploy PF rules to multiple agents

## Building

```bash
# Build for current platform
make build

# Build for OpenBSD
make build-openbsd

# Build for all platforms
make build-all
```

## Running

```bash
# Start controller
./nswall-controller -http :8080 -nats :4222

# With custom data directory
./nswall-controller -http :8080 -nats :4222 -data /var/db/nswall-controller
```

## Configuration

| Flag | Default | Description |
|------|---------|-------------|
| `-http` | `:8080` | HTTP listen address for WebUI/API |
| `-nats` | `0.0.0.0:4222` | NATS server listen address |
| `-cluster` | `` | NATS cluster URL for HA |
| `-data` | `/var/db/nswall-controller` | Data directory |

## Agent Configuration

On each NSWall appliance, configure the API to connect to the controller:

```bash
# Via CLI
nswall# configure terminal
nswall(config)# fleet mode agent
nswall(config-fleet)# controller nats://controller.example.com:4222
nswall(config-fleet)# exit
nswall(config)# write memory
```

Or start the API with NATS agent mode:

```bash
nswall-api -nats nats://controller.example.com:4222
```

## API Endpoints

### Agents
- `GET /api/v1/agents` - List all agents
- `GET /api/v1/agents/summary` - Fleet summary
- `GET /api/v1/agents/{id}` - Get agent details
- `POST /api/v1/agents/{id}/command` - Send command
- `POST /api/v1/agents/{id}/config` - Push config

### Broadcast
- `POST /api/v1/broadcast/config` - Push config to all
- `POST /api/v1/broadcast/command` - Send command to all

### Groups
- `GET /api/v1/groups` - List groups
- `POST /api/v1/groups` - Create group
- `POST /api/v1/groups/{name}/config` - Push config to group

### Templates
- `GET /api/v1/templates` - List templates
- `POST /api/v1/templates` - Create template
- `PUT /api/v1/templates/{name}` - Update template

### Events
- `GET /api/v1/events` - Get recent events
- `GET /api/v1/events/stream` - SSE event stream

## NATS Topics

| Topic | Direction | Description |
|-------|-----------|-------------|
| `nswall.agent.{id}.status` | Agent → Controller | Status updates |
| `nswall.agent.{id}.config` | Controller → Agent | Config push |
| `nswall.agent.{id}.command` | Controller → Agent | Commands |
| `nswall.broadcast.config` | Controller → All | Broadcast config |
| `nswall.broadcast.command` | Controller → All | Broadcast command |
| `nswall.events` | Internal | Event stream (JetStream) |

## High Availability

For HA, run multiple controllers with NATS clustering:

```bash
# Controller 1
./nswall-controller -nats :4222 -cluster nats://controller2:5222

# Controller 2
./nswall-controller -nats :4222 -cluster nats://controller1:5222
```

## License

BSD-3-Clause
