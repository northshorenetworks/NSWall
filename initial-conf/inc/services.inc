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

function services_dhcpd_configure() {
    global $config, $g;

    if ($g['booting'])
        echo "Starting DHCPD service... ";

    services_dhcpd_configure_rtable('DEFAULT');
    
    if(is_array($config['system']['routetables']['routetable'])) {    
        foreach ($config['system']['routetables']['routetable'] as $rtable) {
            services_dhcpd_configure_rtable($rtable['name']);
        }
    }

    if (!$g['booting']) {
        filter_configure();
    } else {
        echo "done\n";
    }
}

function services_dhcpd_configure_rtable($rtable) {
    global $config, $g;
   
    mwexec('/usr/bin/pkill -f \'dhcpd -c /etc/' . $rtable . '_dhcpd.conf\'');
    $rtableid = get_route_table_id_by_name($rtable);

    $dhcpdenable = false;
    $dhcpdifs = '';

    foreach ($config['vlans']['vlan'] as $ifcfg) {

        # if the rtable isn't set on an interface we set it to DEFAULT;
        if (!isset($ifcfg['rtable']))
            $ifcfg['rtable'] = 'DEFAULT';

        # check to see if this interface uses this route table
        if ($ifcfg['rtable'] != $rtable)
            continue;

        # check to see if dhcpd is enabled on this interface
        if (isset($ifcfg['dhcpd']['enable'])) {
            $dhcpdenable = 'true';
        } else {
            continue;
        }

        # if it is add it to the list of interfaces
        $dhcpdifs .= " " . get_interface_name_by_descr($ifcfg['descr']);
        $dhcpdsubnets .= services_generate_dhcpd_subnet($ifcfg);
    }

    if ($dhcpdenable != 'true')
        return;
    
    /* write dhcpd.conf */
    $fd = fopen("{$g['varetc_path']}/$rtable" . "_dhcpd.conf", "w");
    if (!$fd) {
        printf("Error: cannot open $rtable" . "_dhcpd.conf in services_dhcpd_configure().\n");
        return 1;
    }

    $dhcpdconf = <<<EOD
option domain-name "{$config['system']['domain']}";
default-lease-time 7200;
max-lease-time 86400;
authoritative;

EOD;

    fwrite($fd, $dhcpdconf . $dhcpdsubnets);
    fclose($fd);
        
    /* create an empty leases database */
    touch("{$g['vardb_path']}/$rtable" . "_dhcpd.leases");

    /* fire up dhcpd, setup syncing if its enabled */
    if (is_array($config['carp']['configsynchost'])) {
                $synchoststring = '';
        foreach($config['carp']['configsynchost'] as $synchost) {   
            $synchoststring .= "-Y {$synchost['ip']} ";
        }           
        $synchoststring = preg_replace('/ $/', '', $synchoststring);    
        mwexec("/usr/sbin/dhcpd -y " . get_interface_name_by_descr($config['pfsync']['interface']) .
            " $synchoststring -c {$g['varetc_path']}/dhcpd.conf " . $dhcpdinterfaces);
    } else {
        mwexec("/sbin/route -T " . $rtableid . " exec /usr/sbin/dhcpd -c {$g['varetc_path']}/$rtable" . "_dhcpd.conf " . $dhcpdifs);
    }   
    
    return 0;
}

function services_generate_dhcpd_subnet($ifcfg) {
        
        $dhcpifconf = $ifcfg['dhcpd'];
    
        if (!isset($dhcpifconf['enable']))
            return;
            
        $subnet = gen_subnet($ifcfg['ipaddr'], $ifcfg['subnet']);
        $subnetmask = gen_subnet_mask($ifcfg['subnet']);
        
        $dnscfg = "";
        
        if ($dhcpifconf['domain']) {
            $dnscfg .= "    option domain-name \"{$dhcpifconf['domain']}\";\n";
        }
        
        if (is_array($dhcpifconf['dnsserver']) && ($dhcpifconf['dnsserver'][0])) {
            $dnscfg .= "    option domain-name-servers " . join(",", $dhcpifconf['dnsserver']) . ";";
        } else if (isset($config['dnsmasq']['enable'])) {
            $dnscfg .= "    option domain-name-servers " . $ifcfg['ipaddr'] . ";";
        } else if (is_array($syscfg['dnsserver']) && ($syscfg['dnsserver'][0])) {
            $dnscfg .= "    option domain-name-servers " . join(",", $syscfg['dnsserver']) . ";";
        }

        $dhcpdconf .= "subnet $subnet netmask $subnetmask {\n";
        if (isset($dhcpifconf['denyunknown'])) 
           $dhcpdconf .= "      deny unknown clients;\n";
        
        if ($dhcpifconf['gateway'])
            $routers = $dhcpifconf['gateway'];
        else
            $routers = $ifcfg['ipaddr'];
        
        $dhcpdconf .= <<<EOD
    range {$dhcpifconf['range']['from']} {$dhcpifconf['range']['to']};
    option routers {$routers};
$dnscfg

EOD;

        if ($dhcpifconf['defaultleasetime'])
            $dhcpdconf .= " default-lease-time {$dhcpifconf['defaultleasetime']};\n";
        if ($dhcpifconf['maxleasetime'])
            $dhcpdconf .= " max-lease-time {$dhcpifconf['maxleasetime']};\n";
            
        if (is_array($dhcpifconf['winsserver']) && $dhcpifconf['winsserver'][0]) {
            $dhcpdconf .= " option netbios-name-servers " . join(",", $dhcpifconf['winsserver']) . ";\n";
            $dhcpdconf .= " option netbios-node-type 8;\n";
        }
        
        if ($dhcpifconf['next-server'])
            $dhcpdconf .= " next-server {$dhcpifconf['next-server']};\n";
        if ($dhcpifconf['filename'])
            $dhcpdconf .= " filename \"{$dhcpifconf['filename']}\";\n";
            
        $dhcpdconf .= <<<EOD
}

EOD;

        /* add static mappings */
        if (is_array($dhcpifconf['staticmap'])) {
        
            $i = 0;
            foreach ($dhcpifconf['staticmap'] as $sm) {
                $dhcpdconf .= <<<EOD
host s_{$dhcpif}_{$i} {
    hardware ethernet {$sm['mac']};

EOD;
                if ($sm['ipaddr'])
                    $dhcpdconf .= " fixed-address {$sm['ipaddr']};\n";
                
                $dhcpdconf .= "}\n";
                $i++;
            }
        }
        
        return $dhcpdconf;
}

function services_dhcrelay_configure() {
    global $config, $g;
    
    /* kill any running dhcrelay */
    killbypid("{$g['varrun_path']}/dhcrelay.pid");
    
    $dhcrelaycfg = $config['dhcrelay'];
    
    /* DHCPRelay enabled on any interfaces? */
    $dhcrelayenable = false;
    
    if (is_array($dhcrelaycfg)) {
        foreach ($dhcrelaycfg as $dhcrelayif => $dhcrelayifconf) {
            if (isset($dhcrelayifconf['enable']) &&
                (($dhcrelayif == "lan") ||
                (isset($config['interfaces'][$dhcrelayif]['enable']) &&
                $config['interfaces'][$dhcrelayif]['if'] && (!$config['interfaces'][$dhcrelayif]['bridge'])))) {
                
                $dhcrelayenable = true;
                break;
            }
        }
    }
    
    if (!$dhcrelayenable)
        return 0;
    
    if ($g['booting'])
        echo "Starting DHCP relay service... ";
    else
        sleep(1);
    
    $dhcrelayifs = array();
    foreach ($dhcrelaycfg as $dhcrelayif => $dhcrelayifconf) {
        
        $ifcfg = $config['interfaces'][$dhcrelayif];
        
        if (!isset($dhcrelayifconf['enable']) ||
            (($dhcrelayif != "lan") &&
            (!isset($ifcfg['enable']) || !$ifcfg['if'] || $ifcfg['bridge'])))
            continue;
            
        $dhcrelayifs[] = $ifcfg['if'];
    }

    /* In order for the relay to work, it needs to be active on the 
       interface in which the destination server sits */
    foreach ($config['interfaces'] as $ifname) {
        $subnet = $ifname['ipaddr'] . "/" . $ifname['subnet'];
        if (ip_in_subnet($dhcrelaycfg['server'],$subnet)) 
            $destif = $ifname['if'];    
    }
    
    if (!isset($destif)) 
        $destif = $config['interfaces']['wan']['if'];
    
    $dhcrelayifs[] = $destif;
    $dhcrelayifs = array_unique($dhcrelayifs);

    /* fire up dhcrelay */
    $cmd = "/usr/local/sbin/dhcrelay -i " .  join(" -i ", $dhcrelayifs);

    if (isset($dhcrelaycfg['agentoption'])) 
        $cmd .=  " -a -m replace";

    $cmd .= " {$dhcrelaycfg['server']}";
    mwexec($cmd);
        
    if (!$g['booting']) {
        filter_configure();
    } else
        echo "done\n";
    
    return 0;
}

function services_dyndns_reset() {
    global $config, $g;

    if (file_exists("{$g['vardb_path']}/ez-ipupdate.cache")) {
        unlink("{$g['vardb_path']}/ez-ipupdate.cache");
    }   
    
    if (file_exists("{$g['conf_path']}/ez-ipupdate.cache")) {
        conf_mount_rw();
        unlink("{$g['conf_path']}/ez-ipupdate.cache");
        touch("{$g['conf_path']}/ez-ipupdate.cache");
        conf_mount_ro();
    }
    
    return 0;
}

function services_dyndns_configure() {
    global $config, $g;
    
    /* kill any running ez-ipupdate */
    /* ez-ipupdate needs SIGQUIT instead of SIGTERM */
    sigkillbypid("{$g['varrun_path']}/ez-ipupdate.pid", "QUIT");
    
    $dyndnscfg = $config['dyndns'];
    $wancfg = $config['interfaces']['wan'];
    
    if (isset($dyndnscfg['enable'])) {
    
        if ($g['booting'])
            echo "Starting DynDNS client... ";
        else
            sleep(1);
        
        /* determine WAN interface name */
        $wanif = get_real_wan_interface();
        
        /* write ez-ipupdate.conf */
        $fd = fopen("{$g['varetc_path']}/ez-ipupdate.conf", "w");
        if (!$fd) {
            printf("Error: cannot open ez-ipupdate.conf in services_dyndns_configure().\n");
            return 1;
        }
            
        $ezipupdateconf = <<<EOD
service-type={$dyndnscfg['type']}
user={$dyndnscfg['username']}:{$dyndnscfg['password']}
host={$dyndnscfg['host']}
interface=$wanif
max-interval=2073600
pid-file={$g['varrun_path']}/ez-ipupdate.pid
cache-file={$g['vardb_path']}/ez-ipupdate.cache
execute=/etc/rc.dyndns.storecache
daemon

EOD;

        /* enable server[:port]? */
        if ($dyndnscfg['server']) {
            if ($dyndnscfg['port'])
                $ezipupdateconf .= "server={$dyndnscfg['server']}:{$dyndnscfg['port']}\n";
            else
                $ezipupdateconf .= "server={$dyndnscfg['server']}\n";
        }

        /* enable MX? */
        if ($dyndnscfg['mx']) {
            $ezipupdateconf .= "mx={$dyndnscfg['mx']}\n";
        }
        
        /* enable wildcards? */
        if (isset($dyndnscfg['wildcard'])) {
            $ezipupdateconf .= "wildcard\n";
        }

        fwrite($fd, $ezipupdateconf);
        fclose($fd);
        
        /* if we're booting, copy the cache file from /conf */
        if ($g['booting']) {
            if (file_exists("{$g['conf_path']}/ez-ipupdate.cache")) {
                copy("{$g['conf_path']}/ez-ipupdate.cache", "{$g['vardb_path']}/ez-ipupdate.cache");
            }
        }
        
        /* run ez-ipupdate */
        mwexec("ez-ipupdate -c {$g['varetc_path']}/ez-ipupdate.conf");
        
        if ($g['booting'])
            echo "done\n";
    }
    
    return 0;
}

function services_dnsmasq_configure() {
    global $config, $g;

        system_determine_hwplatform();

    /* kill any running dnsmasq */
    mwexec("/usr/bin/pkill dnsmasq");

    if (isset($config['dnsmasq']['enable'])) {
    
        if ($g['booting'])
            echo "Starting DNS forwarder... ";
        else
            sleep(1);

        /* generate hosts file */
        system_hosts_generate();
        
        $args = "";
            
    
        if (isset($config['dnsmasq']['regdhcp'])) {
            $args .= " -l {$g['vardb_path']}/dhcpd.leases" . 
                " -s {$config['system']['general']['domain']}";
        }

        $args = " -c 5000 --except-interface " . get_interface_name_by_descr('wan');

        if (isset($config['dnsmasq']['domainoverrides']) && is_array($config['dnsmasq']['domainoverrides'])) {
            foreach($config['dnsmasq']['domainoverrides'] as $override) {
                $args .= " " . escapeshellarg('--server=/' . $override['domain'] . '/' . $override['ip']);
            }
        }

        /* run dnsmasq */
        mwexec("/usr/sbin/dnsmasq {$args}");

        if ($g['booting'])
            echo "done\n";
    }
    
    if (!$g['booting']) {
        services_dhcpd_configure();
    }

    return 0;
}

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
syslocation "{$config['snmpd']['syslocation']}"
syscontact "{$config['snmpd']['syscontact']}"
rocommunity "{$config['snmpd']['rocommunity']}"

EOD;

        if (isset($config['snmpd']['bindlan'])) {
            $snmpdconf .= "agentaddress udp:161@{$config['interfaces']['lan']['ipaddr']}\n";
        }

        fwrite($fd, $snmpdconf);
        fclose($fd);

        /* run snmpd */
        mwexec("/usr/local/sbin/snmpd -c {$g['varetc_path']}/snmpd.conf" .
            " -P {$g['varrun_path']}/snmpd.pid");

        if ($g['booting'])
            echo "done\n";
    }

    return 0;
}

function services_proxyarp_configure() {
    global $config, $g;

    /* kill any running choparp */
    killbyname("choparp");
    
    if (is_array($config['proxyarp']) && count($config['proxyarp'])) {
        
        $paa = array();
        
        /* group by interface */
        foreach ($config['proxyarp']['proxyarpnet'] as $paent) {
            if ($paent['interface'])
                $if = $paent['interface'];
            else
                $if = "wan";
            
            if (!is_array($paa[$if]))
                $paa[$if] = array();
            
            $paa[$if][] = $paent;
        } 
    
        foreach ($paa as $paif => $paents) {
            if ($paif == "wan" && !(is_ipaddr($config['interfaces']['wan']['ipaddr']) ||
                    ($config['interfaces']['wan']['ipaddr'] == "dhcp") ||
                    ($config['interfaces']['wan']['ipaddr'] == "bigpond")))
                continue;
            
            $args = $config['interfaces'][$paif]['if'] . " auto";
        
            foreach ($paents as $paent) {
                if (isset($paent['network']))
                    $args .= " " . escapeshellarg($paent['network']);
                else if (isset($paent['range']))
                    $args .= " " . escapeshellarg($paent['range']['from'] . "-" . 
                        $paent['range']['to']);
            }
            
            mwexec_bg("/usr/local/sbin/choparp " . $args);
        }
    }
}

function services_dnsupdate_process() {
    global $config, $g;
    
    /* Dynamic DNS updating active? */
    if (isset($config['dnsupdate']['enable'])) {
        
        $wanip = get_current_wan_address();
        
        if ($wanip) {
                
            $keyname = $config['dnsupdate']['keyname'];
            /* trailing dot */
            if (substr($keyname, -1) != ".")
                $keyname .= ".";
            
            $hostname = $config['dnsupdate']['host'];
            /* trailing dot */
            if (substr($hostname, -1) != ".")
                $hostname .= ".";
            
            /* write private key file
               this is dumb - public and private keys are the same for HMAC-MD5,
               but nsupdate insists on having both */
            $fd = fopen("{$g['varetc_path']}/K{$keyname}+157+00000.private", "w");
            $privkey .= <<<EOD
Private-key-format: v1.2
Algorithm: 157 (HMAC)
Key: {$config['dnsupdate']['keydata']}

EOD;
            fwrite($fd, $privkey);
            fclose($fd);
            
            /* write public key file */
            if ($config['dnsupdate']['keytype'] == "host") {
                $flags = 513;
                $proto = 3;
            } else if ($config['dnsupdate']['keytype'] == "user") {
                $flags = 0;
                $proto = 2;
            } else {
                $flags = 257;
                $proto = 3;
            }
            
            $fd = fopen("{$g['varetc_path']}/K{$keyname}+157+00000.key", "w");
            fwrite($fd, "{$keyname} IN KEY {$flags} {$proto} 157 {$config['dnsupdate']['keydata']}\n");
            fclose($fd);
            
            /* generate update instructions */
            $upinst =  "update delete {$hostname} A\n";
            $upinst .= "update add {$hostname} {$config['dnsupdate']['ttl']} A {$wanip}\n";
            $upinst .= "\n";    /* mind that trailing newline! */
            
            $fd = fopen("{$g['varetc_path']}/nsupdatecmds", "w");
            fwrite($fd, $upinst);
            fclose($fd);
            
            /* invoke nsupdate */
            $cmd = "/usr/sbin/nsupdate -k {$g['varetc_path']}:{$keyname}";
            if (isset($config['dnsupdate']['usetcp']))
                $cmd .= " -v";
            $cmd .= " {$g['varetc_path']}/nsupdatecmds";
            
            mwexec_bg($cmd);
        }
    }
    
    return 0;
}

function services_generate_bgpd_conf() {
	global $config, $g;
	
	$bgpcfg = $config['bgpd'];
	
	$bgpdconf = '# global configuration' . "\n";

	# Setup the AS
	$bgpdconf .= 'AS ' . $bgpcfg['as'] . "\n";
	
	# Setup router-id
	$bgpdconf .= 'router-id ' . $bgpcfg['routerid'] . "\n";

	if (isset($bgpcfg['connectretry']))	
		$bgpdconf .= 'connect-retry ' . $bgpcfg['connectretry'] . "\n";
	else
		$bgpdconf .= 'connect-retry 120' . "\n"; 
	
	if (isset($bgpcfg['holdtime']))
                $bgpdconf .= 'holdtime ' . $bgpcfg['holdtime'] . "\n";
        else
                $bgpdconf .= 'holdtime 90' . "\n";
	
	if (isset($bgpcfg['holdtimemin']))
                $bgpdconf .= 'holdtime min ' . $bgpcfg['holdtimemin'] . "\n";
        else
                $bgpdconf .= 'holdtime min 3' . "\n";

	if (isset($bgpcfg['listenon']))
                $bgpdconf .= 'listen on ' . $bgpcfg['listenon'] . "\n";
        else
                $bgpdconf .= 'listen on 127.0.0.1' . "\n";
	
	if (isset($bgpcfg['logupdates']))
                $bgpdconf .= 'log updates' . "\n";

	if (isset($bgpcfg['qualifyviabgp']))
                $bgpdconf .= 'nexthop qualify via bgp' . "\n";
        else
                $bgpdconf .= 'nexthop qualify via default' . "\n";

	if (isset($bgpcfg['updatefib'])) 
		$bgpdconf .= 'fib-update yes' . "\n";
	else 
		$bgpdconf .= 'fib-update no' . "\n";
	
	if (isset($bgpcfg['routecollector']))
                $bgpdconf .= 'route-collector yes' . "\n";
        else
                $bgpdconf .= 'route-collector no' . "\n";
	
	if (isset($bgpcfg['rtable']))
                $bgpdconf .= 'rtable ' . $bgpcfg['rtable'] . "\n";
        else
                $bgpdconf .= 'rtable 0' . "\n";

	if (isset($bgpcfg['transparentas']))
                $bgpdconf .= 'transparent-as yes' . "\n";
        else
                $bgpdconf .= 'transparent-as no' . "\n";
	
	foreach($bgpcfg['routelist'] as $network) {
                $bgpdconf .= 'network ' . $network . "\n";
        }

	$bgpdconf .= "\n" . '# groups and neighbors' . "\n";
	foreach($bgpcfg['group'] as $group) {
                $bgpdconf .= 'group ' . $group['name'] . " {\n";
		$bgpdconf .= "\t" . 'remote-as ' . $group['remoteas'] . "\n";
		if (isset($group['descr']))
			$bgpdconf .= "\t" . 'descr "' . $group['descr'] . '"' . "\n";
        	if (isset($group['announce4byte']))
                	$bgpdconf .= "\t" . 'announce as-4byte yes' . "\n";
        	else
                	$bgpdconf .= "\t" . 'announce as-4byte no' . "\n";
		if (isset($group['announcecapabilities']))
                        $bgpdconf .= "\t" . 'announce capabilities yes' . "\n";
                else
                        $bgpdconf .= "\t" . 'announce capabilities no' . "\n";
	
		if (isset($group['announcerefresh']))
                        $bgpdconf .= "\t" . 'announce refresh yes' . "\n";
                else
                        $bgpdconf .= "\t" . 'announce refresh no' . "\n";
		
		if (isset($group['announcerestart']))
                        $bgpdconf .= "\t" . 'announce restart yes' . "\n";
                else
                        $bgpdconf .= "\t" . 'announce restart no' . "\n";
		
		if (isset($group['holdtime']))
           	     	$bgpdconf .= "\t" . 'holdtime ' . $group['holdtime'] . "\n";

		if (isset($group['minholdtime']))
                        $bgpdconf .= "\t" . 'holdtime min ' . $group['minholdtime'] . "\n";

		if (isset($group['multihop']))
                        $bgpdconf .= "\t" . 'multihop ' . $group['multihop'] . "\n";

		if (isset($group['localaddress']))
                        $bgpdconf .= "\t" . 'local-address ' . $group['localaddress'] . "\n";

		if (isset($group['transparentas']))
                        $bgpdconf .= "\t" . 'transparent-as yes' . "\n";
                else
                        $bgpdconf .= "\t" . 'transparent-as no' . "\n";

		if (isset($group['ttlsecurity']))
                        $bgpdconf .= "\t" . 'ttl-security yes' . "\n";
                else
                        $bgpdconf .= "\t" . 'ttl-security no' . "\n";

		foreach($bgpcfg['neighbor'] as $neighbor) {
			if($neighbor['groupname'] == $group['name']) {
 	                $bgpdconf .= "\t" . 'neighbor ' . $neighbor['name'] . " {\n";
         	        if (isset($neighbor['remote-as'])) 
				$bgpdconf .= "\t\t" . 'remote-as ' . $neighbor['remoteas'] . "\n";
                	$bgpdconf .= "\t\t" . 'descr "' . $neighbor['descr'] . '"' . "\n";
              		if (!isset($neighbor['announce4byte']))
                        	$bgpdconf .= "\t\t" . 'announce as-4byte no' . "\n";
                	if (!isset($neighbor['announcecapabilities']))
                        	$bgpdconf .= "\t\t" . 'announce capabilities no' . "\n";
 		        if (!isset($neighbor['announcerefresh']))
                        	$bgpdconf .= "\t\t" . 'announce refresh no' . "\n";
 	                if (isset($neighbor['announcerestart']))
             		        $bgpdconf .= "\t\t" . 'announce restart yes' . "\n";
	                if (isset($neighbor['holdtime']))
        	                $bgpdconf .= "\t\t" . 'holdtime ' . $neighbor['holdtime'] . "\n";

 	                if (isset($neighbor['minholdtime']))
                        	$bgpdconf .= "\t\t" . 'holdtime min ' . $neighbor['minholdtime'] . "\n";

                	if (isset($neighbor['multihop']))
                        	$bgpdconf .= "\t\t" . 'multihop ' . $neighbor['multihop'] . "\n";

                	if (isset($neighbor['localaddress']))
                        	$bgpdconf .= "\t\t" . 'local-address ' . $neighbor['localaddress'] . "\n";

 	               if (isset($neighbor['transparentas']))
        	                $bgpdconf .= "\t\t" . 'transparent-as yes' . "\n";
                       if (isset($neighbor['ttlsecurity']))
                        	$bgpdconf .= "\t\t" . 'ttl-security yes' . "\n";
        	       $bgpdconf .= "\t}\n";
 	      		}
		}

		$bgpdconf .= "}\n";
	}


$bgpdconf .= <<<EOD

# filters
deny from any


EOD;

if (isset($config['bgpd']['filter']['options']['prefixfilter']))
	$bgpdconf .= '# filter out prefixes longer than 24 or shorter than 8 bits' . "\nallow from any inet prefixlen 8 - 24\n";

if (isset($config['bgpd']['filter']['options']['defaultroute']))
        $bgpdconf .= "\n" . '# filter out default routes' . "\ndeny from any inet prefix 0.0.0.0/0 prefixlen = 0\n";

if (isset($config['bgpd']['filter']['options']['rfc5735'])) {

$bgpdconf .= <<<EOD

# filter bogus networks according to RFC5735
deny from any prefix 0.0.0.0/8 prefixlen >= 8
deny from any prefix 10.0.0.0/8 prefixlen >= 8
deny from any prefix 127.0.0.0/8 prefixlen >= 8
deny from any prefix 169.254.0.0/16 prefixlen >= 16
deny from any prefix 172.16.0.0/12 prefixlen >= 12
deny from any prefix 192.0.2.0/24 prefixlen >= 24
deny from any prefix 192.168.0.0/16 prefixlen >= 16
deny from any prefix 198.18.0.0/15 prefixlen >= 15
deny from any prefix 198.51.100.0/24 prefixlen >= 24
deny from any prefix 203.0.113.0/24 prefixlen >= 24
deny from any prefix 224.0.0.0/4 prefixlen >= 4
deny from any prefix 240.0.0.0/4 prefixlen >= 4

EOD;

}

$bgpdconf .= <<<EOD

################################
################################
##### User Defined Filters #####
################################
################################
\n
EOD;

       	foreach ($config['bgpd']['filter']['rule'] as $rule) {
		if(count($rule['srclist']) > 1)
			$bgpdconf .= $rule['type'] . ' ' . $rule['direction'] . ' {';
		else 
			$bgpdconf .= $rule['type'] . ' ' . $rule['direction'];

		foreach ($rule['srclist'] as $src) {
			if (preg_match('/^g:/', $src)) {
				$src = preg_replace('/^g:/', ' group "', $src);
				$bgpdconf .= $src . '", ';
			} else {
				$bgpdconf .= ' ' . $src . ', '; 
			}
		}
		$bgpdconf = preg_replace('/, $/', '', $bgpdconf);	
	
		if(count($rule['srclist']) > 1)
                        $bgpdconf .= ' }';

		$bgpdconf .= ' prefix ' . $rule['prefix'];
	        
		if ($rule['prefixlen'] != '')
			$bgpdconf .= ' prefixlen ' . $rule['prefixlenop'] . ' ' . $rule['prefixlen'];
		
		$bgpdconf .= "\n";
	}

	$bgpdconf .= "\nsocket ".'"/var/run/bgpd.rsock"'." restricted\n";
	
	return $bgpdconf;
}

function services_print_bgpdconf() {                                                                                                                             
        global $config, $g;                                                                                                                             
        printf("# bgpd.conf file generated by NSWall\n\n");                                                                                                 
        $bgpdconf = services_generate_bgpd_conf();                                                                                                              
        printf("$bgpdconf\n");                                                                                                                              
}   

function services_bgpd_configure() {
        global $config, $g;

        if ($g['booting'])
                echo "Configuring BGPd... ";

        /* generate bgpd.conf */
        $bgpdconf = services_generate_bgpd_conf();

        /* write out a new bgpd.conf to the /etc/ dir */
        /* write dhcpd.conf */
        $fd = fopen("{$g['varetc_path']}/bgpd.conf", "w");
        if (!$fd) {
                printf("Error: cannot open bgpd.conf in services_bgp_configure().\n");
                return 1;
        }
        fwrite($fd, $bgpdconf);
        fclose($fd);
        chmod("{$g['varetc_path']}/bgpd.conf", 0600);

	if(!file_exists("{$g['varrun_path']}/bgpd.pid")) {
		/* fire up bgpd */
        	$cmd = "/usr/sbin/bgpd";
		system("/usr/bin/pgrep -o bgpd > {$g['varrun_path']}/bgpd.pid");
		mwexec($cmd);
	} else {
		/* reload the configuration */
		$cmd = "/usr/sbin/bgpctl reload";
		mwexec($cmd);
	}

        if ($g['booting'])
                echo "done\n";

        return 0;

}

?>
