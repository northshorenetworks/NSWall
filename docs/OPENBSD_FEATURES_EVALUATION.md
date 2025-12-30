# OpenBSD Features Evaluation for NSWall

This document evaluates OpenBSD base system features that could be incorporated into NSWall to enhance its capabilities as a network appliance framework.

## Executive Summary

NSWall already leverages many core OpenBSD features (PF, CARP, BGP, OSPF, etc.). This evaluation identifies additional OpenBSD components that could significantly enhance NSWall's functionality while maintaining its lightweight, embedded-friendly design.

---

## Current OpenBSD Integration Status

### Already Implemented
| Feature | Status | Notes |
|---------|--------|-------|
| PF (Packet Filter) | ✅ Full | Dynamic rule management via nssh |
| CARP | ✅ Full | HA with VHID, ADVBASE, ADVSKEW |
| pfsync | ✅ Full | State synchronization |
| BGP (bgpd) | ✅ Full | Full routing support |
| OSPF (ospfd) | ✅ Full | Link-state routing |
| RIP (ripd) | ✅ Partial | Distance-vector routing |
| DVMRP | ✅ Partial | Multicast routing |
| IPSec | ✅ Full | VPN support |
| DHCP Server/Relay | ✅ Full | dhcpd/dhcrelay integration |
| NTP | ✅ Partial | ntpd support |
| SNMP | ✅ Partial | snmpd support |

---

## Recommended Feature Additions

### Priority 1: High Impact, Low Complexity

#### 1. httpd (OpenBSD HTTP Server)
**Purpose:** Native web server for management UI and REST API

**Benefits:**
- Replaces external web server dependencies
- Native OpenBSD security (privilege separation, chroot)
- FastCGI support for dynamic content
- TLS/SSL built-in

**Implementation:**
```c
// Add to externs.h
#define HTTPDCONF_TEMP  "/var/run/httpd.conf"
#define HTTPDCTL        "/usr/sbin/httpd"

// Add to ctl.c - control structure
struct ctl ctl_httpd[] = {
    { "enable",   "Enable HTTP server",    { HTTPDCTL, "-f", HTTPDCONF_TEMP, NULL } },
    { "disable",  "Disable HTTP server",   { PKILL, "httpd", NULL } },
    { "reload",   "Reload configuration",  { HTTPDCTL, "-n", HTTPDCONF_TEMP, NULL } },
    { 0, 0, { 0 } }
};
```

**CLI Integration:**
```
nswall(config)# http server enable
nswall(config)# http server listen 443 tls
nswall(config)# http server root /var/www/htdocs
```

**Effort:** Medium
**Priority:** High

---

#### 2. relayd (Load Balancer/Relay Daemon)
**Purpose:** Application-level load balancing and SSL termination

**Current Status:** Partially implemented (relayctl commands exist)

**Enhancements Needed:**
- Full configuration syntax support
- Health check management
- Backend pool management

**Benefits:**
- Layer 7 load balancing
- SSL/TLS offloading
- HTTP header manipulation
- Health monitoring

**Implementation:**
```c
// Enhanced relay commands in commands.h
struct prot1 rlcs_enhanced[] = {
    { "hosts",      "Show host status",     { RELAYCTL, "show", "hosts", NULL } },
    { "redirects",  "Show redirects",       { RELAYCTL, "show", "redirects", NULL } },
    { "relays",     "Show relay status",    { RELAYCTL, "show", "relays", NULL } },
    { "sessions",   "Show active sessions", { RELAYCTL, "show", "sessions", NULL } },
    { "summary",    "Show summary",         { RELAYCTL, "show", "summary", NULL } },
    { "routers",    "Show routers",         { RELAYCTL, "show", "routers", NULL } },
    { 0, 0, { 0 } }
};
```

**CLI Integration:**
```
nswall(config)# relay mypool
nswall(config-relay)# backend 10.0.0.1 port 80 weight 5
nswall(config-relay)# backend 10.0.0.2 port 80 weight 3
nswall(config-relay)# health check http "/" expect "OK"
nswall(config-relay)# listen 0.0.0.0 port 80
```

**Effort:** Medium
**Priority:** High

---

#### 3. unbound (DNS Resolver)
**Purpose:** Validating, recursive, caching DNS resolver with DNSSEC

**Benefits:**
- DNSSEC validation
- DNS-over-TLS support
- Local DNS caching
- Split-horizon DNS
- DNS blackholing (ad-blocking, malware protection)

**Implementation:**
```c
// Add to externs.h
#define UNBOUNDCONF_TEMP "/var/run/unbound.conf"
#define UNBOUNDCTL      "/usr/sbin/unbound-control"

// Add control structure
struct ctl ctl_unbound[] = {
    { "enable",     "Enable DNS resolver",  { "/usr/sbin/unbound", "-c", UNBOUNDCONF_TEMP, NULL } },
    { "disable",    "Disable DNS resolver", { PKILL, "unbound", NULL } },
    { "stats",      "Show statistics",      { UNBOUNDCTL, "stats_noreset", NULL } },
    { "flush",      "Flush cache",          { UNBOUNDCTL, "flush_zone", ".", NULL } },
    { "reload",     "Reload configuration", { UNBOUNDCTL, "reload", NULL } },
    { 0, 0, { 0 } }
};
```

**CLI Integration:**
```
nswall(config)# dns resolver enable
nswall(config)# dns forwarder 8.8.8.8
nswall(config)# dns forwarder 1.1.1.1
nswall(config)# dns local-zone "internal.local" static
nswall(config)# dns dnssec enable
nswall(config)# dns cache-size 50m
```

**Effort:** Medium
**Priority:** High

---

### Priority 2: Medium Impact, Medium Complexity

#### 4. rad (Router Advertisement Daemon)
**Purpose:** IPv6 Router Advertisement for SLAAC

**Benefits:**
- Native IPv6 SLAAC support
- Prefix advertisement
- Default router advertisement
- DNS server advertisement (RDNSS)

**Implementation:**
```c
// Add to externs.h
#define RADCONF_TEMP    "/var/run/rad.conf"
#define RAD             "/usr/sbin/rad"

// Add to commands structure
struct ctl ctl_rad[] = {
    { "enable",   "Enable router advertisements", { RAD, "-f", RADCONF_TEMP, NULL } },
    { "disable",  "Disable router advertisements", { PKILL, "rad", NULL } },
    { 0, 0, { 0 } }
};
```

**CLI Integration:**
```
nswall(config)# interface em0 ipv6
nswall(config-if)# ipv6 router-advertisement enable
nswall(config-if)# ipv6 ra prefix 2001:db8::/64
nswall(config-if)# ipv6 ra rdnss 2001:4860:4860::8888
nswall(config-if)# ipv6 ra default-lifetime 1800
```

**Effort:** Medium
**Priority:** Medium

---

#### 5. smtpd (OpenSMTPD)
**Purpose:** Mail transfer for system alerts and notifications

**Benefits:**
- Lightweight MTA for alerts
- Local mail delivery
- Relay to external SMTP servers
- TLS support

**Implementation:**
```c
// Add to externs.h
#define SMTPDCONF_TEMP  "/var/run/smtpd.conf"
#define SMTPCTL         "/usr/sbin/smtpctl"

struct ctl ctl_smtpd[] = {
    { "enable",   "Enable mail service",    { "/usr/sbin/smtpd", NULL } },
    { "disable",  "Disable mail service",   { SMTPCTL, "stop", NULL } },
    { "pause",    "Pause mail delivery",    { SMTPCTL, "pause", "smtp", NULL } },
    { "resume",   "Resume mail delivery",   { SMTPCTL, "resume", "smtp", NULL } },
    { "show",     "Show queue",             { SMTPCTL, "show", "queue", NULL } },
    { 0, 0, { 0 } }
};
```

**CLI Integration:**
```
nswall(config)# mail relay smtp.example.com port 587
nswall(config)# mail tls require
nswall(config)# mail auth user@example.com password
nswall(config)# mail alert syslog critical admin@example.com
```

**Effort:** Medium
**Priority:** Medium

---

#### 6. vmd/vmctl (Virtual Machine Daemon)
**Purpose:** Native virtualization for network function isolation

**Benefits:**
- Run isolated network functions
- Service separation
- Testing environment
- Container-like isolation

**Implementation:**
```c
// Add to externs.h
#define VMDCONF_TEMP    "/var/run/vm.conf"
#define VMCTL           "/usr/sbin/vmctl"

struct ctl ctl_vmd[] = {
    { "list",     "List virtual machines",  { VMCTL, "status", NULL } },
    { "start",    "Start a VM",             { VMCTL, "start", REQ, NULL } },
    { "stop",     "Stop a VM",              { VMCTL, "stop", REQ, NULL } },
    { "console",  "Connect to VM console",  { VMCTL, "console", REQ, NULL } },
    { 0, 0, { 0 } }
};
```

**CLI Integration:**
```
nswall(config)# vm firewall-test
nswall(config-vm)# memory 512M
nswall(config-vm)# disk /var/vm/firewall-test.qcow2
nswall(config-vm)# interface switch uplink
nswall(config-vm)# boot
```

**Effort:** High
**Priority:** Medium (for specific use cases)

---

### Priority 3: Enhanced Monitoring & Security

#### 7. syslogd Enhancements
**Purpose:** Enhanced logging with remote syslog, TLS, and filtering

**Current:** Basic syslogd
**Enhancement:** Remote logging, TLS encryption, structured logging

**Implementation:**
```c
// Add to CLI
nswall(config)# logging host 10.0.0.100 port 514
nswall(config)# logging host 10.0.0.100 port 6514 tls
nswall(config)# logging facility local0 level info
nswall(config)# logging buffer 10000
```

**Effort:** Low
**Priority:** High

---

#### 8. npppd (PPP Daemon)
**Purpose:** PPPoE and VPN dial-in support

**Benefits:**
- DSL/PPPoE connections
- L2TP VPN server
- PPTP (legacy) support

**CLI Integration:**
```
nswall(config)# interface pppoe0
nswall(config-if)# pppoe service-name "ISP"
nswall(config-if)# pppoe interface em0
nswall(config-if)# pppoe username user@isp.com
nswall(config-if)# pppoe password secret
```

**Effort:** Medium
**Priority:** Medium

---

#### 9. iked (IKEv2 Daemon)
**Purpose:** Modern IKEv2 VPN support (in addition to existing ipsec)

**Benefits:**
- IKEv2 protocol (more modern than IKEv1)
- MOBIKE support for mobile clients
- EAP authentication
- Road warrior configuration

**Implementation:**
```c
// Add to externs.h
#define IKEDCONF_TEMP   "/var/run/iked.conf"
#define IKECTL          "/usr/sbin/ikectl"

struct ctl ctl_iked[] = {
    { "enable",   "Enable IKEv2",           { "/sbin/iked", NULL } },
    { "disable",  "Disable IKEv2",          { PKILL, "iked", NULL } },
    { "show",     "Show SA database",       { IKECTL, "show", "sa", NULL } },
    { "reload",   "Reload configuration",   { IKECTL, "reload", NULL } },
    { 0, 0, { 0 } }
};
```

**CLI Integration:**
```
nswall(config)# crypto ikev2 profile roadwarrior
nswall(config-ikev2)# authentication eap "mschap-v2"
nswall(config-ikev2)# local-address 0.0.0.0
nswall(config-ikev2)# peer any
nswall(config-ikev2)# child-sa esp aes-256-gcm
```

**Effort:** Medium
**Priority:** High (VPN is critical for network appliances)

---

#### 10. acme-client (Let's Encrypt)
**Purpose:** Automatic TLS certificate management

**Benefits:**
- Automated certificate renewal
- Let's Encrypt integration
- No manual certificate management

**Implementation:**
```c
// Add to externs.h
#define ACMECONF_TEMP   "/var/run/acme-client.conf"
#define ACMECLIENT      "/usr/sbin/acme-client"
```

**CLI Integration:**
```
nswall(config)# certificate domain firewall.example.com
nswall(config)# certificate provider letsencrypt
nswall(config)# certificate auto-renew
nswall(config)# certificate challenge http
```

**Effort:** Low
**Priority:** Medium

---

### Priority 4: Network Visibility & Diagnostics

#### 11. pflogd (PF Logging Daemon)
**Purpose:** Enhanced packet filter logging

**Current Status:** PF exists, but pflogd integration could be enhanced

**Enhancements:**
- Log rotation
- Log file management
- Real-time log viewing

**CLI Integration:**
```
nswall# show pf log
nswall# show pf log interface em0
nswall# show pf log action block
nswall(config)# pf log interface pflog0
nswall(config)# pf log snaplen 160
```

**Effort:** Low
**Priority:** Medium

---

#### 12. hoststatd/carp Enhancement
**Purpose:** Host monitoring and failover triggers

**Benefits:**
- Monitor external hosts
- Trigger CARP failover on host failure
- Health-check based HA

**CLI Integration:**
```
nswall(config)# carp group web-servers
nswall(config-carp)# monitor 10.0.0.10 port 80
nswall(config-carp)# monitor 10.0.0.11 port 80
nswall(config-carp)# demote-threshold 2
```

**Effort:** Medium
**Priority:** Medium

---

## Implementation Roadmap

### Phase 1: Core Network Services (Recommended First)
1. **unbound** - DNS resolver with DNSSEC
2. **httpd** - Management web interface
3. **iked** - Modern IKEv2 VPN
4. **Enhanced syslogd** - Remote logging

### Phase 2: Advanced Networking
5. **rad** - IPv6 router advertisements
6. **relayd enhancements** - Load balancing
7. **smtpd** - Alert notifications

### Phase 3: Extended Features
8. **acme-client** - Certificate automation
9. **pflogd enhancements** - Logging
10. **vmd** - Virtualization (optional)

---

## Code Architecture Recommendations

### New Directory Structure
```
nssh/
├── dns/              # unbound integration
│   ├── dns.c
│   ├── dns.h
│   └── unbound.subr
├── http/             # httpd integration
│   ├── http.c
│   └── httpd.subr
├── vpn/              # Enhanced VPN
│   ├── iked.c
│   ├── ipsec.c       # existing
│   └── vpn.subr
├── ipv6/             # IPv6 features
│   ├── rad.c
│   └── ipv6.subr
└── lb/               # Load balancing
    ├── relay.c       # enhanced
    └── relay.subr
```

### Configuration File Conventions
All new daemons should follow the existing pattern:
- Temp config: `/var/run/<daemon>.conf`
- Control via standard `struct ctl` mechanism
- Integration with `show` command hierarchy

---

## Testing Requirements

Each new feature should include:
1. Unit tests for configuration parsing
2. Integration tests on OpenBSD VMs
3. QEMU harness tests in `test/` directory
4. CI workflow validation

---

## Conclusion

The recommended priority order for implementation:
1. **unbound** - Immediate security and DNS functionality
2. **httpd** - Management interface foundation
3. **iked** - Modern VPN (critical for appliance use)
4. **rad** - IPv6 readiness
5. **relayd enhancements** - Enterprise features

This phased approach allows incremental enhancement while maintaining NSWall's lightweight, embedded-friendly design philosophy.
