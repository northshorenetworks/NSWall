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
	{ "fib",        "fib couple/decouple",
	    { OSPFCTL, "fib", REQ, NULL }, NULL, NULL },
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
	{ "trap",	"send traps",
	    { SNMPCTL, "trap", "send", REQ, OPT, NULL }, NULL, NULL },
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
	{ "host",	"per-host control",
	    { RELAYCTL, "host", OPT, OPT, NULL }, NULL, NULL },
	{ "table",	"per-table control",
	    { RELAYCTL, "table", OPT, OPT, NULL }, NULL, NULL },
	{ "redirect",	"per-redirect control",
	    { RELAYCTL, "redirect", OPT, OPT, NULL }, NULL, NULL },
	{ "monitor",	"monitor mode",
	    { RELAYCTL, "monitor", NULL }, NULL, NULL },
	{ "poll",	"poll mode",
	    { RELAYCTL, "poll", NULL }, NULL, NULL},
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
	{ "reset",      "reset IKE SA",
	    { IKECTL, "reset", "all", NULL }, NULL, NULL },
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
	{ "pause",      "pause mail processing",
	    { SMTPCTL, "pause", "smtp", NULL }, NULL, NULL },
	{ "resume",     "resume mail processing",
	    { SMTPCTL, "resume", "smtp", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* acme-client - Let's Encrypt certificate management */
struct ctl ctl_acme[] = {
	{ "renew",      "renew certificates",
	    { ACMECLIENT, "-v", OPT, NULL }, NULL, NULL },
	{ "edit",       "edit configuration",
	    { "acme", NULL, NULL }, call_editor, NULL },
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
	    { LDPCTL, "clear", "neighbors", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/* pflogd - PF logging daemon */
struct ctl ctl_pflog[] = {
	{ "enable",     "enable PF logging",
	    { PFLOGD, "-s", "128", "-f", PFLOGD_LOGFILE, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable PF logging",
	    { PKILL, "pflogd", NULL }, NULL, X_DISABLE },
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
	    { EIGRPCTL, "clear", "neighbors", NULL }, NULL, NULL },
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
