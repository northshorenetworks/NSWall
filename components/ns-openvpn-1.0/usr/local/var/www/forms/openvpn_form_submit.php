#!/bin/php
<?php 
/*
    Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

    $form = $_POST['formname'];

    switch($form) {
        case "openvpn_config_server":
        unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "ip");
		$reqdfieldsn = explode(",", "IP");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if (!$input_errors) {
		unset($config['system']['configserver']);
		$config['system']['configserver']['ip']             = $_POST['ip'];
		$config['system']['configserver']['configserverip'] = $_POST['configserverip'];
		$config['system']['configserver']['identifier']     = $_POST['identifier'];
		$config['system']['configserver']['caname']         = $_POST['caname'];
		$config['system']['configserver']['certname']       = $_POST['certname'];
		$config['system']['configserver']['takey']          = base64_encode($_POST['takey']);

		write_config();

		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = openvpn_monitor_configure();

			config_unlock();
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
	} 
	case "openvpn_gateway":
			if ($_POST) {
				if (is_numeric($_POST['id']))
				$id = $_POST['id'];

				if (!is_array($config['openvpn']['gw']))
				$config['openvpn']['gw'] = array();

				openvpn_gateway_sort();
				$a_openvpn = &$config['openvpn']['gw'];
				unset($input_errors);
				$pconfig = $_POST;

				if (!$input_errors) {
				 	$openvpnent['name']     = $_POST['name'];	
					$openvpnent['descr']    = $_POST['descr'];	
					$openvpnent['context']  = $_POST['context'];
					$openvpnent['device']   = $_POST['device'];
					$openvpnent['port']     = $_POST['port'];
					$openvpnent['proto']    = $_POST['proto'];
                			$openvpnent['caname']   = $_POST['caname'];
                			$openvpnent['certname'] = $_POST['certname'];
                			$openvpnent['takey']    = base64_encode($_POST['takey']);
					if ($_POST['context'] == 'server') {
						$openvpnent['subnet']     = $_POST['subnet'];
						$openvpnent['subnetmask'] = $_POST['subnetmask'];
						$openvpnent['dhkey']      =  base64_encode($_POST['dhkey']);
						$srclist = array_reverse(explode(' ', $_POST['srclist']));
						for($i=0;$i<sizeof($srclist); $i++) {
							$member = 'route'."$i";
							$source = preg_replace("/ /", "", $srclist[$i]);
							$openvpnent['localroutes'][$member] = $source;
						}
						$dstlist = array_reverse(explode(' ', $_POST['dstlist']));
						for($i=0;$i<sizeof($dstlist); $i++) {
							$member = 'route'."$i";
							$dest = preg_replace("/ /", "", $dstlist[$i]);
							$openvpnent['remoteroutes'][$member] = $dest;
						}
					} else {
						$openvpnent['ip']       = $_POST['ip'];	
					}


					if (isset($id) && $a_openvpn[$id])
						$a_openvpn[$id] = $openvpnent;
					else
						$a_openvpn[] = $openvpnent;

					$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);

					$retval = 0;
					if (!$input_errors) {
						write_config();
						config_lock();
						$retval = openvpn_site2site_configure($openvpnent['name']);	
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

        if ($type == 'openvpn_gw') {
                if (!is_array($config['openvpn']['gw']))
                $config['openvpn']['gw'] = array();
                
		openvpn_gateway_sort();
                $a_gw = &$config['openvpn']['gw'];
                if ($action == 'delete') {
                        unset($a_gw[$id]);
                        write_config();
                }
        }
}

?>
