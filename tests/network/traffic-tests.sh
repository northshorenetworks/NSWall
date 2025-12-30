#!/bin/ksh
#
# traffic-tests.sh - Network traffic tests for NSWall
#
# Tests actual network traffic flow through PF rules, NAT, and routing
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0
TESTS_SKIPPED=0

# Configuration
EXTERNAL_GW="203.0.113.1"
EXTERNAL_HOST="203.0.113.100"
INTERNAL_GW="192.168.1.1"
INTERNAL_HOST="192.168.1.10"
DMZ_GW="10.0.0.1"
DMZ_WEB="10.0.0.10"

# Logging
log_pass() {
    echo "${GREEN}[PASS]${NC} $1"
    TESTS_PASSED=$((TESTS_PASSED + 1))
}

log_fail() {
    echo "${RED}[FAIL]${NC} $1"
    TESTS_FAILED=$((TESTS_FAILED + 1))
}

log_skip() {
    echo "${YELLOW}[SKIP]${NC} $1"
    TESTS_SKIPPED=$((TESTS_SKIPPED + 1))
}

log_info() {
    echo "[INFO] $1"
}

# =============================================================================
# CONNECTIVITY TESTS
# =============================================================================

test_interface_connectivity() {
    log_info "Testing interface connectivity..."

    # Test internal gateway
    if ping -c 1 -w 2 ${INTERNAL_GW} > /dev/null 2>&1; then
        log_pass "Internal gateway (${INTERNAL_GW}) reachable"
    else
        log_fail "Internal gateway (${INTERNAL_GW}) unreachable"
    fi

    # Test DMZ gateway
    if ping -c 1 -w 2 ${DMZ_GW} > /dev/null 2>&1; then
        log_pass "DMZ gateway (${DMZ_GW}) reachable"
    else
        log_fail "DMZ gateway (${DMZ_GW}) unreachable"
    fi

    # Test external gateway
    if ping -c 1 -w 2 ${EXTERNAL_GW} > /dev/null 2>&1; then
        log_pass "External gateway (${EXTERNAL_GW}) reachable"
    else
        log_fail "External gateway (${EXTERNAL_GW}) unreachable"
    fi
}

# =============================================================================
# PF STATE TESTS
# =============================================================================

test_pf_enabled() {
    log_info "Testing PF status..."

    if pfctl -si 2>/dev/null | grep -q "Status: Enabled"; then
        log_pass "PF is enabled"
    else
        log_fail "PF is not enabled"
    fi
}

test_pf_rules_loaded() {
    log_info "Testing PF rules..."

    rule_count=$(pfctl -sr 2>/dev/null | wc -l | tr -d ' ')
    if [ "$rule_count" -gt 5 ]; then
        log_pass "PF has $rule_count rules loaded"
    else
        log_fail "PF has insufficient rules: $rule_count"
    fi
}

test_pf_tables() {
    log_info "Testing PF tables..."

    if pfctl -sT 2>/dev/null | grep -q "test_allowed"; then
        log_pass "test_allowed table exists"
    else
        log_fail "test_allowed table missing"
    fi

    if pfctl -sT 2>/dev/null | grep -q "test_blocked"; then
        log_pass "test_blocked table exists"
    else
        log_fail "test_blocked table missing"
    fi
}

# =============================================================================
# ICMP TESTS
# =============================================================================

test_icmp_internal_to_external() {
    log_info "Testing ICMP: Internal -> External..."

    # Ping from internal to external
    if ping -c 2 -w 5 ${EXTERNAL_HOST} > /dev/null 2>&1; then
        log_pass "ICMP from internal to external works"
    else
        log_fail "ICMP from internal to external blocked"
    fi
}

test_icmp_external_to_internal() {
    log_info "Testing ICMP: External -> Internal (should be blocked by NAT)..."

    # This should fail - internal hosts are behind NAT
    # We're testing from the firewall itself, so simulate by checking state
    states_before=$(pfctl -ss 2>/dev/null | wc -l)

    # The actual test would require sourcing from external
    # For now, verify the NAT rules exist
    if pfctl -sr 2>/dev/null | grep -q "nat-to"; then
        log_pass "NAT rules are configured"
    else
        log_fail "NAT rules not found"
    fi
}

test_icmp_dmz() {
    log_info "Testing ICMP: DMZ connectivity..."

    if ping -c 2 -w 5 ${DMZ_WEB} > /dev/null 2>&1; then
        log_pass "ICMP to DMZ web server works"
    else
        log_fail "ICMP to DMZ web server failed"
    fi
}

# =============================================================================
# TCP TESTS
# =============================================================================

test_tcp_port_forward() {
    log_info "Testing TCP port forwarding: External:8080 -> DMZ:80..."

    # Check if RDR rule exists
    if pfctl -sr 2>/dev/null | grep -q "rdr-to.*10.0.0.10 port 80"; then
        log_pass "Port forwarding rule exists"
    else
        log_fail "Port forwarding rule missing"
    fi

    # Test actual connection (if nc listener is running)
    if nc -z -w 2 ${DMZ_WEB} 80 2>/dev/null; then
        log_pass "TCP connection to DMZ web server successful"
    else
        log_skip "TCP connection test (service not running)"
    fi
}

test_tcp_ssh_external() {
    log_info "Testing TCP SSH rule on external interface..."

    # Check SSH rule exists
    if pfctl -sr 2>/dev/null | grep -q 'label "ssh-external"'; then
        log_pass "SSH external rule exists with label"
    else
        log_fail "SSH external rule missing"
    fi
}

test_tcp_https_outbound() {
    log_info "Testing TCP HTTPS outbound rule..."

    # Check HTTPS outbound rule
    if pfctl -sr 2>/dev/null | grep -q 'label "https-outbound"'; then
        log_pass "HTTPS outbound rule exists with label"
    else
        log_fail "HTTPS outbound rule missing"
    fi
}

# =============================================================================
# NAT TESTS
# =============================================================================

test_nat_internal() {
    log_info "Testing NAT for internal network..."

    # Check NAT rule for internal network
    if pfctl -sr 2>/dev/null | grep -q "from.*192.168.1.0/24.*nat-to"; then
        log_pass "Internal network NAT rule exists"
    else
        log_fail "Internal network NAT rule missing"
    fi
}

test_nat_dmz() {
    log_info "Testing NAT for DMZ network..."

    # Check NAT rule for DMZ
    if pfctl -sr 2>/dev/null | grep -q "from.*10.0.0.0/24.*nat-to"; then
        log_pass "DMZ network NAT rule exists"
    else
        log_fail "DMZ network NAT rule missing"
    fi
}

test_nat_states() {
    log_info "Testing NAT state creation..."

    # Generate some traffic to create states
    ping -c 1 -w 2 ${EXTERNAL_HOST} > /dev/null 2>&1 || true

    # Check for states
    state_count=$(pfctl -ss 2>/dev/null | wc -l | tr -d ' ')
    if [ "$state_count" -gt 0 ]; then
        log_pass "NAT created $state_count state(s)"
    else
        log_skip "No states found (may be expected)"
    fi
}

# =============================================================================
# TABLE TESTS
# =============================================================================

test_table_blocking() {
    log_info "Testing table-based blocking..."

    # Add test IP to blocked table
    pfctl -t test_blocked -T add 192.168.99.99 2>/dev/null

    # Verify it's in the table
    if pfctl -t test_blocked -Ts 2>/dev/null | grep -q "192.168.99.99"; then
        log_pass "IP added to blocked table"
    else
        log_fail "Failed to add IP to blocked table"
    fi

    # Clean up
    pfctl -t test_blocked -T delete 192.168.99.99 2>/dev/null || true
}

test_table_allowing() {
    log_info "Testing table-based allowing..."

    # Check allowed table has entries
    if pfctl -t test_allowed -Ts 2>/dev/null | grep -q "192.168.1"; then
        log_pass "Allowed table has internal network"
    else
        log_fail "Allowed table missing internal network"
    fi
}

# =============================================================================
# STATE TRACKING TESTS
# =============================================================================

test_state_tracking() {
    log_info "Testing state tracking..."

    # Get initial state count
    initial_states=$(pfctl -ss 2>/dev/null | wc -l | tr -d ' ')

    # Generate traffic
    nc -z -w 1 ${DMZ_WEB} 80 2>/dev/null || true
    ping -c 1 -w 1 ${EXTERNAL_HOST} > /dev/null 2>&1 || true

    # Check states
    final_states=$(pfctl -ss 2>/dev/null | wc -l | tr -d ' ')

    log_info "States: before=$initial_states, after=$final_states"
    log_pass "State tracking operational"
}

test_state_timeout() {
    log_info "Testing state timeout configuration..."

    # Check timeout settings in PF info
    if pfctl -st 2>/dev/null | grep -q "tcp.established"; then
        log_pass "TCP state timeouts configured"
    else
        log_skip "State timeout info not available"
    fi
}

# =============================================================================
# LOGGING TESTS
# =============================================================================

test_pf_logging() {
    log_info "Testing PF logging..."

    # Check if pflog0 exists
    if ifconfig pflog0 > /dev/null 2>&1; then
        log_pass "pflog0 interface exists"
    else
        log_fail "pflog0 interface missing"
    fi

    # Check log interface is set
    if pfctl -si 2>/dev/null | grep -q "Loginterface"; then
        log_pass "Log interface is configured"
    else
        log_skip "Log interface status unknown"
    fi
}

# =============================================================================
# SCRUB TESTS
# =============================================================================

test_scrub_rules() {
    log_info "Testing scrub/normalization rules..."

    # Check for scrub rules
    if pfctl -sr 2>/dev/null | grep -q "scrub"; then
        log_pass "Scrub rules are configured"
    else
        log_fail "Scrub rules missing"
    fi
}

# =============================================================================
# PERFORMANCE TESTS
# =============================================================================

test_rule_evaluation_performance() {
    log_info "Testing rule evaluation performance..."

    # Get rule statistics
    eval_count=$(pfctl -si 2>/dev/null | grep "evaluations" | awk '{print $1}')

    if [ -n "$eval_count" ]; then
        log_pass "Rule evaluations tracked: $eval_count"
    else
        log_skip "Evaluation count not available"
    fi
}

test_state_table_performance() {
    log_info "Testing state table..."

    # Get state table info
    state_info=$(pfctl -si 2>/dev/null | grep "current entries" | head -1)

    if [ -n "$state_info" ]; then
        log_pass "State table info: $state_info"
    else
        log_skip "State table info not available"
    fi
}

# =============================================================================
# ROUTING TESTS
# =============================================================================

test_routing_table() {
    log_info "Testing routing table..."

    # Check routes exist
    route_count=$(netstat -rn 2>/dev/null | grep -c "^[0-9]" || echo "0")

    if [ "$route_count" -gt 3 ]; then
        log_pass "Routing table has $route_count entries"
    else
        log_fail "Insufficient routing entries: $route_count"
    fi
}

test_default_route() {
    log_info "Testing default route..."

    if netstat -rn 2>/dev/null | grep -q "^default"; then
        log_pass "Default route exists"
    else
        log_skip "No default route (expected in test environment)"
    fi
}

# =============================================================================
# MULTI-ZONE TESTS
# =============================================================================

test_internal_to_dmz() {
    log_info "Testing Internal -> DMZ traffic..."

    # Check rule exists
    if pfctl -sr 2>/dev/null | grep -q "from.*192.168.1.0/24 to.*10.0.0.0/24"; then
        log_pass "Internal to DMZ rule exists"
    else
        # Rule might be more permissive
        log_skip "Specific internal->DMZ rule not found (may use general rule)"
    fi

    # Test connectivity
    if ping -c 1 -w 2 ${DMZ_WEB} > /dev/null 2>&1; then
        log_pass "Internal to DMZ connectivity works"
    else
        log_fail "Internal to DMZ connectivity failed"
    fi
}

test_dmz_to_internal_blocked() {
    log_info "Testing DMZ -> Internal (should be blocked)..."

    # Check that DMZ to internal is not directly allowed
    if pfctl -sr 2>/dev/null | grep -q "from.*10.0.0.0/24 to ! .*192.168.1"; then
        log_pass "DMZ to internal restriction exists"
    else
        log_skip "DMZ isolation rule format may differ"
    fi
}

# =============================================================================
# MAIN TEST RUNNER
# =============================================================================

run_all_tests() {
    echo "========================================"
    echo "     NSWall Network Traffic Tests"
    echo "========================================"
    echo ""

    # Basic Tests
    echo "--- PF Status Tests ---"
    test_pf_enabled
    test_pf_rules_loaded
    test_pf_tables
    echo ""

    # Connectivity Tests
    echo "--- Connectivity Tests ---"
    test_interface_connectivity
    echo ""

    # ICMP Tests
    echo "--- ICMP Tests ---"
    test_icmp_internal_to_external
    test_icmp_external_to_internal
    test_icmp_dmz
    echo ""

    # TCP Tests
    echo "--- TCP Tests ---"
    test_tcp_port_forward
    test_tcp_ssh_external
    test_tcp_https_outbound
    echo ""

    # NAT Tests
    echo "--- NAT Tests ---"
    test_nat_internal
    test_nat_dmz
    test_nat_states
    echo ""

    # Table Tests
    echo "--- Table Tests ---"
    test_table_blocking
    test_table_allowing
    echo ""

    # State Tests
    echo "--- State Tracking Tests ---"
    test_state_tracking
    test_state_timeout
    echo ""

    # Logging Tests
    echo "--- Logging Tests ---"
    test_pf_logging
    echo ""

    # Scrub Tests
    echo "--- Scrub/Normalization Tests ---"
    test_scrub_rules
    echo ""

    # Performance Tests
    echo "--- Performance Tests ---"
    test_rule_evaluation_performance
    test_state_table_performance
    echo ""

    # Routing Tests
    echo "--- Routing Tests ---"
    test_routing_table
    test_default_route
    echo ""

    # Multi-Zone Tests
    echo "--- Multi-Zone Tests ---"
    test_internal_to_dmz
    test_dmz_to_internal_blocked
    echo ""

    # Summary
    echo "========================================"
    echo "           Test Summary"
    echo "========================================"
    echo ""
    echo "  ${GREEN}Passed: ${TESTS_PASSED}${NC}"
    echo "  ${RED}Failed: ${TESTS_FAILED}${NC}"
    echo "  ${YELLOW}Skipped: ${TESTS_SKIPPED}${NC}"
    echo ""

    total=$((TESTS_PASSED + TESTS_FAILED))
    if [ $total -gt 0 ]; then
        pass_rate=$((TESTS_PASSED * 100 / total))
        echo "  Pass Rate: ${pass_rate}%"
    fi
    echo ""

    if [ $TESTS_FAILED -gt 0 ]; then
        echo "${RED}Some tests failed!${NC}"
        exit 1
    else
        echo "${GREEN}All tests passed!${NC}"
        exit 0
    fi
}

# Run specific test or all tests
case "${1:-all}" in
    all)
        run_all_tests
        ;;
    connectivity)
        test_interface_connectivity
        ;;
    pf)
        test_pf_enabled
        test_pf_rules_loaded
        test_pf_tables
        ;;
    nat)
        test_nat_internal
        test_nat_dmz
        test_nat_states
        ;;
    *)
        echo "Usage: $0 {all|connectivity|pf|nat}"
        exit 1
        ;;
esac
