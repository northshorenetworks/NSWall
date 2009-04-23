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

$pgtitle = array("Services", "Configuration Server");
require("guiconfig.inc");

$pconfig['ip']       = $config['system']['configserver']['ip'];
$pconfig['username'] = $config['system']['configserver']['username'];
$pconfig['password'] = $config['system']['configserver']['password'];
$pconfig['protocol'] = $config['system']['configserver']['protocol'];
$pconfig['checkint'] = $config['system']['configserver']['checkint'];
$pconfig['debuglog'] = isset($config['system']['configserver']['debuglog']);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if (!$input_errors) {
		$config['system']['configserver']['ip'] = $_POST['ip'];
		$config['system']['configserver']['username'] = $_POST['username'];
		$config['system']['configserver']['password'] = $_POST['password'];
                $config['system']['configserver']['protocol'] = $_POST['protocol'];
		$config['system']['configserver']['checkint'] = $_POST['checkint'];
		$config['system']['configserver']['debuglog'] = $_POST['debuglog'] ? true : false;

		write_config();
	}
		
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
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
            <form action="services_config_server.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">Configuration Server</td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Server Address</td>
                  <td width="78%" class="vtable">
                    <input name="ip" type="text" class="formfld" id="ip" size="16" value="<?=htmlspecialchars($pconfig['ip']);?>">
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
                  <td width="22%" valign="top" class="vncellreq">Protocol</td>
                  <td width="78%" class="vtable">
                    <input name="protocol" type="text" class="formfld" id="protocol" size="5" value="<?=htmlspecialchars($pconfig['protocol']);?>">
                    </span></td>
                </tr>
		 <tr>
                  <td width="22%" valign="top" class="vncellreq">Check Interval</td>
                  <td width="78%" class="vtable">
                    <input name="checkint" type="text" class="formfld" id="checkint" size="5" value="<?=htmlspecialchars($pconfig['checkint']);?>">
                    </span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Debug Logging</td>
                  <td width="78%" class="vtable">
                    <input name="debuglog" type="checkbox" id="debuglog" value="yes" <?php if ($pconfig['debuglog']) echo "checked"; ?>>
                    <br> <span class="vexpl">Enable debug logging for configuration server checks</span></td>
                </tr>
		<tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save" onclick="enable_change(true)"> 
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
