#!/bin/php
<?php 
/*
	$Id: firewall_pfsync.php,v 1.1 2009/04/20 06:56:53 jrecords Exp $
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

$pgtitle = array("Firewall", "Sync", "Settings");
require("guiconfig.inc");

$pconfig['pfsyncenable'] = isset($config['pfsync']['pfsyncenable']);
$pconfig['interface'] = $config['pfsync']['interface'];

$a_pfsync = &$config['pfsync'];

if ($_POST) {

	$pconfig = $_POST;
	if (!$input_errors) {
                $config['pfsync']['pfsyncenable'] = $_POST['pfsyncenable'];
		$config['pfsync']['interface'] = $_POST['interface'];	
	}
	write_config();
	
	$retval = 0;
	$savemsg = get_std_save_message($retval);
}

?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="firewall_pfsync.php" method="post">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
  </ul>
  </td>
  </tr>
  </table>
	<table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                 <tr>
                  <td width="22%" valign="top" class="vncell">Enable Sync</td>
                  <td width="78%" class="vtable">
                  <input name="pfsyncenable" type="checkbox" value="pfsyncenable" <?php if ($pconfig['pfsyncenable']) echo "checked"; ?>>
                </td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Interface</td>
                  <td width="78%" class="vtable">
                                        <select name="interface" class="formfld">
                      <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
                                          for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                                                $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                                          }
                                          foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">Choose on which interface Sync services will run.</span></td>
                </tr>
		<tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="Save"> 
                  </td>
                </tr>
</table>
</form>
