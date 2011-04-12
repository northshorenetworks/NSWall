#!/bin/php
<?php 
/*
    Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

    $form = $_POST['formname'];

    switch($form) {
            case "service_snmpd":
            unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "rocommunity");
		$reqdfieldsn = explode(",", "Community");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if (!$input_errors) {
		$config['snmpd']['syslocation'] = $_POST['syslocation'];
		$config['snmpd']['syscontact'] = $_POST['syscontact'];
		$config['snmpd']['rocommunity'] = $_POST['rocommunity'];
		$config['snmpd']['enable'] = $_POST['enable'] ? true : false;
		$config['snmpd']['bindlan'] = $_POST['bindlan'] ? true : false;

		write_config();

		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = services_snmpd_configure();
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
