/* $NSWall$ */
/*
 * NSWall - Network Shell for OpenBSD firewalls
 * Forked from NSH (Network Shell) by Chris Cappuccio
 *
 * Copyright (c) 2008 Chris Cappuccio <chris@nmedia.net>
 * Copyright (c) 2024-2025 NSWall Contributors
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
 * This file is part of the NSWall project, which extends NSH with
 * additional OpenBSD daemon support and API functionality.
 * Original NSH: https://github.com/yellowman/nsh
 */

#ifndef _CTL_H_
#define _CTL_H_

/*
 * Daemon executable paths
 * These are the service daemons managed by NSWall
 */

/* Core routing daemons */
#define OSPFD		"/usr/sbin/ospfd"
#define BGPD		"/usr/sbin/bgpd"
#define RIPD		"/usr/sbin/ripd"
#define DVMRPD		"/usr/sbin/dvmrpd"
#define EIGRPD		"/usr/sbin/eigrpd"
#define LDPD		"/usr/sbin/ldpd"

/* VPN daemons */
#define ISAKMPD		"/sbin/isakmpd"
#define IKED		"/sbin/iked"
#define IKECTL		"/usr/sbin/ikectl"

/* Network services */
#define RELAYD		"/usr/sbin/relayd"
#define DHCPD		"/usr/sbin/dhcpd"
#define SNMPD		"/usr/sbin/snmpd"
#define NTPD		"/usr/sbin/ntpd"
#define SSHD		"/usr/sbin/sshd"
#define HTTPD		"/usr/sbin/httpd"
#define SMTPD		"/usr/sbin/smtpd"
#define SMTPCTL		"/usr/sbin/smtpctl"
#define FTPPROXY	"/usr/sbin/ftp-proxy"
#define INETD		"/usr/sbin/inetd"
#define SASYNCD		"/usr/sbin/sasyncd"

/* DNS services */
#define UNBOUND		"/usr/sbin/unbound"
#define UNBOUNDCTL	"/usr/sbin/unbound-control"

/* IPv6 services */
#define RAD		"/usr/sbin/rad"
#define RACTL		"/usr/sbin/ractl"

/* NTP control */
#define NTPCTL		"/usr/sbin/ntpctl"

/* Certificate management */
#define ACMECLIENT	"/usr/sbin/acme-client"

/* Logging */
#define PFLOGD		"/usr/sbin/pflogd"
#define TCPDUMP		"/usr/sbin/tcpdump"

/* NSWall-specific tools */
#define NSPFSH		"/usr/bin/nspf"
#define PFRELOAD	"/usr/bin/pfreload"

/*
 * Shared function prototypes for ctl modules
 */
void call_editor(char *, char **, char *);
void ctl_symlink(char *, char **, char *);
void ctl_show_config(char *, char **, char *);
void ctl_show_status(char *, char **, char *);
void ctl_set_config(char *, char **, char *);
void ctl_unset_config(char *, char **, char *);
void ctl_append_config(char *, char **, char *);
void ctl_init_config(char *, char **, char *);

#endif /* _CTL_H_ */
