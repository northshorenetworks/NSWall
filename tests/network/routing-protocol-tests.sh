#!/bin/ksh
#
# routing-protocol-tests.sh - Test routing protocol configurations
#
# Tests BGP and OSPF configuration validation and basic operation
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

# =============================================================================
# BGP CONFIGURATION TESTS
# =============================================================================

test_bgp_config_validation() {
    log_info "Testing BGP configuration validation..."

    # Create test BGP configuration
    cat > /tmp/bgpd.conf.test << 'EOF'
AS 65001
router-id 192.168.1.1
holdtime 90

# Networks to announce
network 10.0.0.0/8

# Peer configuration
neighbor 192.168.1.2 {
    remote-as 65002
    descr "Test Peer"
    announce IPv4 unicast
}

# Filter rules
deny from any prefix 0.0.0.0/0 prefixlen > 24
allow from any
EOF

    # Validate with bgpd -n
    if bgpd -nf /tmp/bgpd.conf.test 2>/dev/null; then
        log_pass "BGP configuration syntax valid"
    else
        log_fail "BGP configuration syntax invalid"
    fi

    rm -f /tmp/bgpd.conf.test
}

test_bgp_complex_config() {
    log_info "Testing complex BGP configuration..."

    cat > /tmp/bgpd.conf.complex << 'EOF'
AS 65001
router-id 192.168.1.1
holdtime 90
holdtime min 30
fib-update yes
log updates

# Prefix sets
prefix-set "bogons" {
    0.0.0.0/8 prefixlen >= 8
    10.0.0.0/8 prefixlen >= 8
    127.0.0.0/8 prefixlen >= 8
    169.254.0.0/16 prefixlen >= 16
    172.16.0.0/12 prefixlen >= 12
    192.168.0.0/16 prefixlen >= 16
    224.0.0.0/3 prefixlen >= 3
}

prefix-set "our-networks" {
    203.0.113.0/24
}

# Announce our networks
network 203.0.113.0/24 set { localpref 200 }

# Upstream providers
group "upstreams" {
    remote-as 65002
    announce IPv4 unicast

    neighbor 10.0.0.1 {
        descr "Primary"
    }
    neighbor 10.0.0.2 {
        descr "Secondary"
    }
}

# iBGP
group "ibgp" {
    remote-as 65001

    neighbor 192.168.1.2
    neighbor 192.168.1.3
}

# Filters
deny from any prefix-set "bogons"
deny from any prefixlen > 24
allow from group "upstreams"
allow from group "ibgp"

# Outbound
allow to group "upstreams" prefix-set "our-networks" set { prepend-self 1 }
deny to any
EOF

    if bgpd -nf /tmp/bgpd.conf.complex 2>/dev/null; then
        log_pass "Complex BGP configuration valid"
    else
        log_fail "Complex BGP configuration invalid"
    fi

    rm -f /tmp/bgpd.conf.complex
}

test_bgp_with_communities() {
    log_info "Testing BGP communities configuration..."

    cat > /tmp/bgpd.conf.communities << 'EOF'
AS 65001
router-id 192.168.1.1

neighbor 10.0.0.1 {
    remote-as 65002
}

# Community tagging
match from any community 65001:100 set localpref 200
match from any community 65001:200 set localpref 100

# Large communities
match from any set large-community 65001:0:1

# Extended communities for VPN
match from any set ext-community rt 65001:100

allow from any
EOF

    if bgpd -nf /tmp/bgpd.conf.communities 2>/dev/null; then
        log_pass "BGP communities configuration valid"
    else
        log_fail "BGP communities configuration invalid"
    fi

    rm -f /tmp/bgpd.conf.communities
}

# =============================================================================
# OSPF CONFIGURATION TESTS
# =============================================================================

test_ospf_config_validation() {
    log_info "Testing OSPF configuration validation..."

    cat > /tmp/ospfd.conf.test << 'EOF'
router-id 192.168.1.1
fib-update yes

area 0.0.0.0 {
    interface em0 {
        cost 10
        hello-interval 10
        router-dead-time 40
    }
}
EOF

    if ospfd -nf /tmp/ospfd.conf.test 2>/dev/null; then
        log_pass "OSPF configuration syntax valid"
    else
        log_fail "OSPF configuration syntax invalid"
    fi

    rm -f /tmp/ospfd.conf.test
}

test_ospf_multi_area() {
    log_info "Testing OSPF multi-area configuration..."

    cat > /tmp/ospfd.conf.multiarea << 'EOF'
router-id 192.168.1.1
fib-update yes
rfc1583compat yes

redistribute connected set { metric 100, type 1 }
redistribute static set { metric 200, type 2 }

# Backbone area
area 0.0.0.0 {
    interface em0 {
        cost 10
        hello-interval 10
        router-dead-time 40
        router-priority 100
        auth-type crypt
        auth-md5 1 "secretkey123"
    }
}

# Stub area
area 0.0.0.1 {
    stub default-cost 100

    interface em1 {
        cost 20
        passive
    }
}

# Normal area with dependencies
area 0.0.0.2 {
    demote carp

    interface em2 {
        cost 10
        type p2p
        depend on em0
    }
}
EOF

    if ospfd -nf /tmp/ospfd.conf.multiarea 2>/dev/null; then
        log_pass "OSPF multi-area configuration valid"
    else
        log_fail "OSPF multi-area configuration invalid"
    fi

    rm -f /tmp/ospfd.conf.multiarea
}

# =============================================================================
# ROUTING TABLE TESTS
# =============================================================================

test_route_commands() {
    log_info "Testing route command syntax..."

    # Create test interface
    ifconfig vether0 create 2>/dev/null || true
    ifconfig vether0 inet 192.168.100.1/24

    # Test route add
    if route add 10.10.0.0/16 192.168.100.254 2>/dev/null; then
        log_pass "Route add command works"
        route delete 10.10.0.0/16 2>/dev/null || true
    else
        log_fail "Route add command failed"
    fi

    # Test route with label
    if route add -label test 10.20.0.0/16 192.168.100.254 2>/dev/null; then
        log_pass "Route with label works"
        route delete 10.20.0.0/16 2>/dev/null || true
    else
        log_fail "Route with label failed"
    fi

    # Cleanup
    ifconfig vether0 destroy 2>/dev/null || true
}

test_routing_table_parsing() {
    log_info "Testing routing table parsing..."

    # Get routing table
    routes=$(netstat -rn 2>/dev/null | wc -l)
    if [ "$routes" -gt 3 ]; then
        log_pass "Routing table has $routes entries"
    else
        log_fail "Routing table has insufficient entries"
    fi

    # Check for loopback route
    if netstat -rn | grep -q "127.0.0.1"; then
        log_pass "Loopback route exists"
    else
        log_fail "Loopback route missing"
    fi
}

# =============================================================================
# RIP CONFIGURATION TESTS
# =============================================================================

test_rip_config() {
    log_info "Testing RIP configuration..."

    # Check if ripd exists
    if command -v ripd >/dev/null 2>&1; then
        cat > /tmp/ripd.conf.test << 'EOF'
fib-update yes

interface em0 {
    cost 1
}

redistribute connected
redistribute static
EOF

        if ripd -nf /tmp/ripd.conf.test 2>/dev/null; then
            log_pass "RIP configuration valid"
        else
            log_fail "RIP configuration invalid"
        fi

        rm -f /tmp/ripd.conf.test
    else
        log_info "ripd not available, skipping"
    fi
}

# =============================================================================
# EIGRP CONFIGURATION TESTS
# =============================================================================

test_eigrp_config() {
    log_info "Testing EIGRP configuration..."

    # Check if eigrpd exists
    if command -v eigrpd >/dev/null 2>&1; then
        cat > /tmp/eigrpd.conf.test << 'EOF'
router-id 192.168.1.1
fib-update yes

address-family ipv4 autonomous-system 100 {
    interface em0 {
        delay 10
        bandwidth 1000000
    }

    redistribute connected
}
EOF

        if eigrpd -nf /tmp/eigrpd.conf.test 2>/dev/null; then
            log_pass "EIGRP configuration valid"
        else
            log_fail "EIGRP configuration invalid"
        fi

        rm -f /tmp/eigrpd.conf.test
    else
        log_info "eigrpd not available, skipping"
    fi
}

# =============================================================================
# LDP CONFIGURATION TESTS
# =============================================================================

test_ldp_config() {
    log_info "Testing LDP configuration..."

    # Check if ldpd exists
    if command -v ldpd >/dev/null 2>&1; then
        cat > /tmp/ldpd.conf.test << 'EOF'
router-id 192.168.1.1

interface em0 {
    link-hello-holdtime 15
    link-hello-interval 5
}
EOF

        if ldpd -nf /tmp/ldpd.conf.test 2>/dev/null; then
            log_pass "LDP configuration valid"
        else
            log_fail "LDP configuration invalid"
        fi

        rm -f /tmp/ldpd.conf.test
    else
        log_info "ldpd not available, skipping"
    fi
}

# =============================================================================
# MAIN
# =============================================================================

run_all_tests() {
    echo "========================================"
    echo "   Routing Protocol Tests"
    echo "========================================"
    echo ""

    echo "--- BGP Tests ---"
    test_bgp_config_validation
    test_bgp_complex_config
    test_bgp_with_communities
    echo ""

    echo "--- OSPF Tests ---"
    test_ospf_config_validation
    test_ospf_multi_area
    echo ""

    echo "--- Routing Table Tests ---"
    test_route_commands
    test_routing_table_parsing
    echo ""

    echo "--- Other Protocols ---"
    test_rip_config
    test_eigrp_config
    test_ldp_config
    echo ""

    echo "========================================"
    echo "           Summary"
    echo "========================================"
    echo ""
    echo "  ${GREEN}Passed: ${TESTS_PASSED}${NC}"
    echo "  ${RED}Failed: ${TESTS_FAILED}${NC}"
    echo ""

    if [ $TESTS_FAILED -gt 0 ]; then
        exit 1
    fi
}

case "${1:-all}" in
    all)
        run_all_tests
        ;;
    bgp)
        test_bgp_config_validation
        test_bgp_complex_config
        test_bgp_with_communities
        ;;
    ospf)
        test_ospf_config_validation
        test_ospf_multi_area
        ;;
    *)
        echo "Usage: $0 {all|bgp|ospf}"
        exit 1
        ;;
esac
