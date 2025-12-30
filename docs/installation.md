---
layout: default
title: Installation - NSWall
---

# Installation Guide

This guide covers installing NSWall on OpenBSD systems.

---

## Requirements

### System Requirements
- OpenBSD 7.4 or later (7.6 recommended)
- 512MB RAM minimum (1GB+ recommended)
- 1GB disk space
- Network interface(s)

### Build Requirements
- OpenBSD source tree (`/usr/src`)
- C compiler (included with OpenBSD)
- Make (included with OpenBSD)

---

## Quick Installation

### 1. Install OpenBSD

Download and install OpenBSD from [openbsd.org](https://www.openbsd.org/).

During installation:
- Select minimal install
- Configure network interface
- Enable SSH for remote access

### 2. Get OpenBSD Source

```bash
cd /usr
cvs -qd anoncvs@anoncvs.openbsd.org:/cvs checkout -P src
```

Or download source tarball:
```bash
cd /usr/src
ftp https://cdn.openbsd.org/pub/OpenBSD/7.6/src.tar.gz
tar xzf src.tar.gz
```

### 3. Clone NSWall

```bash
pkg_add git
git clone https://github.com/northshorenetworks/NSWall.git
cd NSWall
```

### 4. Build NSWall

```bash
# Build nssh CLI
cd nssh
make
make install

# Build complete system (optional)
cd ..
make KCONF=GENERIC-RD
```

---

## Build Options

### Kernel Configurations

NSWall supports multiple hardware platforms:

| Config | Target Hardware |
|--------|-----------------|
| `GENERIC-RD` | Standard x86/amd64 |
| `COMMELL-LE564` | Commell embedded PC |
| `SOEKRIS4501` | Soekris Net4501 |
| `SOEKRIS4801` | Soekris Net4801 |
| `WRAP12` | PC Engines WRAP |
| `NSWALL` | Custom NSWall config |

Build for specific platform:
```bash
make KCONF=SOEKRIS4801
```

### RAM Disk Sizes

Adjust in Makefile:
```makefile
NBLKS=51200     # 25MB (default)
#NBLKS=40960    # 20MB
#NBLKS=30720    # 15MB
#NBLKS=20480    # 10MB
```

---

## Installation Methods

### Method 1: Bootable Image

Create a bootable disk image:

```bash
# Build the image
make KCONF=GENERIC-RD

# Write to USB drive (replace sd1 with your device)
dd if=bsd.gz of=/dev/rsd1c bs=1m
```

### Method 2: Install on Existing OpenBSD

Install nssh CLI on running OpenBSD:

```bash
cd nssh
make
make install

# Copy initial configuration
cp -r ../initial-conf/* /conf/
```

### Method 3: Live CD

Build a bootable ISO:

```bash
./build-livecd.sh
```

---

## Post-Installation

### Initial Configuration

1. Connect via serial console or SSH
2. Login as root
3. Run nssh:

```bash
nssh
```

### Basic Setup

```
nswall> enable
Password: [enter enable password]

nswall# configure terminal

! Set hostname
nswall(config)# hostname myrouter

! Configure management interface
nswall(config)# interface em0
nswall(config-if)# ip address 192.168.1.1/24
nswall(config-if)# no shutdown
nswall(config-if)# exit

! Enable SSH
nswall(config)# sshd enable

! Enable packet filter
nswall(config)# pf enable

! Save configuration
nswall(config)# exit
nswall# write memory
```

### Set Enable Password

```
nswall# configure terminal
nswall(config)# enable password
Enter new password:
Confirm password:
```

---

## Upgrading

### From Git

```bash
cd NSWall
git pull
cd nssh
make clean
make
make install
```

### Preserving Configuration

Before upgrading, backup your configuration:

```
nswall# copy running-config tftp://backup-server/nswall.conf
```

Or:
```bash
cp /conf/nssh.conf /backup/
```

---

## Troubleshooting

### Build Errors

**Missing source tree:**
```
error: cannot find /usr/src/sys
```
Solution: Install OpenBSD source (see step 2).

**Compiler errors:**
```
undefined reference to...
```
Solution: Ensure source tree matches your OpenBSD version.

### Runtime Issues

**nssh not starting:**
```bash
# Check for errors
nssh -d
```

**Configuration not saving:**
```bash
# Check /conf directory exists and is writable
ls -la /conf/
```

---

## Next Steps

- [Features Overview](features/)
- [CLI Reference](cli-reference/)
- [Configuration Examples](documentation/)
