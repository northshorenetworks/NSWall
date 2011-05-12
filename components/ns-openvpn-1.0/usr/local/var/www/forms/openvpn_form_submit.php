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
		$config['system']['configserver']['ip']       = $_POST['ip'];
		$config['system']['configserver']['username'] = $_POST['username'];
		$config['system']['configserver']['password'] = $_POST['password'];
		$config['system']['configserver']['caname']   = $_POST['caname'];
		$config['system']['configserver']['certname'] = $_POST['certname'];
		$config['system']['configserver']['takey']    = base64_encode($_POST['takey']);

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
		default;
                  echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="$(\'#save_config\').dialog(\'close\')"></center>';
            return 0;
    }
}

?>
