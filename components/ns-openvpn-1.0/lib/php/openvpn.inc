<?php

require_once("functions.inc");

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

?>
