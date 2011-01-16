#!/bin/php
<?php

$stat = $_GET['stat'];

function dump_pfconf() {
	global $g, $config;
	printf("<pre>");
	require_once("filter.inc");
	$pfconf = filter_print_pfconf();
	$pfconf = htmlentities($pfconf);
	printf("$pfconf");
	printf("</pre>");
}

function dump_pfoptions() {
	global $g, $config;
	printf("<pre>");
	$pfrules = "Timeouts:\n";
	$pfrules .= `/sbin/pfctl -s timeouts`;
	$pfrules .= "\nLimits:\n";
	$pfrules .= `/sbin/pfctl -s memory`;
	$pfrules = preg_replace("/@/", "\n<b>", $pfrules);
	$pfrules = preg_replace("/\n\s+\[/", "</b>\n [", $pfrules);
	printf("$pfrules");
}

function dump_df() {
	global $g, $config;
	printf("<pre>");
	$df = `/bin/df -h`;
	echo $df;
}

function dump_arp() {
	global $g, $config;
	printf("<pre>");
	$arp = `/usr/sbin/arp -a`;
	echo $arp;
}

function dump_interfaces() {
	global $g, $config;
	printf("<pre>");
	$ifconfig = `/sbin/ifconfig -A`;
	echo $ifconfig;
}

function dump_routes() {
	global $g, $config;
	printf("<pre>");
	$routes = `/sbin/route -n show -inet`;
	echo $routes;
}

function dump_xmlconf() {
	global $g, $config;
	printf("<pre>\n");
	$xml = `cat /conf/config.xml`;
	$xml = htmlentities($xml);
	echo $xml;
	printf("</pre>\n");
}

function dump_dmesg() {
	global $g, $config;
	printf("<pre>\n");
	$dmesg = `cat /var/log/dmesg.boot`;
	$dmesg = htmlentities($dmesg);
	echo $dmesg;
	printf("</pre>\n");
}

function dump_dhcp() {
	global $g, $config;
	printf("<pre>");
	$leases = `cat /var/db/dhcpd.leases`;
	echo $leases;
}

function get_cgi_stat($stat) {
	$output = <<<EOD
    <script type="text/javascript">
        clearInterval(refreshId);
        $('#livediag').html('Loading Content...');
        var refreshId = setInterval(function()
        {
            $.get('stats.cgi?$stat' + "&random=" + Math.random(), function(response){
                $('#livediag').html(response);
            });
        }, 5000);
    </script>
EOD;
	printf("$output");
}

function get_php_stat($stat) {
	$output = <<<EOD
    <script type="text/javascript">
        clearInterval(refreshId);
        $.get('stats.php?stat=$stat' + "&random=" + Math.random(), function(response){
            $('#livediag').html(response);
        });
    </script>
EOD;
	printf("$output");
}

switch ($stat) {
	case 'pfconf':
		get_php_stat('pfconf_content');
		break;
	case 'pfconf_content':
		dump_pfconf();
		break;
	case 'pfoptions':
		get_php_stat('pfoptions_content');
		break;
	case 'pfoptions_content':
		dump_pfoptions();
		break;
	case 'pfrules':
		get_cgi_stat('rules');
		break;
	case 'pfstates':
		get_cgi_stat('states');
		break;
	case 'pfnat':
		get_cgi_stat('nat');
		break;
	case 'pfqueues':
		get_cgi_stat('queues');
		break;
	case 'top':
		get_cgi_stat('top');
		break;
	case 'df':
		get_php_stat('df_content');
		break;
	case 'df_content':
		dump_df();
		break;
	case 'arp':
		get_php_stat('arp_content');
		break;
	case 'arp_content':
		dump_arp();
		break;
	case 'interfaces':
		get_php_stat('interfaces_content');
		break;
	case 'interfaces_content':
		dump_interfaces();
		break;
	case 'routes':
		get_php_stat('routes_content');
		break;
	case 'routes_content':
		dump_routes();
		break;
	case 'dhcp':
		get_php_stat('dhcp_content');
		break;
	case 'dhcp_content':
		dump_dhcp();
		break;
	case 'xmlconf':
		get_php_stat('xmlconf_content');
		break;
	case 'xmlconf_content':
		dump_xmlconf();
		break;
	case 'dmesg':
		get_php_stat('dmesg_content');
		break;
	case 'dmesg_content':
		dump_dmesg();
		break;
}
?>
