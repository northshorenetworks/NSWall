#!/bin/ksh
#
# network-test-setup.sh - Set up virtual network topology for testing
#
# Creates a test network with:
#   - pair0/pair1: Simulates external/internal network boundary
#   - vether0: Internal network segment
#   - vether1: DMZ network segment
#   - bridge0: Connects internal hosts
#
# Topology:
#   [External]--pair0--[Firewall]--pair1--[Internal 192.168.1.0/24]
#                           |
#                       vether0--[DMZ 10.0.0.0/24]
#

set -e

# Check if running as root
if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root"
    exit 1
fi

# Configuration
EXTERNAL_NET="203.0.113"
INTERNAL_NET="192.168.1"
DMZ_NET="10.0.0"

echo "=== NSWall Network Test Setup ==="
echo ""

# Clean up any existing test interfaces
cleanup() {
    echo "Cleaning up existing test interfaces..."
    ifconfig pair0 destroy 2>/dev/null || true
    ifconfig pair1 destroy 2>/dev/null || true
    ifconfig vether0 destroy 2>/dev/null || true
    ifconfig vether1 destroy 2>/dev/null || true
    ifconfig bridge0 destroy 2>/dev/null || true
    ifconfig lo1 destroy 2>/dev/null || true
}

# Create test interfaces
setup_interfaces() {
    echo "Creating virtual interfaces..."

    # Create pair interfaces (connected to each other)
    # pair0 = external side, pair1 = firewall external interface
    ifconfig pair0 create
    ifconfig pair1 create

    # Link the pair interfaces
    ifconfig pair0 patch pair1

    # Create vether interfaces for internal networks
    ifconfig vether0 create  # Internal network
    ifconfig vether1 create  # DMZ network

    # Create loopback for testing
    ifconfig lo1 create

    echo "Interfaces created:"
    ifconfig pair0
    ifconfig pair1
    ifconfig vether0
    ifconfig vether1
}

# Configure IP addresses
setup_addresses() {
    echo ""
    echo "Configuring IP addresses..."

    # External interface (simulated internet-facing)
    ifconfig pair1 inet ${EXTERNAL_NET}.1/24
    ifconfig pair0 inet ${EXTERNAL_NET}.100/24  # Simulated external host

    # Internal network
    ifconfig vether0 inet ${INTERNAL_NET}.1/24

    # DMZ network
    ifconfig vether1 inet ${DMZ_NET}.1/24

    # Loopback for services
    ifconfig lo1 inet 127.0.0.2/8

    echo ""
    echo "Address configuration:"
    echo "  External (pair1): ${EXTERNAL_NET}.1/24"
    echo "  External host (pair0): ${EXTERNAL_NET}.100/24"
    echo "  Internal (vether0): ${INTERNAL_NET}.1/24"
    echo "  DMZ (vether1): ${DMZ_NET}.1/24"
}

# Enable IP forwarding
setup_forwarding() {
    echo ""
    echo "Enabling IP forwarding..."
    sysctl net.inet.ip.forwarding=1
    sysctl net.inet6.ip6.forwarding=1
}

# Create test PF configuration
setup_pf() {
    echo ""
    echo "Setting up PF test configuration..."

    cat > /etc/pf.conf.test << 'EOF'
# NSWall Network Test PF Configuration

# Interfaces
ext_if = "pair1"
int_if = "vether0"
dmz_if = "vether1"

# Networks
ext_net = "203.0.113.0/24"
int_net = "192.168.1.0/24"
dmz_net = "10.0.0.0/24"

# Tables for testing
table <test_blocked> persist
table <test_allowed> persist { 192.168.1.0/24 }

# Options
set skip on lo
set block-policy return
set loginterface $ext_if
set state-policy if-bound

# Scrub
match in all scrub (no-df random-id max-mss 1440)

# NAT - Internal to External
match out on $ext_if from $int_net nat-to ($ext_if)
match out on $ext_if from $dmz_net nat-to ($ext_if)

# Port forwarding - External port 8080 to DMZ web server
match in on $ext_if proto tcp to ($ext_if) port 8080 rdr-to 10.0.0.10 port 80

# Default block with logging
block log all

# Allow loopback
pass quick on lo

# Allow ICMP for testing
pass inet proto icmp icmp-type { echoreq, echorep, unreach }

# Allow internal to external (with NAT)
pass in on $int_if from $int_net
pass out on $ext_if from $int_net nat-to ($ext_if)

# Allow internal to DMZ
pass in on $int_if from $int_net to $dmz_net
pass out on $dmz_if from $int_net to $dmz_net

# Allow DMZ to external (with NAT)
pass in on $dmz_if from $dmz_net to ! $int_net
pass out on $ext_if from $dmz_net nat-to ($ext_if)

# Allow established connections back
pass out on $int_if from any to $int_net
pass out on $dmz_if from any to $dmz_net

# Test rules with labels for verification
pass in log on $ext_if proto tcp to ($ext_if) port 22 label "ssh-external"
pass in log on $ext_if proto tcp to 10.0.0.10 port 80 label "web-dmz-rdr"
pass in log on $int_if proto tcp from $int_net to any port 443 label "https-outbound"

# Block from blocked table
block in quick from <test_blocked> label "blocked-table"
EOF

    # Validate configuration
    if pfctl -nf /etc/pf.conf.test; then
        echo "PF configuration validated successfully"
        pfctl -f /etc/pf.conf.test
        pfctl -e 2>/dev/null || true
        echo "PF rules loaded"
    else
        echo "ERROR: PF configuration validation failed"
        exit 1
    fi
}

# Add simulated hosts (using IP aliases)
setup_hosts() {
    echo ""
    echo "Setting up simulated hosts..."

    # Internal hosts
    ifconfig vether0 inet ${INTERNAL_NET}.10/24 alias  # Internal client
    ifconfig vether0 inet ${INTERNAL_NET}.20/24 alias  # Internal server

    # DMZ hosts
    ifconfig vether1 inet ${DMZ_NET}.10/24 alias  # Web server
    ifconfig vether1 inet ${DMZ_NET}.20/24 alias  # Mail server

    echo "Simulated hosts:"
    echo "  Internal client: ${INTERNAL_NET}.10"
    echo "  Internal server: ${INTERNAL_NET}.20"
    echo "  DMZ web server: ${DMZ_NET}.10"
    echo "  DMZ mail server: ${DMZ_NET}.20"
}

# Start test services
setup_services() {
    echo ""
    echo "Starting test services..."

    # Start a simple HTTP server on DMZ web server address
    cat > /tmp/test-httpd.conf << EOF
server "test" {
    listen on 10.0.0.10 port 80
    root "/var/www/htdocs"
}
EOF

    # Create test content
    mkdir -p /var/www/htdocs
    echo "NSWall Test Server" > /var/www/htdocs/index.html

    # Use nc for simple TCP listeners instead of full httpd
    # Port 80 on DMZ web server
    (while true; do echo -e "HTTP/1.1 200 OK\r\nContent-Length: 19\r\n\r\nNSWall Test Server" | nc -l 10.0.0.10 80 > /dev/null 2>&1; done) &
    echo $! > /tmp/test-http.pid

    # Port 22 simulation on external interface
    (while true; do echo "SSH-2.0-OpenSSH_Test" | nc -l 203.0.113.1 2222 > /dev/null 2>&1; done) &
    echo $! > /tmp/test-ssh.pid

    echo "Test services started"
}

# Display network topology
show_topology() {
    echo ""
    echo "=== Network Topology ==="
    echo ""
    echo "                    [External Host]"
    echo "                    ${EXTERNAL_NET}.100"
    echo "                          |"
    echo "                       pair0"
    echo "                          |"
    echo "                       pair1 (${EXTERNAL_NET}.1)"
    echo "                          |"
    echo "                    [FIREWALL/NAT]"
    echo "                     /         \\"
    echo "                    /           \\"
    echo "            vether0              vether1"
    echo "    (${INTERNAL_NET}.1)       (${DMZ_NET}.1)"
    echo "              |                    |"
    echo "    [Internal Network]      [DMZ Network]"
    echo "    ${INTERNAL_NET}.10      ${DMZ_NET}.10 (web)"
    echo "    ${INTERNAL_NET}.20      ${DMZ_NET}.20 (mail)"
    echo ""
}

# Show PF status
show_status() {
    echo "=== PF Status ==="
    pfctl -si | head -20
    echo ""
    echo "=== PF Rules ==="
    pfctl -sr | head -30
    echo ""
}

# Main
case "${1:-setup}" in
    setup)
        cleanup
        setup_interfaces
        setup_addresses
        setup_forwarding
        setup_pf
        setup_hosts
        setup_services
        show_topology
        show_status
        echo "=== Setup Complete ==="
        ;;
    cleanup)
        cleanup
        echo "Cleanup complete"
        ;;
    status)
        show_topology
        show_status
        ;;
    *)
        echo "Usage: $0 {setup|cleanup|status}"
        exit 1
        ;;
esac
