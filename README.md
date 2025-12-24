# NSWall 2.0

## Network Appliance Framework for OpenBSD

---

**Based on [NSH](https://github.com/yellowman/nsh) by Chris Cappuccio <chris@nmedia.net>**

NSWall transforms OpenBSD into a network appliance with a Junos/IOS-like CLI.
It replaces ifconfig, sysctl and route with its own command language, and
provides unified configuration for network daemons.

---

## Features

- **Familiar CLI**: Cisco IOS / Juniper Junos style interface
- **30+ Daemons**: bgpd, ospfd, eigrpd, pf, iked, relayd, and more
- **WireGuard VPN**: Native wg interface support
- **High Availability**: CARP and pfsync integration
- **OpenBSD Security**: Built on the most secure OS foundation

## Supported Daemons

bgpd, dhcpd, dhcpleased, dhcrelay, dvmrpd, eigrpd, ftp-proxy, ifstated, inetd,
iked, ipsecctl, ldapd, ldpd, npppd, ntpd, ospfd, ospf6d, pf, rad, relayd,
resolvd, ripd, sasyncd, slaacd, smtpd, snmpd, sshd, tftpd, tftp-proxy.

---

## Quick Install

### From Packages (OpenBSD)

```shell
# Install dependencies
pkg_add sqlite3

# Clone and build
git clone https://github.com/northshorenetworks/NSWall.git
cd NSWall
make
doas make install
```

### Set as Login Shell

```shell
doas sh -c 'echo /usr/local/bin/nsh >> /etc/shells'
chsh -s /usr/local/bin/nsh
```

---

## Usage

```
$ nsh
nswall> enable
Password:

nswall# show interface
nswall# show route
nswall# show running-config

nswall# configure
nswall(config)# hostname myrouter
nswall(config)# interface em0
nswall(config-em0)# ip 10.0.0.1/24
nswall(config-em0)# description "WAN uplink"
nswall(config-em0)# exit
nswall(config)# write-config
```

---

## System Integration

To have NSWall manage your system configuration at boot:

```shell
cd scripts/shell
doas sh ./rc.local-nsh-openbsd-integrate.sh
```

This will:
- Backup existing /etc configuration
- Import settings into NSWall format
- Configure system to load NSWall config at boot

---

## Documentation

- **Manual**: `man nsh` or type `manual <topic>` within the shell
- **Upstream NSH**: https://github.com/yellowman/nsh
- **NSH Website**: http://www.nmedia.net/nsh/

### Presentations

- [BSDCAN 2024: Supporting Business IT with OpenBSD and NSH](https://www.youtube.com/watch?v=9T9-v5NLjXk)
- [EurobsdCon 2022: NSH for Network Administrators](https://www.youtube.com/watch?v=WMKxIHaWaG0)

---

## License

BSD License - See [COPYRIGHT](COPYRIGHT)

NSWall is based on NSH, freely licensed in the BSD style by Chris Cappuccio.

---

## Contributing

- GitHub: https://github.com/northshorenetworks/NSWall
- Upstream NSH: https://github.com/yellowman/nsh
- NSH Mailing List: nsh@lists.deschutesdigital.com

## CI/CD

This fork includes GitHub Actions for automated builds on OpenBSD:
- OpenBSD 7.8 and 7.6 (x86_64)
- OpenBSD 7.8 (ARM64)
- Automated release packages
