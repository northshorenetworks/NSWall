#!/bin/sh
# Install NSWall components

set -e

echo "=== Installing NSWall Components ==="

VERSION="${NSWALL_VERSION:-2.0.0}"
RELEASES_URL="https://github.com/northshorenetworks/nswall/releases/download/v${VERSION}"

# Download and install API
echo "Installing nswall-api..."
curl -sL "${RELEASES_URL}/nswall-api-openbsd-amd64" -o /usr/local/sbin/nswall-api
chmod 755 /usr/local/sbin/nswall-api

# Create API rc script
cat > /etc/rc.d/nswall_api << 'EOF'
#!/bin/ksh

daemon="/usr/local/sbin/nswall-api"
daemon_flags="-listen 0.0.0.0:8443 -data /var/db/nswall-api"
daemon_user="_nswall"

. /etc/rc.d/rc.subr

rc_bg=YES
rc_reload=NO

rc_cmd $1
EOF
chmod 755 /etc/rc.d/nswall_api

# Enable API at boot
echo 'nswall_api=YES' >> /etc/rc.conf.local

# Download and install NSH (if not building from source)
echo "Installing nsh..."
if [ -f /tmp/nsh ]; then
    cp /tmp/nsh /usr/local/bin/nsh
else
    # Build from source
    cd /tmp
    git clone --depth 1 https://github.com/northshorenetworks/nswall.git
    cd nswall/nssh
    make
    make install
    cd /
    rm -rf /tmp/nswall
fi

# Create initial configuration
cat > /etc/nshrc << 'EOF'
# NSWall Initial Configuration
# Configure interfaces and services here

# Example: configure em0 for DHCP
# interface em0
#   ip dhcp
#   no shutdown

# Example: static IP
# interface em0
#   ip address 192.168.1.1/24
#   no shutdown
EOF

# Create motd
cat > /etc/motd << 'EOF'

  _   _  ______          __   _ _
 | \ | |/ ___\ \        / /  | | |
 |  \| | (___  \ \  /\  / /_ _| | |
 | . ` |\___ \  \ \/  \/ / _` | | |
 | |\  |____) |  \  /\  / (_| | | |
 |_| \_|_____/    \/  \/ \__,_|_|_|

 OpenBSD Network Appliance
 Type 'nsh' for CLI, 'nswall-api' for REST API

EOF

echo "NSWall installation complete."
