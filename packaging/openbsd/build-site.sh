#!/bin/ksh
#
# Build NSWall siteXX.tgz for OpenBSD installation
#
# Usage: ./build-site.sh [options]
#   -v VERSION    OpenBSD version (e.g., 75 for 7.5)
#   -c CONFIG     Import config tarball (from backup export)
#   -b BINARIES   Path to pre-built binaries directory
#   -o OUTPUT     Output directory (default: ./dist)
#
# The resulting siteXX.tgz can be:
# 1. Placed in the OpenBSD install media root
# 2. Served via HTTP during netboot installation
# 3. Used with autoinstall for unattended deployments

set -e

VERSION="75"
CONFIG=""
BINARIES=""
OUTPUT="./dist"

usage() {
    echo "Usage: $0 [-v VERSION] [-c CONFIG] [-b BINARIES] [-o OUTPUT]"
    echo ""
    echo "  -v VERSION    OpenBSD version number (default: 75)"
    echo "  -c CONFIG     Path to config backup tarball to pre-import"
    echo "  -b BINARIES   Path to directory containing nswall-api binary"
    echo "  -o OUTPUT     Output directory (default: ./dist)"
    echo ""
    echo "Examples:"
    echo "  $0 -v 75                     # Build site75.tgz with default config"
    echo "  $0 -v 75 -c backup.tar.gz    # Build with pre-imported config"
    echo "  $0 -v 75 -b ./bin            # Build with local binaries"
    exit 1
}

while getopts "v:c:b:o:h" opt; do
    case $opt in
        v) VERSION="$OPTARG" ;;
        c) CONFIG="$OPTARG" ;;
        b) BINARIES="$OPTARG" ;;
        o) OUTPUT="$OPTARG" ;;
        h) usage ;;
        *) usage ;;
    esac
done

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
SITE_DIR="$SCRIPT_DIR/site"
WORK_DIR=$(mktemp -d)
trap "rm -rf $WORK_DIR" EXIT

echo "=== Building NSWall site${VERSION}.tgz ==="

# Create directory structure
mkdir -p "$WORK_DIR"/{etc,etc/rc.d,etc/ssl/private,var/nswall,usr/local/bin,usr/local/share/nswall}

# Copy base site files
echo "Copying base configuration..."
cp -R "$SITE_DIR"/* "$WORK_DIR/"

# Set permissions on rc.d scripts
chmod 755 "$WORK_DIR/etc/rc.d/"* 2>/dev/null || true

# Copy install.site to root
if [ -f "$WORK_DIR/install.site" ]; then
    chmod 755 "$WORK_DIR/install.site"
fi

# Add binaries if provided
if [ -n "$BINARIES" ] && [ -d "$BINARIES" ]; then
    echo "Adding binaries from $BINARIES..."

    if [ -f "$BINARIES/nswall-api" ]; then
        cp "$BINARIES/nswall-api" "$WORK_DIR/usr/local/bin/"
        chmod 755 "$WORK_DIR/usr/local/bin/nswall-api"
    fi

    if [ -f "$BINARIES/nswall-controller" ]; then
        cp "$BINARIES/nswall-controller" "$WORK_DIR/usr/local/bin/"
        chmod 755 "$WORK_DIR/usr/local/bin/nswall-controller"
    fi
else
    echo "No binaries provided - creating download script..."

    # Create a script to download binaries on first boot
    cat > "$WORK_DIR/usr/local/bin/nswall-download" << 'DLEOF'
#!/bin/ksh
# Download NSWall binaries
RELEASE_URL="https://github.com/northshorenetworks/nswall/releases/latest/download"
ARCH=$(uname -m)

case $ARCH in
    amd64) ARCH="amd64" ;;
    arm64) ARCH="arm64" ;;
    *) echo "Unsupported architecture: $ARCH"; exit 1 ;;
esac

echo "Downloading NSWall API for OpenBSD $ARCH..."
ftp -o /usr/local/bin/nswall-api "$RELEASE_URL/nswall-api-openbsd-$ARCH"
chmod 755 /usr/local/bin/nswall-api

echo "Done! Start with: rcctl start nswall_api"
DLEOF
    chmod 755 "$WORK_DIR/usr/local/bin/nswall-download"

    # Update install.site to download on first boot
    cat >> "$WORK_DIR/install.site" << 'INSTEOF'

# Download binaries if not present
if [ ! -f /usr/local/bin/nswall-api ]; then
    echo "Downloading NSWall binaries..."
    /usr/local/bin/nswall-download || true
fi
INSTEOF
fi

# Import configuration if provided (expects nsh.conf file)
if [ -n "$CONFIG" ] && [ -f "$CONFIG" ]; then
    echo "Including configuration: $CONFIG"
    # Accept either .nsh file or extract nsh.conf from tarball
    case "$CONFIG" in
        *.nsh|*.conf)
            cp "$CONFIG" "$WORK_DIR/var/nswall/import-config.nsh"
            ;;
        *.tar.gz|*.tgz)
            # Extract nsh.conf from backup tarball
            tar -xzf "$CONFIG" -C "$WORK_DIR/var/nswall" etc/nsh/nsh.conf 2>/dev/null && \
                mv "$WORK_DIR/var/nswall/etc/nsh/nsh.conf" "$WORK_DIR/var/nswall/import-config.nsh" && \
                rm -rf "$WORK_DIR/var/nswall/etc" || \
                echo "Warning: Could not extract nsh.conf from tarball"
            ;;
        *)
            echo "Warning: Unknown config format, copying as-is"
            cp "$CONFIG" "$WORK_DIR/var/nswall/import-config.nsh"
            ;;
    esac
fi

# Create version file
cat > "$WORK_DIR/var/nswall/version" << EOF
NSWall Appliance
Version: 2.0.0
Build: $(date +%Y%m%d%H%M%S)
OpenBSD: ${VERSION%?}.${VERSION#?}
EOF

# Copy factory defaults for reset functionality
mkdir -p "$WORK_DIR/usr/local/share/nswall"
cp "$WORK_DIR/etc/nsh/nsh.conf" "$WORK_DIR/usr/local/share/nswall/factory-defaults.nsh"

# Create factory reset script
cat > "$WORK_DIR/usr/local/bin/nswall-factory-reset" << 'RESETEOF'
#!/bin/ksh
#
# NSWall Factory Reset
#
# This restores the firewall to factory defaults using the default nsh.conf

echo "WARNING: This will erase all configuration!"
echo "The firewall will reboot with factory default settings."
echo ""
echo -n "Type 'RESET' to confirm: "
read confirm

if [ "$confirm" != "RESET" ]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Creating backup of current configuration..."
nswall-export-config /var/nswall/pre-reset-$(date +%Y%m%d-%H%M%S).nsh

echo "Restoring factory defaults..."

# Copy factory default nsh.conf
if [ -f /usr/local/share/nswall/factory-defaults.nsh ]; then
    cp /usr/local/share/nswall/factory-defaults.nsh /etc/nsh/nsh.conf
else
    # Minimal default if factory file is missing
    cat > /etc/nsh/nsh.conf << 'NSHEOF'
!
! NSWall Factory Default Configuration
!
hostname nswall
!
interface em0
 description "WAN"
 ip dhcp
!
interface em1
 description "LAN"
 ip address 192.168.1.1/24
!
sysctl net.inet.ip.forwarding=1
!
pf enable
pf rules
set skip on lo
set block-policy drop
block in log all
block out log all
pass out on em0 proto { tcp udp } all modulate state
pass in on em1 from (em1:network) to any keep state
pass in on em1 proto tcp to (em1) port { 22 443 } keep state
.
!
NSHEOF
fi

chmod 600 /etc/nsh/nsh.conf

# Apply the config
echo "Applying factory configuration..."
nsh -c "write-config"

# Reset hostname
echo "nswall" > /etc/myname

# Reset admin password
NEW_PASS=$(openssl rand -base64 12)
echo "$NEW_PASS" | encrypt > /var/nswall/admin.key
echo "$NEW_PASS" > /root/nswall-initial-password.txt
chmod 600 /root/nswall-initial-password.txt

echo ""
echo "=========================================="
echo "  Factory reset complete!"
echo "  New admin password: $NEW_PASS"
echo "  (Also saved to /root/nswall-initial-password.txt)"
echo "=========================================="
echo ""
echo "Rebooting in 5 seconds..."
sleep 5
reboot
RESETEOF
chmod 755 "$WORK_DIR/usr/local/bin/nswall-factory-reset"

# Create configuration export helper (nsh.conf is the single source of truth)
cat > "$WORK_DIR/usr/local/bin/nswall-export-config" << 'EXPEOF'
#!/bin/ksh
#
# Export NSWall configuration
# nsh.conf is the single source of truth - it contains all configuration
#

OUTPUT="${1:-/tmp/nswall-config-$(date +%Y%m%d).nsh}"

# Ensure we have the latest running config
nsh -c "write-config" 2>/dev/null

if [ ! -f /etc/nsh/nsh.conf ]; then
    echo "Error: No configuration found at /etc/nsh/nsh.conf"
    exit 1
fi

echo "Exporting NSWall configuration to: $OUTPUT"
cp /etc/nsh/nsh.conf "$OUTPUT"

SIZE=$(ls -lh "$OUTPUT" | awk '{print $5}')
echo ""
echo "Export complete: $OUTPUT ($SIZE)"
echo ""
echo "This file contains your complete firewall configuration."
echo ""
echo "To restore on a new system:"
echo "  1. Copy to /var/nswall/import-config.nsh before first boot, or"
echo "  2. Use: nswall-import-config $OUTPUT"
echo ""
echo "To view the config: cat $OUTPUT"
EXPEOF
chmod 755 "$WORK_DIR/usr/local/bin/nswall-export-config"

# Create configuration import helper
cat > "$WORK_DIR/usr/local/bin/nswall-import-config" << 'IMPEOF'
#!/bin/ksh
#
# Import NSWall configuration from backup
# nsh.conf is the single source of truth
#

if [ -z "$1" ]; then
    echo "Usage: nswall-import-config <config.nsh>"
    echo ""
    echo "Import a previously exported NSWall configuration."
    echo "The config file should be an nsh.conf export."
    exit 1
fi

if [ ! -f "$1" ]; then
    echo "Error: File not found: $1"
    exit 1
fi

echo "Importing configuration from: $1"
echo ""
echo "WARNING: This will overwrite your current configuration!"
echo -n "Continue? [y/N] "
read confirm

case $confirm in
    [yY]|[yY][eE][sS])
        echo ""
        echo "Creating backup of current configuration..."
        nswall-export-config /var/nswall/pre-import-$(date +%Y%m%d-%H%M%S).nsh

        echo "Importing new configuration..."
        cp "$1" /etc/nsh/nsh.conf
        chmod 600 /etc/nsh/nsh.conf

        echo "Applying configuration..."
        nsh -c "write-config"

        echo ""
        echo "Import complete!"
        echo "Run 'nsh' to verify the configuration."
        ;;
    *)
        echo "Aborted."
        exit 1
        ;;
esac
IMPEOF
chmod 755 "$WORK_DIR/usr/local/bin/nswall-import-config"

# Build the tarball
mkdir -p "$OUTPUT"
SITE_TGZ="$OUTPUT/site${VERSION}.tgz"

echo "Creating $SITE_TGZ..."
cd "$WORK_DIR"
tar -czf "$SITE_TGZ" .

# Also create SHA256
cd "$OUTPUT"
sha256 "site${VERSION}.tgz" > "site${VERSION}.tgz.sha256"

echo ""
echo "=== Build Complete ==="
echo "Site tarball: $SITE_TGZ"
echo "SHA256:       $SITE_TGZ.sha256"
echo ""
echo "Usage:"
echo "  1. Place on OpenBSD install media as site${VERSION}.tgz"
echo "  2. Or serve via HTTP for netboot installations"
echo "  3. Or use with autoinstall(8) for automated deployments"
echo ""
echo "For autoinstall, add to install.conf:"
echo "  Location of sets = http://your-server/openbsd/${VERSION%?}.${VERSION#?}/amd64"
echo ""
