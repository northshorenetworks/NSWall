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
                
		if ($_POST['submit'] == "Save") {
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
                       		 return $retval;
			}
		}
		default;
 echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="hidediv(\'save_config\')"></center>';
			return 0;
	}
}
?>

