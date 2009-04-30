<?php
/*
	$Id: guiconfig.inc,v 1.41 2009/04/20 06:59:37 jrecords Exp $
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

/* make sure nothing is cached */
if (!$omit_nocacheheaders) {
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
}

/* parse the configuration and include all configuration functions */
require_once("config.inc");
require_once("functions.inc");

/* ensure the user is authorized to view the current page */
require_once("auth.inc");

$d_natconfdirty_path = $g['varrun_path'] . "/nat.conf.dirty";
$d_relaydconfdirty_path = $g['varrun_path'] . "/relay.conf.dirty";
$d_filterconfdirty_path = $g['varrun_path'] . "/filter.conf.dirty";
$d_ipsecconfdirty_path = $g['varrun_path'] . "/ipsec.conf.dirty";
$d_shaperconfdirty_path = $g['varrun_path'] . "/shaper.conf.dirty";
$d_pptpuserdirty_path = $g['varrun_path'] . "/pptpd.user.dirty";
$d_dnsmasqdirty_path = $g['varrun_path'] . "/dnsmasq.dirty";
$d_staticmapsdirty_path = $g['varrun_path'] . "/staticmaps.dirty";
$d_staticroutesdirty_path = $g['varrun_path'] . "/staticroutes.dirty";
$d_aliasesdirty_path = $g['varrun_path'] . "/aliases.dirty";
$d_proxyarpdirty_path = $g['varrun_path'] . "/proxyarp.dirty";
$d_fwupenabled_path = $g['varrun_path'] . "/fwup.enabled";
$d_firmwarelock_path = $g['varrun_path'] . "/firmware.lock";
$d_sysrebootreqd_path = $g['varrun_path'] . "/sysreboot.reqd";
$d_passthrumacsdirty_path = $g['varrun_path'] . "/passthrumacs.dirty";
$d_allowedipsdirty_path = $g['varrun_path'] . "/allowedips.dirty";
$d_pptpclient_pid = $g['varrun_path'] . "/tun0.pid";


if (file_exists($d_firmwarelock_path)) {
	if (!$d_isfwfile) {
		header("Location: system_firmware.php");
		exit;
	} else {
		return;
	}
}

/* some well knows ports */
$wkports = array(21 => "FTP", 22 => "SSH", 23 => "Telnet", 25 => "SMTP", 53 => "DNS", 80 => "HTTP",
				 110 => "POP3", 143 => "IMAP", 443 => "HTTPS");

$iptos = array("lowdelay", "throughput", "reliability", "mincost", "congestion");
/* TCP flags */
$tcpflags = array("fin", "syn", "rst", "psh", "ack", "urg");

$altqbuckets = array("highest", "high", "medium", "low");

$srctracktypes = array("none", "rule", "global");

$statetypes = array("none", "keep state", "modulate state", "synproxy state");

$specialnets = array("wanip" => "WAN address", "lan" => "LAN net", "pptp" => "PPTP clients");

$healthchecks = array("http", "https", "icmp", "data", "ssl", "tcp");

$relayalgs = array("hash", "loadbalance", "roundrobin");

$relayprotos = array("http", "tcp", "dns");

$protoaction3 = array("append", "change", "expect", "filter", "hash", "remove", "log");

$protoaction4 = array("ciphers", "session cache", "sslv2", "sslv3", "tlsv1");

$protoaction5 = array("backlog", "ip minttl", "ip ttl", "no delay", "sack", "socket buffer");

for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
	$specialnets['opt' . $i] = $config['interfaces']['opt' . $i]['descr'] . " net";
}
	
$medias = array("auto" => "autoselect", "100full" => "100BASE-TX full-duplex",
	"100half" => "100BASE-TX half-duplex", "10full" => "10BASE-T full-duplex",
	"10half" => "10BASE-T half-duplex", "1000full" => "1000BASE-TX full-duplex");
	
/* platforms that support firmware updating */
$fwupplatforms = array('COMMELL-LE564', 'WJ-200', 'FBII', 'FBIII', 'FBX500-2500', 'GENERIC', 'PCENGINES', 'SOEKRIS4501', 'SOEKRIS4521', 'SOEKRIS4801', 'SOEKRIS5501', 'VMWARE', 'WRAP12');

/* IPsec defines */
$my_identifier_list = array('myaddress' => 'My IP address',
							'address' => 'IP address',
							'fqdn' => 'Domain name',
							'user_fqdn' => 'User FQDN');

$p1_ealgos = array('des' => 'DES', '3des' => '3DES', 'aes' => 'AES', 'aes-128' => 'AES-128', 'aes-192' => 'AES-192', 'aes-256' => 'AES-256', 'blowfish' => 'Blowfish', 
					'cast' => 'CAST', 'SKIPJACK' => 'skipjack' );
$p2_ealgos = array('des' => 'DES', '3des' => '3DES', 'aes' => 'AES', 'aes-128' => 'AES-128', 'aes-192' => 'AES-192', 'aes-256' => 'AES-256', 'aesctr' => 'AESCTR', 'blowfish' => 'BLOWFISH', 
                                        'cast' => 'CAST', 'SKIPJACK' => 'SKIPJACK', 'none' => 'NONE' );
$p1_halgos = array('hmac-md5' => 'MD5', 'hmac-sha1' => 'SHA1', 'hmac-sha2-256' => 'SHA2-256', 'hmac-sha2-384' => 'SHA2-384', 'hmac-sha2-512' => 'SHA2-512');
$p1_authentication_methods = array('pre_shared_key' => 'Pre-shared key', 'rsasig' => 'RSA signature');
$p2_halgos = array('hmac-md5' => 'MD5', 'hmac-ripemd160' => 'RIPEMD160', 'hmac-sha1' => 'SHA1', 'hmac-sha2-256' => 'SHA2-256', 'hmac-sha2-384' => 'SHA2-384', 'hmac-sha2-512' => 'SHA2-512');
$p2_protos = array('esp' => 'ESP', 'ah' => 'AH');
$p2_pfskeygroups = array('0' => 'off', '1' => '1', '2' => '2', '5' => '5', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18');

function do_input_validation($postdata, $reqdfields, $reqdfieldsn, $input_errors) {
	
	/* check for bad control characters */	
	foreach ($postdata as $pn => $pd) {
		if (is_string($pd) && preg_match("/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f]/", $pd)) {
			$input_errors[] = "The field '" . $pn . "' contains invalid characters.";
		}
	}
	
	for ($i = 0; $i < count($reqdfields); $i++) {
		if (!isset($_POST[$reqdfields[$i]]) || ($_POST[$reqdfields[$i]] === "")) {
			$input_errors[] = "The field '" . $reqdfieldsn[$i] . "' is required.";
		}
	}	
}

function spacepad($string, $length) {
	for($i=0; $i<($length - strlen($string)); $i++) {
		echo "&nbsp;";
	}
} 

function print_input_errors($input_errors) {
	echo "<p><table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" width=\"100%\">\n";
	echo "<tr><td bgcolor=\"#A12A2A\" width=\"36\" align=\"center\" valign=\"top\"><img src=\"/err.gif\" width=\"28\" height=\"32\"></td>\n";
	echo "<td bgcolor=\"#FFD9D1\" style=\"padding-left: 8px; padding-top: 6px\">";
	
	echo "<span class=\"errmsg\"><p>The following input errors were detected:<ul>\n";
	foreach ($input_errors as $ierr) {
		echo "<li>" . htmlspecialchars($ierr) . "</li>\n";
	}
	echo "</ul></span>";
	
	echo "</td></tr></table></p>";
}

function exec_rc_script($scriptname) {
	
	global $d_sysrebootreqd_path;
	
	if (file_exists($d_sysrebootreqd_path))
		return 0;
	
	exec($scriptname . " >/dev/null 2>&1", $execoutput, $retval);
	return $retval; 
}

function exec_rc_script_async($scriptname) {
	
	global $d_sysrebootreqd_path;
	
	if (file_exists($d_sysrebootreqd_path))
		return 0;
		
	exec("nohup " . $scriptname . " >/dev/null 2>&1 &", $execoutput, $retval);
	return $retval; 
}

function verify_gzip_file($fname) {

	$returnvar = mwexec("/usr/bin/gunzip -S -t " . escapeshellarg($fname));
	if ($returnvar == 0)
		return 0;
	else
		return 1;
}

function print_info_box_np($msg) {
	echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" width=\"100%\">\n";
	echo "<tr><td bgcolor=\"#687BA4\" align=\"center\" valign=\"top\" width=\"36\"><img src=\"/exclam.gif\" width=\"28\" height=\"32\"></td>\n";
	echo "<td bgcolor=\"#D9DEE8\" style=\"padding-left: 8px\">";
	echo $msg;
	echo "</td></tr></table>";
}

function print_info_box($msg) {
	echo "<p>";
	print_info_box_np($msg);
	echo "</p>";
}

function format_bytes($bytes) {
	if ($bytes >= 1073741824) {
		return sprintf("%.2f GB", $bytes/1073741824);
	} else if ($bytes >= 1048576) {
		return sprintf("%.2f MB", $bytes/1048576);
	} else if ($bytes >= 1024) {
		return sprintf("%.0f KB", $bytes/1024);
	} else {
		return sprintf("%d bytes", $bytes);
	}
}

function get_std_save_message($ok) {
	global $d_sysrebootreqd_path;

	if ($ok == 0) {
		if (file_exists($d_sysrebootreqd_path))
			return "The changes have been saved. You must <a href=\"/reboot.php\">reboot</a> your firewall for changes to take effect.";
		else
			return "The changes have been applied successfully.";
	} elseif ($ok == 60) {
		return "The changes have been applied successfully to the localhost, but failed to save to all sync hosts, see logs.";
	} else {
		return "ERROR: the changes could not be applied (error code $ok).";
	}
}

function pprint_address($adr) {
	global $specialnets;

	if (isset($adr['any'])) {
		$padr = "*";
	} else if ($adr['network']) {
		$padr = $specialnets[$adr['network']];
	} else {
		$padr = $adr['address'];
	}
	
	if (isset($adr['not']))
		$padr = "! " . $padr;
	
	return $padr;
}

function pprint_port($port) {
	global $wkports;

	$pport = "";
		
	if (!$port)
		echo "*";
	else {
		$srcport = explode("-", $port);
		if ((!$srcport[1]) || ($srcport[0] == $srcport[1])) {
			$pport = $srcport[0];
			if ($wkports[$srcport[0]]) {
				$pport .= " (" . $wkports[$srcport[0]] . ")";
			}
		} else
			$pport .= $srcport[0] . " - " . $srcport[1];
	}
	
	return $pport;
}

/* sort by interface only, retain the original order of rules that apply to
   the same interface */
function filter_rules_sort() {
	global $g, $config;
	
	/* mark each rule with the sequence number (to retain the order while sorting) */
	for ($i = 0; isset($config['filter']['rule'][$i]); $i++)
		$config['filter']['rule'][$i]['seq'] = $i;
	
	function filtercmp($a, $b) {
		if ($a['interface'] == $b['interface'])
			return $a['seq'] - $b['seq'];
		else
			return -strcmp($a['interface'], $b['interface']);
	}
	
	usort($config['filter']['rule'], "filtercmp");
	
	/* strip the sequence numbers again */
	for ($i = 0; isset($config['filter']['rule'][$i]); $i++)
		unset($config['filter']['rule'][$i]['seq']);
}

function nat_rules_sort() {
	global $g, $config;
	
	function natcmp($a, $b) {
		if ($a['external-address'] == $b['external-address']) {
			if ($a['protocol'] == $b['protocol']) {
				if ($a['external-port'] == $b['external-port']) {
					return 0;
				} else {
					return ($a['external-port'] - $b['external-port']);
				}
			} else {
				return strcmp($a['protocol'], $b['protocol']);
			}
		} else if (!$a['external-address'])
			return 1;
		else if (!$b['external-address'])
			return -1;
		else
			return ipcmp($a['external-address'], $b['external-address']);
	}
	
	usort($config['nat']['rule'], "natcmp");
}

function nat_1to1_rules_sort() {
	global $g, $config;
	
	function nat1to1cmp($a, $b) {
		return ipcmp($a['external'], $b['external']);
	}
	
	usort($config['nat']['onetoone'], "nat1to1cmp");
}

function relays_sort() {
        global $g, $config;

        function relaycmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }

        usort($config['relays']['relay'], "relaycmp");
}

function virtualhosts_sort() {
        global $g, $config;

        function virtualhostscmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }

        usort($config['carp']['virtualhost'], "virtualhostscmp");
}

function configsynchosts_sort() {
        global $g, $config;

        function configsynchostscmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }

        usort($config['carp']['configsynchost'], "configsynchostscmp");
}

function system_cert_sort() {
        global $g, $config;

        function certcmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }   

        usort($config['system']['certmgr']['cert'], "certcmp");
}

function system_ca_sort() {
        global $g, $config;

        function cacmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }

        usort($config['system']['certmgr']['ca'], "cacmp");
}

function sslcerts_sort() {
        global $g, $config;

        function sslcertcmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }   

        usort($config['relays']['sslcert'], "sslcertcmp");
}

function proxyactions_sort() {
        global $g, $config;

        function protocmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }   

        usort($config['relays']['proxyaction'], "protocmp");
}

function nat_server_rules_sort() {
	global $g, $config;
	
	function natservercmp($a, $b) {
		return ipcmp($a['ipaddr'], $b['ipaddr']);
	}
	
	usort($config['nat']['servernat'], "natservercmp");
}

function nat_out_rules_sort() {
	global $g, $config;
	
	function natoutcmp($a, $b) {
		return strcmp($a['source']['network'], $b['source']['network']);
	}
	
	usort($config['nat']['advancedoutbound']['rule'], "natoutcmp");
}

function trunks_sort() {
        global $g, $config;

        function trunkcmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }

        usort($config['trunks']['trunk'], "trunkcmp");
}

function pptpd_users_sort() {
	global $g, $config;
	
	function usercmp($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	}
	
	usort($config['pptpd']['user'], "usercmp");
}

function captiveportal_users_sort() {
	global $g, $config;
	
	function cpusercmp($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	}
	
	usort($config['captiveportal']['user'], "cpusercmp");
}

function admin_groups_sort() {
	global $g, $config;
	
	function cpusercmp($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	}
	
	usort($config['system']['accounts']['group'], "cpusercmp");
}

function admin_users_sort() {
	global $g, $config;
	
	function cpusercmp($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	}
	
	usort($config['system']['accounts']['user'], "cpusercmp");
}

function staticroutes_sort() {
	global $g, $config;

	function staticroutecmp($a, $b) {
		return strcmp($a['network'], $b['network']);
	}

	usort($config['staticroutes']['route'], "staticroutecmp");
}

function hosts_sort() {
	global $g, $config;

	function hostcmp($a, $b) {
		if (strcasecmp($a['domain'], $b['domain']) == 0)
			return strcasecmp($a['host'], $b['host']);
		else
			return strcasecmp($a['domain'], $b['domain']);
	}

	usort($config['dnsmasq']['hosts'], "hostcmp");
}

function domainoverrides_sort() {
	global $g, $config;

	function domainoverridescmp($a, $b) {
		return strcasecmp($a['domain'], $b['domain']);
	}

	usort($config['dnsmasq']['domainoverrides'], "domainoverridescmp");
}

function staticmaps_sort($if) {
	global $g, $config;

	function staticmapcmp($a, $b) {
		return ipcmp($a['ipaddr'], $b['ipaddr']);
	}

	usort($config['dhcpd'][$if]['staticmap'], "staticmapcmp");
}

function aliases_sort() {
	global $g, $config;

	function aliascmp($a, $b) {
		return strcmp($a['name'], $b['name']);
	}

	usort($config['aliases']['alias'], "aliascmp");
}

function table_sort() {
        global $g, $config;

        function tablecmp($a, $b) {
                return strcmp($a['name'], $b['name']);
        }   

        usort($config['relayd']['tables']['table'], "tablecmp");
}

function ipsec_mobilekey_sort() {
	global $g, $config;

	function mobilekeycmp($a, $b) {
		return strcmp($a['ident'][0], $b['ident'][0]);
	}

	usort($config['ipsec']['mobilekey'], "mobilekeycmp");
}

function proxyarp_sort() {
	global $g, $config;

	function proxyarpcmp($a, $b) {
		if (isset($a['network']))
			list($ast,$asn) = explode("/", $a['network']);
		else if (isset($a['range'])) {
			$ast = $a['range']['from'];
			$asn = 32;
		}
		if (isset($b['network']))
			list($bst,$bsn) = explode("/", $b['network']);
		else if (isset($b['range'])) {
			$bst = $b['range']['from'];
			$bsn = 32;
		}
		if (ipcmp($ast, $bst) == 0)
			return ($asn - $bsn);
		else
			return ipcmp($ast, $bst);
	}

	usort($config['proxyarp']['proxyarpnet'], "proxyarpcmp");
}

function passthrumacs_sort() {
	global $g, $config;

	function passthrumacscmp($a, $b) {
		return strcmp($a['mac'], $b['mac']);
	}
	
	usort($config['captiveportal']['passthrumac'],"passthrumacscmp");
}

function allowedips_sort() {
	global $g, $config;

	function allowedipscmp($a, $b) {
		return strcmp($a['ip'], $b['ip']);
	}
	
	usort($config['captiveportal']['allowedip'],"allowedipscmp");
}

function cpelements_sort() {
	global $g, $config;

	function cpelementscmp($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	}
	
	usort($config['captiveportal']['element'],"cpelementscmp");
}

function wol_sort() {
	global $g, $config;

	function wolcmp($a, $b) {
		return strcmp($a['descr'], $b['descr']);
	}
	
	usort($config['wol']['wolentry'], "wolcmp");
}

function ipsec_ca_sort() {
	global $g, $config;

	function ipseccacmp($a, $b) {
		return strcmp($a['ident'], $b['ident']);
	}

	usort($config['ipsec']['cacert'], "ipseccacmp");
}

function dynamic_tab_menu(&$tabs) {
	global $allowed, $config;
	
	if ($_SERVER['REMOTE_USER'] == $config['system']['username']) {
		$authorized = &$tabs;
	} else {
		$authorized = &$allowed;
	}
	
	// Below needed to add the "1" onto the end of the class name for the first tab, if it is inactive.
    $linkStyle = '1';
   	foreach ($tabs as $desc => $link) {
   		if (in_array($link,$authorized)) {
            if ($link == basename($_SERVER['PHP_SELF'])) {
   				// special handling for diagnostic Logs tabs.
   				if ((strpos($link,'diag_logs') > -1) && ($link != 'diag_logs_settings.php')) {
	   				if ($desc == "Firewall") {
	       				echo '<li class="tabact"><a href="diag_logs_filter.php" style="color:black" title="reset filter and reload firewall logs page">Firewall</a></li>';
	   				}
	   				else {
	   					echo '<li class="tabact"><a href="'.$link.'" style="color:black" title="reload page">'.$desc.'</a></li>';
	   				}
	   			}
       			else {
       				echo '<li class="tabact">'.$desc.'</li>';
       			}
   				$linkStyle = '';
   			}
   			else {
   				echo '<li class="tabinact'.$linkStyle.'"><a href="'.$link.'">'.$desc.'</a></li>';
   				$linkStyle = '';
   			}
   		}
    }	
 }

?>
