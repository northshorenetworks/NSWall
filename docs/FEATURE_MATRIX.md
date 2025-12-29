# NSWall Feature Matrix

This document provides a comprehensive comparison between OpenBSD daemon control utilities and NSWall's implementation coverage.

**Sources:** [OpenBSD Manual Pages](https://man.openbsd.org/)

## Legend
- ✅ Implemented
- ❌ Not implemented
- ⚠️ Partial implementation
- N/A Not applicable

---

## ospfctl (OSPF Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `fib reload` | Refetch routes | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ❌ |
| `log verbose` | Enable verbose logging | ✅ | ❌ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show database` | Show link state database | ✅ (via show ospf) | ✅ |
| `show database area` | Filter by area ID | ✅ (via show ospf) | ❌ |
| `show database asbr` | ASBR LSAs only | ❌ | ❌ |
| `show database external` | AS-External LSAs | ❌ | ❌ |
| `show database network` | Network LSAs | ❌ | ❌ |
| `show database router` | Router LSAs | ❌ | ❌ |
| `show database self-originated` | Self-originated LSAs | ❌ | ❌ |
| `show database summary` | Summary LSAs | ❌ | ❌ |
| `show fib` | Show forwarding table | ✅ (via show ospf) | ✅ |
| `show fib connected` | Connected routes only | ❌ | ❌ |
| `show fib interface` | Interfaces only | ✅ (via show ospf) | ✅ |
| `show fib ospf` | OSPF routes only | ❌ | ❌ |
| `show fib static` | Static routes only | ❌ | ❌ |
| `show interfaces` | Show interfaces | ✅ (via show ospf) | ✅ |
| `show neighbor` | Show neighbors | ✅ (via show ospf) | ✅ |
| `show rib` | Show routing info base | ✅ (via show ospf) | ✅ |
| `show summary` | Show summary | ✅ (via show ospf) | ✅ |

---

## bgpctl (BGP Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `irrfilter` | Generate BGP filters | ✅ | ❌ |
| `log brief` | Disable verbose logging | ✅ | ❌ |
| `log verbose` | Enable verbose logging | ✅ | ❌ |
| `neighbor up` | Bring neighbor up | ✅ | ✅ |
| `neighbor down` | Take neighbor down | ✅ | ✅ |
| `neighbor clear` | Clear neighbor session | ✅ | ✅ |
| `neighbor refresh` | Refresh neighbor routes | ✅ | ❌ |
| `neighbor destroy` | Destroy cloned peer | ❌ | ❌ |
| `network add` | Add announced network | ✅ | ✅ |
| `network delete` | Remove announced network | ✅ | ✅ |
| `network flush` | Remove all dynamic networks | ✅ | ❌ |
| `network show` | Show announced networks | ✅ | ✅ |
| `network bulk` | Bulk add networks | ❌ | ❌ |
| `network mrt` | Import MRT dump | ❌ | ❌ |
| `reload` | Reload configuration | ✅ | ✅ |
| `flowspec add` | Add flowspec rule | ❌ | ❌ |
| `flowspec delete` | Delete flowspec rule | ❌ | ❌ |
| `flowspec flush` | Flush flowspec rules | ❌ | ❌ |
| `show fib` | Show FIB | ✅ (via show bgp) | ✅ |
| `show interfaces` | Show interfaces | ✅ (via show bgp) | ✅ |
| `show neighbor` | Show neighbor details | ✅ (via show bgp) | ✅ |
| `show nexthop` | Show nexthop routes | ✅ (via show bgp) | ✅ |
| `show rib` | Show RIB | ✅ (via show bgp) | ✅ |
| `show summary` | Show summary | ✅ (via show bgp) | ✅ |
| `show tables` | Show routing tables | ❌ | ❌ |
| `-j` (JSON output) | JSON format output | ❌ | ✅ (native) |

---

## ripctl (RIP Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ❌ |
| `log verbose` | Enable verbose logging | ✅ | ❌ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show fib` | Show forwarding table | ✅ (via show rip) | ✅ |
| `show interfaces` | Show interfaces | ✅ (via show rip) | ✅ |
| `show neighbor` | Show neighbors | ✅ (via show rip) | ✅ |
| `show rib` | Show routing info base | ✅ (via show rip) | ✅ |

---

## ldpctl (MPLS LDP)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `clear neighbors` | Clear neighbor sessions | ✅ | ✅ |
| `fib couple` | Insert labels into kernel | ✅ | ✅ |
| `fib decouple` | Remove labels from kernel | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ❌ |
| `log verbose` | Enable verbose logging | ✅ | ❌ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show discovery` | Show discovery info | ✅ (via show ldp) | ✅ |
| `show fib` | Show label FIB | ✅ (via show ldp) | ✅ |
| `show interfaces` | Show interfaces | ✅ (via show ldp) | ✅ |
| `show l2vpn bindings` | Show L2VPN bindings | ✅ | ❌ |
| `show l2vpn pseudowires` | Show pseudowires | ✅ | ❌ |
| `show lib` | Show label info base | ✅ (via show ldp) | ✅ |
| `show neighbor` | Show neighbors | ✅ (via show ldp) | ✅ |

---

## eigrpctl (EIGRP Routing)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `clear neighbors` | Clear neighbor sessions | ✅ | ✅ |
| `fib couple` | Insert routes into kernel | ✅ | ✅ |
| `fib decouple` | Remove routes from kernel | ✅ | ✅ |
| `log brief` | Disable verbose logging | ✅ | ❌ |
| `log verbose` | Enable verbose logging | ✅ | ❌ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show fib` | Show forwarding table | ✅ (via show eigrp) | ✅ |
| `show interfaces` | Show interfaces | ✅ (via show eigrp) | ✅ |
| `show neighbor` | Show neighbors | ✅ (via show eigrp) | ✅ |
| `show topology` | Show topology | ✅ (via show eigrp) | ✅ |

---

## unbound-control (DNS)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `start` | Start server | N/A (use enable) | N/A |
| `stop` | Stop server | ✅ | ✅ |
| `reload` | Reload config (flush cache) | ✅ | ✅ |
| `reload_keep_cache` | Reload without flushing | ❌ | ❌ |
| `status` | Show daemon status | ✅ | ✅ |
| `stats` | Show statistics | ✅ | ✅ |
| `stats_noreset` | Stats without reset | ✅ | ✅ |
| `verbosity` | Set log level | ✅ | ❌ |
| `flush` | Flush name from cache | ✅ | ✅ |
| `flush_type` | Flush specific RR type | ❌ | ❌ |
| `flush_zone` | Flush entire zone | ✅ | ✅ |
| `flush_infra` | Flush infra cache | ❌ | ❌ |
| `dump_cache` | Dump cache contents | ✅ | ❌ |
| `dump_infra` | Dump infra cache | ❌ | ❌ |
| `list_stubs` | List stub zones | ✅ | ❌ |
| `list_forwards` | List forward zones | ✅ | ❌ |
| `list_local_zones` | List local zones | ✅ | ❌ |
| `list_local_data` | List local data | ❌ | ❌ |
| `list_insecure` | List insecure zones | ❌ | ❌ |
| `list_auth_zones` | List auth zones | ❌ | ❌ |
| `insecure_add` | Add insecure zone | ❌ | ❌ |
| `insecure_remove` | Remove insecure zone | ❌ | ❌ |
| `forward_add` | Add forward zone | ❌ | ❌ |
| `forward_remove` | Remove forward zone | ❌ | ❌ |
| `stub_add` | Add stub zone | ❌ | ❌ |
| `stub_remove` | Remove stub zone | ❌ | ❌ |
| `set_option` | Set runtime option | ❌ | ❌ |
| `log_reopen` | Reopen log file | ❌ | ❌ |
| `auth_zone_reload` | Reload auth zone | ❌ | ❌ |
| `auth_zone_transfer` | Transfer auth zone | ❌ | ❌ |
| `rpz_enable` | Enable RPZ zone | ❌ | ❌ |
| `rpz_disable` | Disable RPZ zone | ❌ | ❌ |

---

## smtpctl (Mail)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `discover` | Discover local user | ✅ | ❌ |
| `encrypt` | Encrypt password | ❌ | ❌ |
| `log brief` | Disable verbose logging | ❌ | ❌ |
| `log verbose` | Enable verbose logging | ❌ | ❌ |
| `monitor` | Monitor mail activity | ✅ | ❌ |
| `pause envelope` | Pause envelope | ❌ | ❌ |
| `pause mda` | Pause local delivery | ❌ | ❌ |
| `pause mta` | Pause remote delivery | ❌ | ❌ |
| `pause smtp` | Pause incoming | ✅ | ✅ |
| `profile` | Profile subsystem | ❌ | ❌ |
| `remove` | Remove from queue | ✅ | ✅ |
| `resume envelope` | Resume envelope | ❌ | ❌ |
| `resume mda` | Resume local delivery | ❌ | ❌ |
| `resume mta` | Resume remote delivery | ❌ | ❌ |
| `resume smtp` | Resume incoming | ✅ | ✅ |
| `schedule` | Schedule delivery | ✅ | ✅ |
| `show envelope` | Show envelope details | ❌ | ❌ |
| `show hosts` | Show remote MX hosts | ❌ | ❌ |
| `show hoststats` | Show host statistics | ❌ | ❌ |
| `show message` | Show message content | ❌ | ❌ |
| `show queue` | Show mail queue | ✅ | ✅ |
| `show relays` | Show relay sessions | ❌ | ❌ |
| `show routes` | Show mail routes | ❌ | ❌ |
| `show stats` | Show statistics | ✅ | ✅ |
| `show status` | Show daemon status | ❌ | ❌ |
| `spf walk` | SPF record lookup | ❌ | ❌ |
| `stop` | Stop daemon | ✅ | ✅ |
| `trace` | Enable tracing | ❌ | ❌ |
| `untrace` | Disable tracing | ❌ | ❌ |
| `update table` | Update table | ❌ | ❌ |

---

## relayctl (Load Balancer)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `host disable` | Disable host | ✅ | ✅ |
| `host enable` | Enable host | ✅ | ✅ |
| `load` | Load config file | ❌ | ❌ |
| `log brief` | Disable verbose logging | ❌ | ❌ |
| `log verbose` | Enable verbose logging | ❌ | ❌ |
| `monitor` | Monitor health checks | ✅ | ❌ |
| `poll` | Immediate health check | ✅ | ✅ |
| `redirect disable` | Disable redirect | ✅ | ✅ |
| `redirect enable` | Enable redirect | ✅ | ✅ |
| `reload` | Reload configuration | ✅ | ✅ |
| `show hosts` | Show host status | ✅ | ✅ |
| `show redirects` | Show redirections | ✅ (via show relay) | ✅ |
| `show relays` | Show relay status | ✅ (via show relay) | ✅ |
| `show routers` | Show routers | ❌ | ❌ |
| `show sessions` | Show active sessions | ✅ | ✅ |
| `show summary` | Show summary | ✅ | ✅ |
| `stop` | Stop daemon | ❌ | ❌ |
| `table disable` | Disable table | ✅ | ✅ |
| `table enable` | Enable table | ✅ | ✅ |

---

## ikectl (IKEv2 VPN)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `active` | Set active mode | ✅ | ✅ |
| `passive` | Set passive mode | ✅ | ✅ |
| `couple` | Load SAs into kernel | ✅ | ✅ |
| `decouple` | Unload SAs from kernel | ✅ | ✅ |
| `load` | Load config file | ❌ | ❌ |
| `log brief` | Disable verbose logging | ✅ | ❌ |
| `log verbose` | Enable verbose logging | ✅ | ❌ |
| `monitor` | Monitor IKE events | ✅ | ❌ |
| `reload` | Reload configuration | ✅ | ✅ |
| `reset all` | Reset all state | ❌ | ❌ |
| `reset ca` | Reset CA state | ❌ | ❌ |
| `reset id` | Reset specific SA | ❌ | ❌ |
| `reset policy` | Reset policies | ❌ | ❌ |
| `reset sa` | Flush running SAs | ✅ | ✅ |
| `reset user` | Reset user database | ❌ | ❌ |
| `show sa` | Show security associations | ✅ | ✅ |
| `ca create` | Create CA | ❌ | ❌ |
| `ca delete` | Delete CA | ❌ | ❌ |
| `ca export` | Export CA | ❌ | ❌ |
| `ca import` | Import CA | ❌ | ❌ |
| `ca install` | Install CA certificate | ❌ | ❌ |
| `ca certificate create` | Create certificate | ❌ | ❌ |
| `ca certificate delete` | Delete certificate | ❌ | ❌ |
| `ca certificate export` | Export certificate | ❌ | ❌ |
| `ca certificate install` | Install certificate | ❌ | ❌ |
| `ca certificate revoke` | Revoke certificate | ❌ | ❌ |
| `show ca certificates` | Show CA certificates | ❌ | ❌ |

---

## ntpctl (NTP)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `-s all` | Show all status | ✅ | ✅ |
| `-s peers` | Show NTP peers | ✅ | ✅ |
| `-s sensors` | Show time sensors | ✅ | ✅ |
| `-s status` | Show sync status | ✅ | ✅ |

---

## snmpctl/snmp (SNMP)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `snmp get` | Get SNMP value | ✅ | ❌ |
| `snmp getnext` | Get next SNMP value | ❌ | ❌ |
| `snmp walk` | Walk SNMP tree | ✅ | ❌ |
| `snmp bulkget` | Bulk get values | ❌ | ❌ |
| `snmp bulkwalk` | Bulk walk tree | ❌ | ❌ |
| `snmp df` | Show disk usage | ❌ | ❌ |
| `snmp mibtree` | Show MIB tree | ❌ | ❌ |
| `snmp trap` | Send trap | ✅ | ❌ |
| `log brief` | Disable verbose logging | ❌ | ❌ |
| `log verbose` | Enable verbose logging | ❌ | ❌ |

---

## acme-client (Certificates)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| `(domain)` | Renew certificate | ✅ | ✅ |
| `-F` | Force renewal | ✅ | ✅ |
| `-f` | Alternate config file | ❌ | ❌ |
| `-n` | Check config (no-op) | ✅ | ❌ |
| `-r` | Revoke certificate | ✅ | ✅ |
| `-v` | Verbose output | ✅ | ✅ |
| `-vv` | Very verbose output | ❌ | ❌ |

---

## pflogd/tcpdump (PF Logging)

| Command | Description | NSWall CLI | NSWall API |
|---------|-------------|------------|------------|
| Read log file | Read /var/log/pflog | ✅ | ✅ |
| Live monitoring | Monitor pflog0 | ✅ | ❌ |
| Filter expression | Filter by rule/port/etc | ✅ | ❌ |
| `-c count` | Limit output lines | ✅ | ✅ |
| `-e` | Print link-level header | ✅ | ✅ |
| `-n` | Don't resolve names | ✅ | ✅ |
| `-i interface` | Specify pflog interface | ❌ | ❌ |
| `-s snaplen` | Set snaplen | ❌ | ❌ |
| Filter: `ip` | IPv4 only | ❌ | ❌ |
| Filter: `ip6` | IPv6 only | ❌ | ❌ |
| Filter: `ifname` | By interface | ❌ | ❌ |
| Filter: `rulenum` | By rule number | ❌ | ❌ |
| Filter: `reason` | By reason | ❌ | ❌ |
| Filter: `action` | By action (pass/block) | ❌ | ❌ |

---

## Summary Statistics

| Category | Total Commands | CLI Implemented | API Implemented |
|----------|----------------|-----------------|-----------------|
| ospfctl | 22 | 13 (59%) | 10 (45%) |
| bgpctl | 28 | 17 (61%) | 12 (43%) |
| ripctl | 9 | 9 (100%) | 8 (89%) |
| ldpctl | 13 | 12 (92%) | 10 (77%) |
| eigrpctl | 10 | 10 (100%) | 8 (80%) |
| unbound-control | 28 | 12 (43%) | 6 (21%) |
| smtpctl | 30 | 11 (37%) | 8 (27%) |
| relayctl | 18 | 14 (78%) | 12 (67%) |
| ikectl | 27 | 13 (48%) | 8 (30%) |
| ntpctl | 4 | 4 (100%) | 4 (100%) |
| snmpctl | 10 | 3 (30%) | 0 (0%) |
| acme-client | 7 | 5 (71%) | 4 (57%) |
| pflogd | 14 | 5 (36%) | 3 (21%) |

**Overall Coverage:**
- CLI: ~62% of commands implemented
- API: ~45% of commands implemented

---

## Priority Missing Features

### High Priority (Common Operations)
1. **bgpctl:** `show tables`, `neighbor destroy`
2. **ospfctl:** `show database` filters
3. **smtpctl:** `show envelope`, `show message`, `pause/resume mda/mta`
4. **ikectl:** CA management commands
5. **unbound-control:** Zone management commands

### Medium Priority (Advanced Features)
1. **bgpctl:** Flowspec support
2. **ospfctl:** `show fib` filters
3. **relayctl:** `show routers`
4. **smtpctl:** `trace/untrace`, `spf walk`

### Low Priority (Rarely Used)
1. **unbound-control:** RPZ management
2. **bgpctl:** MRT import
3. **snmpctl:** Full MIB tree operations

---

*Generated: December 2024*
*OpenBSD Version: 7.6*
