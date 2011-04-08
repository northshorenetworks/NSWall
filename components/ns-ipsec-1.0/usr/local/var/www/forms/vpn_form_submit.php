#!/bin/php
<?php
/*
 Northshore Software Header
 */

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		case "vpn_pptp_client":

			if ($_POST['submit'] == "Disconnect")
			return vpn_pptp_client_disconnect();

			if ($_POST['submit'] == "Connect")
			return vpn_pptp_client_connect();

			unset($input_errors);

			/* input validation */
			/* TODO */

			if (!$input_errors) {
				$config['pptp']['client']['enable'] = $_POST['enable'] ? true : false;
				$config['pptp']['client']['connectonboot'] = $_POST['connectonboot'] ? true : false;
				$config['pptp']['client']['server'] = $_POST['server'];
				$config['pptp']['client']['username'] = $_POST['username'];
				$config['pptp']['client']['password'] = $_POST['password'];
				unset($config['pptp']['client']['routelist']);
				$routelist = array_reverse(explode(' ', $_POST['routelist']));
				for($i=0;$i<sizeof($routelist); $i++) {
					$routeno = 'route'."$i";
					$dest = preg_replace("/ /", "", $routelist[$i]);
					$config['pptp']['client']['routelist'][$routeno] = $dest;
				}
				$config['pptp']['client']['autonat'] = $_POST['autonat'] ? true : false;
				$config['pptp']['client']['lcplog'] = $_POST['lcplog'] ? true : false;
				write_config();

				$retval = 0;
				if (!file_exists($d_sysrebootreqd_path)) {
					config_lock();
					$retval = vpn_pptp_configure();
					config_unlock();
				}
				$savemsg = get_std_save_message($retval);
				if ($retval == 0) {
					sleep(2);
					echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
				}
			}
			return 0;
		case "vpn_ipsec_gw":
			if ($_POST) {
				if (is_numeric($_POST['id']))
				$id = $_POST['id'];

				if (!is_array($config['ipsec']['gw']))
				$config['ipsec']['gw'] = array();

				$a_ipsec = &$config['ipsec']['gw'];
				unset($input_errors);
				$pconfig = $_POST;

				if ($_POST['p1authentication_method'] == "pre_shared_key") {
					$reqdfields = explode(" ", "ipsecroutelist remotegw p1pskey p2ealgo p2halgo name");
					$reqdfieldsn = explode(",", "Address Policies,Remote gw,Pre-Shared Key,P2 Encryption Algorithms,P2 Hash Algorithms, Name");
				}
				else {
					$reqdfields = explode(" ", "remotegw");
					$reqdfieldsn = explode(",", "Remote gw");
					if ($_POST['p1peercert']!="" && (!strstr($_POST['p1peercert'], "BEGIN CERTIFICATE") || !strstr($_POST['p1peercert'], "END CERTIFICATE")))
					$input_errors[] = "This peer certificate does not appear to be valid.";
				}

				do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

				if (($_POST['localnetmask'] && !is_numeric($_POST['localnetmask']))) {
					$input_errors[] = "A valid local network bit count must be specified.";
				}
				if (($_POST['p1lifetime'] && !is_numeric($_POST['p1lifetime']))) {
					$input_errors[] = "The P1 lifetime must be an integer.";
				}
				if (($_POST['p2lifetime'] && !is_numeric($_POST['p2lifetime']))) {
					$input_errors[] = "The P2 lifetime must be an integer.";
				}
				if ($_POST['remotebits'] && (!is_numeric($_POST['remotebits']) || ($_POST['remotebits'] < 0) || ($_POST['remotebits'] > 32))) {
					$input_errors[] = "The remote network bits are invalid.";
				}
				if ($_POST['p1myidentt'] == "user_fqdn") {
					$ufqdn = explode("@",$_POST['p1myident']);
					if (!is_domain($ufqdn[1]))
					$input_errors[] = "A valid User FQDN in the form of user@my.domain.com for 'My identifier' must be specified.";
				}

				if ($_POST['p1myidentt'] == "myaddress")
				$_POST['p1myident'] = "";

				if (!$input_errors) {
					$ipsecent['name'] = $_POST['name'];
					$ipsecent['disabled'] = $_POST['disabled'] ? true : false;
                                        $ipsecent['descr'] = $_POST['descr'];
					$ipsecent['authentication_type'] = $pconfig['authentication_type'];
					$srclist = array_reverse(explode(' ', $_POST['srclist']));
					for($i=0;$i<sizeof($srclist); $i++) {
						$member = 'src'."$i";
						$source = preg_replace("/ /", "", $srclist[$i]);
						$ipsecent['srclist'][$member] = $source;
					}
					$dstlist = array_reverse(explode(' ', $_POST['dstlist']));
					for($i=0;$i<sizeof($dstlist); $i++) {
						$member = 'dst'."$i";
						$dest = preg_replace("/ /", "", $dstlist[$i]);
						$ipsecent['dstlist'][$member] = $dest;
					}

                                        if ($ipsecent['authentication_type'] == 'manual') {
                                                $ipsecent['myident']['myaddress'] = $_POST['myaddress'];
					        $ipsecent['remote-gw'] = $_POST['remotegw'];
                                                $ipsecent['encryption-algorithm'] = $_POST['encryption-algorithm'];
                                                $ipsecent['auth-algorithm'] = $_POST['auth-algorithm'];
					        $ipsecent['spi'] = $_POST['spi'];
					        $ipsecent['authkey'] = $_POST['authkey'];
					        $ipsecent['enckey'] = $_POST['enckey'];	
                                        } elseif ($ipsecent['authentication_type'] == 'auto') {
                                                $ipsecent['myident']['myaddress'] = $_POST['myaddress'];
                                                $ipsecent['remote-gw'] = $_POST['remotegw'];
                                                $ipsecent['p1']['mode'] = $_POST['p1mode'];
					        $ipsecent['p1']['encryption-algorithm'] = $_POST['p1ealgo'];
					        $ipsecent['p1']['hash-algorithm'] = $_POST['p1halgo'];
					        $ipsecent['p1']['dhgroup'] = $_POST['p1dhgroup'];
					        $ipsecent['p1']['lifetime'] = $_POST['p1lifetime'];
					        $ipsecent['p1']['pre-shared-key'] = base64_encode($_POST['p1pskey']);
					        $ipsecent['p2']['protocol'] = $_POST['p2proto'];
					        $ipsecent['p2']['encryption-algorithm'] = $_POST['p2ealgo'];
					        $ipsecent['p2']['hash-algorithm'] = $_POST['p2halgo'];
					        $ipsecent['p2']['pfsgroup'] = $_POST['p2pfsgroup'];
					        $ipsecent['p2']['lifetime'] = $_POST['p2lifetime'];
                                        }

					if (isset($id) && $a_ipsec[$id])
					$a_ipsec[$id] = $ipsecent;
					else
					$a_ipsec[] = $ipsecent;

					$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);

					if (vpn_ipsec_parse_config($config)) {
						$input_errors[] = "Could not parse the generated config file";
						$input_errors[] = "See log file for details";
						$input_errors[] = "XML Config file not modified";
					}

					$retval = 0;
					if (!$input_errors) {
						write_config();
						config_lock();
						$retval = vpn_ipsec_configure();
						config_unlock();
					}
				}
				if ($retval == 0 && !$input_errors) {
					sleep(2);
					echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
				} else {
					print_input_errors($input_errors);
					echo '<script type="text/javascript">
                        $("#okbtn").click(function () {
                            $("#save_config").dialog("close");
                        });
                        </script>';
					echo '<INPUT TYPE="button" value="OK" id="okbtn"></center>';
				}
			}
			return $retval;
		default;
		echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="$(\'#save_config\').dialog(\'close\')"></center>';
		return 0;
	}
}
if ($_GET) {
	$id = $_GET['id'];
	$action = $_GET['action'];
	$type = $_GET['type'];

	if ($type == 'ipsec_gw') {
		if (!is_array($config['ipsec']['gw']))
		$config['ipsec']['gw'] = array();

		vpn_ipsec_gateway_sort();
		$a_gw = &$config['ipsec']['gw'];
		if ($action == 'delete') {
			unset($a_gw[$id]);
			write_config();
		}
	}
}

?>
