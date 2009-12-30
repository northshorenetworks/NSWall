#!/bin/php
<?php 
/*
	Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		case "system_firmware":
			
        	if (stristr($_POST['Submit'], "Upgrade") || $_POST['sig_override'])
                		$mode = "upgrade";

			if ($mode) {
                		if ($mode == "upgrade") {
                        		if (is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
 		                               	/* verify firmware image(s) */
               		                 	if (!stristr($_FILES['ulfile']['name'], $g['fullplatform']) && !$_POST['sig_override'])
                        	                	$input_errors[] = "The uploaded image file is not for this platform ({$g['fullplatform']}).";
                                		else if (!file_exists($_FILES['ulfile']['tmp_name'])) {
                                        		/* probably out of memory for the MFS */
                                        		$input_errors[] = "Image upload failed (out of memory?)";
                                        		exec_rc_script("/etc/rc.firmware disable");
                                        		if (file_exists($d_fwupenabled_path))
                                                		unlink($d_fwupenabled_path);
                                		} else {
                                        		/* move the image so PHP won't delete it */
                                        		rename($_FILES['ulfile']['tmp_name'], "{$g['ftmp_path']}/firmware.img");
						}
					}
                         /* check digital signature */
			$sigchk = verify_digital_signature("{$g['ftmp_path']}/firmware.img");
 
			if ($sigchk == 1)
				$sig_warning = "The digital signature on this image is invalid.";
			else if ($sigchk == 2)
				$sig_warning = "This image is not digitally signed.";
			else if (($sigchk == 3) || ($sigchk == 4))
			$sig_warning = "There has been an error verifying the signature on this image.";
 
			if (!verify_gzip_file("{$g['ftmp_path']}/firmware.img")) {
				$input_errors[] = "The image file is corrupt.";
				unlink("{$g['ftmp_path']}/firmware.img");
			}
                        
			if ($input_errors) {
				echo '<center>File Upload Failed<br>$input_errors<INPUT TYPE="button" value="OK" onClick="hidediv(\'upload_firmware\')"></center>';	
				return 0;
			}
			
			/* If we have an invalid or missing signature, present the user with a dialog to continue or not */
			if ($sig_warning && !$input_errors) {
                               $sig_warning = "<strong>" . $sig_warning . "</strong><br>This means that the image you uploaded " .
				"is not an official/supported image and may lead to unexpected behavior or security " .
				"compromises. Only install images that come from sources that you trust, and make sure ".
				"that the image has not been tampered with.<br><br>".
				"Do you want to install this image anyway (on your own risk)?";
                                echo "<center>$sig_warning</center>";
                                echo '<script type="text/javascript">
				
				// pre-submit callback
				function showRequest(formData, jqForm, options) {
						return true;
				}

			        // wait for the DOM to be loaded
    				$(document).ready(function() {
            			var options = {
                        		target:        \'#upload_firmware\',  // target element(s) to be updated with server response
                        		beforeSubmit:  showRequest,  // pre-submit callback
           			};

           			// bind form using \'ajaxForm\'
           			$(\'#sigform\').ajaxForm(options);
    				});

				</script>
				<form action="forms/system_firmware_form_submit.php" id="sigform" method="post">
				<input name="formname" type="hidden" value="system_firmware_sig_override">
				<input name="sig_override" type="submit" class="formbtn" id="sig_override" value=" Yes ">
				<input name="sig_no" type="submit" class="formbtn" id="sig_no" value=" No "></form>';
                        	return 0;
                       	}
                        }
			if (!$input_errors && !file_exists($d_firmwarelock_path) && (!$sig_warning || $_POST['sig_override'])) {
                                $savemsg = "The firmware is now being installed. The firewall will reboot automatically.";
                                echo "<center>$savemsg</center>";
				echo '<script type="text/javascript">
                                $("#upload_firmware").dialog("close");
                                $("#reboot_nswall").dialog("open");
                                setTimeout(function(){ $("#reboot_nswall").dialog("close"); window.location = "/login.htm"; }, 75000);
								</script>';
                                /* fire up the update script in the background */
                                touch($d_firmwarelock_path);
                                exec_rc_script_async("/etc/rc.firmware upgrade {$g['ftmp_path']}/firmware.img"); 
								return 0;
			}
		}
                        case "system_firmware_sig_override" :
                		if ($_POST['sig_override']) {
		                	$savemsg = "The firmware is now being installed. The firewall will reboot automatically.";
                                	echo "<center>$savemsg</center>";
					echo '<script type="text/javascript">
                                        $("#upload_firmware").dialog("close");
                                        $("#reboot_nswall").dialog("open");
                                        setTimeout(function(){ $("#reboot_nswall").dialog("close"); window.location = "/login.htm"; }, 75000);
										</script>';
                                        /* fire up the update script in the background */
					touch($d_firmwarelock_path);
					exec_rc_script_async("/etc/rc.firmware upgrade {$g['ftmp_path']}/firmware.img");
                        		return 0;
				} elseif ($_POST['sig_no']) {
					unlink("{$g['ftmp_path']}/firmware.img");
					echo '<!-- SUBMITSUCCESS --><center>System Firmware Update Aborted!!!</center>';
					echo '<script type="text/javascript">
					setTimeout(function(){ $("#upload_firmware").dialog("close"); }, 2000);
					</script>';
					return 0;
				}	
			default;
 				echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="hidediv(\'upload_firmware\')"></center>';
			return 0;
	}
}
?>
