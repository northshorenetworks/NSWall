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

struct ctl ctl_pf[] = {
	{ "enable",	"enable service",
	    { PFCTL, "-e", NULL }, NULL, X_ENABLE },
	{ "disable",	"disable service",
	    { PFCTL, "-d", NULL }, NULL, X_DISABLE },
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
