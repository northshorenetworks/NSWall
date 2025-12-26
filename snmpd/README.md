# NSWall SNMP Agent

SNMP agent for monitoring NSWall firewall/router systems. Exposes system metrics, firewall status, and high availability information via SNMP v2c.

## Features

- System metrics (version, hostname, uptime, load, memory)
- PF firewall status (enabled, states, rules, byte/packet counters)
- CARP high availability monitoring
- pfsync state synchronization status
- Custom MIB for NSWall-specific OIDs

## Installation

```bash
# Build
make build

# Install
doas make install
```

## Usage

```bash
# Start with defaults (port 161, community "public")
nswall-snmpd

# Custom port and community
nswall-snmpd -port 1161 -community private
```

## Configuration

Add to `/etc/rc.conf.local`:

```sh
nswall_snmpd_flags="-community mysecret"
```

Enable and start:

```bash
doas rcctl enable nswall_snmpd
doas rcctl start nswall_snmpd
```

## MIB Structure

All objects are under enterprises.59999 (.1.3.6.1.4.1.59999):

### System (.1.3.6.1.4.1.59999.1)

| OID | Name | Type | Description |
|-----|------|------|-------------|
| .1.0 | sysVersion | String | OpenBSD version |
| .2.0 | sysHostname | String | System hostname |
| .3.0 | sysUptime | TimeTicks | System uptime |
| .4.0 | sysLoadAvg1 | String | 1-minute load average |
| .5.0 | sysLoadAvg5 | String | 5-minute load average |
| .6.0 | sysLoadAvg15 | String | 15-minute load average |
| .7.0 | sysMemTotal | Counter64 | Total physical memory |
| .8.0 | sysMemFree | Counter64 | Free memory |

### Firewall (.1.3.6.1.4.1.59999.3)

| OID | Name | Type | Description |
|-----|------|------|-------------|
| .1.0 | pfEnabled | Integer | PF enabled (1) or disabled (0) |
| .2.0 | pfStates | Gauge32 | Current state count |
| .3.0 | pfStateLimit | Gauge32 | Maximum states |
| .4.0 | pfSrcNodes | Gauge32 | Source node count |
| .5.0 | pfRules | Gauge32 | Active rule count |
| .6.0 | pfBytesIn | Counter64 | Bytes in |
| .7.0 | pfBytesOut | Counter64 | Bytes out |
| .8.0 | pfPacketsIn | Counter64 | Packets in |
| .9.0 | pfPacketsOut | Counter64 | Packets out |

### High Availability (.1.3.6.1.4.1.59999.5)

| OID | Name | Type | Description |
|-----|------|------|-------------|
| .1.0 | carpMaster | Integer | CARP interfaces in MASTER |
| .2.0 | carpBackup | Integer | CARP interfaces in BACKUP |
| .3.0 | pfsyncEnabled | Integer | pfsync enabled |

## Testing

Query OIDs using snmpget:

```bash
# Get hostname
snmpget -v2c -c public localhost .1.3.6.1.4.1.59999.1.2.0

# Get PF state count
snmpget -v2c -c public localhost .1.3.6.1.4.1.59999.3.2.0

# Walk all NSWall OIDs
snmpwalk -v2c -c public localhost .1.3.6.1.4.1.59999
```

## Monitoring Integration

### Nagios/Icinga

```bash
check_snmp -H nswall.example.com -C public -o .1.3.6.1.4.1.59999.3.2.0 -w 50000 -c 100000
```

### Zabbix Template

Import the MIB file and create items for the OIDs above.

### PRTG

Add SNMP Custom sensor with the NSWall OIDs.

## License

Same license as NSWall.
