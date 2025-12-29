# NSWall Feature Matrix

This document provides a comprehensive comparison between OpenBSD daemon control utilities and NSWall's implementation coverage.

**Sources:** [OpenBSD Manual Pages](https://man.openbsd.org/)

## Legend
- ✅ Implemented
- N/A Not applicable

---

## ospfctl (OSPF Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `fib reload` | Refetch routes | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show database` | Show link state database | ✅ | ✅ |
| `show database area` | Filter by area ID | ✅ | ✅ |
| `show database asbr` | ASBR LSAs only | ✅ | ✅ |
| `show database external` | AS-External LSAs | ✅ | ✅ |
| `show database network` | Network LSAs | ✅ | ✅ |
| `show database router` | Router LSAs | ✅ | ✅ |
| `show database self-originated` | Self-originated LSAs | ✅ | ✅ |
| `show database summary` | Summary LSAs | ✅ | ✅ |
| `show fib` | Show forwarding table | ✅ | ✅ |
| `show fib connected` | Connected routes only | ✅ | ✅ |
| `show fib interface` | Interfaces only | ✅ | ✅ |
| `show fib ospf` | OSPF routes only | ✅ | ✅ |
| `show fib static` | Static routes only | ✅ | ✅ |
| `show interfaces` | Show interfaces | ✅ | ✅ |
| `show neighbor` | Show neighbors | ✅ | ✅ |
| `show rib` | Show routing info base | ✅ | ✅ |
| `show summary` | Show summary | ✅ | ✅ |

**Coverage: 100%**

---

## bgpctl (BGP Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `irrfilter` | Generate BGP filters | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `neighbor up` | Bring neighbor up | ✅ | ✅ |
| `neighbor down` | Take neighbor down | ✅ | ✅ |
| `neighbor clear` | Clear neighbor session | ✅ | ✅ |
| `neighbor refresh` | Refresh neighbor routes | ✅ | ✅ |
| `neighbor destroy` | Destroy cloned peer | ✅ | ✅ |
| `network add` | Add announced network | ✅ | ✅ |
| `network delete` | Remove announced network | ✅ | ✅ |
| `network flush` | Remove all dynamic networks | ✅ | ✅ |
| `network show` | Show announced networks | ✅ | ✅ |
| `network bulk` | Bulk add networks | ✅ | ✅ |
| `network mrt` | Import MRT dump | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `flowspec add` | Add flowspec rule | ✅ | ✅ |
| `flowspec delete` | Delete flowspec rule | ✅ | ✅ |
| `flowspec flush` | Flush flowspec rules | ✅ | ✅ |
| `show fib` | Show FIB | ✅ | ✅ |
| `show interfaces` | Show interfaces | ✅ | ✅ |
| `show neighbor` | Show neighbor details | ✅ | ✅ |
| `show nexthop` | Show nexthop routes | ✅ | ✅ |
| `show rib` | Show RIB | ✅ | ✅ |
| `show summary` | Show summary | ✅ | ✅ |
| `show tables` | Show routing tables | ✅ | ✅ |
| `show metrics` | Show metrics | ✅ | ✅ |

**Coverage: 100%**

---

## ripctl (RIP Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show fib` | Show forwarding table | ✅ | ✅ |
| `show interfaces` | Show interfaces | ✅ | ✅ |
| `show neighbor` | Show neighbors | ✅ | ✅ |
| `show rib` | Show routing info base | ✅ | ✅ |

**Coverage: 100%**

---

## ldpctl (MPLS LDP)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `clear neighbors` | Clear neighbor sessions | ✅ | ✅ |
| `fib couple` | Insert labels into kernel | ✅ | ✅ |
| `fib decouple` | Remove labels from kernel | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show discovery` | Show discovery info | ✅ | ✅ |
| `show fib` | Show label FIB | ✅ | ✅ |
| `show interfaces` | Show interfaces | ✅ | ✅ |
| `show l2vpn bindings` | Show L2VPN bindings | ✅ | ✅ |
| `show l2vpn pseudowires` | Show pseudowires | ✅ | ✅ |
| `show lib` | Show label info base | ✅ | ✅ |
| `show neighbor` | Show neighbors | ✅ | ✅ |

**Coverage: 100%**

---

## eigrpctl (EIGRP Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `clear neighbors` | Clear neighbor sessions | ✅ | ✅ |
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show fib` | Show forwarding table | ✅ | ✅ |
| `show interfaces` | Show interfaces | ✅ | ✅ |
| `show neighbor` | Show neighbors | ✅ | ✅ |
| `show topology` | Show topology | ✅ | ✅ |
| `show stats` | Show statistics | ✅ | ✅ |

**Coverage: 100%**

---

## unbound-control (DNS)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `start` | Start server | N/A (use enable) | N/A |
| `stop` | Stop server | ✅ | ✅ |
| `reload` | Reload config (flush cache) | ✅ | ✅ |
| `reload_keep_cache` | Reload without flushing | ✅ | ✅ |
| `status` | Show daemon status | ✅ | ✅ |
| `stats` | Show statistics | ✅ | ✅ |
| `stats_noreset` | Stats without reset | ✅ | ✅ |
| `verbosity` | Set log level | ✅ | ✅ |
| `flush` | Flush name from cache | ✅ | ✅ |
| `flush_type` | Flush specific RR type | ✅ | ✅ |
| `flush_zone` | Flush entire zone | ✅ | ✅ |
| `flush_infra` | Flush infra cache | ✅ | ✅ |
| `dump_cache` | Dump cache contents | ✅ | ✅ |
| `dump_infra` | Dump infra cache | ✅ | ✅ |
| `list_stubs` | List stub zones | ✅ | ✅ |
| `list_forwards` | List forward zones | ✅ | ✅ |
| `list_local_zones` | List local zones | ✅ | ✅ |
| `list_local_data` | List local data | ✅ | ✅ |
| `list_insecure` | List insecure zones | ✅ | ✅ |
| `list_auth_zones` | List auth zones | ✅ | ✅ |
| `insecure_add` | Add insecure zone | ✅ | ✅ |
| `insecure_remove` | Remove insecure zone | ✅ | ✅ |
| `forward_add` | Add forward zone | ✅ | ✅ |
| `forward_remove` | Remove forward zone | ✅ | ✅ |
| `stub_add` | Add stub zone | ✅ | ✅ |
| `stub_remove` | Remove stub zone | ✅ | ✅ |
| `set_option` | Set runtime option | ✅ | ✅ |
| `log_reopen` | Reopen log file | ✅ | ✅ |
| `auth_zone_reload` | Reload auth zone | ✅ | ✅ |
| `auth_zone_transfer` | Transfer auth zone | ✅ | ✅ |
| `rpz_enable` | Enable RPZ zone | ✅ | ✅ |
| `rpz_disable` | Disable RPZ zone | ✅ | ✅ |

**Coverage: 100%**

---

## smtpctl (Mail)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `discover` | Discover local user | ✅ | ✅ |
| `encrypt` | Encrypt password | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `monitor` | Monitor mail activity | ✅ | ✅ |
| `pause envelope` | Pause envelope | ✅ | ✅ |
| `pause mda` | Pause local delivery | ✅ | ✅ |
| `pause mta` | Pause remote delivery | ✅ | ✅ |
| `pause smtp` | Pause incoming | ✅ | ✅ |
| `profile` | Profile subsystem | ✅ | ✅ |
| `unprofile` | Disable profiling | ✅ | ✅ |
| `remove` | Remove from queue | ✅ | ✅ |
| `remove all` | Remove all from queue | ✅ | ✅ |
| `resume envelope` | Resume envelope | ✅ | ✅ |
| `resume mda` | Resume local delivery | ✅ | ✅ |
| `resume mta` | Resume remote delivery | ✅ | ✅ |
| `resume smtp` | Resume incoming | ✅ | ✅ |
| `schedule` | Schedule delivery | ✅ | ✅ |
| `schedule all` | Schedule all pending | ✅ | ✅ |
| `show envelope` | Show envelope details | ✅ | ✅ |
| `show hosts` | Show remote MX hosts | ✅ | ✅ |
| `show hoststats` | Show host statistics | ✅ | ✅ |
| `show message` | Show message content | ✅ | ✅ |
| `show queue` | Show mail queue | ✅ | ✅ |
| `show relays` | Show relay sessions | ✅ | ✅ |
| `show routes` | Show mail routes | ✅ | ✅ |
| `show stats` | Show statistics | ✅ | ✅ |
| `show status` | Show daemon status | ✅ | ✅ |
| `spf walk` | SPF record lookup | ✅ | ✅ |
| `stop` | Stop daemon | ✅ | ✅ |
| `trace` | Enable tracing | ✅ | ✅ |
| `untrace` | Disable tracing | ✅ | ✅ |
| `update table` | Update table | ✅ | ✅ |

**Coverage: 100%**

---

## relayctl (Load Balancer)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `host disable` | Disable host | ✅ | ✅ |
| `host enable` | Enable host | ✅ | ✅ |
| `load` | Load table entries | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `monitor` | Monitor health checks | ✅ | ✅ |
| `poll` | Immediate health check | ✅ | ✅ |
| `redirect disable` | Disable redirect | ✅ | ✅ |
| `redirect enable` | Enable redirect | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show hosts` | Show host status | ✅ | ✅ |
| `show redirects` | Show redirections | ✅ | ✅ |
| `show relays` | Show relay status | ✅ | ✅ |
| `show routers` | Show routers | ✅ | ✅ |
| `show sessions` | Show active sessions | ✅ | ✅ |
| `show summary` | Show summary | ✅ | ✅ |
| `stop` | Stop daemon | ✅ | ✅ |
| `table disable` | Disable table | ✅ | ✅ |
| `table enable` | Enable table | ✅ | ✅ |

**Coverage: 100%**

---

## ikectl (IKEv2 VPN)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `active` | Set active mode | ✅ | ✅ |
| `passive` | Set passive mode | ✅ | ✅ |
| `couple` | Load SAs into kernel | ✅ | ✅ |
| `decouple` | Unload SAs from kernel | ✅ | ✅ |
| `load` | Load config file | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |
| `monitor` | Monitor IKE events | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `reset all` | Reset all state | ✅ | ✅ |
| `reset ca` | Reset CA state | ✅ | ✅ |
| `reset id` | Reset specific SA | ✅ | ✅ |
| `reset policy` | Reset policies | ✅ | ✅ |
| `reset sa` | Flush running SAs | ✅ | ✅ |
| `reset user` | Reset user database | ✅ | ✅ |
| `show sa` | Show security associations | ✅ | ✅ |
| `show ca` | Show CA certificates | ✅ | ✅ |
| `show certinfo` | Show certificate info | ✅ | ✅ |
| `ca create` | Create CA | ✅ | ✅ |
| `ca delete` | Delete CA | ✅ | ✅ |
| `ca export` | Export CA | ✅ | ✅ |
| `ca import` | Import CA | ✅ | ✅ |
| `ca install` | Install CA certificate | ✅ | ✅ |
| `ca key create` | Create CA key | ✅ | ✅ |
| `certificate create` | Create certificate | ✅ | ✅ |
| `certificate delete` | Delete certificate | ✅ | ✅ |
| `certificate export` | Export certificate | ✅ | ✅ |
| `certificate install` | Install certificate | ✅ | ✅ |
| `certificate revoke` | Revoke certificate | ✅ | ✅ |

**Coverage: 100%**

---

## ntpctl (NTP)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `-s all` | Show all status | ✅ | ✅ |
| `-s peers` | Show NTP peers | ✅ | ✅ |
| `-s sensors` | Show time sensors | ✅ | ✅ |
| `-s status` | Show sync status | ✅ | ✅ |

**Coverage: 100%**

---

## snmpctl/snmp (SNMP)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `snmp get` | Get SNMP value | ✅ | ✅ |
| `snmp getnext` | Get next SNMP value | ✅ | ✅ |
| `snmp walk` | Walk SNMP tree | ✅ | ✅ |
| `snmp bulkget` | Bulk get values | ✅ | ✅ |
| `snmp bulkwalk` | Bulk walk tree | ✅ | ✅ |
| `snmp df` | Show MIB definitions | ✅ | ✅ |
| `snmp mibtree` | Show MIB tree | ✅ | ✅ |
| `trap send` | Send trap | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ✅ |
| `log verbose` | Enable verbose logging | ✅ | ✅ |

**Coverage: 100%**

---

## acme-client (Certificates)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `(domain)` | Renew certificate | ✅ | ✅ |
| `-F` | Force renewal | ✅ | ✅ |
| `-f` | Alternate config file | ✅ | ✅ |
| `-n` | Check config (no-op) | ✅ | ✅ |
| `-r` | Revoke certificate | ✅ | ✅ |
| `-v` | Verbose output | ✅ | ✅ |
| `-vv` | Very verbose output | ✅ | ✅ |
| `status` | Show certificate status | ✅ | ✅ |

**Coverage: 100%**

---

## pflogd/tcpdump (PF Logging)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| Read log file | Read /var/log/pflog | ✅ | ✅ |
| Read all | Read all log entries | ✅ | ✅ |
| Read count | Read specific count | ✅ | ✅ |
| Live monitoring | Monitor pflog0 | ✅ | ✅ |
| Live interface | Monitor specific interface | ✅ | ✅ |
| Filter expression | Filter by rule/port/etc | ✅ | ✅ |
| `-c count` | Limit output lines | ✅ | ✅ |
| `-e` | Print link-level header | ✅ | ✅ |
| `-n` | Don't resolve names | ✅ | ✅ |
| `-i interface` | Specify pflog interface | ✅ | ✅ |
| `-s snaplen` | Set snaplen | ✅ | ✅ |
| Filter: `ip` | IPv4 only | ✅ | ✅ |
| Filter: `ip6` | IPv6 only | ✅ | ✅ |
| Filter: `ifname` | By interface | ✅ | ✅ |
| Filter: `rulenum` | By rule number | ✅ | ✅ |
| Filter: `reason` | By reason | ✅ | ✅ |
| Filter: `action` | By action (pass/block) | ✅ | ✅ |

**Coverage: 100%**

---

## Summary Statistics

| Category | Total Commands | CLI Implemented | API Implemented |
|----------|----------------|-----------------|-----------------|
| ospfctl | 23 | 23 (100%) | 23 (100%) |
| bgpctl | 28 | 28 (100%) | 28 (100%) |
| ripctl | 9 | 9 (100%) | 9 (100%) |
| ldpctl | 13 | 13 (100%) | 13 (100%) |
| eigrpctl | 11 | 11 (100%) | 11 (100%) |
| unbound-control | 32 | 32 (100%) | 32 (100%) |
| smtpctl | 33 | 33 (100%) | 33 (100%) |
| relayctl | 19 | 19 (100%) | 19 (100%) |
| ikectl | 29 | 29 (100%) | 29 (100%) |
| ntpctl | 4 | 4 (100%) | 4 (100%) |
| snmpctl | 10 | 10 (100%) | 10 (100%) |
| acme-client | 8 | 8 (100%) | 8 (100%) |
| pflogd | 17 | 17 (100%) | 17 (100%) |

**Overall Coverage:**
- CLI: 100% of commands implemented
- API: 100% of commands implemented

---

## New API Endpoints

### Control Commands Endpoint

Execute daemon-specific control commands:

```
POST /api/v1/services/{name}/control
Content-Type: application/json

{
  "command": "reload",
  "args": []
}
```

### List Available Commands

Get list of available commands for a service:

```
GET /api/v1/services/{name}/commands
```

### Example: Execute OSPF Command

```bash
curl -X POST http://localhost:8081/api/v1/services/ospfd/control \
  -H "Content-Type: application/json" \
  -d '{"command": "database-asbr"}'
```

---

*Generated: December 2024*
*OpenBSD Version: 7.6+*
*NSWall Version: Full Feature Coverage*
