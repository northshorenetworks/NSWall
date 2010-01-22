#!/bin/php
<?php 
/*
	$Id: system_firmware.php,v 1.3 2009/04/20 06:59:38 jrecords Exp $
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

$d_isfwfile = 1;
$pgtitle = array("System", "Firmware");
require("guiconfig.inc"); 
include("ns-begin.inc");
/* checks with m0n0.ch to see if a newer firmware version is available;
   returns any HTML message it gets from the server */
function check_firmware_version() {
	global $g;
	$post = "platform=" . rawurlencode($g['fullplatform']) . 
		"&version=" . rawurlencode(trim(file_get_contents("/etc/version")));
		
	$rfd = @fsockopen("www.northshoresoftware.com", 80, $errno, $errstr, 3);
	if ($rfd) {
		$hdr = "POST /nswall/checkversion.php HTTP/1.0\r\n";
		$hdr .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$hdr .= "User-Agent: NSWall-webGUI/1.0\r\n";
		$hdr .= "Host: northshoresoftware.com\r\n";
		$hdr .= "Content-Length: " . strlen($post) . "\r\n\r\n";
		
		fwrite($rfd, $hdr);
		fwrite($rfd, $post);
		
		$inhdr = true;
		$resp = "";
		while (!feof($rfd)) {
			$line = fgets($rfd);
			if ($inhdr) {
				if (trim($line) == "")
					$inhdr = false;
			} else {
				$resp .= $line;
			}
		}
		
		fclose($rfd);
		
		return $resp;
	}
	
	return null;
}

if (!isset($config['system']['disablefirmwarecheck']))
	$fwinfo = check_firmware_version();
?>

<script type="text/javascript">

// pre-submit callback 
function showRequest(formData, jqForm, options) { 
    $('#upload_firmware').dialog('open'); 
    return true; 
}

// post-submit callback 
function showResponse(responseText, statusText)  {
          $("#upload_firmware").html(responseText);
               if(output.match(/SUBMITSUCCESS/))
                   setTimeout(function(){ $('#upload_firmware').dialog('close'); }, 2000);
          return false;
   	
} 

        // wait for the DOM to be loaded
    $(document).ready(function() {
            var options = {
                        target:        '#upload_firmware',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback 
                        success:       showResponse  // post-submit callback
            };

           // bind form using 'ajaxForm'
           $('#iform').ajaxForm(options);
    });
</script>
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if ($fwinfo) echo $fwinfo; ?>
<?php if (!file_exists($d_firmwarelock_path)): ?>
            <p>Click &quot;Begin 
              Upgrade&quot; below, then choose the image file (<?=$g['fullplatform'];?>-*.img)
			  to be uploaded.<br>Click &quot;Upgrade firmware&quot; 
              to start the upgrade process.</p>
         <form action="forms/system_firmware_form_submit.php" method="post" name="iform" id="iform">
           <input name="formname" type="hidden" value="system_firmware">
	      <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <br><br>
		    <strong>Firmware image file: </strong>&nbsp;<input name="ulfile" type="file" class="formfld">
                    <br><br>
                    <input name="Submit" type="submit" class="formbtn" value="Upgrade firmware">
                  </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"><span class="vexpl"><span class="red"><strong>Warning:<br>
                    </strong></span>DO NOT abort the firmware upgrade once it 
                    has started. The firewall will reboot automatically after 
                    storing the new firmware. The configuration will be maintained.</span></td>
                </tr>
              </table>
</form>
<?php endif; ?>
