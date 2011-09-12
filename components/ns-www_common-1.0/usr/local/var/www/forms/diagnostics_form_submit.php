#!/bin/php
<?php 
/*
	Northshore Software Header
*/

function PsExec($commandJob) {

        $command = $commandJob.' > /dev/null 2>&1 & echo $!';
        exec($command ,$op);
        $pid = (int)$op[0];

        if($pid!="") return $pid;

        return false;
} 

function PsExists($pid) {

        exec("ps ax | grep $pid 2>&1", $output);

        while( list(,$row) = each($output) ) {

                $row_array = explode(" ", $row);
                $check_pid = $row_array[0];

                if($pid == $check_pid) {
                        return true;
                }

        }

        return false;
} 

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		case "diagnostic_tcpdump":
	    global $config, $g;
		unset($input_errors);

		/* input validation */
		$reqdfields = explode(" ", "count");
		$reqdfieldsn = explode(",", "Count");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		if (($_POST['count'] < 1) || ($_POST['count'] > MAX_COUNT)) {
			$input_errors[] = "Count must be between 1 and {MAX_COUNT}";
		}
                $retval = 0;
		if (!$input_errors) {
            system_determine_hwplatform();
			$ifname  =  get_interface_name_by_descr($_POST['interface']);
			$count   = $_POST['count'];
            $port    = $_POST['port'];
			$time    = time();
			$command = "/usr/sbin/tcpdump -i $ifname -c $count -s 1500 -vv -w /tmp/debug/$time.cap $port";
			$pid     = PsExec($command); 
		} 
  
		if ($retval == 0) {
            sleep(1);
    		echo '<script type="text/javascript">
				var QueryString = "action=checkpid&pid=' . $pid . '";
				setTimeout(function(){ 
               		$.get("forms/diagnostics_form_submit.php", QueryString, function(output) {
                    	$("#save_config").html(output);
                    if(output.match(/PIDCOMPLETE/))
                        setTimeout(function(){ $("#save_config").dialog("close"); }, 1000);
          	}, 1000);
            	});
			</script>';	
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

			case "diagnostic_backuprestore":

			unset($input_errors);

			if (stristr($_POST['Submit'], "Restore"))
				$mode = "restore";
			else if (stristr($_POST['Submit'], "Download"))
				$mode = "download";

			if ($mode) {
				if ($mode == "download") {
					config_lock();

					$fn = "config-" . $config['system']['hostname'] . "." . 
						$config['system']['general']['domain'] . "-" . date("YmdHis") . ".xml";

					$fs = filesize($g['conf_path'] . "/config.xml");
					header("Content-Type: application/octet-stream"); 
					header("Content-Disposition: attachment; filename=$fn");
					header("Content-Length: $fs");
					readfile($g['conf_path'] . "/config.xml");
					config_unlock();
					exit;
				} else if ($mode == "restore") {
					if (is_uploaded_file($_FILES['conffile']['tmp_name'])) {
						if (config_install($_FILES['conffile']['tmp_name']) == 0) {
							system_reboot();
							$savemsg = "The configuration has been restored. The firewall is now rebooting.";
						} else {
							$errstr = "The configuration could not be restored.";
							if ($xmlerr)
								$errstr .= " (XML error: $xmlerr)";
							$input_errors[] = $errstr;
						}
					} else {
						$input_errors[] = "The configuration could not be restored (file upload error).";
					}
                                        echo "<center>$savemsg</center>";
                                        echo '<script type="text/javascript">
                                        $("#upload_firmware").dialog("close");
                                        $("#reboot_nswall").dialog("open");
                                        setTimeout(function(){ $("#reboot_nswall").dialog("close"); window.location = "/login.php"; }, 75000);
                                        </script>';
				}
			}

            return;
			default;
                  echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="$(\'#save_config\').dialog(\'close\')"></center>';
			return 0;
	}
}

if ($_GET) {
     $action   = $_GET['action'];
     $filename = $_GET['filename'];
	 $pid      = $_GET['pid'];

     if ($action == 'delete') {
		if (preg_match('/\d+.cap/', $filename))
			unlink("/tmp/debug/$filename");
     }
	 if (PsExists($pid)) {
        echo '<script type="text/javascript">
			var QueryString = "action=checkpid&pid=' . $pid . '";
			setTimeout(function(){ 
               		   $.get("forms/diagnostics_form_submit.php", QueryString, function(output) {
                    	      $("#save_config").html(output);
            	           });
			}, 5000);
                        </script>';	
			echo '<center>Tcpdump in progress PID: ' . $pid . '<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner"></center>';
     } else {
        echo '<script type="text/javascript">
			      setTimeout(function(){ $("#save_config").dialog("close"); }, 1000);
                  setTimeout(function(){ $("#content").load("diag_util.php"); }, 1250);
              </script>';	
		echo "<center>Tcpdump PID: $pid completed</center";
	 }
}


?>

