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
 * VPN daemon control commands
 * Covers: iked (IKEv2), isakmpd (IKEv1), WireGuard
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "externs.h"
#include "ctl.h"

/*
 * IPsec - IKEv1 (isakmpd)
 */
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

/*
 * iked - IKEv2 VPN Daemon
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

/*
 * WireGuard VPN
 */
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
