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
 * Routing protocol daemon control commands
 * Covers: ospfd, bgpd, ripd, ldpd, eigrpd
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "externs.h"
#include "ctl.h"

/*
 * OSPF - Open Shortest Path First
 */
char *ctl_ospf_test[] = { OSPFD, "-nf", OSPFCONF_TEMP, '\0' };
char *ctl_ospf_default[] = { NULL, "router-id 0.0.0.0\n", NULL };

struct ctl ctl_ospf[] = {
	{ "enable",     "enable service",
	    { OSPFD, "-f", OSPFCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "ospfd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { OSPFCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "ospfd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { OSPFCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { OSPFCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { OSPFCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { OSPFCONF_TEMP, (char *)ctl_ospf_default, NULL }, ctl_init_config, NULL },
	{ "reload",     "reload service",
	    { OSPFCTL, "reload", NULL }, NULL, NULL },
	{ "fib",        "fib couple/decouple/reload",
	    { OSPFCTL, "fib", REQ, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { OSPFCTL, "log", REQ, NULL }, NULL, NULL },
	/* show database commands */
	{ "database",   "show link state database",
	    { OSPFCTL, "show", "database", OPT, NULL }, NULL, NULL },
	{ "database-asbr", "show ASBR LSAs",
	    { OSPFCTL, "show", "database", "asbr", NULL }, NULL, NULL },
	{ "database-external", "show AS-External LSAs",
	    { OSPFCTL, "show", "database", "external", NULL }, NULL, NULL },
	{ "database-network", "show Network LSAs",
	    { OSPFCTL, "show", "database", "network", NULL }, NULL, NULL },
	{ "database-router", "show Router LSAs",
	    { OSPFCTL, "show", "database", "router", NULL }, NULL, NULL },
	{ "database-self", "show self-originated LSAs",
	    { OSPFCTL, "show", "database", "self-originated", NULL }, NULL, NULL },
	{ "database-summary", "show Summary LSAs",
	    { OSPFCTL, "show", "database", "summary", NULL }, NULL, NULL },
	/* show fib commands */
	{ "show-fib",   "show forwarding table",
	    { OSPFCTL, "show", "fib", OPT, NULL }, NULL, NULL },
	{ "fib-connected", "show connected routes",
	    { OSPFCTL, "show", "fib", "connected", NULL }, NULL, NULL },
	{ "fib-interface", "show interface routes",
	    { OSPFCTL, "show", "fib", "interface", OPT, NULL }, NULL, NULL },
	{ "fib-ospf",   "show OSPF routes",
	    { OSPFCTL, "show", "fib", "ospf", NULL }, NULL, NULL },
	{ "fib-static", "show static routes",
	    { OSPFCTL, "show", "fib", "static", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * BGP - Border Gateway Protocol
 */
char *ctl_bgp_test[] = { BGPD, "-nf", BGPCONF_TEMP, NULL, '\0' };
char *ctl_bgp_default[] = { NULL, "AS 65000\nrouter-id 0.0.0.0\n", NULL };

struct ctl ctl_bgp[] = {
	{ "enable",     "enable service",
	    { BGPD, "-f", BGPCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "bgpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { BGPCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "bgpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { BGPCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { BGPCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { BGPCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { BGPCONF_TEMP, (char *)ctl_bgp_default, NULL }, ctl_init_config, NULL },
	{ "reload",     "reload service",
	    { BGPCTL, "reload", NULL }, NULL, NULL },
	{ "fib",	"fib couple/decouple",
	    { BGPCTL, "fib", REQ, NULL }, NULL, NULL },
	{ "irrfilter",	"generate bgpd filters",
	    { BGPCTL, "irrfilter", REQ, OPT, NULL }, NULL, NULL },
	{ "neighbor",	"neighbor up/down/clear/refresh",
	    { BGPCTL, "neighbor", OPT, OPT, NULL }, NULL, NULL },
	{ "network",	"network add/delete/flush/show",
	    { BGPCTL, "network", REQ, OPT, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { BGPCTL, "log", REQ, NULL }, NULL, NULL },
	{ "show-tables", "show routing tables",
	    { BGPCTL, "show", "tables", NULL }, NULL, NULL },
	{ "show-fib",   "show FIB bgp/connected/static",
	    { BGPCTL, "show", "fib", OPT, NULL }, NULL, NULL },
	{ "flowspec",   "flowspec add/delete/flush/show",
	    { BGPCTL, "flowspec", REQ, OPT, OPT, NULL }, NULL, NULL },
	{ "neighbor-destroy", "destroy cloned peer",
	    { BGPCTL, "neighbor", REQ, "destroy", NULL }, NULL, NULL },
	{ "network-bulk", "bulk add networks via stdin",
	    { BGPCTL, "network", "bulk", REQ, NULL }, NULL, NULL },
	{ "network-mrt", "import MRT table dump",
	    { BGPCTL, "network", "mrt", "file", REQ, OPT, NULL }, NULL, NULL },
	{ "show-metrics", "show metrics",
	    { BGPCTL, "show", "metrics", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * RIP - Routing Information Protocol
 */
char *ctl_rip_test[] = { RIPD, "-nf", RIPCONF_TEMP, '\0' };
char *ctl_rip_default[] = { NULL, "# RIP Configuration\n", NULL };

struct ctl ctl_rip[] = {
	{ "enable",     "enable service",
	    { RIPD, "-f", RIPCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "ripd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { RIPCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "ripd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { RIPCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { RIPCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { RIPCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { RIPCONF_TEMP, (char *)ctl_rip_default, NULL }, ctl_init_config, NULL },
	{ "reload",     "reload service",
	    { RIPCTL, "reload", NULL }, NULL, NULL },
	{ "fib",        "fib couple/decouple",
	    { RIPCTL, "fib", REQ, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { RIPCTL, "log", REQ, NULL }, NULL, NULL },
	/* Additional ripctl commands for 100% coverage */
	{ "show-fib",   "show FIB entries",
	    { RIPCTL, "show", "fib", OPT, NULL }, NULL, NULL },
	{ "show-interfaces", "show interfaces",
	    { RIPCTL, "show", "interfaces", NULL }, NULL, NULL },
	{ "show-neighbor", "show neighbors",
	    { RIPCTL, "show", "neighbor", NULL }, NULL, NULL },
	{ "show-rib",   "show Routing Information Base",
	    { RIPCTL, "show", "rib", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * LDP - MPLS Label Distribution Protocol
 */
char *ctl_ldp_test[] = { LDPD, "-n", "-f", LDPDCONF_TEMP, NULL };
char *ctl_ldp_default[] = { NULL, "router-id 0.0.0.0\n", NULL };

struct ctl ctl_ldp[] = {
	{ "enable",     "enable MPLS LDP service",
	    { LDPD, "-f", LDPDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable MPLS LDP service",
	    { PKILL, "ldpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { LDPDCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "ldpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { LDPDCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { LDPDCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { LDPDCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { LDPDCONF_TEMP, (char *)ctl_ldp_default, NULL }, ctl_init_config, NULL },
	{ "reload",     "reload configuration",
	    { LDPCTL, "reload", NULL }, NULL, NULL },
	{ "fib",        "fib couple/decouple",
	    { LDPCTL, "fib", REQ, NULL }, NULL, NULL },
	{ "clear",      "clear neighbors",
	    { LDPCTL, "clear", "neighbors", OPT, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { LDPCTL, "log", REQ, NULL }, NULL, NULL },
	{ "l2vpn",      "show L2VPN bindings/pseudowires",
	    { LDPCTL, "show", "l2vpn", REQ, NULL }, NULL, NULL },
	/* Additional ldpctl commands for 100% coverage */
	{ "show-fib",   "show FIB entries",
	    { LDPCTL, "show", "fib", OPT, NULL }, NULL, NULL },
	{ "show-interfaces", "show interfaces",
	    { LDPCTL, "show", "interfaces", NULL }, NULL, NULL },
	{ "show-neighbor", "show neighbors",
	    { LDPCTL, "show", "neighbor", OPT, NULL }, NULL, NULL },
	{ "show-lib",   "show Label Information Base",
	    { LDPCTL, "show", "lib", OPT, NULL }, NULL, NULL },
	{ "show-discovery", "show discovery",
	    { LDPCTL, "show", "discovery", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * EIGRP - Enhanced Interior Gateway Routing Protocol
 */
char *ctl_eigrp_test[] = { EIGRPD, "-n", "-f", EIGRPDCONF_TEMP, NULL };
char *ctl_eigrp_default[] = { NULL, "router-id 0.0.0.0\n", NULL };

struct ctl ctl_eigrp[] = {
	{ "enable",     "enable EIGRP routing",
	    { EIGRPD, "-f", EIGRPDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable EIGRP routing",
	    { PKILL, "eigrpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { EIGRPDCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "eigrpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { EIGRPDCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { EIGRPDCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { EIGRPDCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { EIGRPDCONF_TEMP, (char *)ctl_eigrp_default, NULL }, ctl_init_config, NULL },
	{ "reload",     "reload configuration",
	    { EIGRPCTL, "reload", NULL }, NULL, NULL },
	{ "fib",        "fib couple/decouple",
	    { EIGRPCTL, "fib", REQ, NULL }, NULL, NULL },
	{ "clear",      "clear neighbors",
	    { EIGRPCTL, "clear", "neighbors", OPT, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { EIGRPCTL, "log", REQ, NULL }, NULL, NULL },
	/* Additional eigrpctl commands for 100% coverage */
	{ "show-fib",   "show FIB entries",
	    { EIGRPCTL, "show", "fib", OPT, NULL }, NULL, NULL },
	{ "show-interfaces", "show interfaces",
	    { EIGRPCTL, "show", "interfaces", OPT, NULL }, NULL, NULL },
	{ "show-neighbor", "show neighbors",
	    { EIGRPCTL, "show", "neighbor", OPT, NULL }, NULL, NULL },
	{ "show-topology", "show topology",
	    { EIGRPCTL, "show", "topology", OPT, NULL }, NULL, NULL },
	{ "show-stats", "show statistics",
	    { EIGRPCTL, "show", "stats", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};
