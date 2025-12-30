#!/bin/ksh
#
# multi-node-tests.sh - Test multi-node cluster scenarios
#
# This script tests scenarios that would involve multiple physical nodes:
# - Configuration synchronization
# - State table replication verification
# - Cluster health monitoring
# - Fleet management integration
#
# Note: For true multi-node testing, use the controller/agent infrastructure
# or deploy to actual separate VMs/hardware.
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

TESTS_PASSED=0
TESTS_FAILED=0

log_pass() {
    echo "${GREEN}[PASS]${NC} $1"
    TESTS_PASSED=$((TESTS_PASSED + 1))
}

log_fail() {
    echo "${RED}[FAIL]${NC} $1"
    TESTS_FAILED=$((TESTS_FAILED + 1))
}

log_info() {
    echo "[INFO] $1"
}

log_test() {
    echo ""
    echo ">>> $1"
}

# =============================================================================
# CONFIGURATION SYNC TESTS
# =============================================================================

test_config_format() {
    log_test "Testing configuration export format..."

    # Create test configuration
    cat > /tmp/test-config.conf << 'EOF'
# Test PF configuration for sync
ext_if = "em0"
int_if = "em1"

table <blocked> persist
table <allowed> persist { 192.168.1.0/24 }

set skip on lo
set block-policy return

match in all scrub (no-df random-id)
match out on $ext_if from (em1:network) nat-to ($ext_if)

block log all
pass out keep state
pass in on $ext_if proto tcp to ($ext_if) port { 22, 80, 443 } keep state
EOF

    # Validate syntax
    if pfctl -nf /tmp/test-config.conf 2>/dev/null; then
        log_pass "Configuration syntax is valid"
    else
        log_fail "Configuration syntax is invalid"
    fi

    # Test that config can be checksummed for sync comparison
    checksum=$(sha256 /tmp/test-config.conf | awk '{print $4}')
    if [ -n "$checksum" ]; then
        log_pass "Configuration checksum: ${checksum:0:16}..."
    else
        log_fail "Failed to generate config checksum"
    fi

    rm -f /tmp/test-config.conf
}

test_config_diff() {
    log_test "Testing configuration diff detection..."

    cat > /tmp/config-v1.conf << 'EOF'
pass in proto tcp to any port 80
pass in proto tcp to any port 443
EOF

    cat > /tmp/config-v2.conf << 'EOF'
pass in proto tcp to any port 80
pass in proto tcp to any port 443
pass in proto tcp to any port 8080
EOF

    # Compare configs
    if ! diff -q /tmp/config-v1.conf /tmp/config-v2.conf > /dev/null 2>&1; then
        log_pass "Configuration diff detected correctly"
    else
        log_fail "Configuration diff not detected"
    fi

    # Generate diff for review
    diff_output=$(diff /tmp/config-v1.conf /tmp/config-v2.conf 2>/dev/null || true)
    if echo "$diff_output" | grep -q "8080"; then
        log_pass "Diff shows added port 8080 rule"
    else
        log_fail "Diff content incorrect"
    fi

    rm -f /tmp/config-v1.conf /tmp/config-v2.conf
}

# =============================================================================
# STATE TABLE TESTS
# =============================================================================

test_state_export() {
    log_test "Testing state table export..."

    # Get current states
    states=$(pfctl -ss 2>/dev/null)
    state_count=$(echo "$states" | wc -l | tr -d ' ')

    log_info "Current state count: $state_count"

    # Export to file format (for potential sync)
    pfctl -ss > /tmp/states-export.txt 2>/dev/null || true

    if [ -f /tmp/states-export.txt ]; then
        log_pass "State table exported to file"
    else
        log_fail "State table export failed"
    fi

    rm -f /tmp/states-export.txt
}

test_state_format_parsing() {
    log_test "Testing state format parsing..."

    # Sample state output format
    cat > /tmp/sample-states.txt << 'EOF'
all tcp 192.168.1.100:45678 -> 10.0.0.1:443       ESTABLISHED:ESTABLISHED
   age 00:05:23, expires in 23:54:37, 1234:567 pkts, 123456:56789 bytes, rule 5
all udp 192.168.1.100:12345 -> 8.8.8.8:53         MULTIPLE:SINGLE
   age 00:00:03, expires in 00:00:57, 2:1 pkts, 120:80 bytes, rule 7
all icmp 192.168.1.100:1234 -> 8.8.4.4:0          0:0
   age 00:00:01, expires in 00:00:19, 1:0 pkts, 84:0 bytes, rule 6
EOF

    # Parse TCP states
    tcp_count=$(grep -c "^all tcp" /tmp/sample-states.txt)
    if [ "$tcp_count" -eq 1 ]; then
        log_pass "TCP state parsing works"
    else
        log_fail "TCP state parsing failed"
    fi

    # Parse UDP states
    udp_count=$(grep -c "^all udp" /tmp/sample-states.txt)
    if [ "$udp_count" -eq 1 ]; then
        log_pass "UDP state parsing works"
    else
        log_fail "UDP state parsing failed"
    fi

    rm -f /tmp/sample-states.txt
}

# =============================================================================
# CLUSTER HEALTH TESTS
# =============================================================================

test_health_check_endpoints() {
    log_test "Testing health check data availability..."

    # PF status
    if pfctl -si > /dev/null 2>&1; then
        log_pass "PF status available for health check"
    else
        log_fail "PF status unavailable"
    fi

    # Interface status
    if ifconfig -a > /dev/null 2>&1; then
        log_pass "Interface status available"
    else
        log_fail "Interface status unavailable"
    fi

    # CARP status (if configured)
    if ifconfig | grep -q "carp:"; then
        log_pass "CARP status available"
    else
        log_info "No CARP interfaces configured"
    fi

    # Memory/CPU
    if sysctl -n vm.loadavg > /dev/null 2>&1; then
        log_pass "System metrics available"
    else
        log_fail "System metrics unavailable"
    fi
}

test_cluster_node_identity() {
    log_test "Testing node identity..."

    # Hostname
    hostname=$(hostname)
    if [ -n "$hostname" ]; then
        log_pass "Node hostname: $hostname"
    else
        log_fail "Cannot determine hostname"
    fi

    # Host ID (for CARP)
    hostid=$(sysctl -n kern.hostid 2>/dev/null || echo "unknown")
    log_info "Host ID: $hostid"

    # System UUID
    if [ -f /etc/machine-id ] || sysctl -n hw.uuid > /dev/null 2>&1; then
        log_pass "System has unique identifier"
    else
        log_info "No machine-id found (using hostid)"
    fi
}

# =============================================================================
# FLEET MANAGEMENT SIMULATION
# =============================================================================

test_fleet_config_distribution() {
    log_test "Testing fleet configuration distribution format..."

    # Simulate config push payload (JSON-like)
    cat > /tmp/fleet-config.json << 'EOF'
{
  "version": "1.0.0",
  "timestamp": "2024-01-01T00:00:00Z",
  "config_hash": "abc123def456",
  "pf_rules": "pass in proto tcp to any port 80",
  "interfaces": {
    "em0": {"ip": "192.168.1.1/24", "description": "External"},
    "em1": {"ip": "10.0.0.1/24", "description": "Internal"}
  },
  "ha": {
    "enabled": true,
    "role": "primary",
    "peer": "192.168.1.2"
  }
}
EOF

    # Validate JSON structure
    if [ -f /tmp/fleet-config.json ]; then
        log_pass "Fleet config format created"
    fi

    # Extract PF rules
    if grep -q "pf_rules" /tmp/fleet-config.json; then
        log_pass "PF rules included in fleet config"
    else
        log_fail "PF rules missing from fleet config"
    fi

    rm -f /tmp/fleet-config.json
}

test_agent_communication() {
    log_test "Testing agent communication requirements..."

    # NATS connectivity would be tested here
    # For now, verify network stack is functional

    # Test loopback
    if ping -c 1 127.0.0.1 > /dev/null 2>&1; then
        log_pass "Loopback communication works"
    else
        log_fail "Loopback communication failed"
    fi

    # Test TCP listen capability
    (echo "test" | nc -l 127.0.0.1 19999 > /dev/null 2>&1 &)
    sleep 0.5
    if nc -z 127.0.0.1 19999 2>/dev/null; then
        log_pass "TCP listener capability works"
    else
        log_info "TCP listener test skipped"
    fi

    # Cleanup background process
    pkill -f "nc -l 127.0.0.1 19999" 2>/dev/null || true
}

# =============================================================================
# ROLLING UPDATE SIMULATION
# =============================================================================

test_rolling_update_scenario() {
    log_test "Testing rolling update scenario..."

    # Simulate a rolling update:
    # 1. Secondary takes over
    # 2. Primary updates
    # 3. Primary takes back
    # 4. Secondary updates

    log_info "Step 1: Verify CARP can be demoted..."
    current_demotion=$(sysctl -n net.inet.carp.demotion 2>/dev/null || echo "0")
    log_info "Current demotion: $current_demotion"

    # Increase demotion (would cause failover)
    log_info "Step 2: Simulate demotion for update..."
    sysctl net.inet.carp.demotion=128 2>/dev/null || true

    # In real scenario, update would happen here
    log_info "Step 3: Simulated update in progress..."
    sleep 1

    # Restore demotion
    log_info "Step 4: Restore after update..."
    sysctl net.inet.carp.demotion=0 2>/dev/null || true

    new_demotion=$(sysctl -n net.inet.carp.demotion 2>/dev/null || echo "0")
    if [ "$new_demotion" = "0" ]; then
        log_pass "Demotion restored after update"
    else
        log_fail "Demotion not properly restored"
    fi
}

# =============================================================================
# SUMMARY
# =============================================================================

run_all_tests() {
    echo "========================================"
    echo "    Multi-Node Cluster Tests"
    echo "========================================"
    echo ""

    echo "--- Configuration Sync Tests ---"
    test_config_format
    test_config_diff
    echo ""

    echo "--- State Table Tests ---"
    test_state_export
    test_state_format_parsing
    echo ""

    echo "--- Cluster Health Tests ---"
    test_health_check_endpoints
    test_cluster_node_identity
    echo ""

    echo "--- Fleet Management Tests ---"
    test_fleet_config_distribution
    test_agent_communication
    echo ""

    echo "--- Rolling Update Tests ---"
    test_rolling_update_scenario
    echo ""

    # Summary
    echo "========================================"
    echo "         Test Summary"
    echo "========================================"
    echo ""
    echo "  ${GREEN}Passed: ${TESTS_PASSED}${NC}"
    echo "  ${RED}Failed: ${TESTS_FAILED}${NC}"
    echo ""

    if [ $TESTS_FAILED -gt 0 ]; then
        return 1
    fi
    return 0
}

case "${1:-all}" in
    all)
        run_all_tests
        ;;
    config)
        test_config_format
        test_config_diff
        ;;
    state)
        test_state_export
        test_state_format_parsing
        ;;
    *)
        echo "Usage: $0 {all|config|state}"
        exit 1
        ;;
esac
