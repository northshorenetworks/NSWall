#!/bin/php
<?php
/*
	$Id: vpn_pptp.php,v 1.1.1.1 2008/08/01 07:56:20 root Exp $
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

$pgtitle = array("VPN", "PPTP", "Configuration");
require("guiconfig.inc");

$pptpcfg = &$config['pptpd'];

$pconfig['enable'] = isset($pptpcfg['enable']);
$pconfig['remoteip'] = $pptpcfg['remoteip'];
$pconfig['encdrop'] = isset($pptpcfg['encdrop']);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */

	if (isset($_POST['enable'])) {

		$reqdfields = explode(" ", "remoteip");
		$reqdfieldsn = explode(",", "Server address,Remote start address");
		
		
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
		
		if (($_POST['subnet'] && !is_ipaddr($_POST['remoteip']))) {
			$input_errors[] = "A valid remote start address must be specified.";
		}
		
		if (!$input_errors) {	
			$subnet_start = ip2long($_POST['remoteip']);
			$subnet_end = ip2long($_POST['remoteip']) + $g['n_pptp_units'] - 1;
						
		}
	}

	if (!$input_errors) {
		unset($pptpcfg['enable']);
    		unset($pptpcfg['remoteip']);
    		unset($pptpcfg['encdrop']);
	
		if (isset($_POST['enable'])) {
			$pptpcfg['enable'] = $_POST['enable'] ? true : false;
			$pptpcfg['remoteip'] = $_POST['remoteip'];
			$pptpcfg['encdrop'] = $_POST['encdrop'] ? true : false;
		}	
		write_config();
		
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = vpn_pptpd_configure();
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
	}
}
?>
<script language="JavaScript">
<!--
function get_radio_value(obj)
{
	for (i = 0; i < obj.length; i++) {
		if (obj[i].checked)
			return obj[i].value;
	}
	return null;
}

function enable_change() {
	if (document.iform.enable.checked != "") {
		document.iform.remoteip.disabled = 0;
		document.iform.req128.disabled = 0;
	} else {
		document.iform.remoteip.disabled = 1;
		document.iform.req128.disabled = 1;
	}
}
//-->
</script>
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>
<form action="vpn_pptp_server.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('Configuration' => 'vpn_pptp_server.php',
           		  'Users' => 'vpn_pptp_users.php');
	dynamic_tab_menu($tabs);
?>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">PPTP Server</td>
                  <td width="78%" class="vtable"> 
                    <input name="enable" type="checkbox" id="enable" value="yes" onchange="enable_change()"<?php if ($pconfig['enable']) echo "checked"; ?>>
                  Enable PPTP Server.<br>
                  </td>
                </tr>
		<tr> 
                  <td width="22%" valign="top" class="vncellreq">Remote address range</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="remoteip" type="text" class="formfld" id="remoteip" size="20" value="<?=htmlspecialchars($pconfig['remoteip']);?>">
                    <br>
                    Specify the starting address for the client IP address subnet.<br>
                    The PPTP server will assign 
                    <?=$g['n_pptp_units'];?>
                    addresses, starting at the address entered above, to clients.</td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Allow Drop to 40bit Encryption</td>
                  <td width="78%" class="vtable"> 
                  <input name="encdrop" type="checkbox" id="req128" value="yes" <?php if ($pconfig['encdrop']) echo "checked"; ?>> 
                  Allow drop from 128-bit to 40-bit encryption.<br>
                  </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save" onclick="enable_change(true)"> 
                  </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"><span class="vexpl"><span class="red"><strong>Note:<br>
                    </strong></span>don't forget to add a firewall rule to permit 
                    traffic from PPTP clients!</span></td>
                </tr>
              </table>
			</td>
	</tr>
</table>
</form>
<script language="JavaScript">
<!--
enable_change();
//-->
</script>
