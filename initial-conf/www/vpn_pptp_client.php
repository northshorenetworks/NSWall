#!/bin/php
<?php 
/*
	$Id: services_config_server.php,v 1.1 2009/04/20 06:59:37 jrecords Exp $
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

$pgtitle = array("VPN", "PPTP Client");
require("guiconfig.inc");

$pconfig['enable']   = isset($config['pptp']['client']['enable']);
$pconfig['server']   = $config['pptp']['client']['server'];
$pconfig['username'] = $config['pptp']['client']['username'];
$pconfig['password'] = $config['pptp']['client']['password'];
$pconfig['subnet']   = $config['pptp']['client']['subnet'];

if ($_POST) {

	if ($_POST['submit'] == "Disconnect") {
        	vpn_pptp_client_disconnect();
		header("Location: vpn_pptp_client.php");
	}
	if ($_POST['submit'] == "Connect") {
                vpn_pptp_client_connect();
		header("Location: vpn_pptp_client.php");
	}
	if ($_POST['submit'] == "Save") {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if (!$input_errors) {
		$config['pptp']['client']['enable'] = $_POST['enable'] ? true : false;
		$config['pptp']['client']['server'] = $_POST['server'];
		$config['pptp']['client']['username'] = $_POST['username'];
		$config['pptp']['client']['password'] = $_POST['password'];
		$config['pptp']['client']['subnet'] = $_POST['subnet'];
		write_config();
		vpn_pptp_configure();
	}
		
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
	}
}
?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
// -->
</script>
            <?php if ($input_errors) print_input_errors($input_errors); ?>
            <?php if ($savemsg) print_info_box($savemsg); ?>
            <p><span class="vexpl"><span class="red"><strong>Note: </strong></span>the 
              options on this page are intended for use by advanced users only.</span></p>
            <form action="vpn_pptp_client.php" method="post" name="iform" id="iform">
 	    <?php if (!file_exists($d_pptpclient_pid)): ?><p>
	    <input name="submit" type="submit" class="formbtn" id="Connect" value="Connect"></p>
            <?php endif; ?>
            <?php if (file_exists($d_pptpclient_pid)): ?><p>	
	    <input name="submit" type="submit" class="formbtn" id="Disconnect" value="Disconnect"></p>  
            <?php endif; ?> 
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr> 
                  <td colspan="2" valign="top" class="listtopic">PPTP Server</td>
                </tr>
		 <tr>
                  <td width="22%" valign="top" class="vncellreq">Enable PPTP Client</td>
                  <td width="78%" class="vtable">
                  <input name="enable" type="checkbox" id="enable" value="Yes" <?php if ($pconfig['enable']) echo "checked";
?>>
                  </span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Server Address</td>
                  <td width="78%" class="vtable">
                    <input name="server" type="text" class="formfld" id="server" size="25" value="<?=htmlspecialchars($pconfig['server']);?>">
		</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Username</td>
                  <td width="78%" class="vtable">
                    <input name="username" type="text" class="formfld" id="username" size="10" value="<?=htmlspecialchars($pconfig['username']);?>">
                </span></td>
                </tr> 
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Password</td>
                  <td width="78%" class="vtable">
                    <input name="password" type="password" class="formfld" id="password" size="10" value="<?=htmlspecialchars($pconfig['password']);?>">
                   </span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Subnet</td>
                  <td width="78%" class="vtable">
                    <input name="subnet" type="text" class="formfld" id="subnet" size="20" value="<?=htmlspecialchars($pconfig['subnet']);?>">
                    </span></td>
                </tr>
		<tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="submit" type="submit" class="formbtn" value="Save" onclick="enable_change(true)"> 
                  </td>
                </tr>
              </table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
