<?php

require_once("functions.inc");

function cdrtobin ($cdrin){
	return str_pad(str_pad("", $cdrin, "1"), 32, "0");
}

function bintodq ($binin) {
	if ($binin=="N/A") return $binin;
	$binin=explode(".", chunk_split($binin,8,"."));
	for ($i=0; $i<4 ; $i++) {
		$dq[$i]=bindec($binin[$i]);
	}
        return implode(".",$dq) ;
}

function cidrconv ($CIDR) { 
    list ($ip, $mask) = explode ('/', $CIDR); 
    $bin_nmask=cdrtobin($mask);
    return ("$ip ".bintodq($bin_nmask)); 
} 

function openvpn_monitor_cert_generate() {
	/* generate cert files for openvpn monitor connection*/
	global $config, $g;
	if(is_array($config['system']['configserver'])) {
		foreach ($config['system']['certmgr']['cert'] as $sslcert) {
			if($sslcert['name'] == $config['system']['configserver']['certname']) {
				$privatekey = base64_decode($sslcert['prv']);
				$fd = fopen("{$g['varetc_path']}/ssl/private/ns_monitor.key", "w");
				fwrite($fd, $privatekey);;
				fclose($fd);
				chmod("{$g['varetc_path']}/ssl/private/ns_monitor.key", 400);	
				$certificate = base64_decode($sslcert['crt']);
				$fd = fopen("{$g['varetc_path']}/ssl/ns_monitor.crt", "w");
				fwrite($fd, $certificate);;
				fclose($fd);
			}
		}
	}
}

function openvpn_monitor_ca_generate() {
        /* generate ca files for openvpn monitor connection*/
        global $config, $g;
        if(is_array($config['system']['configserver'])) {
                foreach ($config['system']['certmgr']['ca'] as $sslca) {
                        if($sslca['name'] == $config['system']['configserver']['caname']) {
                                $certificate = base64_decode($sslca['crt']);
                                $fd = fopen("{$g['varetc_path']}/ssl/ns_monitor_ca.crt", "w");
                                fwrite($fd, $certificate);;
                                fclose($fd);
                        }
                }
        }
}

function openvpn_monitor_takey_generate() {
        /* generate takey file for openvpn monitor connection*/
        global $config, $g;
        $certificate = base64_decode($config['system']['configserver']['takey']);
        $fd = fopen("{$g['varetc_path']}/ns_monitor_ta.key", "w");
        fwrite($fd, $certificate);;
        fclose($fd);
}

function openvpn_monitor_conf_generate() {
	global $config, $g;
	/* generate openvpn.conf */
        $fd = fopen("{$g['varetc_path']}/vpn/monitor_openvpn.conf", "w");
        if (!$fd) {
        	printf("Error: cannot open monitor_openvpn.conf in openvpn_monitor_conf_generate().\n");
                return 1;
        }

        $openvpnconf = <<<EOD
client
float
dev tun0
proto udp
remote {$config['system']['configserver']['ip']} 1194
resolv-retry infinite
nobind
persist-key
persist-tun
ca /etc/ssl/ns_monitor_ca.crt
cert /etc/ssl/ns_monitor.crt
key /etc/ssl/private/ns_monitor.key
tls-auth /etc/ns_monitor_ta.key 1
ns-cert-type server
verb 3

EOD;

        fwrite($fd, $openvpnconf);

}

function openvpn_monitor_configure() {
        global $config, $g;

	openvpn_monitor_takey_generate();
	openvpn_monitor_ca_generate();
	openvpn_monitor_cert_generate();
	openvpn_monitor_conf_generate();
	system("pkill -f monitor_openvpn.conf");
        mwexec("/usr/sbin/openvpn --daemon --config /etc/vpn/monitor_openvpn.conf");
        
	return 0;
}

function openvpn_site2site_cert_generate($name) {
        /* generate cert files for openvpn site2site connection*/
        global $config, $g;

	foreach ($config['openvpn']['gw'] as $gw) {
		if ($gw['name'] == $name)
			break;
	}	

	foreach ($config['system']['certmgr']['cert'] as $sslcert) {
		if($sslcert['name'] == $gw['certname']) {
			$privatekey = base64_decode($sslcert['prv']);
			$fd = fopen("{$g['varetc_path']}/vpn/{$name}/{$name}.key", "w");
			fwrite($fd, $privatekey);;
			fclose($fd);
			chmod("{$g['varetc_path']}/vpn/{$name}/{$name}.key", 400);
			$certificate = base64_decode($sslcert['crt']);
			$fd = fopen("{$g['varetc_path']}/vpn/{$name}/{$name}.crt", "w");
			fwrite($fd, $certificate);;
			fclose($fd);
		}
	}
}

function openvpn_site2site_ca_generate($name) {
        /* generate ca files for openvpn site2site connection*/
        global $config, $g;
	
	foreach ($config['openvpn']['gw'] as $gw) {
                if ($gw['name'] == $name)
                        break;
        }

	foreach ($config['system']['certmgr']['ca'] as $sslca) {
		if($sslca['name'] == $gw['caname']) {
			$certificate = base64_decode($sslca['crt']);
			$fd = fopen("{$g['varetc_path']}/vpn/{$name}/{$name}-ca.crt", "w");
			fwrite($fd, $certificate);;
			fclose($fd);
		}
	}
}

function openvpn_site2site_takey_generate($name) {
        /* generate takey file for openvpn monitor connection*/
        global $config, $g;

	foreach ($config['openvpn']['gw'] as $gw) {
                if ($gw['name'] == $name)
                        break;
        }

        $certificate = base64_decode($gw['takey']);
        $fd = fopen("{$g['varetc_path']}/vpn/{$name}/{$name}_ta.key", "w");
        fwrite($fd, $certificate);;
        fclose($fd);
}

function openvpn_site2site_dhkey_generate($name) {
        /* generate dhkey file for openvpn monitor connection*/
        global $config, $g;

	foreach ($config['openvpn']['gw'] as $gw) {
                if ($gw['name'] == $name)
                        break;
        }

        $certificate = base64_decode($gw['dhkey']);
        $fd = fopen("{$g['varetc_path']}/vpn/{$name}/{$name}_dh.pem", "w");
        fwrite($fd, $certificate);;
        fclose($fd);
}

function openvpn_site2site_conf_generate($name) {
        global $config, $g;

	foreach ($config['openvpn']['gw'] as $gw) {
                if ($gw['name'] == $name)
                        break;
        }

        /* generate openvpn.conf */
        $fd = fopen("{$g['varetc_path']}/vpn/{$name}/{$name}_openvpn.conf", "w");
        if (!$fd) {
                printf("Error: cannot open site2site_openvpn.conf in {$name}_site2site_conf_generate().\n");
                return 1;
        }

	if ($gw['context'] == 'client') {
       		$openvpnconf = <<<EOD
client
dev {$gw['device']}
proto {$gw['proto']}
remote {$gw['ip']} {$gw['port']} 
resolv-retry infinite
nobind
persist-key
persist-tun
ca /etc/vpn/{$name}/{$name}-ca.crt
cert /etc/vpn/{$name}/{$name}.crt
key /etc/vpn/{$name}/{$name}.key
tls-auth /etc/vpn/{$name}/{$name}_ta.key 1
ns-cert-type server
verb 3

EOD;

	} else { 
                
		$openvpnconf = <<<EOD
port {$gw['port']}
proto {$gw['proto']} 
dev {$gw['device']}
ca /etc/vpn/{$name}/{$name}-ca.crt
cert /etc/vpn/{$name}/{$name}.crt
key /etc/vpn/{$name}/{$name}.key
dh /etc/vpn/{$name}/{$name}_dh.pem
ifconfig-pool-persist ipp.txt
client-config-dir /etc/vpn/{$name}/ccd
keepalive 10 120
tls-auth /etc/vpn/{$name}/{$name}_ta.key 0
max-clients 252
persist-key
persist-tun
status openvpn-status.log
verb 5

EOD;

        $openvpnconf .= "server ". cidrconv($gw['subnet'].'/'.$gw['subnetmask'])."\n"; 
        $remoteroutes = $gw['remoteroutes'];
	for($i=0;$i<sizeof($remoteroutes); $i++) {
		$route = "route" . "$i";
		$openvpnconf .= "route ".cidrconv($remoteroutes[$route])."\n";
	}

	$localroutes = $gw['localroutes'];
	for($i=0;$i<sizeof($localroutes); $i++) {
		$route = "route" . "$i";
		$openvpnconf .= "push \"route ".cidrconv($localroutes[$route])."\"";
	}

	}
        fwrite($fd, $openvpnconf);

}

function openvpn_site2site_configure($name) {

	global $config, $g;

	// Make the dir for this tunnel
	if (!is_dir("/etc/vpn/{$name}"))
        	mkdir("/etc/vpn/{$name}", 0777);

	openvpn_site2site_cert_generate($name);
	openvpn_site2site_ca_generate($name);
	openvpn_site2site_takey_generate($name);
        openvpn_site2site_dhkey_generate($name);
	openvpn_site2site_conf_generate($name);
	system("pkill -f {$name}_openvpn.conf");
        mwexec("/usr/sbin/openvpn --daemon --config /etc/vpn/{$name}/{$name}_openvpn.conf");
}

?>
