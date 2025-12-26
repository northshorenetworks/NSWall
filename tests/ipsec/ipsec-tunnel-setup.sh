#!/bin/ksh
#
# ipsec-tunnel-setup.sh - Set up IPsec tunnel test environment
#
# Creates multiple IPsec tunnels for testing:
#   - Site-to-site tunnels (ESP tunnel mode)
#   - Transport mode tunnels
#   - GRE-over-IPsec
#   - Multiple concurrent tunnels for capacity testing
#
# Uses OpenBSD's iked (IKEv2) and manual ipsecctl configuration
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
MAX_TUNNELS=100  # Maximum tunnels to create for stress testing
BASE_TUNNEL_NET="10.100"  # Base network for tunnel endpoints
BASE_REMOTE_NET="10.200"  # Base network for remote side simulation

log_info() {
    echo "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo "${RED}[ERROR]${NC} $1"
}

# =============================================================================
# CLEANUP
# =============================================================================

cleanup() {
    log_info "Cleaning up IPsec test environment..."

    # Stop iked if running
    rcctl stop iked 2>/dev/null || true

    # Flush all IPsec SAs and flows
    ipsecctl -F 2>/dev/null || true

    # Remove GRE interfaces
    for i in $(ifconfig | grep "^gre" | cut -d: -f1); do
        ifconfig $i destroy 2>/dev/null || true
    done

    # Remove test vether interfaces
    for i in $(seq 0 $MAX_TUNNELS); do
        ifconfig vether${i} destroy 2>/dev/null || true
    done

    # Remove pair interfaces
    for i in $(seq 0 $MAX_TUNNELS); do
        ifconfig pair${i}a destroy 2>/dev/null || true
        ifconfig pair${i}b destroy 2>/dev/null || true
    done

    # Remove enc interface configs
    ifconfig enc0 down 2>/dev/null || true

    log_info "Cleanup complete"
}

# =============================================================================
# BASIC TUNNEL SETUP
# =============================================================================

setup_basic_tunnel() {
    log_info "Setting up basic IPsec tunnel..."

    # Create endpoints
    ifconfig vether0 create
    ifconfig vether0 inet 192.168.1.1/24
    ifconfig vether0 description "Local endpoint"

    ifconfig vether1 create
    ifconfig vether1 inet 192.168.1.2/24
    ifconfig vether1 description "Remote endpoint"

    # Enable enc0 interface for debugging
    ifconfig enc0 up

    # Create manual IPsec SAs (for testing without IKE)
    # Using pre-shared keys and manual keying

    # Generate random keys
    local auth_key=$(openssl rand -hex 32)
    local enc_key=$(openssl rand -hex 32)

    # Create IPsec policy and SAs
    cat > /tmp/ipsec.conf << EOF
# IPsec tunnel: 192.168.1.1 <-> 192.168.1.2
# Protecting traffic between 10.0.1.0/24 and 10.0.2.0/24

ike esp tunnel from 192.168.1.1 to 192.168.1.2 \\
    local 192.168.1.1 peer 192.168.1.2

flow esp out from 10.0.1.0/24 to 10.0.2.0/24 peer 192.168.1.2
flow esp in from 10.0.2.0/24 to 10.0.1.0/24 peer 192.168.1.2
EOF

    # For manual testing without IKE, create static SAs
    cat > /tmp/ipsec-manual.conf << EOF
# Manual IPsec SAs for testing (no IKE)

esp tunnel from 192.168.1.1 to 192.168.1.2 spi 0x1000:0x1001 \\
    authkey 0x${auth_key}:0x${auth_key} \\
    enckey 0x${enc_key}:0x${enc_key}

flow esp out from 10.0.1.0/24 to 10.0.2.0/24 peer 192.168.1.2
flow esp in from 10.0.2.0/24 to 10.0.1.0/24 peer 192.168.1.2
EOF

    log_info "Basic tunnel configuration created"
    log_info "Auth key: ${auth_key:0:16}..."
    log_info "Enc key: ${enc_key:0:16}..."
}

# =============================================================================
# MULTI-TUNNEL SETUP
# =============================================================================

setup_multi_tunnels() {
    local num_tunnels=${1:-10}

    log_info "Setting up $num_tunnels IPsec tunnels..."

    # Create base interfaces
    ifconfig vether0 create 2>/dev/null || true
    ifconfig vether0 inet 192.168.100.1/24

    # Generate iked configuration
    cat > /tmp/iked.conf.multi << EOF
# Multi-tunnel iked configuration
# Generated for $num_tunnels tunnels

# Global settings
set enforcesa

EOF

    for i in $(seq 1 $num_tunnels); do
        local local_net="${BASE_TUNNEL_NET}.$((i % 256)).0/24"
        local remote_net="${BASE_REMOTE_NET}.$((i % 256)).0/24"
        local peer_ip="192.168.100.$((i + 1))"
        local psk="tunnel${i}secret$(openssl rand -hex 8)"

        # Create peer interface
        ifconfig vether$i create 2>/dev/null || true
        ifconfig vether$i inet $peer_ip/24

        # Add to iked config
        cat >> /tmp/iked.conf.multi << EOF
# Tunnel $i: $local_net <-> $remote_net
ikev2 "tunnel$i" passive esp \\
    from $local_net to $remote_net \\
    local 192.168.100.1 peer $peer_ip \\
    psk "$psk"

EOF
    done

    log_info "Created iked configuration for $num_tunnels tunnels"
    log_info "Config saved to /tmp/iked.conf.multi"

    # Validate configuration
    if iked -n -f /tmp/iked.conf.multi 2>/dev/null; then
        log_info "iked configuration syntax valid"
    else
        log_warn "iked configuration may have issues"
    fi
}

# =============================================================================
# GRE OVER IPSEC SETUP
# =============================================================================

setup_gre_over_ipsec() {
    local num_tunnels=${1:-5}

    log_info "Setting up $num_tunnels GRE-over-IPsec tunnels..."

    # Create base interface
    ifconfig vether0 create 2>/dev/null || true
    ifconfig vether0 inet 172.16.0.1/24

    for i in $(seq 1 $num_tunnels); do
        local gre_local="10.255.$i.1"
        local gre_remote="10.255.$i.2"
        local peer_ip="172.16.0.$((i + 1))"

        # Create peer interface
        ifconfig vether$i create 2>/dev/null || true
        ifconfig vether$i inet $peer_ip/24

        # Create GRE tunnel
        ifconfig gre$i create 2>/dev/null || true
        ifconfig gre$i tunnel 172.16.0.1 $peer_ip
        ifconfig gre$i inet $gre_local $gre_remote netmask 255.255.255.252
        ifconfig gre$i up

        log_info "Created GRE tunnel $i: $gre_local <-> $gre_remote via $peer_ip"
    done

    # Create ipsec.conf for GRE protection
    cat > /tmp/ipsec-gre.conf << EOF
# GRE-over-IPsec configuration

# Protect GRE traffic (protocol 47)
flow esp out proto gre from 172.16.0.1 to 172.16.0.0/24
flow esp in proto gre from 172.16.0.0/24 to 172.16.0.1
EOF

    log_info "GRE-over-IPsec configuration created"
}

# =============================================================================
# TRANSPORT MODE SETUP
# =============================================================================

setup_transport_mode() {
    log_info "Setting up transport mode IPsec..."

    # Create endpoints
    ifconfig vether0 create 2>/dev/null || true
    ifconfig vether0 inet 10.0.0.1/24

    ifconfig vether1 create 2>/dev/null || true
    ifconfig vether1 inet 10.0.0.2/24

    # Transport mode config
    cat > /tmp/ipsec-transport.conf << EOF
# Transport mode IPsec - host-to-host encryption

# Encrypt all traffic between hosts
flow esp transport out from 10.0.0.1 to 10.0.0.2
flow esp in transport from 10.0.0.2 to 10.0.0.1
EOF

    log_info "Transport mode configuration created"
}

# =============================================================================
# IPSEC CONFIGURATION VALIDATION
# =============================================================================

validate_ipsec_configs() {
    log_info "Validating IPsec configurations..."

    # Test 1: Basic ipsec.conf syntax
    cat > /tmp/ipsec-test1.conf << 'EOF'
# Basic tunnel
flow esp out from 192.168.1.0/24 to 10.0.0.0/24 peer 203.0.113.1
flow esp in from 10.0.0.0/24 to 192.168.1.0/24 peer 203.0.113.1
EOF

    if ipsecctl -n -f /tmp/ipsec-test1.conf 2>/dev/null; then
        log_info "Basic flow configuration: VALID"
    else
        log_error "Basic flow configuration: INVALID"
    fi

    # Test 2: Multiple flows
    cat > /tmp/ipsec-test2.conf << 'EOF'
# Multiple tunnel flows
flow esp out from 192.168.1.0/24 to 10.0.0.0/24 peer 203.0.113.1
flow esp out from 192.168.2.0/24 to 10.0.0.0/24 peer 203.0.113.1
flow esp out from 192.168.3.0/24 to 10.0.0.0/24 peer 203.0.113.2
flow esp in from 10.0.0.0/24 to 192.168.1.0/24 peer 203.0.113.1
flow esp in from 10.0.0.0/24 to 192.168.2.0/24 peer 203.0.113.1
flow esp in from 10.0.0.0/24 to 192.168.3.0/24 peer 203.0.113.2
EOF

    if ipsecctl -n -f /tmp/ipsec-test2.conf 2>/dev/null; then
        log_info "Multiple flows configuration: VALID"
    else
        log_error "Multiple flows configuration: INVALID"
    fi

    # Test 3: AH flows
    cat > /tmp/ipsec-test3.conf << 'EOF'
# AH authentication only
flow ah out from 192.168.1.0/24 to 10.0.0.0/24 peer 203.0.113.1
flow ah in from 10.0.0.0/24 to 192.168.1.0/24 peer 203.0.113.1
EOF

    if ipsecctl -n -f /tmp/ipsec-test3.conf 2>/dev/null; then
        log_info "AH flow configuration: VALID"
    else
        log_error "AH flow configuration: INVALID"
    fi

    # Test 4: Transport mode
    cat > /tmp/ipsec-test4.conf << 'EOF'
# Transport mode
flow esp transport out from 192.168.1.1 to 192.168.1.2
flow esp transport in from 192.168.1.2 to 192.168.1.1
EOF

    if ipsecctl -n -f /tmp/ipsec-test4.conf 2>/dev/null; then
        log_info "Transport mode configuration: VALID"
    else
        log_error "Transport mode configuration: INVALID"
    fi

    # Cleanup test files
    rm -f /tmp/ipsec-test*.conf
}

# =============================================================================
# IKED CONFIGURATION VALIDATION
# =============================================================================

validate_iked_configs() {
    log_info "Validating iked configurations..."

    # Test 1: Basic IKEv2 tunnel
    cat > /tmp/iked-test1.conf << 'EOF'
ikev2 "tunnel1" passive esp \
    from 192.168.1.0/24 to 10.0.0.0/24 \
    local 203.0.113.1 peer 203.0.113.2 \
    psk "sharedsecret123"
EOF

    if iked -n -f /tmp/iked-test1.conf 2>/dev/null; then
        log_info "Basic IKEv2 tunnel: VALID"
    else
        log_error "Basic IKEv2 tunnel: INVALID"
    fi

    # Test 2: IKEv2 with specific transforms
    cat > /tmp/iked-test2.conf << 'EOF'
ikev2 "tunnel-aes256" passive esp \
    proto esp \
    from 192.168.1.0/24 to 10.0.0.0/24 \
    local 203.0.113.1 peer 203.0.113.2 \
    ikesa enc aes-256 auth hmac-sha2-256 group modp2048 \
    childsa enc aes-256-gcm \
    psk "sharedsecret123"
EOF

    if iked -n -f /tmp/iked-test2.conf 2>/dev/null; then
        log_info "IKEv2 with transforms: VALID"
    else
        log_warn "IKEv2 with transforms: may need adjustment"
    fi

    # Test 3: Multiple tunnels
    cat > /tmp/iked-test3.conf << 'EOF'
ikev2 "tunnel1" passive esp \
    from 192.168.1.0/24 to 10.0.0.0/24 \
    local 203.0.113.1 peer 203.0.113.2 \
    psk "secret1"

ikev2 "tunnel2" passive esp \
    from 192.168.2.0/24 to 10.0.0.0/24 \
    local 203.0.113.1 peer 203.0.113.3 \
    psk "secret2"

ikev2 "tunnel3" passive esp \
    from 192.168.3.0/24 to 10.0.0.0/24 \
    local 203.0.113.1 peer 203.0.113.4 \
    psk "secret3"
EOF

    if iked -n -f /tmp/iked-test3.conf 2>/dev/null; then
        log_info "Multiple IKEv2 tunnels: VALID"
    else
        log_error "Multiple IKEv2 tunnels: INVALID"
    fi

    # Cleanup
    rm -f /tmp/iked-test*.conf
}

# =============================================================================
# STATUS DISPLAY
# =============================================================================

show_status() {
    echo ""
    echo "========================================"
    echo "      IPsec Status"
    echo "========================================"
    echo ""

    echo "=== IPsec Flows ==="
    ipsecctl -sF 2>/dev/null || echo "No flows configured"
    echo ""

    echo "=== IPsec SAs ==="
    ipsecctl -sa 2>/dev/null || echo "No SAs established"
    echo ""

    echo "=== enc0 Interface ==="
    ifconfig enc0 2>/dev/null || echo "enc0 not configured"
    echo ""

    echo "=== GRE Interfaces ==="
    ifconfig | grep -A2 "^gre" || echo "No GRE interfaces"
    echo ""

    echo "=== iked Status ==="
    rcctl check iked 2>/dev/null || echo "iked not running"
    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

case "${1:-help}" in
    basic)
        cleanup
        setup_basic_tunnel
        ;;
    multi)
        cleanup
        setup_multi_tunnels ${2:-10}
        ;;
    gre)
        cleanup
        setup_gre_over_ipsec ${2:-5}
        ;;
    transport)
        cleanup
        setup_transport_mode
        ;;
    validate)
        validate_ipsec_configs
        validate_iked_configs
        ;;
    cleanup)
        cleanup
        ;;
    status)
        show_status
        ;;
    help|*)
        echo "Usage: $0 {basic|multi [count]|gre [count]|transport|validate|cleanup|status}"
        echo ""
        echo "Commands:"
        echo "  basic           - Set up basic IPsec tunnel"
        echo "  multi [count]   - Set up multiple tunnels (default: 10)"
        echo "  gre [count]     - Set up GRE-over-IPsec tunnels (default: 5)"
        echo "  transport       - Set up transport mode IPsec"
        echo "  validate        - Validate IPsec configurations"
        echo "  cleanup         - Remove all test interfaces and SAs"
        echo "  status          - Show IPsec status"
        exit 0
        ;;
esac
