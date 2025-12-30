# NSWall Testing Transparency

This document provides complete transparency into our testing methodology, what we measure, and how you can contribute test scenarios based on your real-world use cases.

## Quick Links

- [Live Test Results Dashboard](./metrics/index.html)
- [Complete Test Library](./metrics/library.html)
- [Upgrade Impact Calculator](./metrics/upgrade.html)
- [Request a New Test](#requesting-new-tests)

---

## Why We Test

Commercial firewalls often have undocumented limits that customers discover in production:
- "Maximum 10,000 NAT rules" buried in release notes
- Slow config parsing at scale causing outages during failover
- Memory exhaustion with large routing tables

**We test these limits proactively and publish the results openly.**

---

## Test Categories

### 1. Control Plane Performance

Tests how fast the firewall can parse, load, and apply configurations.

| Test ID | What We Measure | Why It Matters |
|---------|-----------------|----------------|
| `pf.filter.parse.100` | Time to parse 100 filter rules | Baseline parsing speed |
| `pf.filter.parse.1000` | Time to parse 1,000 filter rules | Medium rulesets |
| `pf.filter.parse.5000` | Time to parse 5,000 filter rules | Large enterprise rulesets |
| `pf.filter.parse.10000` | Time to parse 10,000 filter rules | Maximum practical scale |
| `pf.filter.load.1000` | Time to load 1,000 rules into kernel | Actual activation time |
| `pf.filter.load.5000` | Time to load 5,000 rules into kernel | Failover timing impact |
| `pf.nat.parse.1000` | Time to parse 1,000 NAT rules | NAT-heavy deployments |
| `pf.nat.parse.5000` | Time to parse 5,000 NAT rules | Carrier-grade NAT |

**Real-world impact:** If parsing 10,000 rules takes 5 seconds, your HA failover will have a 5+ second gap.

### 2. Table Operations

Tests address table performance for allowlists, blocklists, and dynamic updates.

| Test ID | What We Measure | Why It Matters |
|---------|-----------------|----------------|
| `pf.table.load.10000` | Load 10K addresses into table | Small blocklist |
| `pf.table.load.100000` | Load 100K addresses into table | Medium threat feed |
| `pf.table.load.500000` | Load 500K addresses into table | Large threat intelligence |
| `pf.table.lookup.100000` | Lookup speed in 100K entry table | Per-packet overhead |
| `pf.table.update.1000` | Add/remove 1K entries dynamically | Real-time blocklist updates |

**Real-world impact:** Slow table updates mean delayed threat response.

### 3. Routing Performance

Tests BGP and OSPF route handling at scale.

| Test ID | What We Measure | Why It Matters |
|---------|-----------------|----------------|
| `bgp.routes.parse.1000` | Parse 1K BGP routes | Small peering |
| `bgp.routes.parse.10000` | Parse 10K BGP routes | Medium IX presence |
| `bgp.routes.parse.100000` | Parse 100K BGP routes | Full table peering |
| `bgp.convergence.1000` | Time to converge 1K routes | Failover speed |
| `ospf.routes.parse.1000` | Parse 1K OSPF routes | Campus network |
| `ospf.routes.parse.10000` | Parse 10K OSPF routes | Large enterprise |
| `ospf.convergence.1000` | OSPF convergence time | Internal failover |

**Real-world impact:** BGP convergence time directly affects customer-facing outage duration.

### 4. IPsec/VPN Performance

Tests tunnel establishment and crypto performance.

| Test ID | What We Measure | Why It Matters |
|---------|-----------------|----------------|
| `ipsec.iked.parse.100` | Parse 100 tunnel configs | Small remote office setup |
| `ipsec.iked.parse.1000` | Parse 1,000 tunnel configs | Hub-and-spoke VPN |
| `ipsec.iked.parse.2000` | Parse 2,000 tunnel configs | Large SD-WAN deployment |
| `ipsec.establish.100` | Establish 100 tunnels | Baseline establishment |
| `ipsec.establish.1000` | Establish 1,000 tunnels | Mass reconnect scenario |
| `ipsec.rekey.100` | Rekey 100 active tunnels | Routine maintenance |
| `ipsec.capacity.max` | Maximum sustainable tunnels | Hard limit discovery |

**Real-world impact:** After a concentrator reboot, how long until all sites reconnect?

### 5. State Table Performance

Tests connection tracking capacity.

| Test ID | What We Measure | Why It Matters |
|---------|-----------------|----------------|
| `pf.states.create.10000` | Create 10K states | Normal operation |
| `pf.states.create.100000` | Create 100K states | Busy proxy/NAT |
| `pf.states.create.1000000` | Create 1M states | High-traffic edge |
| `pf.states.lookup.1000000` | Lookup in 1M state table | Per-packet overhead |
| `pf.states.expire.100000` | Expire 100K states | Cleanup performance |

**Real-world impact:** State table exhaustion causes new connections to fail silently.

### 6. Fleet Management (NATS Controller)

Tests central management scalability.

| Test ID | What We Measure | Why It Matters |
|---------|-----------------|----------------|
| `nats.connect.100` | Connect 100 agents | Small deployment |
| `nats.connect.500` | Connect 500 agents | Medium fleet |
| `nats.connect.1000` | Connect 1,000 agents | Large enterprise |
| `nats.connect.5000` | Connect 5,000 agents | Service provider |
| `nats.throughput.1000` | Messages/sec with 1K agents | Status update capacity |
| `nats.latency.p99` | P99 command latency | Interactive response |
| `nats.capacity.max` | Maximum sustainable agents | Hard limit |

**Real-world impact:** Controller overload means you can't push emergency config updates.

---

## How Tests Are Run

### Automated CI Pipeline

Every commit triggers:
1. **Quick tests** - Subset of scales, ~5 minutes
2. **Full tests** - All scales, ~20 minutes (main branch only)

### Nightly Stress Tests

Daily at 2 AM UTC:
1. All performance tests at all scales
2. Capacity limit discovery
3. Endurance tests (1-hour sustained load)

### Release Qualification

Before each release:
1. Full test suite on target hardware
2. Comparison against baseline
3. Regression analysis with 5% warn / 15% fail thresholds

---

## Understanding Results

### Metrics Dashboard

Visit [the dashboard](./metrics/index.html) to see:
- Current performance for all tests
- Historical trends
- Regression alerts
- Comparison between releases

### Reading the Charts

```
  Value
    │
 ▲  │      ╱╲
    │     ╱  ╲    ← Regression (investigate)
    │────╱────╲───── Warning threshold (+5%)
    │   ╱      ╲
    │──╱────────╲─── Baseline
    │ ╱
    │╱
    └──────────────── Time
```

- **Green zone**: Within 5% of baseline
- **Yellow zone**: 5-15% regression (warning)
- **Red zone**: >15% regression (failure)

---

## Requesting New Tests

We want tests that reflect **your real-world scenarios**.

### Via GitHub (Preferred)

1. Go to [Request New Test](./metrics/library.html)
2. Click "Request New Test"
3. Fill in:
   - **Scenario**: What are you trying to do?
   - **Scale**: How many rules/routes/tunnels/etc?
   - **Environment**: Hardware specs if relevant
   - **Success criteria**: What "good" looks like

### Via Issue Template

Open an issue with:

```markdown
### Test Request

**Category:** [PF / BGP / OSPF / IPsec / NATS / Other]

**Scenario:**
Describe your use case. Example: "We have 50 remote offices each
with 20 local subnets, all connecting to 2 hub firewalls."

**Scale:**
- 100 IPsec tunnels
- 1,000 routes per tunnel
- 5,000 PF rules for inter-site policy

**What should we measure:**
- [ ] Config parse time
- [ ] Config load time
- [ ] Failover time
- [ ] Memory usage
- [ ] CPU usage
- [ ] Throughput
- [ ] Latency

**Expected baseline:**
"Currently takes 30 seconds, would like under 10"

**Hardware context:**
4-core, 8GB RAM, SSD storage
```

### What Makes a Good Test Request

| Good | Less Useful |
|------|-------------|
| "100 sites × 50 routes each" | "Lots of routes" |
| "Failover must complete in <5s" | "Should be fast" |
| "16GB RAM, NVMe storage" | "Normal server" |
| "Parse + load + verify sequence" | "Make it work" |

---

## Contributing Tests

Want to add tests yourself? See [CONTRIBUTING.md](../CONTRIBUTING.md).

### Test File Locations

```
tests/
├── stress/
│   ├── control-plane-stress.sh    # PF/routing tests
│   ├── nats-controller-stress.go  # Fleet management tests
│   ├── capacity-probe.go          # Quick capacity check
│   └── run-stress-tests.sh        # Test runner
├── ipsec/
│   ├── ipsec-tunnel-setup.sh      # Tunnel generation
│   └── ipsec-stress-test.sh       # IPsec scaling tests
└── metrics/
    ├── schema.sql                 # Test definitions
    ├── collector.go               # Results recording
    └── generate-site.go           # Dashboard generation
```

### Adding a New Test

1. Add test definition to `tests/metrics/schema.sql`:
```sql
INSERT INTO test_library (test_id, category, name, test_type, scale, unit, description)
VALUES ('pf.yourtest.1000', 'pf', 'Your Test Name', 'parse', 1000, 'ms', 'Description');
```

2. Add test logic to appropriate stress script

3. Record metrics using the collector:
```bash
./collector record -test "pf.yourtest.1000" -value 123.45 -unit ms
```

4. Submit PR with your changes

---

## Test Infrastructure

### Hardware Profiles

Tests run on multiple hardware profiles to show scaling:

| Profile | CPU | RAM | Storage | Use Case |
|---------|-----|-----|---------|----------|
| Small | 2 cores | 4 GB | HDD | Branch office |
| Medium | 4 cores | 8 GB | SSD | Small datacenter |
| Large | 8 cores | 16 GB | NVMe | Enterprise edge |
| XL | 16 cores | 32 GB | NVMe | Service provider |

### Test Environment

- **OS**: OpenBSD (latest stable)
- **Isolation**: Dedicated test VMs, no production traffic
- **Repeatability**: 3 runs per test, median reported
- **Baseline**: Main branch @ last release

---

## FAQ

### Why aren't you testing X?

Probably because no one asked yet! [Request it](#requesting-new-tests).

### Can I run these tests myself?

Yes! All test scripts are in the `tests/` directory:

```bash
# Control plane tests
./tests/stress/control-plane-stress.sh quick

# NATS capacity probe
go run tests/stress/capacity-probe.go -url nats://localhost:4222

# IPsec scaling
./tests/ipsec/ipsec-stress-test.sh scaling
```

### How do I interpret a regression?

1. Check if the change is real (not noise)
2. Look at the commit that introduced it
3. Decide if the tradeoff is acceptable
4. Document the decision

### What's the SLA for test requests?

- **Triage**: Within 1 week
- **Simple tests**: 2-4 weeks
- **Complex scenarios**: Discuss timeline in issue

---

## Contact

- **Issues**: [GitHub Issues](https://github.com/northshorenetworks/NSWall/issues)
- **Discussions**: [GitHub Discussions](https://github.com/northshorenetworks/NSWall/discussions)

---

*Last updated: 2024*
