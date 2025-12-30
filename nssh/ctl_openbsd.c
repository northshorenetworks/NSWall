/* $nswall: ctl_openbsd.c,v 1.0 2024/01/01 00:00:00 nswall Exp $ */
/*
 * NSWall - OpenBSD Feature Extensions
 *
 * Control structures for additional OpenBSD base system services.
 * This file provides integration points for:
 *   - unbound (DNS resolver with DNSSEC)
 *   - httpd (HTTP server for management UI)
 *   - iked (IKEv2 VPN)
 *   - rad (IPv6 Router Advertisement)
 *   - smtpd (Mail for alerts)
 *   - vmd (Virtualization)
 *
 * Copyright (c) 2024 NSWall Project
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include "externs.h"

/*
 * Service daemon paths
 */
#define UNBOUND         "/usr/sbin/unbound"
#define UNBOUNDCTL      "/usr/sbin/unbound-control"
#define UNBOUNDCONF_TEMP "/var/run/unbound.conf"

#define HTTPD           "/usr/sbin/httpd"
#define HTTPDCONF_TEMP  "/var/run/httpd.conf"

#define IKED            "/sbin/iked"
#define IKECTL          "/usr/sbin/ikectl"
#define IKEDCONF_TEMP   "/var/run/iked.conf"

#define RAD             "/usr/sbin/rad"
#define RADCONF_TEMP    "/var/run/rad.conf"

#define SMTPD           "/usr/sbin/smtpd"
#define SMTPCTL         "/usr/sbin/smtpctl"
#define SMTPDCONF_TEMP  "/var/run/smtpd.conf"

#define VMD             "/usr/sbin/vmd"
#define VMCTL           "/usr/sbin/vmctl"
#define VMDCONF_TEMP    "/var/run/vm.conf"

/*
 * External declarations from ctl.c
 */
extern void call_editor(char *, char **, char *);

/*
 * unbound - DNS resolver with DNSSEC support
 *
 * Provides:
 *   - Validating, recursive, caching DNS resolver
 *   - DNSSEC validation
 *   - DNS-over-TLS support
 *   - Local zone management
 *   - DNS blackholing
 */
char *ctl_unbound_test[] = { UNBOUND, "-c", UNBOUNDCONF_TEMP, "-d", NULL };
struct ctl ctl_unbound[] = {
	{ "enable",     "enable DNS resolver",
	    { UNBOUND, "-c", UNBOUNDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable DNS resolver",
	    { PKILL, "unbound", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "unbound", (char *)ctl_unbound_test, NULL }, call_editor, NULL },
	{ "reload",     "reload configuration",
	    { UNBOUNDCTL, "reload", NULL }, NULL, NULL },
	{ "flush",      "flush DNS cache",
	    { UNBOUNDCTL, "flush_zone", ".", NULL }, NULL, NULL },
	{ "stats",      "show statistics",
	    { UNBOUNDCTL, "stats_noreset", NULL }, NULL, NULL },
	{ "status",     "show resolver status",
	    { UNBOUNDCTL, "status", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * httpd - OpenBSD HTTP server
 *
 * Provides:
 *   - Native web server for management UI
 *   - FastCGI support
 *   - TLS/SSL built-in
 *   - Privilege separation
 */
char *ctl_httpd_test[] = { HTTPD, "-n", "-f", HTTPDCONF_TEMP, NULL };
struct ctl ctl_httpd[] = {
	{ "enable",     "enable HTTP server",
	    { HTTPD, "-f", HTTPDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable HTTP server",
	    { PKILL, "httpd", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "httpd", (char *)ctl_httpd_test, NULL }, call_editor, NULL },
	{ "reload",     "reload configuration",
	    { PKILL, "-HUP", "httpd", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * iked - IKEv2 daemon
 *
 * Provides:
 *   - Modern IKEv2 VPN protocol
 *   - MOBIKE support for mobile clients
 *   - EAP authentication
 *   - Road warrior configuration
 */
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
	{ "show",       "show SA database",
	    { IKECTL, "show", "sa", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * rad - Router Advertisement daemon
 *
 * Provides:
 *   - IPv6 SLAAC support
 *   - Prefix advertisement
 *   - DNS server advertisement (RDNSS)
 *   - Default router advertisement
 */
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

/*
 * smtpd - OpenSMTPD mail server
 *
 * Provides:
 *   - Lightweight MTA for system alerts
 *   - Local mail delivery
 *   - Relay to external SMTP servers
 *   - TLS support
 */
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
	{ "queue",      "show mail queue",
	    { SMTPCTL, "show", "queue", NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * vmd - Virtual Machine daemon
 *
 * Provides:
 *   - Native virtualization
 *   - Network function isolation
 *   - Service separation
 *   - Testing environment
 */
char *ctl_vmd_test[] = { VMD, "-n", "-f", VMDCONF_TEMP, NULL };
struct ctl ctl_vmd[] = {
	{ "enable",     "enable virtualization",
	    { VMD, "-f", VMDCONF_TEMP, NULL }, NULL, X_ENABLE },
	{ "disable",    "disable virtualization",
	    { PKILL, "vmd", NULL }, NULL, X_DISABLE },
	{ "edit",       "edit configuration",
	    { "vmd", (char *)ctl_vmd_test, NULL }, call_editor, NULL },
	{ "list",       "list virtual machines",
	    { VMCTL, "status", NULL }, NULL, NULL },
	{ "start",      "start a virtual machine",
	    { VMCTL, "start", REQ, NULL }, NULL, NULL },
	{ "stop",       "stop a virtual machine",
	    { VMCTL, "stop", REQ, NULL }, NULL, NULL },
	{ "console",    "connect to VM console",
	    { VMCTL, "console", REQ, NULL }, NULL, NULL },
	{ 0, 0, { 0 }, 0, 0 }
};

/*
 * Extended daemons table entry
 * Add these entries to ctl_daemons[] in ctl.c to enable the services:
 *
 * { "unbound",  "DNS Resolver", ctl_unbound, UNBOUNDCONF_TEMP, 0600, 0 },
 * { "httpd",    "HTTP Server",  ctl_httpd,   HTTPDCONF_TEMP,   0600, 0 },
 * { "iked",     "IKEv2 VPN",    ctl_iked,    IKEDCONF_TEMP,    0600, 0 },
 * { "rad",      "Router Adv",   ctl_rad,     RADCONF_TEMP,     0600, 0 },
 * { "smtpd",    "Mail",         ctl_smtpd,   SMTPDCONF_TEMP,   0600, 0 },
 * { "vmd",      "Virtual VM",   ctl_vmd,     VMDCONF_TEMP,     0600, 0 },
 *
 */
