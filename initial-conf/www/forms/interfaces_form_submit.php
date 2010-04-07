#!/bin/php
<?php 
/*
	Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		 case "interface_pfsync":
            if (!$input_errors) {
            	$config['pfsync']['pfsyncenable'] = $_POST['pfsyncenable'];
				$config['pfsync']['interface'] = $_POST['interface'];	
			}
            $retval = 0;
            write_config();
            if ($retval == 0) {
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
            return $retval;	
		case "interface_carp":
			if (!$input_errors) {
                $config['carp']['carpenable'] = $_POST['carpenable'] ? true : false;
                $config['carp']['preemptenable'] = $_POST['preemptenable'] ? true : false;
                $config['carp']['logenable'] = $_POST['logenable'] ? true : false;
                $config['carp']['arpbalance'] = $_POST['arpbalance'] ? true : false;
			}
			$retval = 0;
			write_config();
			if ($retval == 0) {
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
			return $retval;
	    case "interface_carp_vid":
		if (!is_array($config['carp']['virtualhost']))
        $config['carp']['virtualhost'] = array();
 
		virtualhosts_sort();
		$a_virtualhost = &$config['carp']['virtualhost'];
 
		$id = $_GET['id'];
		if (isset($_POST['id']))
        	$id = $_POST['id'];
 
		if (isset($_POST['after']))
        	$after = $_POST['after'];
 
		if (isset($_GET['dup'])) {
        	$id = $_GET['dup'];
        	$after = $_GET['dup'];
		}	
		
		unset($input_errors);
        unset($virtualhost['name']);
        unset($virtualhost['descr']);
        unset($virtualhost['ip']);
        unset($virtualhost['subnet']);
        unset($virtualhost['interface']);
        unset($virtualhost['password']);
		unset($virtualhost['carpmode']);
		unset($virtualhost['carphostmode']);
        unset($virtualhost['activemember']);
		unset($virtualhost['activenodes']);
		$pconfig = $_POST;
 
        /* input validation */
        $reqdfields = explode(" ", "name");
        $reqdfieldsn = explode(",", "Name,");
 
        do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
 
        $virtualhostent = array();
        $virtualhostent['name'] = $_POST['name'];
        $virtualhostent['descr'] = $_POST['descr'];
        $virtualhostent['ip'] = $_POST['ip'];
        $virtualhostent['subnet'] = $_POST['subnet'];
        $virtualhostent['interface'] = $_POST['interface'];
        $virtualhostent['password'] = $_POST['password'];
        $virtualhostent['carpmode'] = $_POST['carpmode'];
		$virtualhostent['carphostmode'] = $_POST['carphostmode'];
 		$virtualhostent['activemember'] = $_POST['activemember'];
		$virtualhostent['activenodes'] = $_POST['activenodes'];
 
		if (isset($id) && $a_virtualhost[$id])
                $a_virtualhost[$id] = $virtualhostent;
        else
                $a_virtualhost[] = $virtualhostent;
 
        if (!$input_errors) {
    		$retval = 0;    
	        write_config();
        } 
		if ($retval == 0) {
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
    return $retval;
	case "interface_wan":

  $wancfg = &$config['interfaces']['wan'];
  unset($input_errors);
  $pconfig = $_POST;
  
  /* input validation */
  if ($_POST['type'] == "Static") {
    $reqdfields = explode(" ", "ipaddr subnet gateway");
    $reqdfieldsn = explode(",", "IP address,Subnet bit count,Gateway");
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  } else if ($_POST['type'] == "PPPoE") {
    if ($_POST['pppoe_dialondemand']) {
      $reqdfields = explode(" ", "username password pppoe_dialondemand pppoe_idletimeout");
      $reqdfieldsn = explode(",", "PPPoE username,PPPoE password,Dial on demand,Idle timeout value");
    } else {
      $reqdfields = explode(" ", "username password");
      $reqdfieldsn = explode(",", "PPPoE username,PPPoE password");
    }
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  } else if ($_POST['type'] == "PPTP") {
    if ($_POST['pptp_dialondemand']) {
      $reqdfields = explode(" ", "pptp_username pptp_password pptp_local pptp_subnet pptp_remote pptp_dialondemand pptp_idletimeout");
      $reqdfieldsn = explode(",", "PPTP username,PPTP password,PPTP local IP address,PPTP subnet,PPTP remote IP address,Dial on demand,Idle timeout value");
    } else {
      $reqdfields = explode(" ", "pptp_username pptp_password pptp_local pptp_subnet pptp_remote");
      $reqdfieldsn = explode(",", "PPTP username,PPTP password,PPTP local IP address,PPTP subnet,PPTP remote IP address");
    }
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  } else if ($_POST['type'] == "BigPond") {
    $reqdfields = explode(" ", "bigpond_username bigpond_password");
    $reqdfieldsn = explode(",", "BigPond username,BigPond password");
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  }
  
  $_POST['spoofmac'] = str_replace("-", ":", $_POST['spoofmac']);
  
  if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
    $input_errors[] = "A valid IP address must be specified.";
  }
  if (($_POST['subnet'] && !is_numeric($_POST['subnet']))) {
    $input_errors[] = "A valid subnet bit count must be specified.";
  }
  if (($_POST['gateway'] && !is_ipaddr($_POST['gateway']))) {
    $input_errors[] = "A valid gateway must be specified.";
  }
  if (($_POST['pointtopoint'] && !is_ipaddr($_POST['pointtopoint']))) {
    $input_errors[] = "A valid point-to-point IP address must be specified.";
  }
  if (($_POST['provider'] && !is_domain($_POST['provider']))) {
    $input_errors[] = "The service name contains invalid characters.";
  }
  if (($_POST['pppoe_idletimeout'] != "") && !is_numericint($_POST['pppoe_idletimeout'])) {
    $input_errors[] = "The idle timeout value must be an integer.";
  }
  if (($_POST['spoofmac'] && !is_macaddr($_POST['spoofmac']))) {
    $input_errors[] = "A valid MAC address must be specified.";
  }
  if ($_POST['mtu'] && (($_POST['mtu'] < 576) || ($_POST['mtu'] > 1500))) {
    $input_errors[] = "The MTU must be between 576 and 1500 bytes.";
  }
  
  /* Wireless interface? */
  if (isset($optcfg['wireless'])) {
    $wi_input_errors = wireless_config_post();
    if ($wi_input_errors) {
      $input_errors = array_merge($input_errors, $wi_input_errors);
    }
  }
 
  if (!$input_errors) {
  
    unset($wancfg['ipaddr']);
    unset($wancfg['subnet']);
    unset($wancfg['gateway']);
    unset($wancfg['aliaslist']);
    unset($wancfg['pointtopoint']);
    unset($wancfg['dhcphostname']);
    unset($wancfg['mtu']);
    unset($wancfg['spoofmac']);
    unset($wancfg['media']);
	unset($wancfg['altqenable']);
	unset($wancfg['bandwidth']);
	unset($config['pppoe']['username']);
    unset($config['pppoe']['password']);
    unset($config['pppoe']['provider']);
    unset($config['pppoe']['ondemand']);
    unset($config['pppoe']['timeout']);
    unset($config['pptp']['username']);
    unset($config['pptp']['password']);
    unset($config['pptp']['local']);
    unset($config['pptp']['subnet']);
    unset($config['pptp']['remote']);
    unset($config['pptp']['ondemand']);
    unset($config['pptp']['timeout']);
    unset($config['bigpond']['username']);
    unset($config['bigpond']['password']);
    unset($config['bigpond']['authserver']);
    unset($config['bigpond']['authdomain']);
    unset($config['bigpond']['minheartbeatinterval']);
  
    if ($_POST['type'] == "Static") {
      $wancfg['ipaddr'] = $_POST['ipaddr'];
      $wancfg['subnet'] = $_POST['subnet'];
      $wancfg['gateway'] = $_POST['gateway'];
      if ($_POST['memberslist'] != '') {
          $aliaslist = explode(' ', $_POST['memberslist']);
          for($i=0;$i<sizeof($aliaslist); $i++) {
              $alias = 'alias'."$i";
              $prop = preg_replace("/ /", "", $aliaslist[$i]);
              $wancfg['aliaslist'][$alias] = $prop;
          }
      }
      if (isset($wancfg['ispointtopoint']))
        $wancfg['pointtopoint'] = $_POST['pointtopoint'];
    } else if ($_POST['type'] == "DHCP") {
      $wancfg['ipaddr'] = "dhcp";
      $wancfg['dhcphostname'] = $_POST['dhcphostname'];
    } else if ($_POST['type'] == "PPPoE") {
      $wancfg['ipaddr'] = "pppoe";
      $config['pppoe']['username'] = $_POST['username'];
      $config['pppoe']['password'] = $_POST['password'];
      $config['pppoe']['provider'] = $_POST['provider'];
      $config['pppoe']['ondemand'] = $_POST['pppoe_dialondemand'] ? true : false;
      $config['pppoe']['timeout'] = $_POST['pppoe_idletimeout'];
    }
     
    $wancfg['spoofmac'] = $_POST['spoofmac'];
    $wancfg['mtu'] = $_POST['mtu'];
    $wancfg['bandwidth'] = $_POST['bandwidth'];
	$wancfg['altqenable'] = $_POST['altqenable'] ? true : false;

    write_config();
    
    $retval = 0;
    
    config_lock();
    $retval = interfaces_wan_configure();
    config_unlock();

    
    $savemsg = get_std_save_message($retval);
  }

    if ($retval == 0) {
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
    return $retval;
    case "interface_lan":
     unset($input_errors);
$lancfg = &$config['interfaces']['lan'];
$pconfig = $_POST;
 
/* input validation */
$reqdfields = explode(" ", "ipaddr subnet");
$reqdfieldsn = explode(",", "IP address,Subnet bit count");
 
do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
 
if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
$input_errors[] = "A valid IP address must be specified.";
}
if (($_POST['subnet'] && !is_numeric($_POST['subnet']))) {
$input_errors[] = "A valid subnet bit count must be specified.";
}
 
/* Wireless interface? */
if (isset($optcfg['wireless'])) {
$wi_input_errors = wireless_config_post();
if ($wi_input_errors) {
$input_errors = array_merge($input_errors, $wi_input_errors);
}
}
 
if (!$input_errors) {
$lancfg['ipaddr'] = $_POST['ipaddr'];
$lancfg['subnet'] = $_POST['subnet'];
unset($lancfg['aliaslist']);
if ($_POST['memberslist'] != '') {
          $aliaslist = explode(' ', $_POST['memberslist']);
          for($i=0;$i<sizeof($aliaslist); $i++) {
              $alias = 'alias'."$i";
              $prop = preg_replace("/ /", "", $aliaslist[$i]);
              $lancfg['aliaslist'][$alias] = $prop;
          }
      }
$dhcpd_was_enabled = 0;
if (isset($config['dhcpd']['enable'])) {
unset($config['dhcpd']['enable']);
$dhcpd_was_enabled = 1;
}
 
write_config();

$retval = 0;
config_lock();
$retval = interfaces_lan_configure();
config_unlock();

if ($retval == 0) {
                    sleep(2);
					if (file_exists('/conf/set_wizard_initial')) {
          				conf_mount_rw();
						unlink('/conf/set_wizard_initial');
						conf_mount_ro();
					}
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
	case "interface_opt":
		unset($input_errors);
		unset($index);
		if ($_GET['index'])
    		$index = $_GET['index'];
		else if ($_POST['index'])
    		$index = $_POST['index'];

		if (!$index)
    		exit;

		$optcfg = &$config['interfaces']['opt' . $index];
		$pconfig = $_POST;

		if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) 
			$input_errors[] = "A valid IP address must be specified.";
		if (($_POST['subnet'] && !is_numeric($_POST['subnet'])))
			$input_errors[] = "A valid subnet bit count must be specified.";

		if (!$input_errors) {
			$optcfg['enable'] = $_POST['enable'] ? true : false;
		    $optcfg['descr'] = $_POST['descr']; 
			unset($optcfg['iftype']);
			$optcfg['iftype'] = $_POST['iftype'];
			unset($optcfg['wantype']);
            $optcfg['wantype'] = $_POST['wantype'];	
			if ($_POST['wantype'] == "DHCP") { 
				$optcfg['ipaddr'] = "dhcp";
			} else { 
				$optcfg['ipaddr'] = $_POST['ipaddr'];
				$optcfg['subnet'] = $_POST['subnet'];
			}
			unset($optcfg['gateway']);
			$optcfg['gateway'] = $_POST['gateway'];
			unset($optcfg['aliaslist']);
			if ($_POST['memberslist'] != '') {
          $aliaslist = explode(' ', $_POST['memberslist']);
          for($i=0;$i<sizeof($aliaslist); $i++) {
              $alias = 'alias'."$i";
              $prop = preg_replace("/ /", "", $aliaslist[$i]);
              $optcfg['aliaslist'][$alias] = $prop;
          }
      }
	  		 $optcfg['bandwidth'] = $_POST['bandwidth'];
			 $optcfg['altqenable'] = $_POST['altqenable'] ? true : false;
		 	/* Wireless interface? */
			if (isset($optcfg['wireless'])) {
				unset($optcfg['wireless']);
 
				$optcfg['wireless']['ifmode'] = $_POST['ifmode'];
				$optcfg['wireless']['ssid'] = $_POST['ssid'];
				$optcfg['wireless']['channel'] = $_POST['channel'];
				$optcfg['wireless']['encmode'] = $_POST['encmode'];
				if ($optcfg['wireless']['encmode'] == 'wpa') {
          			    $optcfg['wireless']['wpamode'] = $_POST['wpamode'];
		                    $optcfg['wireless']['wpacipher'] = $_POST['wpacipher'];
       			            $optcfg['wireless']['wpapsk'] = $_POST['wpapsk'];
				}
				if ($optcfg['wireless']['encmode'] == 'wep') {
					$optcfg['wireless']['wep']['enable'] = $_POST['wep_enable'] ? true : false;
					$optcfg['wireless']['wep']['key'] = array();
					for ($i = 1; $i <= 4; $i++) {
						if ($_POST['key' . $i]) {
							$newkey = array();
							$newkey['value'] = $_POST['key' . $i];
							if ($_POST['txkey'] == $i)
								$newkey['txkey'] = true;
							$optcfg['wireless']['wep']['key'][] = $newkey;
						}
					}
				}
			}
		}	

		$dhcpd_was_enabled = 0;
		if (isset($config['dhcpd']['enable'])) {
			unset($config['dhcpd']['enable']);
			$dhcpd_was_enabled = 1;
		}

		write_config();

		$retval = 0;
		config_lock();
			$retval = interfaces_optional_configure($index);
		config_unlock();

		if ($retval == 0) {
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
}
?>
