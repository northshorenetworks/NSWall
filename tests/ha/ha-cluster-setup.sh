#!/bin/ksh
#
# ha-cluster-setup.sh - Set up simulated 2-node HA cluster for testing
#
# Simulates a 2-node firewall cluster using virtual interfaces:
#   - Node 1 (Primary): vether10, vether11, vether12
#   - Node 2 (Secondary): vether20, vether21, vether22
#   - Sync network: pair interfaces connecting nodes
#   - Shared CARP VIPs on external and internal interfaces
#
# This allows testing CARP failover and PFSync within a single VM
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Network Configuration
# Sync network (between nodes)
SYNC_NET="10.255.0"

# External network
EXT_NET="203.0.113"
EXT_VIP="${EXT_NET}.1"

# Internal network
INT_NET="192.168.1"
INT_VIP="${INT_NET}.1"

# Node 1 (Primary)
NODE1_SYNC="${SYNC_NET}.1"
NODE1_EXT="${EXT_NET}.10"
NODE1_INT="${INT_NET}.10"
NODE1_ADVSKEW=0

# Node 2 (Secondary)
NODE2_SYNC="${SYNC_NET}.2"
NODE2_EXT="${EXT_NET}.20"
NODE2_INT="${INT_NET}.20"
NODE2_ADVSKEW=100

# CARP password and VHID
CARP_PASS="hacluster123"
CARP_VHID_EXT=1
CARP_VHID_INT=2

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
    log_info "Cleaning up HA cluster interfaces..."

    # Node 1 interfaces
    ifconfig vether10 destroy 2>/dev/null || true
    ifconfig vether11 destroy 2>/dev/null || true
    ifconfig vether12 destroy 2>/dev/null || true
    ifconfig carp10 destroy 2>/dev/null || true
    ifconfig carp11 destroy 2>/dev/null || true

    # Node 2 interfaces
    ifconfig vether20 destroy 2>/dev/null || true
    ifconfig vether21 destroy 2>/dev/null || true
    ifconfig vether22 destroy 2>/dev/null || true
    ifconfig carp20 destroy 2>/dev/null || true
    ifconfig carp21 destroy 2>/dev/null || true

    # Sync interfaces
    ifconfig pair10 destroy 2>/dev/null || true
    ifconfig pair20 destroy 2>/dev/null || true

    # PFSync interfaces
    ifconfig pfsync0 destroy 2>/dev/null || true
    ifconfig pfsync1 destroy 2>/dev/null || true

    # Bridge for shared networks
    ifconfig bridge0 destroy 2>/dev/null || true
    ifconfig bridge1 destroy 2>/dev/null || true

    log_info "Cleanup complete"
}

# =============================================================================
# SETUP NODE 1
# =============================================================================

setup_node1() {
    log_info "Setting up Node 1 (Primary)..."

    # Sync interface
    ifconfig vether10 create
    ifconfig vether10 inet ${NODE1_SYNC}/24
    ifconfig vether10 description "Node1-Sync"

    # External interface
    ifconfig vether11 create
    ifconfig vether11 inet ${NODE1_EXT}/24
    ifconfig vether11 description "Node1-External"

    # Internal interface
    ifconfig vether12 create
    ifconfig vether12 inet ${NODE1_INT}/24
    ifconfig vether12 description "Node1-Internal"

    # CARP on external (VHID 1)
    ifconfig carp10 create
    ifconfig carp10 vhid ${CARP_VHID_EXT} pass "${CARP_PASS}" \
        advskew ${NODE1_ADVSKEW} carpdev vether11 \
        inet ${EXT_VIP}/24

    # CARP on internal (VHID 2)
    ifconfig carp11 create
    ifconfig carp11 vhid ${CARP_VHID_INT} pass "${CARP_PASS}" \
        advskew ${NODE1_ADVSKEW} carpdev vether12 \
        inet ${INT_VIP}/24

    log_info "Node 1 interfaces created"
}

# =============================================================================
# SETUP NODE 2
# =============================================================================

setup_node2() {
    log_info "Setting up Node 2 (Secondary)..."

    # Sync interface
    ifconfig vether20 create
    ifconfig vether20 inet ${NODE2_SYNC}/24
    ifconfig vether20 description "Node2-Sync"

    # External interface
    ifconfig vether21 create
    ifconfig vether21 inet ${NODE2_EXT}/24
    ifconfig vether21 description "Node2-External"

    # Internal interface
    ifconfig vether22 create
    ifconfig vether22 inet ${NODE2_INT}/24
    ifconfig vether22 description "Node2-Internal"

    # CARP on external (VHID 1) - same VHID as Node 1
    ifconfig carp20 create
    ifconfig carp20 vhid ${CARP_VHID_EXT} pass "${CARP_PASS}" \
        advskew ${NODE2_ADVSKEW} carpdev vether21 \
        inet ${EXT_VIP}/24

    # CARP on internal (VHID 2) - same VHID as Node 1
    ifconfig carp21 create
    ifconfig carp21 vhid ${CARP_VHID_INT} pass "${CARP_PASS}" \
        advskew ${NODE2_ADVSKEW} carpdev vether22 \
        inet ${INT_VIP}/24

    log_info "Node 2 interfaces created"
}

# =============================================================================
# SETUP SYNC NETWORK
# =============================================================================

setup_sync_network() {
    log_info "Setting up sync network between nodes..."

    # Create pair interfaces to connect sync networks
    ifconfig pair10 create
    ifconfig pair20 create
    ifconfig pair10 patch pair20

    # Bridge sync interfaces
    ifconfig bridge0 create
    ifconfig bridge0 add vether10
    ifconfig bridge0 add pair10
    ifconfig bridge0 up

    # Bridge node2 sync
    ifconfig bridge1 create
    ifconfig bridge1 add vether20
    ifconfig bridge1 add pair20
    ifconfig bridge1 up

    log_info "Sync network connected"
}

# =============================================================================
# SETUP PFSYNC
# =============================================================================

setup_pfsync() {
    log_info "Setting up PFSync..."

    # Node 1 pfsync
    ifconfig pfsync0 create || true
    ifconfig pfsync0 syncdev vether10 syncpeer ${NODE2_SYNC} up

    log_info "PFSync configured on Node 1"
    log_info "  syncdev: vether10 (${NODE1_SYNC})"
    log_info "  syncpeer: ${NODE2_SYNC}"
}

# =============================================================================
# SETUP PF RULES
# =============================================================================

setup_pf_ha() {
    log_info "Setting up HA PF configuration..."

    cat > /etc/pf.conf.ha << EOF
# HA Cluster PF Configuration

# Interfaces - Node 1
ext_if = "vether11"
int_if = "vether12"
sync_if = "vether10"

# CARP VIPs
carp_ext = "carp10"
carp_int = "carp11"

# Networks
ext_net = "${EXT_NET}.0/24"
int_net = "${INT_NET}.0/24"
sync_net = "${SYNC_NET}.0/24"

# Tables
table <ha_nodes> persist { ${NODE1_SYNC}, ${NODE2_SYNC} }

set skip on lo
set block-policy return
set state-policy if-bound

# Scrub
match in all scrub (no-df random-id)

# PFSync - critical for HA
pass quick on \$sync_if proto pfsync keep state (no-sync)

# CARP protocol
pass quick proto carp keep state (no-sync)

# Allow HA sync traffic
pass quick on \$sync_if from <ha_nodes> to <ha_nodes>

# NAT on CARP VIP
match out on \$carp_ext from \$int_net nat-to (\$carp_ext)

# Default block
block log all

# Allow traffic to CARP VIPs
pass in on \$carp_ext proto tcp to (\$carp_ext) port { 22, 80, 443 } keep state
pass in on \$carp_int from \$int_net keep state

# Allow outbound
pass out on \$carp_ext keep state
pass out on \$carp_int keep state

# Allow ICMP for failover detection
pass inet proto icmp icmp-type { echoreq, echorep }
EOF

    # Validate
    if pfctl -nf /etc/pf.conf.ha; then
        log_info "HA PF configuration valid"
        pfctl -f /etc/pf.conf.ha
        pfctl -e 2>/dev/null || true
    else
        log_error "HA PF configuration invalid"
        exit 1
    fi
}

# =============================================================================
# ENABLE HA SERVICES
# =============================================================================

enable_ha_services() {
    log_info "Enabling HA services..."

    # Enable CARP
    sysctl net.inet.carp.allow=1
    sysctl net.inet.carp.preempt=1
    sysctl net.inet.carp.log=2

    # Enable forwarding
    sysctl net.inet.ip.forwarding=1

    log_info "HA services enabled"
}

# =============================================================================
# STATUS DISPLAY
# =============================================================================

show_status() {
    echo ""
    echo "========================================"
    echo "      HA Cluster Status"
    echo "========================================"
    echo ""

    echo "=== Node 1 (Primary) ==="
    echo "Sync:     ${NODE1_SYNC} (vether10)"
    echo "External: ${NODE1_EXT} (vether11)"
    echo "Internal: ${NODE1_INT} (vether12)"
    echo ""

    echo "=== Node 2 (Secondary) ==="
    echo "Sync:     ${NODE2_SYNC} (vether20)"
    echo "External: ${NODE2_EXT} (vether21)"
    echo "Internal: ${NODE2_INT} (vether22)"
    echo ""

    echo "=== CARP VIPs ==="
    echo "External VIP: ${EXT_VIP} (VHID ${CARP_VHID_EXT})"
    echo "Internal VIP: ${INT_VIP} (VHID ${CARP_VHID_INT})"
    echo ""

    echo "=== CARP Status ==="
    echo "Node 1 External (carp10):"
    ifconfig carp10 2>/dev/null | grep -E "carp:|inet" || echo "  Not configured"
    echo ""
    echo "Node 1 Internal (carp11):"
    ifconfig carp11 2>/dev/null | grep -E "carp:|inet" || echo "  Not configured"
    echo ""
    echo "Node 2 External (carp20):"
    ifconfig carp20 2>/dev/null | grep -E "carp:|inet" || echo "  Not configured"
    echo ""
    echo "Node 2 Internal (carp21):"
    ifconfig carp21 2>/dev/null | grep -E "carp:|inet" || echo "  Not configured"
    echo ""

    echo "=== PFSync Status ==="
    ifconfig pfsync0 2>/dev/null | head -5 || echo "  Not configured"
    echo ""

    echo "=== PF State Count ==="
    pfctl -si 2>/dev/null | grep "current entries" || echo "  PF not running"
    echo ""
}

# =============================================================================
# TOPOLOGY DISPLAY
# =============================================================================

show_topology() {
    cat << 'EOF'

                    HA Cluster Topology
    ================================================

                     [External Network]
                      203.0.113.0/24
                            |
              +-------------+-------------+
              |                           |
         vether11                    vether21
      (203.0.113.10)             (203.0.113.20)
              |                           |
         [Node 1]                    [Node 2]
         Primary                    Secondary
              |                           |
         carp10 <-------- VIP --------> carp20
              |     203.0.113.1           |
              |      (VHID 1)             |
              |                           |
         vether10 <---- PFSync ----> vether20
      (10.255.0.1)                 (10.255.0.2)
              |       Sync Net            |
              |                           |
         carp11 <-------- VIP --------> carp21
              |     192.168.1.1           |
              |      (VHID 2)             |
         vether12                    vether22
      (192.168.1.10)             (192.168.1.20)
              |                           |
              +-------------+-------------+
                            |
                     [Internal Network]
                      192.168.1.0/24

EOF
}

# =============================================================================
# MAIN
# =============================================================================

case "${1:-setup}" in
    setup)
        echo "========================================"
        echo "   Setting up HA Cluster"
        echo "========================================"
        cleanup
        enable_ha_services
        setup_node1
        setup_node2
        setup_sync_network
        setup_pfsync
        setup_pf_ha

        # Wait for CARP to elect master
        log_info "Waiting for CARP election..."
        sleep 3

        show_topology
        show_status
        echo "========================================"
        echo "   HA Cluster Setup Complete"
        echo "========================================"
        ;;
    cleanup)
        cleanup
        ;;
    status)
        show_status
        ;;
    topology)
        show_topology
        ;;
    *)
        echo "Usage: $0 {setup|cleanup|status|topology}"
        exit 1
        ;;
esac
