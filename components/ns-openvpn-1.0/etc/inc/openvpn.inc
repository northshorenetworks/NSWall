<?php

require_once("functions.inc");

function services_openvpn_configure() {
	global $config, $g;

	system("pkill openvpn");
	mwexec("/usr/sbin/openvpn --daemon --config /etc/vpn/openvpn.conf");

	return 0;
}

?>
