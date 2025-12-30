/* $nsh $ */
/*
 * Copyright (c) 2024 North Shore Networks
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
#include <errno.h>
#include <sys/limits.h>
#include <sys/socket.h>
#include <sys/ioctl.h>
#include <netinet/in.h>
#include <net/if.h>
#include <arpa/inet.h>
#include "externs.h"

/* WireGuard configuration types */
#define WG_PORT		0
#define WG_PEERIP	1
#define WG_PEERAIP	2
#define WG_PEERPSK	3
#define WG_PEERKEEPALIVE 4
#define WG_PEERENDPOINT	5

static struct intwg {
	char *name;
	char *descr;
	int type;
} intwgs[] = {
	{ "wgport",		"port",		WG_PORT },
	{ "wgpeer",		"base64-key",	WG_PEERIP },
	{ "wgaip",		"allowed-ip",	WG_PEERAIP },
	{ "wgpsk",		"psk",		WG_PEERPSK },
	{ "wgpka",		"seconds",	WG_PEERKEEPALIVE },
	{ "wgendpoint",		"endpoint",	WG_PEERENDPOINT },
	{ 0,			0,		0 }
};

/*
 * intwg - Configure WireGuard interface parameters
 * This handles wgport, wgpeer, wgaip, wgpsk, wgpka, wgendpoint commands
 */
int
intwg(char *ifname, int ifs, int argc, char **argv)
{
	const char *errmsg = NULL;
	struct ifreq ifr;
	int set;
	u_int32_t val = 0;
	struct intwg *x;
	char *args[10] = { NULL };
	int i;

	if (NO_ARG(argv[0])) {
		set = 0;
		argc--;
		argv++;
	} else
		set = 1;

	x = (struct intwg *) genget(argv[0], (char **)intwgs,
	    sizeof(struct intwg));

	if (x == 0) {
		printf("%% Internal error - Invalid argument %s\n", argv[0]);
		return 0;
	} else if (Ambiguous(x)) {
		printf("%% Internal error - Ambiguous argument %s\n", argv[0]);
		return 0;
	}

	argv++;
	argc--;

	strlcpy(ifr.ifr_name, ifname, sizeof(ifr.ifr_name));

	switch(x->type) {
	case WG_PORT:
		if ((!set && argc > 1) || (set && argc != 1)) {
			printf("%% wgport <port>\n");
			printf("%% no wgport [port]\n");
			return (0);
		}
		if (set) {
			val = strtonum(argv[0], 1, 65535, &errmsg);
			if (errmsg) {
				printf("%% port value out of range: %s\n", errmsg);
				return(0);
			}
		}
		/* Build ifconfig command: ifconfig wgX wgport <port> */
		args[0] = IFCONFIG;
		args[1] = ifname;
		if (set) {
			args[2] = "wgport";
			args[3] = argv[0];
			args[4] = NULL;
		} else {
			args[2] = "-wgport";
			args[3] = NULL;
		}
		cmdargs(args[0], args);
		break;

	case WG_PEERIP:
		if ((!set && argc < 1) || (set && argc < 1)) {
			printf("%% wgpeer <base64-public-key>\n");
			printf("%% no wgpeer <base64-public-key>\n");
			return (0);
		}
		args[0] = IFCONFIG;
		args[1] = ifname;
		if (set) {
			args[2] = "wgpeer";
			args[3] = argv[0];
			args[4] = NULL;
		} else {
			args[2] = "-wgpeer";
			args[3] = argv[0];
			args[4] = NULL;
		}
		cmdargs(args[0], args);
		break;

	case WG_PEERAIP:
		if ((!set && argc < 2) || (set && argc < 2)) {
			printf("%% wgaip <peer-key> <allowed-ip/prefix>\n");
			printf("%% no wgaip <peer-key> <allowed-ip/prefix>\n");
			return (0);
		}
		args[0] = IFCONFIG;
		args[1] = ifname;
		args[2] = "wgpeer";
		args[3] = argv[0];
		if (set) {
			args[4] = "wgaip";
			args[5] = argv[1];
			args[6] = NULL;
		} else {
			args[4] = "-wgaip";
			args[5] = argv[1];
			args[6] = NULL;
		}
		cmdargs(args[0], args);
		break;

	case WG_PEERPSK:
		if ((!set && argc < 1) || (set && argc < 2)) {
			printf("%% wgpsk <peer-key> <preshared-key>\n");
			printf("%% no wgpsk <peer-key>\n");
			return (0);
		}
		args[0] = IFCONFIG;
		args[1] = ifname;
		args[2] = "wgpeer";
		args[3] = argv[0];
		if (set) {
			args[4] = "wgpsk";
			args[5] = argv[1];
			args[6] = NULL;
		} else {
			args[4] = "-wgpsk";
			args[5] = NULL;
		}
		cmdargs(args[0], args);
		break;

	case WG_PEERKEEPALIVE:
		if ((!set && argc < 1) || (set && argc < 2)) {
			printf("%% wgpka <peer-key> <seconds>\n");
			printf("%% no wgpka <peer-key>\n");
			return (0);
		}
		if (set) {
			val = strtonum(argv[1], 0, 65535, &errmsg);
			if (errmsg) {
				printf("%% keepalive value out of range: %s\n", errmsg);
				return(0);
			}
		}
		args[0] = IFCONFIG;
		args[1] = ifname;
		args[2] = "wgpeer";
		args[3] = argv[0];
		if (set) {
			args[4] = "wgpka";
			args[5] = argv[1];
			args[6] = NULL;
		} else {
			args[4] = "wgpka";
			args[5] = "0";
			args[6] = NULL;
		}
		cmdargs(args[0], args);
		break;

	case WG_PEERENDPOINT:
		if ((!set && argc < 1) || (set && argc < 2)) {
			printf("%% wgendpoint <peer-key> <ip:port>\n");
			printf("%% no wgendpoint <peer-key>\n");
			return (0);
		}
		args[0] = IFCONFIG;
		args[1] = ifname;
		args[2] = "wgpeer";
		args[3] = argv[0];
		if (set) {
			args[4] = "wgendpoint";
			args[5] = argv[1];
			args[6] = NULL;
		} else {
			args[4] = "-wgendpoint";
			args[5] = NULL;
		}
		cmdargs(args[0], args);
		break;
	}

	return (0);
}

/*
 * intwgkey - Set WireGuard private key
 */
int
intwgkey(char *ifname, int ifs, int argc, char **argv)
{
	int set;
	char *args[6] = { NULL };

	if (NO_ARG(argv[0])) {
		set = 0;
		argc--;
		argv++;
	} else
		set = 1;

	argc--;
	argv++;

	if ((!set && argc > 1) || (set && argc != 1)) {
		printf("%% wgkey <base64-private-key>\n");
		printf("%% no wgkey [key]\n");
		return (0);
	}

	args[0] = IFCONFIG;
	args[1] = ifname;
	if (set) {
		args[2] = "wgkey";
		args[3] = argv[0];
		args[4] = NULL;
	} else {
		args[2] = "-wgkey";
		args[3] = NULL;
	}
	cmdargs(args[0], args);

	return (0);
}

/*
 * intwgrtable - Set WireGuard routing table
 */
int
intwgrtable(char *ifname, int ifs, int argc, char **argv)
{
	const char *errmsg = NULL;
	int set;
	u_int32_t val;
	char *args[6] = { NULL };

	if (NO_ARG(argv[0])) {
		set = 0;
		argc--;
		argv++;
	} else
		set = 1;

	argc--;
	argv++;

	if ((!set && argc > 1) || (set && argc != 1)) {
		printf("%% wgrtable <table-id>\n");
		printf("%% no wgrtable [table-id]\n");
		return (0);
	}

	if (set) {
		val = strtonum(argv[0], 0, RT_TABLEID_MAX, &errmsg);
		if (errmsg) {
			printf("%% table value out of range: %s\n", errmsg);
			return(0);
		}
	}

	args[0] = IFCONFIG;
	args[1] = ifname;
	if (set) {
		args[2] = "wgrtable";
		args[3] = argv[0];
		args[4] = NULL;
	} else {
		args[2] = "-wgrtable";
		args[3] = NULL;
	}
	cmdargs(args[0], args);

	return (0);
}

/*
 * show_wg - Show WireGuard interface status
 */
int
show_wg(int argc, char **argv)
{
	char *args[6] = { NULL };

	if (argc < 3) {
		printf("%% show wireguard <interface>\n");
		return (0);
	}

	/* ifconfig wgX shows all WireGuard configuration */
	args[0] = IFCONFIG;
	args[1] = argv[2];
	args[2] = NULL;

	cmdargs(args[0], args);

	return (1);
}

/*
 * wg_stats - Display WireGuard statistics
 */
void
wg_stats(void)
{
	printf("%% WireGuard statistics:\n");
	/* WireGuard statistics are shown per-interface via ifconfig */
	printf("%% Use 'show interface wgX' to view per-interface statistics\n");
}

/*
 * conf_wg - Generate WireGuard configuration for config file output
 */
int
conf_wg(FILE *output, int ifs, char *ifname)
{
	FILE *wgconf;
	char cmd[256];
	char line[1024];
	char *p;
	int in_peer = 0;
	char current_peer[128] = "";

	/* Only process wg interfaces */
	if (strncmp(ifname, "wg", 2) != 0)
		return (0);

	/* Use ifconfig to get WireGuard status and parse output */
	snprintf(cmd, sizeof(cmd), "/sbin/ifconfig %s 2>/dev/null", ifname);
	wgconf = popen(cmd, "r");
	if (wgconf == NULL)
		return (0);

	while (fgets(line, sizeof(line), wgconf) != NULL) {
		/* Trim newline */
		line[strcspn(line, "\n")] = '\0';

		/* Look for wgport line */
		if ((p = strstr(line, "wgport ")) != NULL) {
			p += 7;  /* Skip "wgport " */
			/* Extract port number */
			char *end = p;
			while (*end && *end != ' ' && *end != '\t')
				end++;
			*end = '\0';
			fprintf(output, " wgport %s\n", p);
		}

		/* Look for wgpubkey (indicates we have a key set) */
		if (strstr(line, "wgpubkey ") != NULL) {
			/* Private key is set - we don't output it for security */
		}

		/* Look for peer entries */
		if ((p = strstr(line, "wgpeer ")) != NULL) {
			p += 7;  /* Skip "wgpeer " */
			/* Extract peer public key */
			char *end = p;
			while (*end && *end != ' ' && *end != '\t')
				end++;
			*end = '\0';
			strlcpy(current_peer, p, sizeof(current_peer));
			in_peer = 1;
			fprintf(output, " wgpeer %s\n", current_peer);
		}

		/* Look for wgaip (allowed IPs) - appears after peer */
		if (in_peer && (p = strstr(line, "wgaip ")) != NULL) {
			p += 6;  /* Skip "wgaip " */
			/* Parse allowed IPs */
			char *aip = p;
			while (*aip) {
				char *end = aip;
				while (*end && *end != ',' && *end != ' ' && *end != '\t')
					end++;
				char term = *end;
				*end = '\0';
				if (strlen(aip) > 0 && strlen(current_peer) > 0) {
					fprintf(output, " wgaip %s %s\n", current_peer, aip);
				}
				if (term == '\0')
					break;
				aip = end + 1;
				while (*aip == ' ' || *aip == '\t' || *aip == ',')
					aip++;
			}
		}

		/* Look for wgendpoint */
		if (in_peer && (p = strstr(line, "wgendpoint ")) != NULL) {
			p += 11;  /* Skip "wgendpoint " */
			char *end = p;
			while (*end && *end != ' ' && *end != '\t')
				end++;
			*end = '\0';
			if (strlen(current_peer) > 0) {
				fprintf(output, " wgendpoint %s %s\n", current_peer, p);
			}
		}

		/* Look for wgpka (persistent keepalive) */
		if (in_peer && (p = strstr(line, "wgpka ")) != NULL) {
			p += 6;  /* Skip "wgpka " */
			char *end = p;
			while (*end && *end != ' ' && *end != '\t')
				end++;
			*end = '\0';
			if (atoi(p) > 0 && strlen(current_peer) > 0) {
				fprintf(output, " wgpka %s %s\n", current_peer, p);
			}
		}
	}

	pclose(wgconf);
	return (0);
}
