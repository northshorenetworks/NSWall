#!/bin/php
<?php 
/*
	$Id: system_advanced.php,v 1.22 2009/04/20 06:59:37 jrecords Exp $
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

$pgtitle = array("System", "Advanced setup");
require("guiconfig.inc");

$pconfig['disableconsolemenu'] = isset($config['system']['disableconsolemenu']);
$pconfig['disablefirmwarecheck'] = isset($config['system']['disablefirmwarecheck']);
$pconfig['bypassstaticroutes'] = isset($config['filter']['bypassstaticroutes']);
$pconfig['noantilockout'] = isset($config['system']['webgui']['noantilockout']);
?> 
<script type="text/javascript">

// pre-submit callback 
function showRequest(formData, jqForm, options) { 
    displayProcessingDiv(); 
    return true; 
}

// post-submit callback 
function showResponse(responseText, statusText)  {
    if(responseText.match(/SUBMITSUCCESS/)) {  
           setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 2000);
    }
} 

        // wait for the DOM to be loaded
    $(document).ready(function() {
            var options = {
                        target:        '#save_config',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback 
                        success:       showResponse  // post-submit callback
            };

           // bind form using 'ajaxForm'
           $('#iform').ajaxForm(options);
    });
</script>
            
	<?php if ($input_errors) print_input_errors($input_errors); ?>
            <?php if ($savemsg) print_info_box($savemsg); ?>
            <p><span class="vexpl"><span class="red"><strong>Note: </strong></span>the 
              options on this page are intended for use by advanced users only.</span></p>
            <form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
		<input name="formname" type="hidden" value="system_advanced">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">Miscellaneous</td>
                </tr>
		<tr> 
                  <td width="22%" valign="top" class="vncell">Console menu </td>
                  <td width="78%" class="vtable"> 
                    <input name="disableconsolemenu" type="checkbox" id="disableconsolemenu" value="yes" <?php if ($pconfig['disableconsolemenu']) echo "checked"; ?>>
                    <strong>Disable console menu</strong><span class="vexpl"><br>
                    Changes to this option will take effect after a reboot.</span></td>
                </tr>
		<tr>
                  <td valign="top" class="vncell">Firmware version check </td>
                  <td class="vtable">
                    <input name="disablefirmwarecheck" type="checkbox" id="disablefirmwarecheck" value="yes" <?php if ($pconfig['disablefirmwarecheck']) echo "checked"; ?>>
                    <strong>Disable firmware version check</strong><span class="vexpl"><br>
    This will cause NSWall not to check for newer firmware versions when the <a href="system_firmware.php">System: Firmware</a> page is viewed.</span></td>
		</tr>
		<tr> 
                  <td width="22%" valign="top" class="vncell">Static route filtering</td>
                  <td width="78%" class="vtable"> 
                    <input name="bypassstaticroutes" type="checkbox" id="bypassstaticroutes" value="yes" <?php if ($pconfig['bypassstaticroutes']) echo "checked"; ?>>
                    <strong>Bypass firewall rules for traffic on the same interface</strong><br>
					This option only applies if you have defined one or more static routes. If it is enabled, traffic that enters and leaves through the same interface will not be checked by the firewall. This may be desirable in some situations where multiple subnets are connected to the same interface. </td>
                </tr>
				<tr> 
                  <td width="22%" valign="top" class="vncell">webGUI anti-lockout</td>
                  <td width="78%" class="vtable"> 
                    <input name="noantilockout" type="checkbox" id="noantilockout" value="yes" <?php if ($pconfig['noantilockout']) echo "checked"; ?>>
                    <strong>Disable webGUI anti-lockout rule</strong><br>
					By default, access to the webGUI on the LAN interface is always permitted, regardless of the user-defined filter rule set. Enable this feature to control webGUI access (make sure to have a filter rule in place that allows you in, or you will lock yourself out!).<br>
					Hint: 
					the &quot;set LAN IP address&quot; option in the console menu  resets this setting as well.</td>
                </tr>
		<tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save"> 
                  </td>
                </tr>
              </table>
</form>
