<?php
/*
	$Id: system.inc,v 1.40 2009/04/20 17:51:02 jrecords Exp $
	
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

function system_build_ps_database(){
	mwexec("dev_mkdb");
}

function system_secure_level(){
	mwexec("sysctl kern.securelevel=1");
}

function system_sshd_start() {
	global $config, $g;
	if (isset($config['system']['general']['sshd']['enabled'] )) {
		$hostkey = '/conf/ssh/ssh_host_rsa_key';
		if ( file_exists( $hostkey )) 
                	exec("cp -p $hostkey $hostkey.pub /etc/ssh");
		else {		
			conf_mount_rw();
			echo "ssh-keygen: generating new RSA host key... ";
			mwexec("mkdir -p /flash/conf/etc/ssh 2>/dev/null");
			exec("/usr/bin/ssh-keygen -q -t rsa -f $hostkey -N ''");
			exec("cp -p $hostkey $hostkey.pub /etc/ssh");
			conf_mount_ro();
			echo "done\n";
		}
	echo "Starting SSH Server... ";
	exec("/bin/echo \"ListenAddress\t\t{$config['interfaces']['lan']['ipaddr']}\" >> /etc/ssh/sshd_config"); 
	exec("/usr/sbin/sshd");
	echo "done\n";
	}
}

function system_check_filesystems() {
	global $config, $g;
	
	if ($g['booting'])
                        echo "Checking filesystems with fsck... ";

	$filesystems = array("/dev/wd0a", "/dev/wd0d");
	foreach ($filesystems as $fs) {
		fsck_fs($fs);
	}
	
	if ($g['booting'])
                echo "done\n";
}

function system_symon_start() {
        global $config, $g;
        if (isset($config['system']['general']['symon']['enabled'] )) {
		if ($g['booting'])
                	echo "Starting Symon service... ";
        	else
                	mwexec("/usr/bin/pkill symon");
                
		/* generate symon config */
		system_symonconf_generate();
		
		/* load symon config */
		mwexec("/usr/libexec/symon");

		echo "done\n";
        }
}

function system_cron_start() {
        global $config, $g;
        if($config['system']['configserver']) {
		if ($g['booting'])
	       		echo "Starting cron service... ";
               	mwexec("/usr/sbin/cron");
               	system_cron_reload();
		
		if ($g['booting'])
			echo "done\n";
	}
}

function system_cron_reload() {
        global $config, $g;
        /* generate crontab file */
        system_cronconf_generate();
        mwexec("/usr/bin/crontab /etc/nswallcron");
}

function system_ftpproxy_start() {
	mwexec("/usr/sbin/ftp-proxy -q bulk -T FTPROXY -D 7 -v");
}

function system_symonconf_generate() {
        global $config, $g;
	$wanip = get_current_wan_address();
	$syscfg = $config['system']['general'];

	$fd = fopen("{$g['varetc_path']}/symon.conf", "w");
                if (!$fd) {
                        printf("Error: cannot open symon.conf in system_symonconf_generate().\n");
                        return 1;
                }

        $symonconf = "monitor { cpu(0), mem, pf, mbuf,\nio(wd0), df(rd0a), df(wd0d),\nproc(relayd), proc(symon), proc(symux),\n";
	foreach ($config['interfaces'] as $interface) {
		$ifstring .= 'if(' . $interface['if'] . '), ';
	}
	$symonconf .= "$ifstring debug } stream from $wanip to {$syscfg['symon']['muxip']} 2100\n";

	fwrite($fd, $symonconf);
        fclose($fd);

	return $symonconf;
}

function system_cronconf_generate() {
        global $config, $g;

	if($config['system']['configserver']) {
      		$fd = fopen("{$g['varetc_path']}/nswallcron", "w");
                	if (!$fd) {
                        	printf("Error: cannot open symon.conf in system_cronconf_generate().\n");
                        	return 1;
                	}
		if($config['system']['configserver']['checkint']) {
			$confcheck = $config['system']['configserver']['checkint'];
		} else {
			$confcheck = '60';
		}
	        $crontab = "*/$confcheck * * * * php -q /etc/rc.queryconfigserver\n";
		fwrite($fd, $crontab);
        	fclose($fd);

	        return $cronconf;
	}
}

function system_resolvconf_generate($dynupdate = false) {
	global $config, $g;
	
	$syscfg = $config['system']['general'];
	
	$fd = fopen("{$g['varetc_path']}/resolv.conf", "w");
	if (!$fd) {
		printf("Error: cannot open resolv.conf in system_resolvconf_generate().\n");
		return 1;
	}
		
	$resolvconf = "domain {$syscfg['domain']}\n";
	
	$havedns = false;
	
	if (isset($syscfg['dnsallowoverride'])) {
		/* get dynamically assigned DNS servers (if any) */
		$nfd = @fopen("{$g['varetc_path']}/nameservers.conf", "r");
		if ($nfd) {
			while (!feof($nfd)) {
				$dnss = trim(fgets($nfd));
				if ($dnss) {
					$resolvconf .= "nameserver $dnss\n";
					$havedns = true;
				}
			}
			fclose($nfd);
		}
	}
	if (!$havedns && is_array($syscfg['dnsserver'])) {
		foreach ($syscfg['dnsserver'] as $ns) {
			if ($ns)
				$resolvconf .= "nameserver $ns\n";
			$havedns = true;
		}
	}
		
	fwrite($fd, $resolvconf);
	fclose($fd);
	
	if (!$g['booting']) {
		/* restart dhcpd (nameservers may have changed) */
		if (!$dynupdate)
			services_dhcpd_configure();
	}
	
	return 0;
}

function system_aliases_generate() {
	global $config, $g;

        /* returns an array of system aliases in the following format */
	/* $sysalias[<number>]['name']*/
	/* $sysalias[<number>]['memberstring']*/
        
	$optcfg = array();

        for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                $oc = $config['interfaces']['opt' . $i];

                if (isset($oc['enable']) && $oc['if']) {
                        $oic = array();
                        $oic['if'] = $oc['if'];

                        if ($oc['bridge']) {
                                if (!strstr($oc['bridge'], "opt") ||
                                         isset($config['interfaces'][$oc['bridge']]['enable'])) {
                                        if (is_ipaddr($config['interfaces'][$oc['bridge']]['ipaddr'])) {
                                                $oic['ip'] = $config['interfaces'][$oc['bridge']]['ipaddr'];
                                                $oic['sn'] = $config['interfaces'][$oc['bridge']]['subnet'];
                                                $oic['sa'] = gen_subnet($oic['ip'], $oic['sn']);
                                        	$oic['desc'] = $config['interfaces'][$oc['bridge']]['desc'];
					}
                                }
                                $oic['bridge'] = 1;
                                $oic['bridge_if'] = $oc['bridge'];
                        } else {
                		$oic['desc'] = $oc['desc'];
		                $oic['ip'] = $oc['ipaddr'];
                                $oic['sn'] = $oc['subnet'];
                                $oic['sa'] = gen_subnet($oic['ip'], $oic['sn']);
                        }

                        $optcfg['opt' . $i] = $oic;
                }
        }
	
	$lansa = gen_subnet($lancfg['ipaddr'], $lancfg['subnet']);

	foreach( $optcfg as $sysalias ) {
		echo "$sysalias[0]\n";
	}
}

function system_hosts_generate() {
	global $config, $g;
	
	$syscfg = $config['system']['general'];
	$lancfg = $config['interfaces']['lan'];
	$dnsmasqcfg = $config['dnsmasq'];

	if (!is_array($dnsmasqcfg['hosts'])) {
		$dnsmasqcfg['hosts'] = array();
	}
	$hostscfg = $dnsmasqcfg['hosts'];
	
	$fd = fopen("{$g['varetc_path']}/hosts", "w");
	if (!$fd) {
		printf("Error: cannot open hosts file in system_hosts_generate().\n");
		return 1;
	}
		
	$hosts = <<<EOD
127.0.0.1	localhost localhost.{$syscfg['domain']}
{$lancfg['ipaddr']}	{$config['system']['hostname']} {$config['system']['hostname']}.{$syscfg['domain']}

EOD;
	foreach ($hostscfg as $host) {
		if ($host['host'])
			$hosts .= "{$host['ip']} 	{$host['host']} {$host['host']}.{$host['domain']}\n";
		else
			$hosts .= "{$host['ip']} 	{$host['domain']}\n";
	}
	fwrite($fd, $hosts);
	fclose($fd);
	
	return 0;
}

function system_hostname_configure() {
	global $config, $g;
	
	$syscfg = $config['system']['general'];
	$localsyscfg = $config['system'];
	
	/* set hostname */
	return mwexec("/bin/hostname " .
		escapeshellarg("{$localsyscfg['hostname']}.{$syscfg['domain']}"));
}

function system_routing_configure() {
	global $config, $g;
	
	/* clear out old routes, if necessary */
	if (file_exists("{$g['vardb_path']}/routes.db")) {
		$fd = fopen("{$g['vardb_path']}/routes.db", "r");
		if (!$fd) {
			printf("Error: cannot open routes DB file in system_routing_configure().\n");
			return 1;		
		}
		while (!feof($fd)) {
			$oldrt = fgets($fd);
			if ($oldrt)
				mwexec("/sbin/route delete -inet $oldrt");
		}
		fclose($fd);
		unlink("{$g['vardb_path']}/routes.db");
	}
	
	if (is_array($config['staticroutes']['route'])) {
		
		$fd = fopen("{$g['vardb_path']}/routes.db", "w");
		if (!$fd) {
			printf("Error: cannot open routes DB file in system_routing_configure().\n");
			return 1;		
		}
		
		foreach ($config['staticroutes']['route'] as $rtent) {
			mwexec("/sbin/route add " . escapeshellarg($rtent['network']) . 
				" " . escapeshellarg($rtent['gateway']));
			
			/* record route so it can be easily removed later (if necessary) */
			fwrite($fd, $rtent['network'] . "\n");
		}
		
		fclose($fd); 
	}
	
	return 0;
}

function system_advancednetwork_configure() {
	global $config, $g;
	/* Set the Maximum queue lenghth */
	$maxquelen = $config['system']['networking']['maxquelen']; 
	mwexec("/sbin/sysctl net.inet.ip.ifq.maxlen=$maxquelen");
	/* Set the Maximum ICMP errors per second */
        $maxicmperr = $config['system']['networking']['maxicmperr']; 
        mwexec("/sbin/sysctl net.inet.icmp.errpplimit=$maxicmperr");	
	/* Enable TCP Ack on push */
        if(isset($config['system']['networking']['ackonpush'])) { 
        	mwexec("/sbin/sysctl net.inet.tcp.ackonpush=1");
	} else {
		mwexec("/sbin/sysctl net.inet.tcp.ackonpush=0");
	}
	/* Enable ECN */
        if(isset($config['system']['networking']['ecn'])) {
        	mwexec("/sbin/sysctl net.inet.tcp.ecn=1");
	} else {
		mwexec("/sbin/sysctl net.inet.tcp.ecn=0");
	}
	/* Enable Window Scaling */
        if(isset($config['system']['networking']['tcpscaling'])) {             
                mwexec("/sbin/sysctl net.inet.tcp.rfc1323=1");
	} else {
		mwexec("/sbin/sysctl net.inet.tcp.rfc1323=0");
	}
	/* TCP receive window */
        $tcprcv = $config['system']['networking']['tcprcv']; 
        mwexec("/sbin/sysctl net.inet.tcp.recvspace=$tcprcv");
	/* TCP send window */
        $tcpsnd = $config['system']['networking']['tcpsnd']; 
        mwexec("/sbin/sysctl net.inet.tcp.sendspace=$tcpsnd");
	/* Enable TCP SACK */
        if(isset($config['system']['networking']['sack'])) {             
                mwexec("/sbin/sysctl net.inet.tcp.sack=1");
	} else {
		mwexec("/sbin/sysctl net.inet.tcp.sack=0");
	}
	/* UDP receive window */
        $udprcv = $config['system']['networking']['udprcv'];        
        mwexec("/sbin/sysctl net.inet.udp.recvspace=$udprcv");
        /* TCP send window */
        $udpsnd = $config['system']['networking']['udpsnd'];        
        mwexec("/sbin/sysctl net.inet.udp.sendspace=$udpsnd");
}	

function system_routing_enable() {
	global $config, $g;
	
	return mwexec("/sbin/sysctl net.inet.ip.forwarding=1");
}

function system_syslogd_start() {
	global $config, $g;
	
	$syslogcfg = $config['syslog'];

	if ($g['booting']) 
		echo "Starting Syslog service... ";
	else
		killbypid("{$g['varrun_path']}/syslog.pid");
			
	//if (isset($syslogcfg['enable'])) {

		/* write syslog.conf */
		$fd = fopen("{$g['varetc_path']}/syslog.conf", "w");
		if (!$fd) {
			printf("Error: cannot open syslog.conf in system_syslogd_start().\n");
			return 1;
		}
		
		$syslogconf = <<<EOD
!!/usr/sbin/cron
cron.info				/dev/null
!*

*.*					:96:all	

EOD;
		if (isset($syslogcfg['remoteserver'])) {
			$syslogconf .= <<<EOD
*.*					@{$syslogcfg['remoteserver']}

EOD;
		}
		fwrite($fd, $syslogconf);
		fclose($fd);
		
		$retval = mwexec("/usr/sbin/syslogd -s {$g['varrun_path']}/syslogd.sock -f {$g['varetc_path']}/syslog.conf");

	if ($g['booting'])
		echo "done\n";
		
	return $retval;
}

function system_pccard_start() {
	global $config, $g;
	
	if ($g['booting'])
		echo "Initializing PC cards... ";
	
	/* kill any running pccardd */
	killbypid("{$g['varrun_path']}/pccardd.pid");
	
	/* fire up pccardd */
	$res = mwexec("/usr/sbin/pccardd -z -f {$g['etc_path']}/pccard.conf");
	
	if ($g['booting']) {
		if ($res == 0)
			echo "done\n";
		else
			echo "failed (probably no PC card controller present)\n";
	}
		
	return $res;
}

function system_webgui_start() {
	global $config, $g;
	
	if ($g['booting']) {
		echo "Starting webGUI... ";
		mwexec("/bin/ln -s /tmp/debug/ /usr/www/debug");
	}
	
        /* kill any running mini_httpd */
	killbypid("{$g['varrun_path']}/mini_httpd.pid");

	/* find out the lan ip, we'll only listen on this one */
	$lanip = $config['interfaces']['lan']['ipaddr'];
	
	/* generate password file */
	system_password_configure();
	
	chdir($g['www_path']);
	
	/* non-standard port? */
	if ($config['system']['general']['webgui']['port'])
		$portarg = "-p {$config['system']['general']['webgui']['port']}";
	else
		$portarg = "-p 443";
	
	$hostname = $config['system']['hostname'];
	
	if ($config['system']['general']['webgui']['certificate']) {
		foreach ($config['system']['certmgr']['cert'] as $certificate) {
			if ($config['system']['general']['webgui']['certificate'] == $certificate['name']) {
				$cert = base64_decode($certificate['crt']);
				$key = base64_decode($certificate['prv']);
			}
		}
	} else {
			/* default certificate/key */
			$cert = <<<EOD
-----BEGIN CERTIFICATE-----
MIIBlDCB/gIBADANBgkqhkiG9w0BAQQFADATMREwDwYDVQQKEwhtMG4wd2FsbDAe
Fw0wNTA1MTAxMjI0NDRaFw0wNzA1MTAxMjI0NDRaMBMxETAPBgNVBAoTCG0wbjB3
YWxsMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDAShszhFz+o8lsMWTGgTxs
TMPR+v4+qL5jXDyY97MLTGFK7aqQOtpIQc+TcTc4jklgOVlHoR7oBXrsi8YrbCd+
83LPQmQoSPC0VqhfU3uYf3NzxiK8r97aPCsmWgwT2pQ6TcESTm6sF7nLprOf/zFP
C4jE2fvjkbzyVolPywBuewIDAQABMA0GCSqGSIb3DQEBBAUAA4GBAFR962c4R5tV
cTn0OQcszYoW6WC+ini9tQQh5ku5jYDAiC+00atawJEVLnL3lwAcpSKTIWlTkD20
tl3lz5br1qFgYky+Rd0kwS2nk9jRbkxSXxd6KJVnNRCKre28aw3ENzZfCSurPQsX
UPp5er+NtwMT1g7s/JDmKTC4w1rGr5/c
-----END CERTIFICATE-----

EOD;

			$key = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDAShszhFz+o8lsMWTGgTxsTMPR+v4+qL5jXDyY97MLTGFK7aqQ
OtpIQc+TcTc4jklgOVlHoR7oBXrsi8YrbCd+83LPQmQoSPC0VqhfU3uYf3NzxiK8
r97aPCsmWgwT2pQ6TcESTm6sF7nLprOf/zFPC4jE2fvjkbzyVolPywBuewIDAQAB
AoGAbJJrQW9fQrggJuLMz/hwsYW2m31oyOBmf5u463YQtjRuSuxe/gj87weZuNqY
H2rXq2k2K+ehl8hgW+egASyUL3L7kCkEAsVREujKTEyhSqqIRDPWTxo9S/YA9Gvn
2ZnJvkrcKjqCO9aHX3rvJOK/ErYI6akctgI3KmgkYw5XNmECQQDuZU97RTWH9rmP
aQr57ysNXxgFsyhetOOqeYkPtIVwpOiNbfwE1zi5RGdtO4Ku3fG1lV4J2UoWJ9yD
awdoyYIHAkEAzn0xJ90IjPsHk+8SODEj5JGdHSZPNu1tgtrbjEi9sfGWg4K7XTxr
QW90pWb1bKKU1uh5FzW6OhnFfuQXt1kC7QJAPSthqY+onKqCEnoxhtAHi/bKgyvl
P+fKQwPMV2tKkgy+XwvJjrRqqZ8TqsOKVLQ+QQmCh6RpjiXMPyxHSmvqIQJBAKLR
HF1ucDuaBROkwx0DwmWMW/KMLpIFDQDNSaiIAuu4rxHrl4mhBoGGPNffI04RtILw
s+qVNs5xW8T+XaT4ztECQQDFHPnZeoPWE5z+AX/UUQIUWaDExz3XRzmIxRbOrlFi
CsF1s0TdJLi/wzNQRAL37A8vqCeVFR/ng3Xpg96Yg+8Z
-----END RSA PRIVATE KEY-----

EOD;
		}
		
		$fd = fopen("{$g['varetc_path']}/cert.pem", "w");
		if (!$fd) {
			printf("Error: cannot open cert.pem in system_webgui_start().\n");
			return 1;
		}
		chmod("{$g['varetc_path']}/cert.pem", 0600);
		fwrite($fd, $cert);
		fwrite($fd, "\n");
		fwrite($fd, $key);
		fclose($fd);
	
		$res = mwexec("/sbin/mini_httpd -S -E {$g['varetc_path']}/cert.pem" .
			" -c \"**.php|**.cgi\" -u root -maxproc 16 $portarg" . 
			" -i {$g['varrun_path']}/mini_httpd.pid -h $lanip");
	
	if ($g['booting']) {
		if ($res == 0)
			echo "done\n";
		else
			echo "failed\n";
	}
	
	return $res;
}

function system_password_configure() {
	global $config, $g;
	
	$fd = fopen("{$g['varrun_path']}/htpasswd", "w");
	if (!$fd) {
		printf("Error: cannot open htpasswd in system_password_configure().\n");
		return 1;
	}
	
	if ($config['system']['username'])
		$username = $config['system']['username'];
	else
		$username = "admin";
	
	fwrite($fd, $username . ":" . $config['system']['password'] . "\n");

	if (is_array($config['system']['accounts']['user'])) {
		foreach ($config['system']['accounts']['user'] as $userent) {
			    fwrite($fd, $userent['name'] . ":" 
		              . $userent['password'] . "\n");
		}
	}
	
	fclose($fd);
	chmod("{$g['varrun_path']}/htpasswd", 0600);
 	#symlink("{$g['varrun_path']}/htpasswd","{$g['www_path']}/.htpasswd");	
	return 0;
}

function system_timezone_configure() {
	global $config, $g;

	$syscfg = $config['system']['general'];

	if ($g['booting'])
		echo "Initializing timezone... ";

	/* extract appropriate timezone file */
	$timezone = $syscfg['timezone'];
	if (!$timezone)
		$timezone = "Etc/UTC";
		
	exec("/bin/tar xzfs /usr/share/zoneinfo.tgz :" . 
		escapeshellarg($timezone) . ":/etc/localtime: " . escapeshellarg($timezone));

	if ($g['booting'])
		echo "done\n";
}

function system_ntp_configure() {
	global $config, $g;

	$syscfg = $config['system']['general'];

	if ($g['booting'])
		echo "Starting NTP client... ";
	else {
		mwexec("/usr/bin/pkill ntpd");
	}

	$ntpconf = "";
	foreach (explode(' ', $syscfg['timeservers']) as $ts)
                        $ntpconf .= "servers " . $ts . "\n";

	/* write ntpd.conf */
        $fd = fopen("{$g['varetc_path']}/ntpd.conf", "w");
        if (!$fd) {
                printf("Error: cannot open ntpd.conf in system_ntp_configure().\n");
                return 1;
        }

	fwrite($fd, $ntpconf);
        fclose($fd);

	mwexec("/usr/sbin/ntpd -s -f {$g['varetc_path']}/ntpd.conf");
	
	if ($g['booting'])
		echo "done\n";
}

function system_reboot() {
	global $g;
	
	mwexec("nohup /etc/rc.reboot > /dev/null 2>&1 &");
}

function system_reboot_sync() {
	global $g;
	
	system_reboot();
}

function system_do_shell_commands($early = 0) {
	global $config, $g;
	
	if ($early)
		$cmdn = "earlyshellcmd";
	else
		$cmdn = "shellcmd";
	
	if (is_array($config['system']['general'][$cmdn])) {
		
		foreach ($config['system']['general'][$cmdn] as $cmd) {
			exec($cmd);
		}
	}
}

function system_do_extensions($early = false) {
	global $config, $g;
	
	if (!is_dir("{$g['etc_path']}/inc/ext"))
		return;
	
	$dh = @opendir("{$g['etc_path']}/inc/ext");
	if ($dh) {
		while (($extd = readdir($dh)) !== false) {
			if (($extd === ".") || ($extd === ".."))
				continue;
			$rcfile = "{$g['etc_path']}/inc/ext/" . $extd . "/" . ($early ? "rc.early" : "rc");
			if (file_exists($rcfile))
				passthru($rcfile);
		}
		closedir($dh);
	}
	
	// Create the symbolic links for .htpasswd and gui.css
	// in each www/ext directory.
	if (!$early) {
    	$dh = @opendir("{$g['www_path']}/ext");
    	if ($dh) {
    		while (($extd = readdir($dh)) !== false) {
    			if (($extd === ".") || ($extd === ".."))
    				continue;
    			if (is_dir("{$g['www_path']}/ext/$extd")) {
    			    // Create links
    			    symlink("{$g['www_path']}/htpasswd",".htpasswd");
    			    symlink("{$g['www_path']}/gui.css","gui.css");
    			}
    		}
    		closedir($dh);
    	}
	}
	
	
}

function system_console_configure() {
	global $config, $g;
	
	if (isset($config['system']['disableconsolemenu'])) {
		touch("{$g['varetc_path']}/disableconsole");
	} else {
		unlink_if_exists("{$g['varetc_path']}/disableconsole");
	}
}

function system_dmesg_save() {
	global $g;
	
	exec("/sbin/dmesg", $dmesg);
	
	/* find last copyright line (output from previous boots may be present) */
	$lastcpline = 0;
	
	for ($i = 0; $i < count($dmesg); $i++) {
		if (strstr($dmesg[$i], "OpenBSD "))
			$lastcpline = $i;
	}
	
	$fd = fopen("{$g['varlog_path']}/dmesg.boot", "w");
	if (!$fd) {
		printf("Error: cannot open dmesg.boot in system_dmesg_save().\n");
		return 1;
	}
	
	for ($i = $lastcpline; $i < count($dmesg); $i++)
		fwrite($fd, $dmesg[$i] . "\n");
	
	fclose($fd);
	
	return 0;
}

function system_set_harddisk_standby() {
	global $g, $config;

	if ($g['platform'] != "generic-pc")
		return;

	if (isset($config['system']['harddiskstandby']) && 
			($config['system']['harddiskstandby'] > 0)) {
		if ($g['booting']) {
			echo 'Setting harddisk standby time... ';
		}

		$standby = $config['system']['harddiskstandby'];
		// Check for a numeric value
		if (is_numeric($standby)) {
			// Sync the disk(s)
			mwexec('/bin/sync');
			if (!mwexec('/sbin/sysctl hw.ata.suspend=' . ((int)$standby*60))) {
				// Reinitialize ATA-drives
				mwexec('/usr/local/sbin/atareinit');
				if ($g['booting']) {
					echo "done\n";
				}
			} else if ($g['booting']) {
				echo "failed\n";
			}
		} else if ($g['booting']) {
			echo "failed\n";
		}
	}
}

function system_polling_configure() {
	global $config, $g;
	
	if (isset($config['system']['general']['polling'])) {
		mwexec("/sbin/sysctl kern.polling.enable=1");
	} else {
		mwexec("/sbin/sysctl kern.polling.enable=0");
	}
}

function system_set_termcap() {
	global $config;
	
	if (isset($config['diag']['ipfstatentries'])) {
		$lines = $config['diag']['ipfstatentries'] + 6;
	}
	else {
		$lines = 306;
	}
	
	$termcap = <<<EOD
cons25w|ansiw|ansi80x25-raw:\
	:am:bs:NP:ms:pt:AX:eo:bw:ut:km:\
	:co#80:li#25:pa#64:Co#8:it#8:\
	:al=\E[L:cd=\E[J:ce=\E[K:cl=\E[H\E[J:cm=\E[%i%d;%dH:\
	:dc=\E[P:dl=\E[M:do=\E[B:bt=\E[Z:ho=\E[H:ic=\E[@:cb=\E[1K:\
	:nd=\E[C:rs=\Ec:so=\E[7m:se=\E[27m:up=\E[A:cr=^M:ta=^I:\
	:AF=\E[3%dm:AB=\E[4%dm:op=\E[39;49m:sc=\E7:rc=\E8:\
	:k1=\E[M:k2=\E[N:k3=\E[O:k4=\E[P:k5=\E[Q:k6=\E[R:k7=\E[S:k8=\E[T:\
	:k9=\E[U:k;=\E[V:F1=\E[W:F2=\E[X:K2=\E[E:nw=\E[E:ec=\E[%dX:\
	:kb=^H:kh=\E[H:ku=\E[A:kd=\E[B:kl=\E[D:kr=\E[C:le=^H:sf=\E[S:sr=\E[T:\
	:kN=\E[G:kP=\E[I:@7=\E[F:kI=\E[L:kD=\\177:kB=\E[Z:\
	:IC=\E[%d@:DC=\E[%dP:SF=\E[%dS:SR=\E[%dT:AL=\E[%dL:DL=\E[%dM:\
	:DO=\E[%dB:LE=\E[%dD:RI=\E[%dC:UP=\E[%dA:cv=\E[%i%dd:ch=\E[%i%d`:\
	:mb=\E[5m:md=\E[1m:mr=\E[7m:me=\E[m:bl=^G:\
	:ve=\E[=S:vi=\E[=1S:vs=\E[=2S:
cons25|ansis|ansi80x25:\
	:ac=l\\332m\\300k\\277j\\331u\\264t\\303v\\301w\\302q\\304x\\263n\\305`^Da\\260f\\370g\\361~\\371.^Y-^Xh\\261i^U0\\333y\\363z\\362:\
	:tc=cons25w:
dumb|su|unknown:\
	:am:co#132:li#$lines:do=^J:
xterm-noapp|xterm with cursor keys in normal mode:\
	:kl=\E[D:kd=\E[B:kr=\E[C:ku=\E[A:ks=\E=:ke=\E>:ti@:te@:tc=xterm:
xterm|xterm-color|X11 terminal emulator:\
	:ti@:te@:tc=xterm-xfree86:
xterm-xfree86|XFree86 xterm:\
	:k1=\EOP:k2=\EOQ:k3=\EOR:k4=\EOS:\
	:k5=\E[15~:k6=\E[17~:k7=\E[18~:k8=\E[19~:\
	:k9=\E[20~:k;=\E[21~:F1=\E[23~:F2=\E[24~:\
	:kH=\EOF:@7=\EOF:kI=\E[2~:\
	:kh=\EOH:*6=\EOF:kP=\E[5~:kN=\E[6~:\
	:ku=\EOA:kd=\EOB:kr=\EOC:kl=\EOD:Km=\E[M:tc=xterm-basic:
xterm-basic|xterm common (XFree86):\
	:li#24:co#80:am:kn#12:km:mi:ms:xn:bl=^G:\
	:is=\E[!p\E[?3;4l\E[4l\E>:rs=\E[!p\E[?3;4l\E[4l\E>:le=^H:\
	:AL=\E[%dL:DL=\E[%dM:DC=\E[%dP:al=\E[L:dc=\E[P:dl=\E[M:\
	:UP=\E[%dA:DO=\E[%dB:LE=\E[%dD:RI=\E[%dC:\
	:ho=\E[H:cd=\E[J:ce=\E[K:cl=\E[H\E[2J:cm=\E[%i%d;%dH:cs=\E[%i%d;%dr:\
	:im=\E[4h:ei=\E[4l:ks=\E[?1h\E=:ke=\E[?1l\E>:kD=\E[3~:kb=^H:\
	:sf=\n:sr=\EM:st=\EH:ct=\E[3g:sc=\E7:rc=\E8:\
	:eA=\E(B\E)0:as=^N:ae=^O:ml=\El:mu=\Em:up=\E[A:nd=\E[C:\
	:md=\E[1m:me=\E[m^O:mr=\E[7m:so=\E[7m:se=\E[27m:us=\E[4m:ue=\E[24m:\
	:ti=\E[?1049h:te=\E[?1049l:vi=\E[?25l:ve=\E[?25h:\
	:ut:Co#8:pa#64:op=\E[39;49m:AB=\E[4%dm:AF=\E[3%dm:\

EOD;

	if (!file_exists("/usr/share/misc"))
		mkdir("/usr/share/misc");

	$fd = @fopen("/usr/share/misc/termcap", "w");
	if (!$fd) {
		printf("Error: cannot open termcap in system_set_termcap().\n");
		return 1;
	}
	chmod("/usr/share/misc/termcap", 0644);
	fwrite($fd, $termcap);
	fclose($fd);
	
	return 0;
}

?>
