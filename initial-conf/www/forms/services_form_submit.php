#!/bin/php
<?php 
/*
	Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		case "service_dyndns":
			unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = array();
	$reqdfieldsn = array();
	if ($_POST['enable']) {
		$reqdfields = array_merge($reqdfields, explode(" ", "host username password type"));
		$reqdfieldsn = array_merge($reqdfieldsn, explode(",", "Hostname,Username,Password,Service type"));
	}
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['host'] && !is_domain($_POST['host']))) {
		$input_errors[] = "The host name contains invalid characters.";
	}
	if (($_POST['server'] && !is_domain($_POST['server']) && !is_ipaddr($_POST['server']))) {
		$input_errors[] = "The server name contains invalid characters.";
	}
	if (($_POST['port'] && !is_port($_POST['port']))) {
		$input_errors[] = "The server port must be an integer between 1 and 65535.";
	}
	if (($_POST['mx'] && !is_domain($_POST['mx']))) {
		$input_errors[] = "The MX contains invalid characters.";
	}
	if (($_POST['username'] && !is_dyndns_username($_POST['username']))) {
		$input_errors[] = "The username contains invalid characters.";
	}

	if (!$input_errors) {
		$config['dyndns']['type'] = $_POST['type'];	
		$config['dyndns']['username'] = $_POST['username'];
		$config['dyndns']['password'] = $_POST['password'];
		$config['dyndns']['host'] = $_POST['host'];
		$config['dyndns']['server'] = $_POST['server'];
		$config['dyndns']['port'] = $_POST['port'];
		$config['dyndns']['mx'] = $_POST['mx'];
		$config['dyndns']['wildcard'] = $_POST['wildcard'] ? true : false;
		$config['dyndns']['enable'] = $_POST['enable'] ? true : false;

		write_config();

		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			/* nuke the cache file */
			config_lock();
			services_dyndns_reset();
			$retval = services_dyndns_configure();
			config_unlock();
		}
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
			case "service_dnsforwarder":
				unset($input_errors);
			    $pconfig = $_POST;
		 		if (!$input_errors) {	
		 			$config['dnsmasq']['enable'] = $_POST['enable'] ? true : false;

        			write_config();

        			$retval = 0;
        			if (!file_exists($d_sysrebootreqd_path)) {
            			/* nuke the cache file */
            			config_lock();
            			$retval = services_dnsmasq_configure();
						config_unlock();
        			}
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
			default;
                  echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="$(\'#save_config\').dialog(\'close\')"></center>';
			return 0;
	}
}

?>
