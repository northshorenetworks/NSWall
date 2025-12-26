#!/bin/sh
# Base system setup for NSWall appliance

set -e

echo "=== NSWall Base Setup ==="

# Update packages
echo "Updating package list..."
pkg_add -u

# Install required packages
echo "Installing base packages..."
pkg_add -I curl git go

# Create nswall user
echo "Creating nswall system user..."
useradd -m -s /sbin/nologin -c "NSWall API" -d /var/empty _nswall || true

# Create directories
mkdir -p /var/db/nswall-api
mkdir -p /var/db/nswall-controller
mkdir -p /var/log/nswall
chown _nswall:_nswall /var/db/nswall-api
chown _nswall:_nswall /var/log/nswall

# Enable PF
echo "Enabling PF..."
echo 'pf=YES' >> /etc/rc.conf.local

# Basic PF rules
cat > /etc/pf.conf << 'EOF'
# NSWall Default PF Configuration

# Macros
ext_if = "em0"
int_if = "em1"

# Options
set skip on lo
set block-policy drop
set loginterface $ext_if

# Scrub
match in all scrub (no-df random-id)

# NAT
match out on $ext_if inet from !($ext_if) to any nat-to ($ext_if)

# Default deny
block all

# Allow loopback
pass quick on lo0

# Allow outbound
pass out quick

# Allow ICMP
pass in inet proto icmp

# Allow SSH (change port in production!)
pass in on $ext_if proto tcp to port 22

# Allow API access from internal
pass in on $int_if proto tcp to port { 8080, 8443 }
EOF

# Enable CARP preempt
echo 'net.inet.carp.preempt=1' >> /etc/sysctl.conf

# Enable IP forwarding
echo 'net.inet.ip.forwarding=1' >> /etc/sysctl.conf
echo 'net.inet6.ip6.forwarding=1' >> /etc/sysctl.conf

echo "Base setup complete."
