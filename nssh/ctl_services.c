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
 * Network service daemon control commands
 * Covers: dhcpd, snmpd, ntpd, sshd, unbound, httpd, smtpd, acme-client, relayd
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "externs.h"
#include "ctl.h"

/*
 * DHCP Server
 */
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

/*
 * SNMP Daemon
 */
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

/*
 * SSH Daemon
 */
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

/*
 * NTP Daemon
 */
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
	{ "ntp-status", "show NTP status",
	    { NTPCTL, "-s", "status", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * Relayd - Load Balancer/Proxy
 */
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

/*
 * Unbound - DNS Resolver with DNSSEC
 */
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

/*
 * httpd - OpenBSD HTTP Server
 */
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

/*
 * smtpd - OpenSMTPD Mail Server
 */
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

/*
 * acme-client - Let's Encrypt Certificate Management
 */
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
