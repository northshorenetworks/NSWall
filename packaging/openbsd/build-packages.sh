#!/bin/sh
# NSWall OpenBSD Package Build Script
# Builds OpenBSD packages for nswall-api and nswall-controller

set -e

VERSION="${VERSION:-2.0.0}"
ARCH="${ARCH:-amd64}"
BUILD_DIR="${BUILD_DIR:-/tmp/nswall-pkg}"
PKG_DIR="${PKG_DIR:-./packages}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

log() {
    echo "${GREEN}[BUILD]${NC} $1"
}

error() {
    echo "${RED}[ERROR]${NC} $1"
    exit 1
}

# Check we're on OpenBSD
if [ "$(uname)" != "OpenBSD" ]; then
    error "This script must be run on OpenBSD"
fi

# Create build directory
log "Creating build directory..."
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"/{api,controller}
mkdir -p "$PKG_DIR"

# Build API binary
log "Building nswall-api..."
cd "$(dirname "$0")/../api"
go build -ldflags "-s -w -X main.version=${VERSION}" \
    -o "$BUILD_DIR/api/nswall-api" \
    ./cmd/nswall-api

# Build Controller binary
log "Building nswall-controller..."
cd "$(dirname "$0")/../controller"
go build -ldflags "-s -w -X main.version=${VERSION}" \
    -o "$BUILD_DIR/controller/nswall-controller" \
    ./cmd/nswall-controller

# Create API package structure
log "Creating nswall-api package..."
API_PKG="$BUILD_DIR/nswall-api-${VERSION}"
mkdir -p "$API_PKG"/{usr/local/sbin,usr/local/share/examples/nswall-api,etc/rc.d}

cp "$BUILD_DIR/api/nswall-api" "$API_PKG/usr/local/sbin/"
chmod 755 "$API_PKG/usr/local/sbin/nswall-api"

# RC script for API
cat > "$API_PKG/etc/rc.d/nswall_api" << 'RCEOF'
#!/bin/ksh

daemon="/usr/local/sbin/nswall-api"
daemon_flags="-listen 127.0.0.1:8443 -data /var/db/nswall-api"
daemon_user="_nswall"

. /etc/rc.d/rc.subr

rc_bg=YES
rc_reload=NO

rc_cmd $1
RCEOF
chmod 755 "$API_PKG/etc/rc.d/nswall_api"

# Example config
cat > "$API_PKG/usr/local/share/examples/nswall-api/nswall-api.conf" << 'CONFEOF'
# NSWall API Configuration
# Copy to /etc/nswall-api.conf and modify as needed

# Listen address (default: 127.0.0.1:8443)
LISTEN="127.0.0.1:8443"

# Path to nsh binary
NSH_PATH="/usr/local/bin/nsh"

# Data directory
DATA_DIR="/var/db/nswall-api"

# API key (generate with: openssl rand -hex 32)
# API_KEY=""

# Controller URL for fleet mode (optional)
# CONTROLLER="nats://controller.example.com:4222"

# Enable authentication
# AUTH=true
CONFEOF

# PLIST for API
cat > "$API_PKG/+CONTENTS" << EOF
@name nswall-api-${VERSION}
@arch ${ARCH}
@owner root
@group wheel
@mode 0755
usr/local/sbin/nswall-api
@mode 0644
usr/local/share/examples/nswall-api/nswall-api.conf
@mode 0755
@sample /etc/rc.d/nswall_api
etc/rc.d/nswall_api
EOF

cat > "$API_PKG/+DESC" << EOF
NSWall REST API Server

Provides a comprehensive REST API for managing NSWall/OpenBSD
network appliances. Features include:

- PF firewall management
- Interface configuration
- Routing and ARP
- VPN (WireGuard, IKEv2)
- DHCP and DNS
- HA (CARP/pfsync)
- Fleet management via NATS

WWW: https://github.com/northshorenetworks/nswall
EOF

cat > "$API_PKG/+COMMENT" << EOF
REST API server for NSWall network appliance
EOF

# Create Controller package structure
log "Creating nswall-controller package..."
CTRL_PKG="$BUILD_DIR/nswall-controller-${VERSION}"
mkdir -p "$CTRL_PKG"/{usr/local/sbin,usr/local/share/examples/nswall-controller,etc/rc.d}

cp "$BUILD_DIR/controller/nswall-controller" "$CTRL_PKG/usr/local/sbin/"
chmod 755 "$CTRL_PKG/usr/local/sbin/nswall-controller"

# RC script for Controller
cat > "$CTRL_PKG/etc/rc.d/nswall_controller" << 'RCEOF'
#!/bin/ksh

daemon="/usr/local/sbin/nswall-controller"
daemon_flags="-http :8080 -nats :4222 -data /var/db/nswall-controller"
daemon_user="_nswall"

. /etc/rc.d/rc.subr

rc_bg=YES
rc_reload=NO

rc_cmd $1
RCEOF
chmod 755 "$CTRL_PKG/etc/rc.d/nswall_controller"

# Example config for controller
cat > "$CTRL_PKG/usr/local/share/examples/nswall-controller/nswall-controller.conf" << 'CONFEOF'
# NSWall Controller Configuration
# Copy to /etc/nswall-controller.conf and modify as needed

# HTTP listen address for WebUI/API
HTTP=":8080"

# NATS server listen address
NATS="0.0.0.0:4222"

# Data directory
DATA_DIR="/var/db/nswall-controller"

# NATS cluster URL for HA (optional)
# CLUSTER="nats://other-controller:5222"
CONFEOF

# PLIST for Controller
cat > "$CTRL_PKG/+CONTENTS" << EOF
@name nswall-controller-${VERSION}
@arch ${ARCH}
@owner root
@group wheel
@mode 0755
usr/local/sbin/nswall-controller
@mode 0644
usr/local/share/examples/nswall-controller/nswall-controller.conf
@mode 0755
@sample /etc/rc.d/nswall_controller
etc/rc.d/nswall_controller
EOF

cat > "$CTRL_PKG/+DESC" << EOF
NSWall Central Controller

Central management server for NSWall fleet using NATS messaging.
Features include:

- Embedded NATS server with JetStream
- Real-time agent status monitoring
- Configuration push to multiple agents
- Group management for bulk operations
- WebUI dashboard
- HA via NATS clustering

WWW: https://github.com/northshorenetworks/nswall
EOF

cat > "$CTRL_PKG/+COMMENT" << EOF
Central controller for NSWall fleet management
EOF

# Build packages
log "Building OpenBSD packages..."
cd "$BUILD_DIR"

pkg_create -p / -B "$API_PKG" -f "$API_PKG/+CONTENTS" \
    -D COMMENT="$(cat $API_PKG/+COMMENT)" \
    -D FULLPKGPATH=nswall-api \
    -d "$API_PKG/+DESC" \
    "$PKG_DIR/nswall-api-${VERSION}.tgz"

pkg_create -p / -B "$CTRL_PKG" -f "$CTRL_PKG/+CONTENTS" \
    -D COMMENT="$(cat $CTRL_PKG/+COMMENT)" \
    -D FULLPKGPATH=nswall-controller \
    -d "$CTRL_PKG/+DESC" \
    "$PKG_DIR/nswall-controller-${VERSION}.tgz"

log "Packages created:"
ls -la "$PKG_DIR"/*.tgz

log "Done!"
