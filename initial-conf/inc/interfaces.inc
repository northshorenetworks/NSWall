<?php
/*
	$Id: interfaces.inc,v 1.49 2009/03/03 09:24:45 jrecords Exp $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>
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

function interfaces_loopback_configure() {
	global $config, $g;

	mwexec("/sbin/ifconfig lo0 127.0.0.1 -inet6");
	
	return 0;
}

function interfaces_trunk_configure() {
	global $config, $g;

	if (is_array($config['trunks']['trunk']) && count($config['trunks']['trunk'])) {
		if ($g['booting'])
                echo "Configuring Trunk interfaces... ";
		/* prepare the children interfaces*/
        	foreach($config['trunks']['trunk'] as $trunkcfg) {
			/* only bring up the trunk if it's directly in use */
			foreach ($config['interfaces'] as $interface) {
        			if ($interface['if'] == $trunkcfg['trunkport']) {
					$trunkif = $trunkcfg['trunkport'] . ' trunkproto ';
					$trunkif .= $trunkcfg['type'];
					for($i=0;$i<sizeof($trunkcfg['childiflist']); $i++ ) {
        					$child = "childif" . "$i";
        					$trunkif .= " trunkport {$trunkcfg['childiflist'][$child]}";
        					/*bring up the child interfaces*/
						mwexec("/sbin/ifconfig {$trunkcfg['childiflist'][$child]} up");
	        			}
        				mwexec("/sbin/ifconfig $trunkif up");
				}
			}
			/* only bring up the trunk if it's in use by a vlan */
			foreach ($config['vlans']['vlan'] as $vlan) {
                		if ($vlan['if'] == $trunkcfg['trunkport']) {
                			$trunkif = $trunkcfg['trunkport'] . ' trunkproto ';
                			$trunkif .= $trunkcfg['type'];
                			for($i=0;$i<sizeof($trunkcfg['childiflist']); $i++ ) {
                				$child = "childif" . "$i";
                				$trunkif .= " trunkport {$trunkcfg['childiflist'][$child]}";
                				/*bring up the child interfaces*/
                				mwexec("/sbin/ifconfig {$trunkcfg['childiflist'][$child]} up");
                			}
                			mwexec("/sbin/ifconfig $trunkif up");
                		}
                	}
		}
		if ($g['booting'])
        		echo "done\n";
	}
	return 0;
}

function interfaces_carp_configure() {
        global $config, $g;

	/* Set the carp settings and configure the CARP interfaces. */
        if (isset($config['carp']['carpenable']))
	 	mwexec("/sbin/sysctl net.inet.carp.allow=1");
	
	if (isset($config['carp']['preemptenable']))
	 	mwexec("/sbin/sysctl net.inet.carp.preempt=1");

	if (isset($config['carp']['logenable']))
	 	mwexec("/sbin/sysctl net.inet.carp.log=1");

       	if (is_array($config['carp']['virtualhost']) && count($config['carp']['virtualhost'])) {
               	if ($g['booting'])
                       	echo "Configuring CARP interfaces... ";
               	$i = 0;
               	foreach($config['carp']['virtualhost'] as $carpcfg) {
               		if($carpcfg['carpmode'] == 'activestandby') {
				if($carpcfg['carphostmode'] == 'active') {
					$vid = 1;
					$advskew = 0;
				} else {
					$vid = 2;
					$advskew = 254;
				}
				$carpdev = $config['interfaces'][$carpcfg['interface']]['if'];
                       		mwexec("/sbin/ifconfig carp" . $i . " create");
                       		$carpcmd = "carp$i" . " vhid " . $vid . " pass ";
                       		$carpcmd .= $carpcfg['password'] . " carpdev $carpdev" . " advskew " . $advskew;
                       		$carpcmd .= " " . $carpcfg['ip'] . "/" . $carpcfg['subnet'];
                       		mwexec("/sbin/ifconfig $carpcmd");
                       		$i++;
        		} else {
				$carpdev = $config['interfaces'][$carpcfg['interface']]['if'];
                                mwexec("/sbin/ifconfig carp" . $i . " create");
                                $carpcmd = "carp$i "  . $carpcfg['ip'] . "/" . $carpcfg['subnet'];
                                $carpcmd .= " pass " . $carpcfg['password'] . " carpdev $carpdev ";
                                $carpcmd .= interfaces_cluster_generate($carpcfg['activemember'], $carpcfg['activenodes']); 
				mwexec("/sbin/ifconfig $carpcmd");
                                $i++;	
			}
	       	}
               	if ($g['booting'])
                       	echo "done\n";
       	}
       	return 0;
}

function interfaces_cluster_generate($member, $nodecount) {
        $askdev = (254/$nodecount) * (1 + (1 / ($nodecount -1)));
        $string = "balancing ip carpnodes ";
        #make an array of nodes
        $nodes = array();
        for ($i=1;$i<=$nodecount;$i++) {
                array_push($nodes, $i);
        }
        #sort the array so that the member we are using is at the begining
        for ($i=1;$i<=$nodecount;$i++) {
                if($member != 1) {
                        if ($i < $member) {
                                array_shift($nodes);
                                array_push($nodes, $i);
                        }
                }
        }
        $n = 0;
        foreach ($nodes as $_) {
                $string .= "$_:" . floor($n * $askdev) . ",";
                $n++;
        }
	return preg_replace('/,$/','',$string);
}

function interfaces_vlan_configure() {
	global $config, $g;
	
	if (is_array($config['vlans']['vlan']) && count($config['vlans']['vlan'])) {
		if ($g['booting'])
                echo "Configuring VLAN interfaces... ";		
		
		$i = 0;
		
		foreach ($config['vlans']['vlan'] as $vlan) {
			
			$cmd = "/sbin/ifconfig vlan{$i} create vlan " . 
				escapeshellarg($vlan['tag']) . " vlandev " . 
				escapeshellarg($vlan['if']);
			
			mwexec($cmd);
			
			$i++;
		}
	if ($g['booting'])
                echo "done\n";
	}
	return 0;
}

function interfaces_lan_configure() {
	global $config, $g;

	system_determine_hwplatform();

	if ($g['booting'])
		echo "Configuring LAN interface... ";
	
	$lancfg = $config['interfaces']['lan'];
	$lancfg['if'] = $g['hwplatformconfig'][INTERFACES][LAN];

	/* wireless configuration? */
	if (preg_match($g['wireless_regex'], $lancfg['if']))	
		interfaces_wireless_configure($lancfg['if'], $lancfg['wireless']);
	
	/* MAC spoofing? */
	if ($lancfg['spoofmac'])
		mwexec("/sbin/ifconfig " . escapeshellarg($lancfg['if']) . 
			" link " . escapeshellarg($lancfg['spoofmac']));
	
	/* media */
	if ($lancfg['media'] || $lancfg['mediaopt']) {
		$cmd = "/sbin/ifconfig " . escapeshellarg($lancfg['if']);
		if ($lancfg['media'])
			$cmd .= " media " . escapeshellarg($lancfg['media']);
		if ($lancfg['mediaopt'])
			$cmd .= " mediaopt " . escapeshellarg($lancfg['mediaopt']);
		mwexec($cmd);
	}
	
	$addflags = ' -inet6 description "LAN"';
	if (strpos($lancfg['if'], "fxp") !== false)
		$addflags .= " link0";
	
        	$lanif = $lancfg['if'];
	
	mwexec("/sbin/ifconfig " . $lanif . " " . escapeshellarg($lancfg['ipaddr'] . "/" . $lancfg['subnet']) . $addflags);
	/* if aliases are defined bring them up */
       	for($i=0;$i<sizeof($lancfg['aliaslist']); $i++ ) { 
       		$alias = "alias" . "$i";
        	mwexec("/sbin/ifconfig " . $lanif . " alias " . escapeshellarg($lancfg['aliaslist'][$alias]) . " netmask 255.255.255.255");
       	}

	/* Check for any old aliases or ip's that are on the interface and delete them if not configured */
        foreach (get_current_ip_addresses($lanif) as $ip) {
            if ($ip == $lancfg['ipaddr']) {
                continue;
            } elseif (in_array($ip, $lancfg['aliaslist'])) {
                continue;
            } else {
                mwexec("/usr/bin/logger -p local0.info -t webui removing address: $ip from $lanif");
                mwexec("/sbin/ifconfig " . $lanif . " delete " . $ip);
            }
        }
		
	if (!$g['booting']) {
		
		/* make new hosts file */
		system_hosts_generate();
		
		/* reconfigure static routes (kernel may have deleted them) */
		system_routing_configure();
		
		/* reload ipfilter (address may have changed) */
		filter_configure();
		
		/* reload IPsec tunnels */
		vpn_ipsec_configure();
		
		/* reload dhcpd (gateway may have changed) */
		services_dhcpd_configure();
		
		/* reload dnsmasq */
		services_dnsmasq_configure();
		
		/* reload webgui */
		system_webgui_start();
		
	}
	
	if ($g['booting'])
		echo "done\n";
	
	return 0;
}

function interfaces_optional_configure() {
	global $config, $g;

        system_determine_hwplatform();

        $ifcount = sizeof($g['hwplatformconfig'][INTERFACES]) - 2;
 
	for ($i = 1; $i<=$ifcount; $i++) {
		interfaces_optional_configure_if($i);
	}
	
	if (!$g['booting']) {
		/* reconfigure static routes (kernel may have deleted them) */
		system_routing_configure();
		
		/* reload ipfilter (address may have changed) */
		filter_configure();
		
		/* reload IPsec tunnels */
		vpn_ipsec_configure();
		
		/* reload dhcpd (interface enabled/disabled/bridged status may have changed) */
		services_dhcpd_configure();
		
		/* restart dnsmasq */
		services_dnsmasq_configure();
	}
	
	return 0;
}

function interfaces_optional_configure_if($opti) {
	global $config, $g;
	global $bridgeconfig;
	
    system_determine_hwplatform();

    $optcfg = $config['interfaces']['opt' . $opti];
    $optcfg['if'] = $g['hwplatformconfig'][INTERFACES]['OPT' . $opti]['IF'];
	
	if (isset($optcfg['enable'])) {

            if ($g['booting']) {
		$optdescr = "";
		if ($optcfg['descr'])
			$optdescr = "{$optcfg['descr']}";
		echo "Configuring {$optdescr} interface... ";
	    }

		/* wireless configuration? */
		if (preg_match($g['wireless_regex'], $optcfg['if']))
			interfaces_wireless_configure($optcfg['if'], $optcfg['wireless']);
		
		/* MAC spoofing? */
		if ($optcfg['spoofmac'])
			mwexec("/sbin/ifconfig " . escapeshellarg($optcfg['if']) . 
				" link " . escapeshellarg($optcfg['spoofmac']));
		
		/* media */
		if ($optcfg['media'] || $optcfg['mediaopt']) {
			$cmd = "/sbin/ifconfig " . escapeshellarg($optcfg['if']);
			if ($optcfg['media'])
				$cmd .= " media " . escapeshellarg($optcfg['media']);
			if ($optcfg['mediaopt'])
				$cmd .= " mediaopt " . escapeshellarg($optcfg['mediaopt']);
			mwexec($cmd);
		}
	
		$addflags = " -inet6 description " . escapeshellarg($optcfg['descr']);
		$optif = $optcfg['if'];
	
		if ($optcfg['wireless']['ifmode'] == 'lanbridge' || $optcfg['wireless']['ifmode'] == 'dmzbridge') {
			if ($optcfg['wireless']['ifmode'] == 'lanbridge') {
				mwexec("/sbin/ifconfig ral0 up" . $addflags);	
				mwexec("/sbin/ifconfig bridge0 create up");
				mwexec("/sbin/brconfig bridge0 add " . $optif . " add " . $config['interfaces']['lan']['if']);
			}
			if ($optcfg['wireless']['ifmode'] == 'dmzbridge') {
      				mwexec("/sbin/ifconfig ral0 up"); 
	                        mwexec("/sbin/ifconfig bridge0 create up");
                        	mwexec("/sbin/brconfig bridge0 add " . $optif . " add " . $config['interfaces']['opt1']['if']);
			}
			if ($g['booting'])
                        	echo "done\n";
			return 0;
		}

		mwexec("/sbin/ifconfig " . $optif . " " . 
		escapeshellarg($optcfg['ipaddr'] . "/" . $optcfg['subnet']) . $addflags);
		
		/* if aliases are defined bring them up */
       	for($i=0;$i<sizeof($optcfg['aliaslist']); $i++ ) {     
        	$alias = "alias" . "$i";
        	mwexec("/sbin/ifconfig " . $optif . " alias " . 
        	escapeshellarg($optcfg['aliaslist'][$alias]) . " netmask 255.255.255.255");
       	}	
		
	/* Check for any old aliases or ip's that are on the interface and delete them if not configured */
        foreach (get_current_ip_addresses($optif) as $ip) {
            if ($ip == $optcfg['ipaddr']) {
                continue;
            } elseif (in_array($ip, $optcfg['aliaslist'])) {
                    continue;
            } else {
                mwexec("/usr/bin/logger -p local0.info -t webui removing address: $ip from $optif");
                mwexec("/sbin/ifconfig " . $optif . " delete " . $ip);
            }

        }

        if ($g['booting'])
        echo "done\n";
        
	} else {
		mwexec("/sbin/ifconfig " . escapeshellarg($optcfg['if']) . 
			" delete down");
	}

        
	
	return 0;
}

function interfaces_pfsync_configure() {
	global $config, $g;
	
	if(isset($config['pfsync']['pfsyncenable'])) {
	
		if ($g['booting']) {
        	        echo "Configuring PFSync interface... ";
        	}

		/*bring up the pfsync interface*/
		mwexec("/sbin/ifconfig " . $config['interfaces'][$config['pfsync']['interface']]['if'] . " up");
		mwexec("/sbin/ifconfig pfsync0 up syncdev " . $config['interfaces'][$config['pfsync']['interface']]['if'] . " up");
		
		if ($g['booting'])
                	echo "done\n";
		return 0;
	}
}

function interfaces_wireless_configure($if, $wlcfg) {
	global $config, $g;
	
	/* wireless configuration */
	$ifcargs = escapeshellarg($if) . 
		" nwid " . escapeshellarg($wlcfg['ssid']) . " chan " . 
		escapeshellarg($wlcfg['channel']) . " ";
	
	
        $ifcargs .= "media OFDM54 mediaopt hostap ";

	 if ($wlcfg['encmode'] == 'wep') {	
		$wepargs = "nwkey ";
		
		$i = 1;
		foreach ($wlcfg['wep']['key'] as $wepkey) {
			if (isset($wepkey['txkey'])) {
				$wepargs .= "n:";
			}
                        $wepargs .= escapeshellarg("{$wepkey['value']}") . ",";
			$i++;
		}
                $ifcargs .= preg_replace('/,$/',' ',$wepargs);
	} else {
		$ifcargs .= "-nwkey ";
	}

	if ($wlcfg['encmode'] == 'wpa') {
                $wpaargs = "wpa ";
		$wpassid = escapeshellarg($wlcfg['ssid']);
                
		switch ($wlcfg['wpamode']) {
			case 'Auto':
                                $wpaargs .= "wpaprotos wpa1,wpa2 ";
                        	break;
                	case 'WPA-1':
				$wpaargs .= "wpaprotos wpa1 ";
                                break;
			case 'WPA-2':
                                $wpaargs .= "wpaprotos wpa2 ";
                                break;
		}
		
		switch ($wlcfg['wpacipher']) {
                        case 'Auto':
                                $wpaargs .= "wpaciphers tkip,ccmp ";
                                break;
                        case 'TKIP':
                                $wpaargs .= "wpaciphers tkip ";
                                break;
                        case 'CCMP':
                                $wpaargs .= "wpaciphers ccmp ";
                                break;
                }

		$wpapsk = escapeshellarg($wlcfg['wpapsk']);
		$wpapsk = `/sbin/wpa-psk $wpassid $wpapsk`;
	        
		$wpaargs .= "wpapsk $wpapsk ";
		$ifcargs .= $wpaargs;
        
	} else {
                $ifcargs .= "-wpa -wpapsk ";
        }
	
	$ifcargs .= "-inet6 up";

	mwexec("/sbin/ifconfig " . $ifcargs);
	
	return 0;
}

function interfaces_wan_configure() {
	global $config, $g;

	system_determine_hwplatform();

	$wancfg = $config['interfaces']['wan'];
	$wancfg['if'] = $g['hwplatformconfig'][INTERFACES][WAN];

	if ($g['booting'])
		echo "Configuring WAN interface... ";
	else {
		/* kill dhclient */
		killbypid("{$g['varrun_path']}/dhclient.pid");
		
		/* kill PPPoE client (mpd) */
		killbypid("{$g['varrun_path']}/mpd.pid");
		
		/* wait for processes to die */
		sleep(2);
		
		unlink_if_exists("{$g['varetc_path']}/dhclient.conf");
		unlink_if_exists("{$g['varetc_path']}/mpd.conf");
		unlink_if_exists("{$g['varetc_path']}/mpd.links");
		unlink_if_exists("{$g['vardb_path']}/wanip");
		unlink_if_exists("{$g['varetc_path']}/nameservers.conf");
	}
	
	/* remove all addresses first */
	while (mwexec("/sbin/ifconfig " . escapeshellarg($wancfg['if']) . " -alias") == 0);
	mwexec("/sbin/ifconfig " . escapeshellarg($wancfg['if']) . " down");
		
	/* wireless configuration? */
	if (preg_match($g['wireless_regex'], $wancfg['if']))
		interfaces_wireless_configure($wancfg['if'], $wancfg['wireless']);
	
	if ($wancfg['spoofmac'])
		mwexec("/sbin/ifconfig " . escapeshellarg($wancfg['if']) . 
			" link " . escapeshellarg($wancfg['spoofmac']));
		
	/* media */
	if ($wancfg['media'] || $wancfg['mediaopt']) {
		$cmd = "/sbin/ifconfig " . escapeshellarg($wancfg['if']);
		if ($wancfg['media'])
			$cmd .= " media " . escapeshellarg($wancfg['media']);
		if ($wancfg['mediaopt'])
			$cmd .= " mediaopt " . escapeshellarg($wancfg['mediaopt']);
		mwexec($cmd);
	}
	
        $addflags = ' -inet6 description "WAN"';

	if (strpos($wancfg['if'], "fxp") !== false)
		$addflags .= " link0";

	switch ($wancfg['ipaddr']) {
	
		case 'dhcp':
			interfaces_wan_dhcp_configure();
			break;
			
		case 'pppoe':
			interfaces_wan_pppoe_configure();
			break;
			
		case 'pptp':
			interfaces_wan_pptp_configure();
			break;
			
		case 'bigpond':
			/* just configure DHCP for now; fire up bpalogin when we've got the lease */
			interfaces_wan_dhcp_configure();
			break;
			
		default:
                 	
			$wanif = $wancfg['if'];
                        
			
			if (isset($wancfg['ispointtopoint']) && $wancfg['pointtopoint']) {
				mwexec("/sbin/ifconfig " . escapeshellarg($wancfg['if']) . " " . 
					escapeshellarg($wancfg['ipaddr'] . "/" . $wancfg['subnet']) . 
					" " . escapeshellarg($wancfg['pointtopoint']) . $addflags . " up");
			} else {
				mwexec("/sbin/ifconfig " . $wanif . " " . 
					escapeshellarg($wancfg['ipaddr'] . "/" . $wancfg['subnet']) . $addflags);
			}
			/* if aliases are defined bring them up */
        		for($i=0;$i<sizeof($wancfg['aliaslist']); $i++ ) {                             
                		$alias = "alias" . "$i";
				mwexec("/sbin/ifconfig " . $wanif . " alias " . escapeshellarg($wancfg['aliaslist'][$alias]) . " netmask 255.255.255.255");
			}
	
			/* Check for any old aliases or ip's that are on the interface and delete them if not configured */
        foreach (get_current_ip_addresses($wanif) as $ip) {
            if ($ip == $wancfg['ipaddr']) {
                continue;
            } elseif (is_array($wancfg['aliaslist'])) {
                if (in_array($ip, $wancfg['aliaslist'])) {
                    continue;
                }
            } else {
                mwexec("/usr/bin/logger -p local0.info -t webui removing address: $ip from $wanif");
                mwexec("/sbin/ifconfig " . $wanif . " delete " . $ip);
            }
        }
	

			/* install default route */
			mwexec("/sbin/route delete default");
			mwexec("/sbin/route add default " . escapeshellarg($wancfg['gateway']));
			
	}
	
	if (!$g['booting']) {
		/* reconfigure static routes (kernel may have deleted them) */
		system_routing_configure();
		
		/* reload ipfilter */
		filter_configure();
		
		/* reload ipsec tunnels */
		vpn_ipsec_configure();
		
		/* restart ez-ipupdate */
		services_dyndns_configure();
		
		/* force DNS update */
		services_dnsupdate_process();
		
		/* restart dnsmasq */
		services_dnsmasq_configure();
	}
	
	if ($g['booting'])
		echo "done\n";
	
	return 0;
}

function interfaces_wan_dhcp_configure() {
	global $config, $g;

	system_determine_hwplatform();

	$wancfg = $config['interfaces']['wan'];
	$wancfg['if'] = $g['hwplatformconfig'][INTERFACES][WAN];

	/* generate dhclient.conf */
	$fd = fopen("{$g['varetc_path']}/dhclient.conf", "w");
	if (!$fd) {
		printf("Error: cannot open dhclient.conf in interfaces_wan_dhcp_configure().\n");
		return 1;
	}
 
 	$dhclientconf = "";
 
 	if ($wancfg['dhcphostname']) {
		$dhclientconf .= <<<EOD
send dhcp-client-identifier "{$wancfg['dhcphostname']}";
interface "{$wancfg['if']}" {
	send host-name "{$wancfg['dhcphostname']}";
}

EOD;
	}

	fwrite($fd, $dhclientconf);
	fclose($fd);

	/* if its a trunk prepare the children */
        if(strpos($wancfg['if'], "trunk") !== false) {
	        $wanif = $wancfg['if'] . ' trunkproto ';
        	foreach($config['trunks']['trunk'] as $trunkcfg) {
        		if($trunkcfg['trunkport'] == $wancfg['if']) {
        			$wanif .= $trunkcfg['type'];
        			for($i=0;$i<sizeof($trunkcfg['childiflist']); $i++ ) {
        				$child = "childif" . "$i";
        				$wanif .= " trunkport {$trunkcfg['childiflist'][$child]}";
        				mwexec("/sbin/ifconfig {$trunkcfg['childiflist'][$child]} up");
        			}
        		}
        	}
        }
        else {
        	$wanif = escapeshellarg($wancfg['if']);
        }


	$addflags = ' -inet6 description "WAN"';  
        mwexec("/sbin/ifconfig " . $wanif); 

	/* fire up dhclient - don't wait for the lease (-n) */
	mwexec("/sbin/dhclient -qc {$g['varetc_path']}/dhclient.conf " . $wanif);
system("/usr/bin/pgrep -o dhclient > {$g['varrun_path']}/dhclient.pid");

	mwexec("/sbin/ifconfig " . $wanif . $addflags);
	
	return 0;
}

function interfaces_wan_dhcp_down() {
 	/* kill dhclient */
	global $config, $g;
	mwexec("pkill dhclient");
	unlink("{$g['varrun_path']}/dhclient.pid");
	
	sleep(3);
}

function interfaces_wan_dhcp_up() {
	interfaces_wan_dhcp_configure();

	sleep(3);
}

function interfaces_wan_pppoe_configure() {
	global $config, $g;
	
	$wancfg = $config['interfaces']['wan'];
	$pppoecfg = $config['pppoe'];
	
	/* generate mpd.conf */
	$fd = fopen("{$g['varetc_path']}/mpd.conf", "w");
	if (!$fd) {
		printf("Error: cannot open mpd.conf in interfaces_wan_pppoe_configure().\n");
		return 1;
	}
	
	$idle = 0;
	
	if (isset($pppoecfg['ondemand'])) {
		$ondemand = "enable";
		if ($pppoecfg['timeout'])
			$idle = $pppoecfg['timeout'];
	} else {
		$ondemand = "disable";
	}
	
	$mpdconf = <<<EOD
pppoe:
	new -i ng0 pppoe pppoe
	set iface route default
	set iface {$ondemand} on-demand
	set iface idle {$idle}
	set iface up-script /usr/local/sbin/ppp-linkup

EOD;
	
	if (isset($pppoecfg['ondemand'])) {
		if (isset($pppoecfg['local-ip']) && isset($pppoecfg['remote-ip'])) {
			$mpdconf .= <<<EOD
	set iface addrs {$pppoecfg['local-ip']} {$pppoecfg['remote-ip']}

EOD;
		} else {
			$mpdconf .= <<<EOD
	set iface addrs 192.0.2.112 192.0.2.113

EOD;
		}
	}
	
	$mpdconf .= <<<EOD
	set bundle disable multilink
	set bundle authname "{$pppoecfg['username']}"
	set bundle password "{$pppoecfg['password']}"
	set link keep-alive 10 60
	set link max-redial 0
	set link no acfcomp protocomp
	set link disable pap chap
	set link accept chap
	set link mtu 1492
	set ipcp yes vjcomp
	set ipcp ranges 0.0.0.0/0 0.0.0.0/0

EOD;

	if (isset($config['system']['general']['dnsallowoverride'])) {
		$mpdconf .= <<<EOD
	set ipcp enable req-pri-dns

EOD;
		if (!isset($config['pppoe']['dnsnosec'])) {
			$mpdconf .= <<<EOD
	set ipcp enable req-sec-dns

EOD;
		}
	}
	
	$mpdconf .= <<<EOD
	open iface

EOD;

	fwrite($fd, $mpdconf);
	fclose($fd);
	
	/* generate mpd.links */
	$fd = fopen("{$g['varetc_path']}/mpd.links", "w");
	if (!$fd) {
		printf("Error: cannot open mpd.links in interfaces_wan_pppoe_configure().\n");
		return 1;
	}
		
	$mpdconf = <<<EOD
pppoe:
	set link type pppoe
	set pppoe iface {$wancfg['if']}
	set pppoe service "{$pppoecfg['provider']}"
	set pppoe enable originate
	set pppoe disable incoming

EOD;

	fwrite($fd, $mpdconf);
	fclose($fd);
	
	/* fire up mpd */
	mwexec("/usr/local/sbin/mpd -b -d {$g['varetc_path']} -p {$g['varrun_path']}/mpd.pid pppoe");
	
	return 0;
}

function interfaces_wan_pppoe_down() {
	global $g;
	sigkillbypid("{$g['varrun_path']}/mpd.pid", "SIGUSR2");

	sleep(3);
}

function interfaces_wan_pppoe_up() {
	global $g;
	sigkillbypid("{$g['varrun_path']}/mpd.pid", "SIGUSR1");

	sleep(3);
}

function interfaces_wan_pptp_configure() {
	global $config, $g;
	
	$wancfg = $config['interfaces']['wan'];
	$pptpcfg = $config['pptp'];
	
	/* generate mpd.conf */
	$fd = fopen("{$g['varetc_path']}/mpd.conf", "w");
	if (!$fd) {
		printf("Error: cannot open mpd.conf in interfaces_wan_pptp_configure().\n");
		return 1;
	}
	
	$idle = 0;
	
	if (isset($pptpcfg['ondemand'])) {
		$ondemand = "enable";
		if ($pptpcfg['timeout'])
			$idle = $pptpcfg['timeout'];
	} else {
		$ondemand = "disable";
	}
		
	$mpdconf = <<<EOD
pptp:
	new -i ng0 pptp pptp
	set iface route default
	set iface {$ondemand} on-demand
	set iface idle {$idle}
	set iface up-script /usr/local/sbin/ppp-linkup

EOD;
	
	if (isset($pptpcfg['ondemand'])) {
		$mpdconf .= <<<EOD
	set iface addrs 10.0.0.1 10.0.0.2

EOD;
	}
	
	$mpdconf .= <<<EOD
	set bundle disable multilink
	set bundle authname "{$pptpcfg['username']}"
	set bundle password "{$pptpcfg['password']}"
	set link keep-alive 10 60
	set link max-redial 0
	set link no acfcomp protocomp
	set link disable pap chap
	set link accept chap
	set ipcp no vjcomp
	set ipcp ranges 0.0.0.0/0 0.0.0.0/0

EOD;
	
	if (isset($config['system']['general']['dnsallowoverride'])) {
		$mpdconf .= <<<EOD
	set ipcp enable req-pri-dns
	set ipcp enable req-sec-dns

EOD;
	}
	
	$mpdconf .= <<<EOD
	open

EOD;

	fwrite($fd, $mpdconf);
	fclose($fd);
	
	/* generate mpd.links */
	$fd = fopen("{$g['varetc_path']}/mpd.links", "w");
	if (!$fd) {
		printf("Error: cannot open mpd.links in interfaces_wan_pptp_configure().\n");
		return 1;
	}
		
	$mpdconf = <<<EOD
pptp:
	set link type pptp
	set pptp enable originate outcall
	set pptp disable windowing
	set pptp self {$pptpcfg['local']}
	set pptp peer {$pptpcfg['remote']}

EOD;

	fwrite($fd, $mpdconf);
	fclose($fd);
	
	/* configure interface */
	mwexec("/sbin/ifconfig " . escapeshellarg($wancfg['if']) . " " . 
		escapeshellarg($pptpcfg['local'] . "/" . $pptpcfg['subnet']));
	
	/* fire up mpd */
	mwexec("/usr/local/sbin/mpd -b -d {$g['varetc_path']} -p {$g['varrun_path']}/mpd.pid pptp");
	
	return 0;
}

function interfaces_wan_pptp_down() {
	global $g;
	sigkillbypid("{$g['varrun_path']}/mpd.pid", "SIGUSR2");

	sleep(3);
}

function interfaces_wan_pptp_up() {
	global $g;
	sigkillbypid("{$g['varrun_path']}/mpd.pid", "SIGUSR1");

	sleep(3);
}

function interfaces_wan_bigpond_configure($curwanip) {
	global $config, $g;
	
	$bpcfg = $config['bigpond'];
	
	if (!$curwanip) {
		/* IP address not configured yet, exit */
		return 0;
	}
		
	/* kill bpalogin */
	killbyname("bpalogin");
	
	/* wait a moment */
	sleep(1);
	
	/* get the default domain */
	$nfd = @fopen("{$g['varetc_path']}/defaultdomain.conf", "r");
	if ($nfd) {
		$defaultdomain = trim(fgets($nfd));
		fclose($nfd);
	}
	
	/* generate bpalogin.conf */
	$fd = fopen("{$g['varetc_path']}/bpalogin.conf", "w");
	if (!$fd) {
		printf("Error: cannot open bpalogin.conf in interfaces_wan_bigpond_configure().\n");
		return 1;
	}
	
	if (!$bpcfg['authserver'])
		$bpcfg['authserver'] = "dce-server";
	if (!$bpcfg['authdomain'])
		$bpcfg['authdomain'] = $defaultdomain;
		
	$bpconf = <<<EOD
username {$bpcfg['username']}
password {$bpcfg['password']}
authserver {$bpcfg['authserver']}
authdomain {$bpcfg['authdomain']}
localport 5050

EOD;

	if ($bpcfg['minheartbeatinterval'])
		$bpconf .= "minheartbeatinterval {$bpcfg['minheartbeatinterval']}\n";

	fwrite($fd, $bpconf);
	fclose($fd);
	
	/* fire up bpalogin */
	mwexec("/usr/local/sbin/bpalogin -c {$g['varetc_path']}/bpalogin.conf");
	
	return 0;
}

function get_real_wan_interface() {
	global $config, $g;

	system_determine_hwplatform();

	$wancfg = $config['interfaces']['wan'];
 	$wancfg['if'] = $g['hwplatformconfig'][INTERFACES][WAN];
	$wanif = $wancfg['if'];
	if (($wancfg['ipaddr'] == "pppoe") || ($wancfg['ipaddr'] == "pptp")) {
		$wanif = $g['pppoe_interface'];
	}
	
	return $wanif;
}

function get_current_wan_address() {
	global $config, $g;
	
	$wancfg = $config['interfaces']['wan'];
	
	if (in_array($wancfg['ipaddr'], array('pppoe','dhcp','pptp','bigpond'))) {
		/* dynamic WAN IP address, find out which one */
		$wanif = get_real_wan_interface();
		
		/* get interface info with netstat */
		exec("/usr/bin/netstat -nI " . escapeshellarg($wanif) . " -f inet", $ifinfo);
		
		if (isset($ifinfo[2])) {
			$aif = preg_split("/\s+/", $ifinfo[2]);
			$curwanip = chop($aif[3]);
			
			if ($curwanip && is_ipaddr($curwanip) && ($curwanip != "0.0.0.0"))
				return $curwanip;
		}
		
		return null;
	} else {
		/* static WAN IP address */
		return $wancfg['ipaddr'];
	}
}

function get_current_default_gateway() {
	/* get default gateway with netstat */
        exec("/usr/bin/netstat -rn -f inet", $routeinfo);

        if (isset($routeinfo[4])) {
               $dgw = preg_split("/\s+/", $routeinfo[4]);
               $dgw = chop($dgw[1]);
               return $dgw;
        }
        return null;
}

function get_current_ip_address($iface) {
        global $config, $g;

        $ifcfg = $config['interfaces'][$iface];

        if (in_array($wancfg['ipaddr'], array('pppoe','dhcp','pptp','bigpond'))) {
                /* get interface info with netstat */
                exec("/usr/bin/netstat -nI " . escapeshellarg($iface) . " -f inet", $ifinfo);

                if (isset($ifinfo[3])) {
                        $aif = preg_split("/\s+/", $ifinfo[3]);
                        $curwanip = chop($aif[3]);

                        if ($curwanip && is_ipaddr($curwanip) && ($curwanip != "0.0.0.0"))
                                return $curwanip;
                }

                return null;
        } else {
                /* static WAN IP address */
                return $ifcfg['ipaddr'];
        }
}

function get_current_ip_addresses($iface) {
        global $config, $g;

        exec("/usr/bin/netstat -nI " . escapeshellarg($iface) . " -f inet", $ifinfo);

        for ($i=2; isset($ifinfo[$i]); $i++) {
            $aif = preg_split("/\s+/", $ifinfo[$i]);
            $ips[] = chop($aif[3]);
        }
        /* Return array of ips on the interface*/
        return  $ips;
}

function get_interface_mac($interface) {
        /* build interface list with netstat */
        exec("/usr/bin/netstat -I $interface -n -f inet", $linkinfo);
        array_shift($linkinfo);
        $alink = preg_split("/\s+/", $linkinfo[0]);
        $mac = chop($alink[3]);
        return $mac;
}

function get_interface_gw($interface) {
        global $config, $g;
	foreach($config['interfaces'] as $if) {
		if($if['if'] == $interface) {
			$gw = $if['gateway'];
			break;
		}
	}
	return $gw;
}

function get_interface_carpnodes($id, $nodes) {
        $nodeval = number_format(254 / $nodes);
        $askdev = 0;
        $rtrnstr = "carpnodes ";
        for($i=$id; $i<=$nodes; $i++) {
                $rtrnstr .= $i . ":$askdev,"; 
                $askdev += $nodeval;
        }   
        for($i=1; $i<$id; $i++) {
                $rtrnstr .= $i . ":$askdev,"; 
                $askdev += $nodeval;
        }   
        return preg_replace('/,$/','',$rtrnstr);
}

?>
