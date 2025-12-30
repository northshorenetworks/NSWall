#!/bin/sh
# Cleanup before image creation

set -e

echo "=== Cleanup ==="

# Clear package cache
echo "Clearing package cache..."
rm -rf /var/cache/pkg/*

# Clear logs
echo "Clearing logs..."
find /var/log -type f -exec truncate -s 0 {} \;

# Clear temporary files
echo "Clearing temporary files..."
rm -rf /tmp/*
rm -rf /var/tmp/*

# Clear shell history
echo "Clearing history..."
rm -f /root/.history
rm -f /root/.bash_history
rm -f /home/*/.history
rm -f /home/*/.bash_history

# Clear SSH host keys (regenerate on first boot)
echo "Clearing SSH host keys..."
rm -f /etc/ssh/ssh_host_*

# Create first-boot script to regenerate keys
cat > /etc/rc.firsttime << 'EOF'
#!/bin/sh
# First boot initialization for NSWall

# Regenerate SSH host keys
ssh-keygen -A

# Generate random API key if not set
if [ ! -f /etc/nswall-api.key ]; then
    openssl rand -hex 32 > /etc/nswall-api.key
    chmod 600 /etc/nswall-api.key
    echo "API key generated: $(cat /etc/nswall-api.key)"
fi

# Start services
rcctl start nswall_api

echo "NSWall first boot complete!"
EOF

# Zero free space (for smaller images)
echo "Zeroing free space (this may take a while)..."
dd if=/dev/zero of=/zero bs=1M 2>/dev/null || true
rm -f /zero

# Sync filesystem
sync

echo "Cleanup complete. Ready for image creation."
