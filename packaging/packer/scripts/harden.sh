#!/bin/sh
# Security hardening for NSWall appliance

set -e

echo "=== Security Hardening ==="

# Secure SSH
echo "Hardening SSH..."
cat >> /etc/ssh/sshd_config << 'EOF'

# NSWall SSH Hardening
PermitRootLogin prohibit-password
PasswordAuthentication no
PubkeyAuthentication yes
X11Forwarding no
AllowTcpForwarding no
MaxAuthTries 3
LoginGraceTime 30
ClientAliveInterval 300
ClientAliveCountMax 2
EOF

# Secure permissions
echo "Setting secure permissions..."
chmod 700 /root
chmod 600 /etc/master.passwd
chmod 600 /etc/ssh/ssh_host_*_key

# Remove unnecessary services
echo "Disabling unnecessary services..."
rcctl disable sndiod || true

# Set secure sysctl values
cat >> /etc/sysctl.conf << 'EOF'

# Security hardening
kern.securelevel=1
net.inet.ip.sourceroute=0
net.inet.icmp.rediraccept=0
net.inet.ip.redirect=0
net.inet6.ip6.redirect=0
EOF

# Create security audit script
cat > /usr/local/sbin/nswall-audit << 'EOF'
#!/bin/sh
# NSWall Security Audit

echo "=== NSWall Security Audit ==="
echo "Date: $(date)"
echo ""

echo "=== PF Status ==="
pfctl -si

echo ""
echo "=== Open Ports ==="
netstat -an | grep LISTEN

echo ""
echo "=== Failed Logins ==="
tail -20 /var/log/authlog | grep -i failed || echo "None"

echo ""
echo "=== Disk Usage ==="
df -h

echo ""
echo "=== Process List ==="
ps aux | head -20

echo ""
echo "=== Recent Auth Events ==="
tail -10 /var/log/authlog
EOF
chmod 755 /usr/local/sbin/nswall-audit

echo "Hardening complete."
