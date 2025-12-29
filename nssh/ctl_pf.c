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
 * Packet Filter (pf) control commands
 * Provides comprehensive coverage of pfctl(8) functionality
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "externs.h"
#include "ctl.h"

char *ctl_pf_test[] = { PFCTL, "-nf", PFCONF_TEMP, '\0' };
char *ctl_pf_default[] = { NULL, "# PF Configuration\nset skip on lo0\nblock in all\npass out all keep state\n", NULL };

struct ctl ctl_pf[] = {
	{ "enable",	"enable service",
	    { PFCTL, "-e", NULL }, NULL, X_ENABLE },
	{ "disable",	"disable service",
	    { PFCTL, "-d", NULL }, NULL, X_DISABLE },
	/*
	 * Configuration file commands (Junos/IOS-style)
	 */
	{ "show-config", "show configuration file",
	    { PFCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "set",        "set config <key> <value>",
	    { PFCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { PFCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { PFCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { PFCONF_TEMP, (char *)ctl_pf_default, NULL }, ctl_init_config, NULL },
	{ "test",       "test configuration syntax",
	    { PFCTL, "-nf", PFCONF_TEMP, NULL }, NULL, NULL },
	{ "status",     "show daemon status",
	    { "pfctl", NULL, NULL }, ctl_show_status, NULL },
	/*
	 * pf.conf option commands (Junos/IOS-style)
	 * These provide direct manipulation of pf.conf options
	 */
	{ "set-block-policy", "set block-policy drop|return",
	    { "/bin/sh", "-c", "grep -v '^set block-policy' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set block-policy $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-state-policy", "set state-policy if-bound|floating",
	    { "/bin/sh", "-c", "grep -v '^set state-policy' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set state-policy $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-optimization", "set optimization aggressive|normal|...",
	    { "/bin/sh", "-c", "grep -v '^set optimization' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set optimization $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-loginterface", "set loginterface <interface>",
	    { "/bin/sh", "-c", "grep -v '^set loginterface' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set loginterface $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-skip",   "set skip on <interface>",
	    { "/bin/sh", "-c", "grep -v '^set skip on' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set skip on $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-syncookies", "set syncookies adaptive|always|never",
	    { "/bin/sh", "-c", "grep -v '^set syncookies' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set syncookies $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-reassemble", "set reassemble yes|no",
	    { "/bin/sh", "-c", "grep -v '^set reassemble' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set reassemble $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-debug",  "set debug none|urgent|misc|loud",
	    { "/bin/sh", "-c", "grep -v '^set debug' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set debug $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-ruleset-optimization", "set ruleset-optimization basic|none|profile",
	    { "/bin/sh", "-c", "grep -v '^set ruleset-optimization' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set ruleset-optimization $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-fingerprints", "set fingerprints <file>",
	    { "/bin/sh", "-c", "grep -v '^set fingerprints' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set fingerprints \\\"$0\\\"\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-hostid", "set hostid <id> for pfsync",
	    { "/bin/sh", "-c", "grep -v '^set hostid' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set hostid $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * pf.conf timeout commands
	 */
	{ "set-timeout-tcp.established", "set tcp.established timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout tcp.established' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout tcp.established $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-tcp.opening", "set tcp.opening timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout tcp.opening' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout tcp.opening $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-tcp.closing", "set tcp.closing timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout tcp.closing' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout tcp.closing $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-tcp.finwait", "set tcp.finwait timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout tcp.finwait' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout tcp.finwait $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-tcp.closed", "set tcp.closed timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout tcp.closed' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout tcp.closed $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-udp.first", "set udp.first timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout udp.first' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout udp.first $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-udp.single", "set udp.single timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout udp.single' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout udp.single $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-udp.multiple", "set udp.multiple timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout udp.multiple' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout udp.multiple $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-icmp.first", "set icmp.first timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout icmp.first' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout icmp.first $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-icmp.error", "set icmp.error timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout icmp.error' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout icmp.error $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-other.first", "set other.first timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout other.first' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout other.first $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-other.single", "set other.single timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout other.single' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout other.single $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-other.multiple", "set other.multiple timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout other.multiple' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout other.multiple $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-frag", "set fragment reassembly timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout frag' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout frag $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-interval", "set purge interval",
	    { "/bin/sh", "-c", "grep -v '^set timeout interval' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout interval $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-src.track", "set source tracking timeout",
	    { "/bin/sh", "-c", "grep -v '^set timeout src.track' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout src.track $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-adaptive.start", "set adaptive timeout start",
	    { "/bin/sh", "-c", "grep -v '^set timeout adaptive.start' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout adaptive.start $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-timeout-adaptive.end", "set adaptive timeout end",
	    { "/bin/sh", "-c", "grep -v '^set timeout adaptive.end' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set timeout adaptive.end $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * pf.conf limit commands
	 */
	{ "set-limit-states", "set max state entries",
	    { "/bin/sh", "-c", "grep -v '^set limit states' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set limit states $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-limit-src-nodes", "set max source tracking entries",
	    { "/bin/sh", "-c", "grep -v '^set limit src-nodes' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set limit src-nodes $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-limit-frags", "set max fragment entries",
	    { "/bin/sh", "-c", "grep -v '^set limit frags' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set limit frags $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-limit-tables", "set max tables",
	    { "/bin/sh", "-c", "grep -v '^set limit tables' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set limit tables $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-limit-table-entries", "set max table entries",
	    { "/bin/sh", "-c", "grep -v '^set limit table-entries' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set limit table-entries $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "set-limit-anchors", "set max anchors",
	    { "/bin/sh", "-c", "grep -v '^set limit anchors' " PFCONF_TEMP " > /tmp/pf.$$ && echo \"set limit anchors $0\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * Table definition commands
	 */
	{ "define-table", "define table <name> persist",
	    { "/bin/sh", "-c", "echo \"table <$0> persist\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "define-table-file", "define table <name> from file",
	    { "/bin/sh", "-c", "echo \"table <$0> persist file \\\"$1\\\"\" >> " PFCONF_TEMP, REQ, REQ, NULL }, NULL, NULL },
	{ "define-table-const", "define table <name> const",
	    { "/bin/sh", "-c", "echo \"table <$0> const persist\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "define-table-counters", "define table <name> with counters",
	    { "/bin/sh", "-c", "echo \"table <$0> persist counters\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "remove-table-def", "remove table definition from config",
	    { "/bin/sh", "-c", "grep -v \"^table <$0>\" " PFCONF_TEMP " > /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * Rule manipulation commands
	 */
	{ "add-pass-in", "add pass in rule",
	    { "/bin/sh", "-c", "echo \"pass in $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-pass-out", "add pass out rule",
	    { "/bin/sh", "-c", "echo \"pass out $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-block-in", "add block in rule",
	    { "/bin/sh", "-c", "echo \"block in $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-block-out", "add block out rule",
	    { "/bin/sh", "-c", "echo \"block out $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-match", "add match rule for traffic shaping",
	    { "/bin/sh", "-c", "echo \"match $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-nat", "add NAT translation rule",
	    { "/bin/sh", "-c", "echo \"match out on $0 from $1 nat-to $2\" >> " PFCONF_TEMP, REQ, REQ, REQ, NULL }, NULL, NULL },
	{ "add-rdr", "add redirect rule",
	    { "/bin/sh", "-c", "echo \"match in on $0 proto $1 to port $2 rdr-to $3\" >> " PFCONF_TEMP, REQ, REQ, REQ, REQ, NULL }, NULL, NULL },
	{ "add-binat", "add bidirectional NAT rule",
	    { "/bin/sh", "-c", "echo \"match on $0 from $1 binat-to $2\" >> " PFCONF_TEMP, REQ, REQ, REQ, NULL }, NULL, NULL },
	/*
	 * Queue definition commands
	 */
	{ "define-queue-root", "define root queue on interface",
	    { "/bin/sh", "-c", "echo \"queue $0 on $1 bandwidth $2\" >> " PFCONF_TEMP, REQ, REQ, REQ, NULL }, NULL, NULL },
	{ "define-queue-child", "define child queue",
	    { "/bin/sh", "-c", "echo \"queue $0 parent $1 bandwidth $2\" >> " PFCONF_TEMP, REQ, REQ, REQ, NULL }, NULL, NULL },
	{ "define-queue-default", "define default queue",
	    { "/bin/sh", "-c", "echo \"queue $0 parent $1 bandwidth $2 default\" >> " PFCONF_TEMP, REQ, REQ, REQ, NULL }, NULL, NULL },
	{ "remove-queue-def", "remove queue definition from config",
	    { "/bin/sh", "-c", "grep -v \"^queue $0\" " PFCONF_TEMP " > /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * Anchor definition commands
	 */
	{ "define-anchor", "define anchor in config",
	    { "/bin/sh", "-c", "echo \"anchor \\\"$0\\\"\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "define-anchor-rules", "define anchor with inline rules",
	    { "/bin/sh", "-c", "echo \"anchor \\\"$0\\\" { $1 }\" >> " PFCONF_TEMP, REQ, REQ, NULL }, NULL, NULL },
	{ "define-anchor-quick", "define anchor with quick",
	    { "/bin/sh", "-c", "echo \"anchor \\\"$0\\\" quick\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "remove-anchor-def", "remove anchor definition from config",
	    { "/bin/sh", "-c", "grep -v \"^anchor \\\"$0\\\"\" " PFCONF_TEMP " > /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * Macro definition commands
	 */
	{ "define-macro", "define macro variable",
	    { "/bin/sh", "-c", "grep -v \"^$0 *= *\" " PFCONF_TEMP " > /tmp/pf.$$ && echo \"$0 = \\\"$1\\\"\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, REQ, NULL }, NULL, NULL },
	{ "define-macro-list", "define macro with list",
	    { "/bin/sh", "-c", "grep -v \"^$0 *= *\" " PFCONF_TEMP " > /tmp/pf.$$ && echo \"$0 = { $1 }\" >> /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, REQ, NULL }, NULL, NULL },
	{ "remove-macro", "remove macro definition",
	    { "/bin/sh", "-c", "grep -v \"^$0 *= *\" " PFCONF_TEMP " > /tmp/pf.$$ && mv /tmp/pf.$$ " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * Antispoof commands
	 */
	{ "add-antispoof", "add antispoof protection",
	    { "/bin/sh", "-c", "echo \"antispoof for $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-antispoof-log", "add antispoof with logging",
	    { "/bin/sh", "-c", "echo \"antispoof log for $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-antispoof-quick", "add antispoof with quick",
	    { "/bin/sh", "-c", "echo \"antispoof quick for $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	/*
	 * Scrub/reassembly commands
	 */
	{ "add-scrub", "add scrub rule (deprecated, use match)",
	    { "/bin/sh", "-c", "echo \"match in all scrub $0\" >> " PFCONF_TEMP, REQ, NULL }, NULL, NULL },
	{ "add-reassemble-tcp", "add TCP reassembly",
	    { "/bin/sh", "-c", "echo \"match in all scrub (reassemble tcp)\" >> " PFCONF_TEMP, NULL }, NULL, NULL },
	/*
	 * Legacy commands
	 */
	{ "add",   "global/filter/nat/binat",
	    { NSPFSH, REQ, OPT, OPT, OPT, NULL }, NULL, NULL },
	{ "show",   "queue/rules/states/info/all",
	    { PFCTL, "-s", REQ, NULL }, NULL, NULL },
	{ "reload",	"reload service",
	    { PFRELOAD, NULL, NULL }, NULL, NULL },
	/* Show commands for full coverage */
	{ "show-rules",    "show filter rules",
	    { PFCTL, "-s", "rules", NULL }, NULL, NULL },
	{ "show-nat",      "show NAT rules",
	    { PFCTL, "-s", "nat", NULL }, NULL, NULL },
	{ "show-queue",    "show queue rules",
	    { PFCTL, "-s", "queue", NULL }, NULL, NULL },
	{ "show-states",   "show state table",
	    { PFCTL, "-s", "states", NULL }, NULL, NULL },
	{ "show-sources",  "show source tracking",
	    { PFCTL, "-s", "Sources", NULL }, NULL, NULL },
	{ "show-info",     "show filter info",
	    { PFCTL, "-s", "info", NULL }, NULL, NULL },
	{ "show-labels",   "show per-rule labels",
	    { PFCTL, "-s", "labels", NULL }, NULL, NULL },
	{ "show-interfaces", "show interfaces",
	    { PFCTL, "-s", "Interfaces", NULL }, NULL, NULL },
	{ "show-tables",   "show address tables",
	    { PFCTL, "-s", "Tables", NULL }, NULL, NULL },
	{ "show-osfp",     "show OS fingerprints",
	    { PFCTL, "-s", "osfp", NULL }, NULL, NULL },
	{ "show-anchors",  "show anchor list",
	    { PFCTL, "-s", "Anchors", NULL }, NULL, NULL },
	{ "show-timeouts", "show timeout values",
	    { PFCTL, "-s", "timeouts", NULL }, NULL, NULL },
	{ "show-memory",   "show memory limits",
	    { PFCTL, "-s", "memory", NULL }, NULL, NULL },
	{ "show-running",  "show running config",
	    { PFCTL, "-s", "Running", NULL }, NULL, NULL },
	{ "show-all",      "show all information",
	    { PFCTL, "-s", "all", NULL }, NULL, NULL },
	/* Flush commands */
	{ "flush-all",     "flush all PF elements",
	    { PFCTL, "-Fall", NULL }, NULL, NULL },
	{ "flush-rules",   "flush filter rules",
	    { PFCTL, "-Frules", NULL }, NULL, NULL },
	{ "flush-nat",     "flush NAT rules",
	    { PFCTL, "-Fnat", NULL }, NULL, NULL },
	{ "flush-queue",   "flush queue rules",
	    { PFCTL, "-Fqueue", NULL }, NULL, NULL },
	{ "flush-states",  "flush state table",
	    { PFCTL, "-Fstate", NULL }, NULL, NULL },
	{ "flush-sources", "flush source tracking",
	    { PFCTL, "-FSources", NULL }, NULL, NULL },
	{ "flush-info",    "flush filter info/counters",
	    { PFCTL, "-Finfo", NULL }, NULL, NULL },
	{ "flush-tables",  "flush address tables",
	    { PFCTL, "-FTables", NULL }, NULL, NULL },
	{ "flush-osfp",    "flush OS fingerprints",
	    { PFCTL, "-Fosfp", NULL }, NULL, NULL },
	/* Table commands */
	{ "table-show",    "show table contents",
	    { PFCTL, "-t", REQ, "-T", "show", NULL }, NULL, NULL },
	{ "table-add",     "add to table",
	    { PFCTL, "-t", REQ, "-T", "add", REQ, NULL }, NULL, NULL },
	{ "table-delete",  "delete from table",
	    { PFCTL, "-t", REQ, "-T", "delete", REQ, NULL }, NULL, NULL },
	{ "table-replace", "replace table contents",
	    { PFCTL, "-t", REQ, "-T", "replace", REQ, NULL }, NULL, NULL },
	{ "table-test",    "test address in table",
	    { PFCTL, "-t", REQ, "-T", "test", REQ, NULL }, NULL, NULL },
	{ "table-zero",    "zero table counters",
	    { PFCTL, "-t", REQ, "-T", "zero", NULL }, NULL, NULL },
	{ "table-flush",   "flush table contents",
	    { PFCTL, "-t", REQ, "-T", "flush", NULL }, NULL, NULL },
	{ "table-expire",  "expire table entries",
	    { PFCTL, "-t", REQ, "-T", "expire", REQ, NULL }, NULL, NULL },
	{ "table-kill",    "kill states from table",
	    { PFCTL, "-t", REQ, "-T", "kill", NULL }, NULL, NULL },
	/* State killing */
	{ "kill-states",   "kill matching states",
	    { PFCTL, "-k", REQ, NULL }, NULL, NULL },
	{ "kill-src",      "kill by source address",
	    { PFCTL, "-K", REQ, NULL }, NULL, NULL },
	{ "kill-label",    "kill states by rule label",
	    { PFCTL, "-k", "label", "-k", REQ, NULL }, NULL, NULL },
	{ "kill-id",       "kill state by ID",
	    { PFCTL, "-k", "id", "-k", REQ, NULL }, NULL, NULL },
	{ "kill-key",      "kill state by key",
	    { PFCTL, "-k", "key", "-k", REQ, NULL }, NULL, NULL },
	/* Other commands */
	{ "clear-stats",   "clear per-rule statistics",
	    { PFCTL, "-z", NULL }, NULL, NULL },
	{ "debug",         "set debug level (none/urgent/misc/loud)",
	    { PFCTL, "-x", REQ, NULL }, NULL, NULL },
	{ "optimize",      "set optimization level (0-2)",
	    { PFCTL, "-o", REQ, NULL }, NULL, NULL },
	{ "anchor-show",   "show anchor rules",
	    { PFCTL, "-a", REQ, "-s", "rules", NULL }, NULL, NULL },
	{ "anchor-flush",  "flush anchor rules",
	    { PFCTL, "-a", REQ, "-Frules", NULL }, NULL, NULL },
	{ "load",          "load rules from file",
	    { PFCTL, "-f", REQ, NULL }, NULL, NULL },
	{ "test",          "test rules file syntax",
	    { PFCTL, "-nf", REQ, NULL }, NULL, NULL },
	{ "verbose-rules", "show rules with evaluations",
	    { PFCTL, "-vs", "rules", NULL }, NULL, NULL },
	{ "verbose-states", "show states with details",
	    { PFCTL, "-vs", "states", NULL }, NULL, NULL },
	{ "verbose-tables", "show tables with counters",
	    { PFCTL, "-vvs", "Tables", NULL }, NULL, NULL },
	/* Selective rule loading */
	{ "load-rules",    "load only filter rules",
	    { PFCTL, "-R", "-f", REQ, NULL }, NULL, NULL },
	{ "load-nat",      "load only NAT rules",
	    { PFCTL, "-N", "-f", REQ, NULL }, NULL, NULL },
	{ "load-options",  "load only options",
	    { PFCTL, "-O", "-f", REQ, NULL }, NULL, NULL },
	/* Interface-specific */
	{ "show-iface",    "show specific interface stats",
	    { PFCTL, "-s", "Interfaces", "-i", REQ, NULL }, NULL, NULL },
	/* Additional table operations */
	{ "table-load",    "load table from file",
	    { PFCTL, "-t", REQ, "-T", "replace", "-f", REQ, NULL }, NULL, NULL },
	/* Combined operations */
	{ "rules-numbered", "show rules with line numbers",
	    { PFCTL, "-vvs", "rules", NULL }, NULL, NULL },
	/* State file operations */
	{ "save-states",   "save state table to file",
	    { PFCTL, "-S", REQ, NULL }, NULL, NULL },
	{ "load-states",   "load state table from file",
	    { PFCTL, "-L", REQ, NULL }, NULL, NULL },
	/* Reset and additional show commands */
	{ "flush-reset",   "reset limits/timeouts to defaults",
	    { PFCTL, "-FReset", NULL }, NULL, NULL },
	{ "show-stlimiter", "show state limiters",
	    { PFCTL, "-s", "Stlimiter", NULL }, NULL, NULL },
	{ "show-srclimiter", "show source limiters",
	    { PFCTL, "-s", "Srclimiter", NULL }, NULL, NULL },
	/* Routing domain */
	{ "kill-rdomain",  "kill states in routing domain",
	    { PFCTL, "-V", REQ, "-k", REQ, NULL }, NULL, NULL },
	/*
	 * Additional table commands for complete coverage
	 */
	{ "table-reset",   "reset non-zero counters only",
	    { PFCTL, "-t", REQ, "-T", "reset", NULL }, NULL, NULL },
	{ "table-show-counters", "show table with byte/pkt counters",
	    { PFCTL, "-t", REQ, "-vT", "show", NULL }, NULL, NULL },
	/*
	 * Comprehensive anchor operations
	 */
	{ "anchor-load",   "load rules into anchor",
	    { PFCTL, "-a", REQ, "-f", REQ, NULL }, NULL, NULL },
	{ "anchor-show-nat", "show NAT rules in anchor",
	    { PFCTL, "-a", REQ, "-s", "nat", NULL }, NULL, NULL },
	{ "anchor-show-queue", "show queue rules in anchor",
	    { PFCTL, "-a", REQ, "-s", "queue", NULL }, NULL, NULL },
	{ "anchor-show-states", "show states in anchor",
	    { PFCTL, "-a", REQ, "-s", "states", NULL }, NULL, NULL },
	{ "anchor-show-info", "show info for anchor",
	    { PFCTL, "-a", REQ, "-s", "info", NULL }, NULL, NULL },
	{ "anchor-show-labels", "show labels in anchor",
	    { PFCTL, "-a", REQ, "-s", "labels", NULL }, NULL, NULL },
	{ "anchor-show-all", "show all in anchor",
	    { PFCTL, "-a", REQ, "-s", "all", NULL }, NULL, NULL },
	{ "anchor-flush-nat", "flush NAT in anchor",
	    { PFCTL, "-a", REQ, "-Fnat", NULL }, NULL, NULL },
	{ "anchor-flush-queue", "flush queues in anchor",
	    { PFCTL, "-a", REQ, "-Fqueue", NULL }, NULL, NULL },
	{ "anchor-flush-states", "flush states in anchor",
	    { PFCTL, "-a", REQ, "-Fstate", NULL }, NULL, NULL },
	{ "anchor-flush-all", "flush all in anchor",
	    { PFCTL, "-a", REQ, "-Fall", NULL }, NULL, NULL },
	{ "anchor-recursive", "show anchors recursively",
	    { PFCTL, "-vs", "Anchors", NULL }, NULL, NULL },
	{ "anchor-table-show", "show table in anchor",
	    { PFCTL, "-a", REQ, "-t", REQ, "-T", "show", NULL }, NULL, NULL },
	{ "anchor-table-add", "add to table in anchor",
	    { PFCTL, "-a", REQ, "-t", REQ, "-T", "add", REQ, NULL }, NULL, NULL },
	{ "anchor-table-delete", "delete from table in anchor",
	    { PFCTL, "-a", REQ, "-t", REQ, "-T", "delete", REQ, NULL }, NULL, NULL },
	/*
	 * Source tracking operations
	 */
	{ "kill-src-track", "kill source tracking entry",
	    { PFCTL, "-K", REQ, NULL }, NULL, NULL },
	{ "kill-src-track-all", "kill all source tracking",
	    { PFCTL, "-K", "0.0.0.0/0", NULL }, NULL, NULL },
	/*
	 * Additional loading options
	 */
	{ "load-queue",    "load only queue rules",
	    { PFCTL, "-A", "-f", REQ, NULL }, NULL, NULL },
	{ "load-tables",   "load only table definitions",
	    { PFCTL, "-T", "load", "-f", REQ, NULL }, NULL, NULL },
	/*
	 * Additional show commands
	 */
	{ "show-keys",     "show state keys for pfsync",
	    { PFCTL, "-s", "keys", NULL }, NULL, NULL },
	{ "show-queue-verbose", "show queue with statistics",
	    { PFCTL, "-vs", "queue", NULL }, NULL, NULL },
	{ "show-nat-verbose", "show NAT with counters",
	    { PFCTL, "-vs", "nat", NULL }, NULL, NULL },
	{ "show-sources-verbose", "show source tracking verbose",
	    { PFCTL, "-vs", "Sources", NULL }, NULL, NULL },
	/*
	 * Interface-specific operations
	 */
	{ "show-iface-verbose", "show interface with stats",
	    { PFCTL, "-vs", "Interfaces", "-i", REQ, NULL }, NULL, NULL },
	{ "flush-iface-states", "flush states on interface",
	    { PFCTL, "-i", REQ, "-Fstate", NULL }, NULL, NULL },
	/*
	 * Rule-specific operations
	 */
	{ "show-rule",     "show specific rule by ID",
	    { PFCTL, "-R", REQ, "-s", "rules", NULL }, NULL, NULL },
	/*
	 * Syncookie operations
	 */
	{ "show-syncookies", "show syncookie status",
	    { PFCTL, "-s", "syncookies", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};
