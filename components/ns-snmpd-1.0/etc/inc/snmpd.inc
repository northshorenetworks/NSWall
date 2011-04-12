<?php
/*
 $Id: services.inc,v 1.9 2009/04/20 17:51:02 jrecords Exp $
 part of m0n0wall (http://m0n0.ch/wall)

 Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
 All rights reserved.

 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:

 1. Redistributions of source code must retain the above copyright notice,
 this list of conditions and the following disclaimer.

 2. Redistributions in binary form must reproduce the above copyright
 notice, this list of conditions and the following disclaimer in the
 documentation and/or other materials provided with the distribution.

 THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 POSSIBILITY OF SUCH DAMAGE.
 */

/* include all configuration functions */
require_once("functions.inc");

function services_snmpd_configure() {
	global $config, $g;

	/* kill any running snmpd */
	sigkillbypid("{$g['varrun_path']}/snmpd.pid", "TERM");

	if (isset($config['snmpd']['enable'])) {

		if ($g['booting'])
		echo "Starting SNMP agent... ";

		/* generate snmpd.conf */
		$fd = fopen("{$g['varetc_path']}/snmpd.conf", "w");
		if (!$fd) {
			printf("Error: cannot open snmpd.conf in services_snmpd_configure().\n");
			return 1;
		}

		$snmpdconf = <<<EOD

listen_addr="0.0.0.0"

# Restrict daemon to listen on localhost only
listen on \$listen_addr

# Specify a number of trap receivers
#trap receiver nms.localdomain.local

# Adjust the local system information
system contact "{$config['snmpd']['syscontact']}"
#system description "Powered by OpenBSD"
system location "{$config['snmpd']['syslocation']}"
system services 74

# Provide static user-defined SNMP OIDs
oid 1.3.6.1.4.1.30155.42.3.1 name testStringValue read-only string "Test"
oid 1.3.6.1.4.1.30155.42.3.4 name testIntValue read-write integer 1


# rocommunity "{$config['snmpd']['rocommunity']}"

EOD;

		if (isset($config['snmpd']['bindlan'])) {
			$snmpdconf .= "agentaddress udp:161@{$config['interfaces']['lan']['ipaddr']}\n";
		}

		fwrite($fd, $snmpdconf);
		fclose($fd);

		/* run snmpd */
		mwexec("/usr/sbin/snmpd -f {$g['varetc_path']}/snmpd.conf");
          

		if ($g['booting'])
		echo "done\n";
	}

	return 0;
}

?>
