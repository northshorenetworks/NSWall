/* $nswall: ctl_openbsd.h,v 1.0 2024/01/01 00:00:00 nswall Exp $ */
/*
 * NSWall - OpenBSD Feature Extensions
 *
 * This header defines control structures for additional OpenBSD
 * base system services that can be integrated into NSWall.
 *
 * Copyright (c) 2024 NSWall Project
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 */

#ifndef _CTL_OPENBSD_H_
#define _CTL_OPENBSD_H_

/*
 * Additional OpenBSD service daemons
 */

/* DNS - unbound */
#define UNBOUND         "/usr/sbin/unbound"
#define UNBOUNDCTL      "/usr/sbin/unbound-control"
#define UNBOUNDCONF_TEMP "/var/run/unbound.conf"

/* HTTP - httpd */
#define HTTPD           "/usr/sbin/httpd"
#define HTTPDCONF_TEMP  "/var/run/httpd.conf"

/* IKEv2 - iked */
#define IKED            "/sbin/iked"
#define IKECTL          "/usr/sbin/ikectl"
#define IKEDCONF_TEMP   "/var/run/iked.conf"

/* IPv6 Router Advertisement - rad */
#define RAD             "/usr/sbin/rad"
#define RADCONF_TEMP    "/var/run/rad.conf"

/* Mail - smtpd */
#define SMTPD           "/usr/sbin/smtpd"
#define SMTPCTL         "/usr/sbin/smtpctl"
#define SMTPDCONF_TEMP  "/var/run/smtpd.conf"

/* Certificate - acme-client */
#define ACMECLIENT      "/usr/sbin/acme-client"
#define ACMECONF_TEMP   "/var/run/acme-client.conf"

/* Virtualization - vmd */
#define VMD             "/usr/sbin/vmd"
#define VMCTL           "/usr/sbin/vmctl"
#define VMDCONF_TEMP    "/var/run/vm.conf"

/* PF Logging - pflogd */
#define PFLOGD          "/usr/sbin/pflogd"

/*
 * Control structure declarations
 * These follow the same pattern as existing ctl_* structures in ctl.c
 */

extern struct ctl ctl_unbound[];
extern struct ctl ctl_httpd[];
extern struct ctl ctl_iked[];
extern struct ctl ctl_rad[];
extern struct ctl ctl_smtpd[];
extern struct ctl ctl_vmd[];

/*
 * Show command structures for new services
 */

/* unbound show commands */
struct prot1 uncs[] = {
    { "stats",      "DNS resolver statistics",
        { UNBOUNDCTL, "stats_noreset", NULL } },
    { "status",     "Resolver status",
        { UNBOUNDCTL, "status", NULL } },
    { "cache",      "Cache contents",
        { UNBOUNDCTL, "dump_cache", NULL } },
    { "zones",      "Local zones",
        { UNBOUNDCTL, "list_local_zones", NULL } },
    { 0, 0, { 0 } }
};

/* iked show commands */
struct prot1 ikcs[] = {
    { "sa",         "Security associations",
        { IKECTL, "show", "sa", NULL } },
    { "stats",      "IKE statistics",
        { IKECTL, "show", "stats", NULL } },
    { 0, 0, { 0 } }
};

/* smtpd show commands */
struct prot1 smcs[] = {
    { "queue",      "Mail queue",
        { SMTPCTL, "show", "queue", NULL } },
    { "stats",      "SMTP statistics",
        { SMTPCTL, "show", "stats", NULL } },
    { "status",     "Service status",
        { SMTPCTL, "show", "status", NULL } },
    { 0, 0, { 0 } }
};

/* vmd show commands */
struct prot1 vmcs[] = {
    { "status",     "VM status",
        { VMCTL, "status", NULL } },
    { 0, 0, { 0 } }
};

#endif /* _CTL_OPENBSD_H_ */
