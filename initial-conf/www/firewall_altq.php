#!/bin/php
<?php 
/*
	$Id: firewall_altq.php,v 1.1.1.1 2008/08/01 07:56:20 root Exp $
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

$pgtitle = array("Firewall", "ALTQ");
require("guiconfig.inc");

$pconfig['enable'] = isset($config['altq']['enable']);
if (isset($config['altq']['enable'])) {
	$pconfig['bandwidth'] = $config['altq']['bandwidth'];
}


if ($_POST) {

	if ($_POST['submit']) {
		$pconfig = $_POST;
		$config['altq']['enable'] = $_POST['enable'] ? true : false;
		$config['altq']['bandwidth'] = $_POST['bandwidth'];
		write_config();
	}
	
	if ($_POST['apply'] || $_POST['submit']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = filter_configure();
			config_unlock();
			push_config('altq');
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_shaperconfdirty_path))
				unlink($d_shaperconfdirty_path);
		}
	}

} else if ($_GET['act'] == "toggle") {
	if ($a_shaper[$_GET['id']]) {
		$a_shaper[$_GET['id']]['disabled'] = !isset($a_shaper[$_GET['id']]['disabled']);
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="firewall_altq.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_shaperconfdirty_path)): ?><p>
<?php print_info_box_np("The traffic shaper configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td class="vtable">
                      <input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable']) echo "checked";?>>
                      <strong>Enable ALTQ prioritization</strong></td>
                </tr>
	        <tr>
		  <td><strong>Uplink Speed</strong>
                  <input name="bandwidth" type="text" class="formfld" id="bandwidth" size="10" value="<?php if ($pconfig['bandwidth']) echo $pconfig['bandwidth']?>"></td>
		</tr>
                <tr> 
                  <td> <input name="submit" type="submit" class="formbtn" value="Save"> 
                  </td>
                </tr>
              </table>
      </td>
   </tr>
            </form>
<?php include("fend.inc"); ?>

