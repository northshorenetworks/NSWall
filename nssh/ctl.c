/* $NSWall$ */
/*
 * NSWall - Network Shell for OpenBSD firewalls
 * Forked from NSH (Network Shell) by Chris Cappuccio
 *
 * Original NSH Copyright (c) 2008 Chris Cappuccio <chris@nmedia.net>
 * NSWall Extensions Copyright (c) 2024-2025 NSWall Contributors
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
 * ACKNOWLEDGEMENT:
 * NSWall is a fork of NSH (Network Shell) originally created by
 * Chris Cappuccio. NSH provides the foundation for this project's
 * network configuration and service management capabilities.
 * Original NSH project: https://github.com/yellowman/nsh
 *
 * This file contains core control functions and the daemon registry.
 * Individual daemon control arrays are in separate files:
 *   ctl_pf.c      - Packet Filter (pf)
 *   ctl_routing.c - Routing protocols (ospf, bgp, rip, ldp, eigrp)
 *   ctl_services.c - Network services (dhcp, snmp, ntp, sshd, etc.)
 *   ctl_vpn.c     - VPN daemons (iked, ipsec, wireguard)
 *   ctl_misc.c    - Miscellaneous (dvmrp, sasync, ftp-proxy, etc.)
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
#include "ctl.h"

/* Internal function prototypes */
int rule_writeline(char *, mode_t, char *);
int acq_lock(char *);
void rls_lock(int);

/*
 * Show config file contents
 */
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

/*
 * Show daemon status
 */
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

/*
 * Set/replace a config line (key value)
 */
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

/*
 * Remove a config line
 */
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

/*
 * Append a line to config
 */
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

/*
 * Initialize config with defaults
 */
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

/*
 * Master daemon registry
 * References control arrays from ctl_*.c files
 */
struct daemons ctl_daemons[] = {
	/* Packet Filter */
	{ "pf",		"PF",	ctl_pf,		PFCONF_TEMP,	0600, 1 },
	/* Routing protocols */
	{ "ospf",	"OSPF", ctl_ospf,	OSPFCONF_TEMP,	0600, 0 },
	{ "bgp",	"BGP",	ctl_bgp,	BGPCONF_TEMP,	0600, 0 },
	{ "rip",	"RIP",	ctl_rip,	RIPCONF_TEMP,	0600, 0 },
	{ "ldp",	"MPLS LDP", ctl_ldp,	LDPDCONF_TEMP,	0600, 0 },
	{ "eigrp",	"EIGRP", ctl_eigrp,	EIGRPDCONF_TEMP, 0600, 0 },
	/* Network services */
	{ "relay",	"Relay", ctl_relay,	RELAYCONF_TEMP,	0600, 0 },
	{ "dhcp",	"DHCP",	ctl_dhcp,	DHCPCONF_TEMP,	0600, 0 },
	{ "snmp",	"SNMP",	ctl_snmp,	SNMPCONF_TEMP,	0600, 0 },
	{ "sshd",	"SSH",	ctl_sshd,	SSHDCONF_TEMP,	0600, 0 },
	{ "ntp",	"NTP",	ctl_ntp,	NTPCONF_TEMP,	0600, 0 },
	{ "unbound",	"DNS Resolver", ctl_unbound, UNBOUNDCONF_TEMP, 0600, 0 },
	{ "httpd",	"HTTP Server", ctl_httpd, HTTPDCONF_TEMP, 0600, 0 },
	{ "smtpd",	"Mail", ctl_smtpd,	SMTPDCONF_TEMP,	0600, 0 },
	{ "acme",	"ACME/LE", ctl_acme,	ACMECONF_TEMP,	0600, 0 },
	/* VPN */
	{ "ipsec",	"IPsec", ctl_ipsec,	IPSECCONF_TEMP,	0600, 1 },
	{ "iked",	"IKEv2 VPN", ctl_iked,	IKEDCONF_TEMP,	0600, 0 },
	{ "wireguard",	"WireGuard", ctl_wg,	WGCONF_TEMP,	0600, 0 },
	/* Miscellaneous */
	{ "dvmrp",	"DVMRP", ctl_dvmrp,	DVMRPCONF_TEMP, 0600, 0 },
	{ "sasync",	"SAsync", ctl_sasync,	SASYNCCONF_TEMP,0600, 0 },
	{ "ftp-proxy",  "FTP proxy", ctl_ftpproxy, FTPPROXY_TEMP, 0600, 0 },
	{ "dns", 	"DNS", ctl_dns,		RESOLVCONF_TEMP,0644, 0 },
	{ "inet",	"Inet", ctl_inet,	INETCONF_TEMP,	0600, 0 },
	{ "rad",	"Router Adv", ctl_rad,	RADCONF_TEMP,	0600, 0 },
	{ "pflog",	"PF Log", ctl_pflog,	PFLOGD_LOGFILE, 0600, 0 },
	{ 0, 0, 0, 0, 0 }
};

/*
 * Symlink management for DNS control
 */
void
ctl_symlink(char *temp, char **z, char *real)
{
	rmtemp(temp);
	symlink(real, temp);
}

/*
 * Flag to other nsh sessions or nsh conf() that actions have been taken
 * with parameter in text file
 */
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

/*
 * Main control handler
 * Dispatches commands to appropriate daemon control tables
 */
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

/*
 * Call editor for configuration files
 */
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

/*
 * Write a rule line to configuration file
 */
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

/*
 * Acquire file lock for configuration editing
 */
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

/*
 * Release file lock
 */
void
rls_lock(int fd)
{
	/* best-effort, who cares */
	flock(fd, LOCK_UN);
	close(fd);
	return;
}

/*
 * Remove temporary file
 */
void
rmtemp(char *file)
{
	if (unlink(file) != 0)
		if (errno != ENOENT)
			printf("%% Unable to remove temporary file %s: %s\n",
			    file, strerror(errno));
}
