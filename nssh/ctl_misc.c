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
 */

/*
 * Miscellaneous daemon control commands
 * Covers: dvmrpd, sasyncd, ftp-proxy, dns, inetd, rad, pflogd
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "externs.h"
#include "ctl.h"

/*
 * DVMRP - Distance Vector Multicast Routing Protocol
 */
char *ctl_dvmrp_test[] = { DVMRPD, "-nf", DVMRPCONF_TEMP, '\0' };
char *ctl_dvmrp_default[] = { NULL, "# DVMRP Configuration\n", NULL };

struct ctl ctl_dvmrp[] = {
	{ "enable",     "enable service",
	    { DVMRPD, "-f", DVMRPCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "dvmrpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { DVMRPCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "dvmrpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { DVMRPCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { DVMRPCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { DVMRPCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { DVMRPCONF_TEMP, (char *)ctl_dvmrp_default, NULL }, ctl_init_config, NULL },
	{ "test",       "test configuration syntax",
	    { DVMRPD, "-nf", DVMRPCONF_TEMP, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * sasyncd - IPsec SA Synchronization Daemon
 */
char *ctl_sasync_default[] = { NULL, "# sasyncd Configuration\n# interface carp0\n# peer 10.0.0.2\n", NULL };

struct ctl ctl_sasync[] = {
	{ "enable",     "enable service",
	    { SASYNCD, "-c", SASYNCCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "sasyncd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { SASYNCCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "sasyncd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { SASYNCCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { SASYNCCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { SASYNCCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { SASYNCCONF_TEMP, (char *)ctl_sasync_default, NULL }, ctl_init_config, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * FTP Proxy
 */
struct ctl ctl_ftpproxy[] = {
	{ "enable",	"enable service",
	    { FTPPROXY, "-T", "ftp-proxy", "-D", "2", NULL }, NULL, X_ENABLE },
	{ "disable",	"disable service",
	    { PKILL, "ftp-proxy", NULL }, NULL, X_DISABLE },
	{ "status",     "show daemon status",
	    { "ftp-proxy", NULL, NULL }, ctl_show_status, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * DNS (resolv.conf management)
 */
char *ctl_dns_default[] = { NULL, "# DNS Configuration\nnameserver 8.8.8.8\nnameserver 8.8.4.4\n", NULL };

struct ctl ctl_dns[] = {
	{ "local-control", "local control over DNS settings",
	    { RESOLVCONF_SYM, NULL, RESOLVCONF_TEMP, NULL }, ctl_symlink,
	    X_LOCAL },
	{ "dhcp-control",   "DHCP client control over DNS settings",
	    { RESOLVCONF_SYM, NULL, RESOLVCONF_DHCP, NULL }, ctl_symlink,
	    X_OTHER },
	{ "show",       "show configuration",
	    { RESOLVCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "set",        "set config <key> <value>",
	    { RESOLVCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { RESOLVCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { RESOLVCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { RESOLVCONF_TEMP, (char *)ctl_dns_default, NULL }, ctl_init_config, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * inetd - Internet Super-Server
 */
char *ctl_inet_default[] = { NULL, "# inetd Configuration\n# service socket protocol wait/nowait user program args\n", NULL };

struct ctl ctl_inet[] = {
	{ "enable",     "enable service",
	    { INETD, INETCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "inetd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { INETCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "inetd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { INETCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { INETCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { INETCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { INETCONF_TEMP, (char *)ctl_inet_default, NULL }, ctl_init_config, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * rad - IPv6 Router Advertisement Daemon
 */
char *ctl_rad_test[] = { RAD, "-n", "-f", RADCONF_TEMP, NULL };
char *ctl_rad_default[] = { NULL, "# Router Advertisement Configuration\ninterface em0\n", NULL };

struct ctl ctl_rad[] = {
	{ "enable",     "enable router advertisements",
	    { RAD, "-f", RADCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable router advertisements",
	    { PKILL, "rad", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { RADCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "rad", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { RADCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { RADCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { RADCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { RADCONF_TEMP, (char *)ctl_rad_default, NULL }, ctl_init_config, NULL },
	{ "test",       "test configuration syntax",
	    { RAD, "-n", "-f", RADCONF_TEMP, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { RACTL, "log", REQ, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * pflogd - PF Logging Daemon
 */
struct ctl ctl_pflog[] = {
	{ "enable",     "enable PF logging",
	    { PFLOGD, "-s", "160", "-f", PFLOGD_LOGFILE, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable PF logging",
	    { PKILL, "pflogd", NULL }, NULL, X_DISABLE },
	{ "read",       "read recent log entries",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "-c", "50", NULL }, NULL, NULL },
	{ "live",       "monitor live traffic",
	    { TCPDUMP, "-n", "-e", "-ttt", "-i", "pflog0", NULL }, NULL, NULL },
	{ "filter",     "read with filter expression",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, REQ, OPT, OPT, NULL }, NULL, NULL },
	/* Additional pflogd commands for 100% coverage */
	{ "enable-iface", "enable with custom interface",
	    { PFLOGD, "-i", REQ, "-s", "160", "-f", PFLOGD_LOGFILE, NULL }, NULL, X_ENABLE },
	{ "enable-snaplen", "enable with custom snaplen",
	    { PFLOGD, "-s", REQ, "-f", PFLOGD_LOGFILE, NULL }, NULL, X_ENABLE },
	{ "live-iface", "monitor on specific interface",
	    { TCPDUMP, "-n", "-e", "-ttt", "-i", REQ, NULL }, NULL, NULL },
	{ "filter-ip",  "filter by IP protocol",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "ip", NULL }, NULL, NULL },
	{ "filter-ip6", "filter by IPv6 protocol",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "ip6", NULL }, NULL, NULL },
	{ "filter-rulenum", "filter by rule number",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "rulenum", REQ, NULL }, NULL, NULL },
	{ "filter-reason", "filter by reason (match/bad-offset/...)",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "reason", REQ, NULL }, NULL, NULL },
	{ "filter-action", "filter by action (pass/block)",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "action", REQ, NULL }, NULL, NULL },
	{ "filter-ifname", "filter by interface name",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "ifname", REQ, NULL }, NULL, NULL },
	{ "read-all",   "read all log entries",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, NULL }, NULL, NULL },
	{ "read-count", "read specific count",
	    { TCPDUMP, "-n", "-e", "-ttt", "-r", PFLOGD_LOGFILE, "-c", REQ, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};
