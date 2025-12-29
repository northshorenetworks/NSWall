/* $nsh: ctl.c,v 1.25 2009/05/26 22:08:06 chris Exp $ */
/*
 * Copyright (c) 2008 Chris Cappuccio <chris@nmedia.net>
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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <fcntl.h>
#include <unistd.h>
#include <errno.h>
#include <sys/signal.h>
#include <sys/types.h>
#include <sys/stat.h>
#include "externs.h"

/* service daemons */
#define OSPFD		"/usr/sbin/ospfd"
#define BGPD		"/usr/sbin/bgpd"
#define RIPD		"/usr/sbin/ripd"
#define ISAKMPD		"/sbin/isakmpd"
#define DVMRPD		"/usr/sbin/dvmrpd"
#define RELAYD		"/usr/sbin/relayd"
#define DHCPD		"/usr/sbin/dhcpd"
#define SASYNCD		"/usr/sbin/sasyncd"
#define	SNMPD		"/usr/sbin/snmpd"
#define NTPD		"/usr/sbin/ntpd"
#define FTPPROXY	"/usr/sbin/ftp-proxy"
#define INETD		"/usr/sbin/inetd"
#define SSHD		"/usr/sbin/sshd"
#define NSPFSH          "/usr/bin/nspf"
#define PFRELOAD        "/usr/bin/pfreload"

/* OpenBSD base system services (new) */
#define NTPCTL		"/usr/sbin/ntpctl"
#define UNBOUND		"/usr/sbin/unbound"
#define UNBOUNDCTL	"/usr/sbin/unbound-control"
#define HTTPD		"/usr/sbin/httpd"
#define IKED		"/sbin/iked"
#define IKECTL		"/usr/sbin/ikectl"
#define RAD		"/usr/sbin/rad"
#define SMTPD		"/usr/sbin/smtpd"
#define SMTPCTL		"/usr/sbin/smtpctl"
#define ACMECLIENT	"/usr/sbin/acme-client"
#define LDPD		"/usr/sbin/ldpd"
#define LDPCTL		"/usr/sbin/ldpctl"
#define PFLOGD		"/usr/sbin/pflogd"
#define TCPDUMP		"/usr/sbin/tcpdump"
#define RACTL		"/usr/sbin/ractl"
#define EIGRPD		"/usr/sbin/eigrpd"
#define EIGRPCTL	"/usr/sbin/eigrpctl"

void call_editor(char *, char **, char *);
void ctl_symlink(char *, char **, char *);
int rule_writeline(char *, mode_t, char *);
int acq_lock(char *);
void rls_lock(int);
void ctl_show_config(char *, char **, char *);
void ctl_show_status(char *, char **, char *);
void ctl_set_config(char *, char **, char *);
void ctl_unset_config(char *, char **, char *);
void ctl_append_config(char *, char **, char *);
void ctl_init_config(char *, char **, char *);

/* Show config file contents */
void
ctl_show_config(char *conffile, char **notused, char *notused2)
{
	FILE *f;
	char line[1024];

	f = fopen(conffile, "r");
	if (f == NULL) {
		printf("%% Configuration file %s not found\n", conffile);
		printf("%% Use 'init' to create default configuration\n");
		return;
	}
	printf("%% Configuration: %s\n", conffile);
	printf("%% ----------------------------------------\n");
	while (fgets(line, sizeof(line), f) != NULL) {
		printf("%s", line);
	}
	printf("%% ----------------------------------------\n");
	fclose(f);
}

/* Show daemon status */
void
ctl_show_status(char *daemon, char **notused, char *notused2)
{
	char cmd[256];
	FILE *f;
	char line[64];
	int found = 0;

	snprintf(cmd, sizeof(cmd), "/usr/bin/pgrep -l %s 2>/dev/null", daemon);
	f = popen(cmd, "r");
	if (f != NULL) {
		while (fgets(line, sizeof(line), f) != NULL) {
			if (!found) {
				printf("%% %s is running:\n", daemon);
				found = 1;
			}
			printf("%%   PID %s", line);
		}
		pclose(f);
	}
	if (!found) {
		printf("%% %s is not running\n", daemon);
	}
}

/* Set/replace a config line (key value) */
void
ctl_set_config(char *conffile, char **args, char *notused)
{
	FILE *f, *tmp;
	char tmpfile[256];
	char line[1024];
	char *key = args[1];
	char *value = args[2];
	int found = 0;

	if (key == NULL) {
		printf("%% Usage: set <key> <value>\n");
		return;
	}

	snprintf(tmpfile, sizeof(tmpfile), "%s.tmp", conffile);

	f = fopen(conffile, "r");
	tmp = fopen(tmpfile, "w");
	if (tmp == NULL) {
		printf("%% Cannot write to %s\n", tmpfile);
		if (f) fclose(f);
		return;
	}

	/* Read existing config and replace matching line */
	if (f != NULL) {
		while (fgets(line, sizeof(line), f) != NULL) {
			if (strncmp(line, key, strlen(key)) == 0 &&
			    (line[strlen(key)] == ' ' || line[strlen(key)] == '\t')) {
				found = 1;
				if (value)
					fprintf(tmp, "%s %s\n", key, value);
				else
					fprintf(tmp, "%s\n", key);
			} else {
				fprintf(tmp, "%s", line);
			}
		}
		fclose(f);
	}

	/* Append if not found */
	if (!found) {
		if (value)
			fprintf(tmp, "%s %s\n", key, value);
		else
			fprintf(tmp, "%s\n", key);
	}

	fclose(tmp);
	rename(tmpfile, conffile);
	chmod(conffile, 0600);
	printf("%% Configuration updated\n");
}

/* Remove a config line */
void
ctl_unset_config(char *conffile, char **args, char *notused)
{
	FILE *f, *tmp;
	char tmpfile[256];
	char line[1024];
	char *key = args[1];
	int removed = 0;

	if (key == NULL) {
		printf("%% Usage: unset <key>\n");
		return;
	}

	snprintf(tmpfile, sizeof(tmpfile), "%s.tmp", conffile);

	f = fopen(conffile, "r");
	if (f == NULL) {
		printf("%% Configuration file not found\n");
		return;
	}

	tmp = fopen(tmpfile, "w");
	if (tmp == NULL) {
		printf("%% Cannot write to %s\n", tmpfile);
		fclose(f);
		return;
	}

	while (fgets(line, sizeof(line), f) != NULL) {
		if (strncmp(line, key, strlen(key)) == 0 &&
		    (line[strlen(key)] == ' ' || line[strlen(key)] == '\t' ||
		     line[strlen(key)] == '\n')) {
			removed = 1;
			continue;
		}
		fprintf(tmp, "%s", line);
	}

	fclose(f);
	fclose(tmp);
	rename(tmpfile, conffile);
	chmod(conffile, 0600);

	if (removed)
		printf("%% Configuration line removed\n");
	else
		printf("%% Key not found in configuration\n");
}

/* Append a line to config */
void
ctl_append_config(char *conffile, char **args, char *notused)
{
	FILE *f;
	char *line = args[1];
	int i;

	if (line == NULL) {
		printf("%% Usage: append <config line>\n");
		return;
	}

	f = fopen(conffile, "a");
	if (f == NULL) {
		printf("%% Cannot open %s for writing\n", conffile);
		return;
	}

	/* Write all remaining args as one line */
	for (i = 1; args[i] != NULL; i++) {
		fprintf(f, "%s", args[i]);
		if (args[i+1] != NULL)
			fprintf(f, " ");
	}
	fprintf(f, "\n");
	fclose(f);
	chmod(conffile, 0600);
	printf("%% Configuration line appended\n");
}

/* Initialize config with defaults */
void
ctl_init_config(char *conffile, char **defaults, char *notused)
{
	FILE *f;
	struct stat sb;

	if (stat(conffile, &sb) == 0) {
		printf("%% Configuration file already exists\n");
		printf("%% Use 'show' to view or 'set'/'unset' to modify\n");
		return;
	}

	f = fopen(conffile, "w");
	if (f == NULL) {
		printf("%% Cannot create %s\n", conffile);
		return;
	}

	/* Write default config passed in defaults array */
	if (defaults != NULL && defaults[1] != NULL) {
		fprintf(f, "%s", defaults[1]);
	} else {
		fprintf(f, "# Default configuration\n");
	}

	fclose(f);
	chmod(conffile, 0600);
	printf("%% Default configuration created at %s\n", conffile);
}

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
	{ 0, 0, { 0 }, 0, 0 }
};

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

char *ctl_ipsec_test[] = { IPSECCTL, "-nf", IPSECCONF_TEMP, '\0' };
struct ctl ctl_ipsec[] = {
	{ "enable",     "enable service",
	    { ISAKMPD, "-Sa", NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",                   
	    { PKILL, "isakmpd", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",   
	    { "ipsec", (char *)ctl_ipsec_test, NULL }, call_editor, NULL },
	{ "reload",     "reload service",
	    { IPSECCTL, "-f", IPSECCONF_TEMP, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

char *ctl_dvmrp_test[] = { DVMRPD, "-nf", DVMRPCONF_TEMP, '\0' };
struct ctl ctl_dvmrp[] = {
	{ "enable",     "enable service",
	    { DVMRPD, "-f", DVMRPCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",   
	    { PKILL, "dvmrpd", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "dvmrp", (char *)ctl_dvmrp_test,  NULL }, call_editor, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

struct ctl ctl_sasync[] = {
	{ "enable",     "enable service",
	    { SASYNCD, "-c", SASYNCCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "sasyncd", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "sasync", NULL, NULL }, call_editor, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

char *ctl_dhcp_test[] = { DHCPD, "-nc", DHCPCONF_TEMP, '\0' };
char *ctl_dhcp_default[] = { NULL, "option domain-name-servers 8.8.8.8;\ndefault-lease-time 600;\nmax-lease-time 7200;\n", NULL };
struct ctl ctl_dhcp[] = {
	{ "enable",     "enable service",
	    { DHCPD, "-c", DHCPCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "dhcpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { DHCPCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "dhcpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { DHCPCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { DHCPCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { DHCPCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { DHCPCONF_TEMP, (char *)ctl_dhcp_default, NULL }, ctl_init_config, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

char *ctl_snmp_test[] = { SNMPD, "-nf", SNMPCONF_TEMP, '\0' };
char *ctl_snmp_default[] = { NULL, "listen on 127.0.0.1\n", NULL };
struct ctl ctl_snmp[] = {
	{ "enable",     "enable service",
	    { SNMPD, "-f", SNMPCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "snmpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { SNMPCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "snmpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { SNMPCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { SNMPCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { SNMPCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { SNMPCONF_TEMP, (char *)ctl_snmp_default, NULL }, ctl_init_config, NULL },
	{ "trap",	"send trap message",
	    { SNMPCTL, "trap", "send", REQ, OPT, NULL }, NULL, NULL },
	{ "walk",	"SNMP walk OID tree",
	    { SNMPCTL, "snmp", "walk", REQ, "community", REQ, "oid", REQ, NULL }, NULL, NULL },
	{ "get",	"SNMP get OID value",
	    { SNMPCTL, "snmp", "get", REQ, "community", REQ, "oid", REQ, NULL }, NULL, NULL },
	{ "bulkwalk",	"SNMP bulk walk",
	    { SNMPCTL, "snmp", "bulkwalk", REQ, "community", REQ, "oid", REQ, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { SNMPCTL, "log", REQ, NULL }, NULL, NULL },
	{ "mibtree",    "show MIB tree",
	    { SNMPCTL, "snmp", "mibtree", OPT, NULL }, NULL, NULL },
	/* Additional snmpctl commands for 100% coverage */
	{ "getnext",    "SNMP getnext OID",
	    { SNMPCTL, "snmp", "getnext", REQ, "community", REQ, "oid", REQ, NULL }, NULL, NULL },
	{ "bulkget",    "SNMP bulk get",
	    { SNMPCTL, "snmp", "bulkget", REQ, "community", REQ, "oid", REQ, NULL }, NULL, NULL },
	{ "df",         "show MIB definitions",
	    { SNMPCTL, "snmp", "df", OPT, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

char *ctl_sshd_default[] = { NULL, "Port 22\nPermitRootLogin no\nPasswordAuthentication yes\n", NULL };
struct ctl ctl_sshd[] = {
	{ "enable",	"enable service",
	    { SSHD, "-f", SSHDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",	"disable service",
	    { PKILL, "-f", SSHD, "-f", SSHDCONF_TEMP, NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { SSHDCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "sshd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { SSHDCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { SSHDCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { SSHDCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { SSHDCONF_TEMP, (char *)ctl_sshd_default, NULL }, ctl_init_config, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

char *ctl_ntp_test[] = { NTPD, "-nf", NTPCONF_TEMP, '\0' };
char *ctl_ntp_default[] = { NULL, "servers pool.ntp.org\nsensor *\n", NULL };
struct ctl ctl_ntp[] = {
	{ "enable",     "enable service",
	    { NTPD, "-sf", NTPCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "ntpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { NTPCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "ntpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { NTPCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { NTPCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { NTPCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { NTPCONF_TEMP, (char *)ctl_ntp_default, NULL }, ctl_init_config, NULL },
	{ "peers",      "show NTP peers",
	    { NTPCTL, "-s", "peers", NULL }, NULL, NULL },
	{ "sensors",    "show time sensors",
	    { NTPCTL, "-s", "sensors", NULL }, NULL, NULL },
	{ "all",        "show all NTP status",
	    { NTPCTL, "-s", "all", NULL }, NULL, NULL },
	/* Additional ntpctl commands for 100% coverage */
	{ "status",     "show NTP status",
	    { NTPCTL, "-s", "status", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

char *ctl_relay_test[] = { RELAYD, "-nf", RELAYCONF_TEMP, '\0' };
struct ctl ctl_relay[] = {
	{ "enable",	"enable service",
	    { RELAYD, "-f", RELAYCONF_TEMP, NULL }, NULL, X_ENABLE },
        { "disable",	"disable service",
	    { PKILL, "relayd", NULL }, NULL, X_DISABLE },
        { "edit",	"edit configuration",
	    { "relay", (char *)ctl_relay_test, NULL }, call_editor, NULL },
        { "reload",	"reload configuration",
	    { RELAYCTL, "reload", NULL }, NULL, NULL },
	{ "host",	"per-host enable/disable",
	    { RELAYCTL, "host", REQ, REQ, NULL }, NULL, NULL },
	{ "table",	"per-table enable/disable",
	    { RELAYCTL, "table", REQ, REQ, NULL }, NULL, NULL },
	{ "redirect",	"per-redirect enable/disable",
	    { RELAYCTL, "redirect", REQ, REQ, NULL }, NULL, NULL },
	{ "monitor",	"monitor health checks",
	    { RELAYCTL, "monitor", NULL }, NULL, NULL },
	{ "poll",	"immediate health check",
	    { RELAYCTL, "poll", NULL }, NULL, NULL},
	{ "show-hosts", "show host status",
	    { RELAYCTL, "show", "hosts", NULL }, NULL, NULL },
	{ "show-sessions", "show active sessions",
	    { RELAYCTL, "show", "sessions", NULL }, NULL, NULL },
	{ "show-summary", "show summary",
	    { RELAYCTL, "show", "summary", NULL }, NULL, NULL },
	{ "show-routers", "show routers",
	    { RELAYCTL, "show", "routers", NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { RELAYCTL, "log", REQ, NULL }, NULL, NULL },
	{ "stop",       "stop daemon",
	    { RELAYCTL, "stop", NULL }, NULL, NULL },
	/* Additional relayctl commands for 100% coverage */
	{ "load",       "load table entries",
	    { RELAYCTL, "load", REQ, REQ, NULL }, NULL, NULL },
	{ "show-redirects", "show redirects",
	    { RELAYCTL, "show", "redirects", NULL }, NULL, NULL },
	{ "show-relays", "show relays",
	    { RELAYCTL, "show", "relays", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

struct ctl ctl_ftpproxy[] = {
	{ "enable",	"enable service",
	    { FTPPROXY, "-T", "ftp-proxy", "-D", "2", NULL }, NULL, X_ENABLE },
	{ "disable",	"disable service",
	    { PKILL, "ftp-proxy", NULL }, NULL, X_DISABLE },
	{ 0, 0, { 0 }, 0, 0 }
};

struct ctl ctl_dns[] = {
	{ "local-control", "local control over DNS settings",
	    { RESOLVCONF_SYM, NULL, RESOLVCONF_TEMP, NULL }, ctl_symlink,
	    X_LOCAL },
	{ "dhcp-control",   "DHCP client control over DNS settings",
	    { RESOLVCONF_SYM, NULL, RESOLVCONF_DHCP, NULL }, ctl_symlink,
	    X_OTHER },
	{ "edit",	    "edit DNS settings",
	    { "dns", NULL, NULL }, call_editor, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

struct ctl ctl_inet[] = {
	{ "enable",     "enable service",
	    { INETD, INETCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable service",
	    { PKILL, "inetd", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "inet", NULL, NULL }, call_editor, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * OpenBSD Base System Services
 * These integrate additional OpenBSD daemons into NSWall
 */

/* unbound - DNS resolver with DNSSEC */
char *ctl_unbound_test[] = { UNBOUND, "-c", UNBOUNDCONF_TEMP, "-d", NULL };
char *ctl_unbound_default[] = { NULL, "server:\n\tinterface: 127.0.0.1\n\taccess-control: 127.0.0.0/8 allow\n", NULL };
struct ctl ctl_unbound[] = {
	{ "enable",     "enable DNS resolver",
	    { UNBOUND, "-c", UNBOUNDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable DNS resolver",
	    { PKILL, "unbound", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { UNBOUNDCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "unbound", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { UNBOUNDCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { UNBOUNDCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { UNBOUNDCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { UNBOUNDCONF_TEMP, (char *)ctl_unbound_default, NULL }, ctl_init_config, NULL },
	{ "reload",     "reload configuration",
	    { UNBOUNDCTL, "reload", NULL }, NULL, NULL },
	{ "flush",      "flush DNS cache",
	    { UNBOUNDCTL, "flush_zone", ".", NULL }, NULL, NULL },
	{ "stats",      "show statistics",
	    { UNBOUNDCTL, "stats_noreset", NULL }, NULL, NULL },
	{ "stop",       "stop DNS resolver",
	    { UNBOUNDCTL, "stop", NULL }, NULL, NULL },
	{ "verbosity",  "set verbosity level",
	    { UNBOUNDCTL, "verbosity", REQ, NULL }, NULL, NULL },
	{ "list-stubs", "list stub zones",
	    { UNBOUNDCTL, "list_stubs", NULL }, NULL, NULL },
	{ "list-forwards", "list forward zones",
	    { UNBOUNDCTL, "list_forwards", NULL }, NULL, NULL },
	{ "list-local", "list local zones",
	    { UNBOUNDCTL, "list_local_zones", NULL }, NULL, NULL },
	{ "dump-cache", "dump cache contents",
	    { UNBOUNDCTL, "dump_cache", NULL }, NULL, NULL },
	/* Additional unbound-control commands for 100% coverage */
	{ "reload-keep", "reload keeping cache",
	    { UNBOUNDCTL, "reload_keep_cache", NULL }, NULL, NULL },
	{ "flush-type", "flush specific RR type",
	    { UNBOUNDCTL, "flush_type", REQ, REQ, NULL }, NULL, NULL },
	{ "flush-infra", "flush infra cache",
	    { UNBOUNDCTL, "flush_infra", REQ, NULL }, NULL, NULL },
	{ "dump-infra", "dump infra cache",
	    { UNBOUNDCTL, "dump_infra", NULL }, NULL, NULL },
	{ "list-local-data", "list local data RRs",
	    { UNBOUNDCTL, "list_local_data", NULL }, NULL, NULL },
	{ "list-insecure", "list domain-insecure zones",
	    { UNBOUNDCTL, "list_insecure", NULL }, NULL, NULL },
	{ "list-auth", "list auth zones",
	    { UNBOUNDCTL, "list_auth_zones", NULL }, NULL, NULL },
	{ "insecure-add", "add domain-insecure zone",
	    { UNBOUNDCTL, "insecure_add", REQ, NULL }, NULL, NULL },
	{ "insecure-remove", "remove domain-insecure zone",
	    { UNBOUNDCTL, "insecure_remove", REQ, NULL }, NULL, NULL },
	{ "forward-add", "add forward zone",
	    { UNBOUNDCTL, "forward_add", REQ, REQ, NULL }, NULL, NULL },
	{ "forward-remove", "remove forward zone",
	    { UNBOUNDCTL, "forward_remove", REQ, NULL }, NULL, NULL },
	{ "stub-add", "add stub zone",
	    { UNBOUNDCTL, "stub_add", REQ, REQ, NULL }, NULL, NULL },
	{ "stub-remove", "remove stub zone",
	    { UNBOUNDCTL, "stub_remove", REQ, NULL }, NULL, NULL },
	{ "set-option", "set runtime option",
	    { UNBOUNDCTL, "set_option", REQ, NULL }, NULL, NULL },
	{ "log-reopen", "reopen log file",
	    { UNBOUNDCTL, "log_reopen", NULL }, NULL, NULL },
	{ "auth-zone-reload", "reload auth zone",
	    { UNBOUNDCTL, "auth_zone_reload", REQ, NULL }, NULL, NULL },
	{ "auth-zone-transfer", "transfer auth zone",
	    { UNBOUNDCTL, "auth_zone_transfer", REQ, NULL }, NULL, NULL },
	{ "rpz-enable", "enable RPZ zone",
	    { UNBOUNDCTL, "rpz_enable", REQ, NULL }, NULL, NULL },
	{ "rpz-disable", "disable RPZ zone",
	    { UNBOUNDCTL, "rpz_disable", REQ, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* httpd - OpenBSD HTTP server */
char *ctl_httpd_test[] = { HTTPD, "-n", "-f", HTTPDCONF_TEMP, NULL };
char *ctl_httpd_default[] = { NULL, "server \"default\" {\n\tlisten on * port 80\n\troot \"/htdocs\"\n}\n", NULL };
struct ctl ctl_httpd[] = {
	{ "enable",     "enable HTTP server",
	    { HTTPD, "-f", HTTPDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable HTTP server",
	    { PKILL, "httpd", NULL }, NULL, X_DISABLE },
	{ "show",       "show configuration",
	    { HTTPDCONF_TEMP, NULL, NULL }, ctl_show_config, NULL },
	{ "status",     "show daemon status",
	    { "httpd", NULL, NULL }, ctl_show_status, NULL },
	{ "set",        "set config <key> <value>",
	    { HTTPDCONF_TEMP, OPT, OPT, NULL }, ctl_set_config, NULL },
	{ "unset",      "remove config <key>",
	    { HTTPDCONF_TEMP, OPT, NULL }, ctl_unset_config, NULL },
	{ "append",     "append config line",
	    { HTTPDCONF_TEMP, OPT, OPT, OPT, OPT, NULL }, ctl_append_config, NULL },
	{ "init",       "create default config",
	    { HTTPDCONF_TEMP, (char *)ctl_httpd_default, NULL }, ctl_init_config, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* iked - IKEv2 VPN daemon */
char *ctl_iked_test[] = { IKED, "-n", "-f", IKEDCONF_TEMP, NULL };
struct ctl ctl_iked[] = {
	{ "enable",     "enable IKEv2 VPN",
	    { IKED, "-f", IKEDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable IKEv2 VPN",
	    { PKILL, "iked", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "iked", (char *)ctl_iked_test, NULL }, call_editor, NULL },
	{ "reload",     "reload configuration",
	    { IKECTL, "reload", NULL }, NULL, NULL },
	{ "reset",      "reset sa/ca/policy/user/id",
	    { IKECTL, "reset", REQ, OPT, NULL }, NULL, NULL },
	{ "show-sa",    "show security associations",
	    { IKECTL, "show", "sa", NULL }, NULL, NULL },
	{ "active",     "set active mode",
	    { IKECTL, "active", NULL }, NULL, NULL },
	{ "passive",    "set passive mode",
	    { IKECTL, "passive", NULL }, NULL, NULL },
	{ "couple",     "load SAs into kernel",
	    { IKECTL, "couple", NULL }, NULL, NULL },
	{ "decouple",   "unload SAs from kernel",
	    { IKECTL, "decouple", NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { IKECTL, "log", REQ, NULL }, NULL, NULL },
	{ "monitor",    "monitor IKE events",
	    { IKECTL, "monitor", NULL }, NULL, NULL },
	/* Additional ikectl commands for 100% coverage */
	{ "load",       "load configuration",
	    { IKECTL, "load", REQ, NULL }, NULL, NULL },
	{ "reset-all",  "reset everything",
	    { IKECTL, "reset", "all", NULL }, NULL, NULL },
	{ "reset-ca",   "reset CA state",
	    { IKECTL, "reset", "ca", NULL }, NULL, NULL },
	{ "reset-id",   "reset identity state",
	    { IKECTL, "reset", "id", NULL }, NULL, NULL },
	{ "reset-policy", "reset policy state",
	    { IKECTL, "reset", "policy", NULL }, NULL, NULL },
	{ "reset-user", "reset user state",
	    { IKECTL, "reset", "user", REQ, NULL }, NULL, NULL },
	/* CA management commands */
	{ "ca-create",  "create new CA",
	    { IKECTL, "ca", REQ, "create", NULL }, NULL, NULL },
	{ "ca-delete",  "delete CA",
	    { IKECTL, "ca", REQ, "delete", NULL }, NULL, NULL },
	{ "ca-export",  "export CA certificate",
	    { IKECTL, "ca", REQ, "export", OPT, NULL }, NULL, NULL },
	{ "ca-import",  "import CA certificate",
	    { IKECTL, "ca", REQ, "import", REQ, NULL }, NULL, NULL },
	{ "ca-install", "install CA certificate",
	    { IKECTL, "ca", REQ, "install", REQ, NULL }, NULL, NULL },
	{ "ca-certificate", "create CA certificate",
	    { IKECTL, "ca", REQ, "certificate", REQ, "create", NULL }, NULL, NULL },
	{ "ca-key",     "create CA key",
	    { IKECTL, "ca", REQ, "key", "create", NULL }, NULL, NULL },
	/* Certificate commands */
	{ "cert-create", "create host certificate",
	    { IKECTL, "ca", REQ, "certificate", REQ, "create", NULL }, NULL, NULL },
	{ "cert-delete", "delete host certificate",
	    { IKECTL, "ca", REQ, "certificate", REQ, "delete", NULL }, NULL, NULL },
	{ "cert-export", "export host certificate",
	    { IKECTL, "ca", REQ, "certificate", REQ, "export", OPT, NULL }, NULL, NULL },
	{ "cert-install", "install host certificate",
	    { IKECTL, "ca", REQ, "certificate", REQ, "install", REQ, NULL }, NULL, NULL },
	{ "cert-revoke", "revoke host certificate",
	    { IKECTL, "ca", REQ, "certificate", REQ, "revoke", NULL }, NULL, NULL },
	{ "show-ca",    "show CA certificates",
	    { IKECTL, "show", "ca", OPT, NULL }, NULL, NULL },
	{ "show-certinfo", "show certificate info",
	    { IKECTL, "show", "certinfo", REQ, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* rad - IPv6 Router Advertisement daemon */
char *ctl_rad_test[] = { RAD, "-n", "-f", RADCONF_TEMP, NULL };
struct ctl ctl_rad[] = {
	{ "enable",     "enable router advertisements",
	    { RAD, "-f", RADCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable router advertisements",
	    { PKILL, "rad", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "rad", (char *)ctl_rad_test, NULL }, call_editor, NULL },
	{ "log",        "log brief/verbose",
	    { RACTL, "log", REQ, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* smtpd - OpenSMTPD mail server */
char *ctl_smtpd_test[] = { SMTPD, "-n", "-f", SMTPDCONF_TEMP, NULL };
struct ctl ctl_smtpd[] = {
	{ "enable",     "enable mail service",
	    { SMTPD, "-f", SMTPDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable mail service",
	    { SMTPCTL, "stop", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "smtpd", (char *)ctl_smtpd_test, NULL }, call_editor, NULL },
	{ "pause",      "pause smtp/mda/mta",
	    { SMTPCTL, "pause", REQ, NULL }, NULL, NULL },
	{ "resume",     "resume smtp/mda/mta",
	    { SMTPCTL, "resume", REQ, NULL }, NULL, NULL },
	{ "queue",      "show mail queue",
	    { SMTPCTL, "show", "queue", NULL }, NULL, NULL },
	{ "stats",      "show statistics",
	    { SMTPCTL, "show", "stats", NULL }, NULL, NULL },
	{ "monitor",    "monitor mail activity",
	    { SMTPCTL, "monitor", NULL }, NULL, NULL },
	{ "schedule",   "schedule message for delivery",
	    { SMTPCTL, "schedule", REQ, NULL }, NULL, NULL },
	{ "remove",     "remove message from queue",
	    { SMTPCTL, "remove", REQ, NULL }, NULL, NULL },
	{ "discover",   "discover local user",
	    { SMTPCTL, "discover", REQ, NULL }, NULL, NULL },
	{ "log",        "log brief/verbose",
	    { SMTPCTL, "log", REQ, NULL }, NULL, NULL },
	{ "show-envelope", "show envelope details",
	    { SMTPCTL, "show", "envelope", REQ, NULL }, NULL, NULL },
	{ "show-message", "show message content",
	    { SMTPCTL, "show", "message", REQ, NULL }, NULL, NULL },
	{ "show-hosts", "show remote MX hosts",
	    { SMTPCTL, "show", "hosts", NULL }, NULL, NULL },
	{ "show-routes", "show mail routes",
	    { SMTPCTL, "show", "routes", NULL }, NULL, NULL },
	{ "trace",      "enable subsystem tracing",
	    { SMTPCTL, "trace", REQ, NULL }, NULL, NULL },
	{ "untrace",    "disable subsystem tracing",
	    { SMTPCTL, "untrace", REQ, NULL }, NULL, NULL },
	{ "update-table", "update lookup table",
	    { SMTPCTL, "update", "table", REQ, NULL }, NULL, NULL },
	{ "spf",        "SPF record walk",
	    { SMTPCTL, "spf", "walk", REQ, NULL }, NULL, NULL },
	/* Additional smtpctl commands for 100% coverage */
	{ "encrypt",    "encrypt password string",
	    { SMTPCTL, "encrypt", OPT, NULL }, NULL, NULL },
	{ "pause-envelope", "pause envelope processing",
	    { SMTPCTL, "pause", "envelope", REQ, NULL }, NULL, NULL },
	{ "pause-mda",  "pause local delivery agent",
	    { SMTPCTL, "pause", "mda", NULL }, NULL, NULL },
	{ "pause-mta",  "pause mail transfer agent",
	    { SMTPCTL, "pause", "mta", NULL }, NULL, NULL },
	{ "pause-smtp", "pause SMTP listener",
	    { SMTPCTL, "pause", "smtp", NULL }, NULL, NULL },
	{ "resume-envelope", "resume envelope processing",
	    { SMTPCTL, "resume", "envelope", REQ, NULL }, NULL, NULL },
	{ "resume-mda", "resume local delivery agent",
	    { SMTPCTL, "resume", "mda", NULL }, NULL, NULL },
	{ "resume-mta", "resume mail transfer agent",
	    { SMTPCTL, "resume", "mta", NULL }, NULL, NULL },
	{ "resume-smtp", "resume SMTP listener",
	    { SMTPCTL, "resume", "smtp", NULL }, NULL, NULL },
	{ "profile",    "enable profiler",
	    { SMTPCTL, "profile", REQ, NULL }, NULL, NULL },
	{ "unprofile",  "disable profiler",
	    { SMTPCTL, "unprofile", REQ, NULL }, NULL, NULL },
	{ "show-hoststats", "show host delivery stats",
	    { SMTPCTL, "show", "hoststats", NULL }, NULL, NULL },
	{ "show-relays", "show relay connections",
	    { SMTPCTL, "show", "relays", NULL }, NULL, NULL },
	{ "show-status", "show smtpd status",
	    { SMTPCTL, "show", "status", NULL }, NULL, NULL },
	{ "schedule-all", "schedule all pending",
	    { SMTPCTL, "schedule", "all", NULL }, NULL, NULL },
	{ "remove-all", "remove all messages",
	    { SMTPCTL, "remove", "all", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* acme-client - Let's Encrypt certificate management */
struct ctl ctl_acme[] = {
	{ "renew",      "renew certificates",
	    { ACMECLIENT, "-v", REQ, NULL }, NULL, NULL },
	{ "force",      "force renewal (ignore expiry)",
	    { ACMECLIENT, "-Fv", REQ, NULL }, NULL, NULL },
	{ "revoke",     "revoke certificate",
	    { ACMECLIENT, "-rv", REQ, NULL }, NULL, NULL },
	{ "check",      "check configuration",
	    { ACMECLIENT, "-n", REQ, NULL }, NULL, NULL },
	{ "edit",       "edit configuration",
	    { "acme", NULL, NULL }, call_editor, NULL },
	/* Additional acme-client commands for 100% coverage */
	{ "renew-config", "renew with alternate config",
	    { ACMECLIENT, "-f", REQ, "-v", REQ, NULL }, NULL, NULL },
	{ "force-config", "force with alternate config",
	    { ACMECLIENT, "-f", REQ, "-Fv", REQ, NULL }, NULL, NULL },
	{ "verbose",    "very verbose renewal",
	    { ACMECLIENT, "-vv", REQ, NULL }, NULL, NULL },
	{ "status",     "show certificate status",
	    { ACMECLIENT, "-v", "-n", REQ, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* ldpd - MPLS Label Distribution Protocol */
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

/* pflogd - PF logging daemon */
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

/* eigrpd - EIGRP routing protocol */
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

/* WireGuard VPN */
struct ctl ctl_wg[] = {
	{ "enable",     "enable WireGuard interfaces",
	    { "/bin/sh", "-c", "for i in /etc/hostname.wg*; do [ -f \"$i\" ] && sh /etc/netstart ${i##*/hostname.}; done", NULL }, NULL, X_ENABLE },
	{ "disable",    "disable WireGuard interfaces",
	    { "/bin/sh", "-c", "for i in $(ifconfig wg 2>/dev/null | grep ^wg | cut -d: -f1); do ifconfig $i destroy 2>/dev/null; done", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit WireGuard configuration",
	    { "wireguard", NULL, NULL }, call_editor, NULL },
	{ "genkey",     "generate new private key",
	    { "/bin/sh", "-c", "openssl rand -base64 32", NULL }, NULL, NULL },
	{ "genpsk",     "generate preshared key",
	    { "/bin/sh", "-c", "openssl rand -base64 32", NULL }, NULL, NULL },
	{ "pubkey",     "derive public key from private key",
	    { "/bin/sh", "-c", "echo 'Enter private key:' && read key && echo $key | openssl ec -pubout 2>/dev/null | tail -2 | head -1", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

struct daemons ctl_daemons[] = {
	{ "pf",		"PF",	ctl_pf,		PFCONF_TEMP,	0600, 1 },
	{ "ospf",	"OSPF", ctl_ospf,	OSPFCONF_TEMP,	0600, 0 },
	{ "bgp",	"BGP",	ctl_bgp,	BGPCONF_TEMP,	0600, 0 },
	{ "rip",	"RIP",	ctl_rip,	RIPCONF_TEMP,	0600, 0 },
	{ "relay",	"Relay", ctl_relay,	RELAYCONF_TEMP,	0600, 0 },
	{ "ipsec",	"IPsec", ctl_ipsec,	IPSECCONF_TEMP,	0600, 1 },
	{ "dvmrp",	"DVMRP", ctl_dvmrp,	DVMRPCONF_TEMP, 0600, 0 },
	{ "sasync",	"SAsync", ctl_sasync,	SASYNCCONF_TEMP,0600, 0 },
	{ "dhcp",	"DHCP",	ctl_dhcp,	DHCPCONF_TEMP,	0600, 0 },
	{ "snmp",	"SNMP",	ctl_snmp,	SNMPCONF_TEMP,	0600, 0 },
	{ "sshd",	"SSH",	ctl_sshd,	SSHDCONF_TEMP,	0600, 0 },
	{ "ntp",	"NTP",	ctl_ntp,	NTPCONF_TEMP,	0600, 0 },
	{ "ftp-proxy",  "FTP proxy", ctl_ftpproxy, FTPPROXY_TEMP, 0600, 0 },
	{ "dns", 	"DNS", ctl_dns,		RESOLVCONF_TEMP,0644, 0 },
	{ "inet",	"Inet", ctl_inet,	INETCONF_TEMP,	0600, 0 },
	/* OpenBSD base system services */
	{ "unbound",	"DNS Resolver", ctl_unbound, UNBOUNDCONF_TEMP, 0600, 0 },
	{ "httpd",	"HTTP Server", ctl_httpd, HTTPDCONF_TEMP, 0600, 0 },
	{ "iked",	"IKEv2 VPN", ctl_iked, IKEDCONF_TEMP, 0600, 0 },
	{ "rad",	"Router Adv", ctl_rad, RADCONF_TEMP, 0600, 0 },
	{ "smtpd",	"Mail", ctl_smtpd, SMTPDCONF_TEMP, 0600, 0 },
	{ "acme",	"ACME/LE", ctl_acme, ACMECONF_TEMP, 0600, 0 },
	{ "ldp",	"MPLS LDP", ctl_ldp, LDPDCONF_TEMP, 0600, 0 },
	{ "pflog",	"PF Log", ctl_pflog, PFLOGD_LOGFILE, 0600, 0 },
	{ "eigrp",	"EIGRP", ctl_eigrp, EIGRPDCONF_TEMP, 0600, 0 },
	{ "wireguard",	"WireGuard", ctl_wg,	WGCONF_TEMP,	0600, 0 },
	{ 0, 0, 0, 0, 0 }
};

void
ctl_symlink(char *temp, char **z, char *real)
{
	rmtemp(temp);
	symlink(real,temp);
}

/* flag to other nsh sessions or nsh conf() that actions have been taken with parameter in text file*/
void
flag_x(char *fname, int *y, char *data)
{
	FILE *file;
	char fenabled[SIZE_CONF_TEMP + sizeof(".enabled") + 1];
	char fother[SIZE_CONF_TEMP + sizeof(".other") + 1];
	char flocal[SIZE_CONF_TEMP + sizeof(".local") + 1];

	snprintf(fenabled, sizeof(fenabled), "%s.enabled", fname);
	snprintf(fother, sizeof(fother), "%s.other", fname);
	snprintf(flocal, sizeof(flocal), "%s.local", fname);

	if (y == X_ENABLE) {
		if ((file = fopen(fenabled, "w")) == NULL)
			return;
		chmod(fenabled, 0600);
		if (data)
			fprintf(file, "%s", data);
		fclose(file);
	} else if (y == X_DISABLE) {
		rmtemp(fenabled);
	} else if (y == X_OTHER) {
		rmtemp(flocal);
		if ((file = fopen(fother, "w")) == NULL)
			return;
		chmod(fother, 0600);
		if (data)
			fprintf(file, "%s", data);
		fclose(file);   
	} else if (y == X_LOCAL) {
		rmtemp(fother);
		if ((file = fopen(flocal, "w")) == NULL)
			return;
		chmod(flocal, 0600);
		if (data)
			fprintf(file, "%s", data);
		fclose(file);
	}
}

int
ctlhandler(int argc, char **argv, char *modhvar)
{
	struct daemons *daemons;
	struct ctl *x;
	char *args[NOPTFILL] = { NULL, NULL, NULL, NULL, NULL, NULL, '\0' };
	char **fillargs;

	/* loop daemon list to find table pointer */
	daemons = (struct daemons *) genget(hname, (char **)ctl_daemons,
	    sizeof(struct daemons));
	if (daemons == 0) {
		printf("%% Internal error - Invalid argument %s\n", argv[1]);
		return 0;
	} else if (Ambiguous(daemons)) {
		printf("%% Internal error - Ambiguous argument %s\n", argv[1]);
		return 0;
	}

	if (modhvar) {
		/* action specified or indented command specified */
		if (argc == 2 && isprefix(argv[1], "rules")) {
			/* skip 'X rules' line */
			return(0);
		}
		if (argc == 2 && isprefix(argv[1], "action")) {
			printf("%% Old configuration WILL NOT WORK! FIX IT!\n");
			return(0);
		}
		if (isprefix(modhvar, "rules")) {
			/* write indented line to tmp config file */
			rule_writeline(daemons->tmpfile, daemons->mode,
			    saveline);
			return 0;
		}
	}
	if (argc < 2 || argv[1][0] == '?') {
		gen_help((char **)daemons->table, "", "", sizeof(struct ctl));
		return 0;
	}

	x = (struct ctl *) genget(argv[1], (char **)daemons->table,
	    sizeof(struct ctl));
	if (x == 0) {
		printf("%% Invalid argument %s\n", argv[1]);
		return 0;
	} else if (Ambiguous(x)) {
		printf("%% Ambiguous argument %s\n", argv[1]);
		return 0;
	}

	fillargs = step_optreq(x->args, args, argc, argv, 2);
	if (fillargs == NULL)
		return 0;

	if (x->handler)
		(*x->handler)(fillargs[0], (char **)fillargs[1], fillargs[2]);
	else
		cmdargs(fillargs[0], fillargs);

	if (x->flag_x != NULL)
		flag_x(daemons->tmpfile, x->flag_x, NULL);

	return 1;
}

void
call_editor(char *name, char **args, char *z)
{
	int fd, found = 0;
	char *editor;
	struct daemons *daemons;

	for (daemons = ctl_daemons; daemons->name != 0; daemons++)
		if (strncmp(daemons->name, name, strlen(name)) == 0) {
			found = 1;
			break;
		}

	if (!found) {
		printf("%% call_editor internal error\n");
		return;
	}

	/* acq lock, call editor, test config with cmd and args, release lock */

	if ((editor = getenv("EDITOR")) == NULL || *editor == '\0')
		editor = DEFAULT_EDITOR;
	if ((fd = acq_lock(daemons->tmpfile)) > 0) {
		cmdarg(editor, daemons->tmpfile);
		chmod(daemons->tmpfile, daemons->mode);
		if (args != NULL)
			cmdargs(args[0], args);
		rls_lock(fd);
	} else
		printf ("%% %s configuration is locked for editing\n",
		    daemons->propername);
}

int
rule_writeline(char *fname, mode_t mode, char *writeline)
{
	FILE *rulefile;

	rulefile = fopen(fname, "a");
	if (rulefile == NULL) {
		printf("%% Rule write failed: %s\n", strerror(errno));
		return(1);
	}
	if (writeline[0] == ' ')
		writeline++;
	fprintf(rulefile, "%s", writeline);
	fclose(rulefile);
	chmod(fname, mode);
	return(0);
}

int
acq_lock(char *fname)
{
	int fd;
	char lockf[SIZE_CONF_TEMP + sizeof(".lock")];

	/*
	 * some text editors lock (vi), some don't (mg)
	 *
	 * here we lock a separate, do-nothing file so we don't interfere
	 * with the editors that do...
	 */
	snprintf(lockf, sizeof(lockf), "%s.lock", fname);
	if ((fd = open(lockf, O_RDWR | O_CREAT, 0600)) == -1)
			return(-1);
	if (flock(fd, LOCK_EX | LOCK_NB) == 0)
		return(fd);
	else {
		close(fd);
		return(-1);
	}
}

void
rls_lock(int fd)
{
	/* best-effort, who cares */
	flock(fd, LOCK_UN);
	close(fd);
	return;
}

void
rmtemp(char *file)
{
	if (unlink(file) != 0)
		if (errno != ENOENT)
			printf("%% Unable to remove temporary file %s: %s\n",
			    file, strerror(errno));
}
