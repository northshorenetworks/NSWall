#!/bin/php
<?php
/*
 $Id: firewall_relays.php,v 1.8 2008/10/20 19:19:46 jrecords Exp $
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

$pgtitle = array("Firewall", "Relayd", "Relays");
require("guiconfig.inc");

if (!is_array($config['relays']['relay'])) {
	$config['relays']['relay'] = array();
}
$a_relay = &$config['relays']['relay'];
relays_sort();

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_relaydconf_path)) {
			config_lock();
			$retval = relay_relayd_reload();
			config_unlock();
			push_config('relays');
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_relaydconfdirty_path))
			unlink($d_relaydconfdirty_path);
		}
		header("Location: firewall_relayd_relays.php");
	}
}

if ($_GET['act'] == "del") {
	if ($a_relay[$_GET['id']]) {
		unset($a_relay[$_GET['id']]);
		write_config();
		header("Location: firewall_relayd_relays.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="firewall_relayd_relays.php" method="post"><?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_relaydconfdirty_path)): ?>
<p><?php print_info_box_np("The firewall rule configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply"
	value="Apply changes"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
		<ul id="tabnav">
		<?php
		$tabs = array('Relays' => 'firewall_relayd_relays.php',
		'Redirects' => 'firewall_relayd_redirects.php',
                'Protocols' => 'firewall_relayd_protocols.php',    
                'Global Settings' => 'firewall_relayd_globals.php');

		dynamic_tab_menu($tabs);
		?>
		</ul>
		</ul>
		</td>
	</tr>
	<td class="tabcont">
	<table width="100%" border="0" cellpadding="6" cellspacing="0">
	</table>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td width="20%" class="listhdrr">Name</td>
			<td width="80%" class="listhdr">Description</td>
		</tr>
		<?php $nrelays = 0; for ($i = 0; isset($a_relay[$i]); $i++):
		$relayent = $a_relay[$i];
		?>
		<tr>
			<td class="listr"><?php echo $relayent['name']; ?></td>
			<td class="listbg"><?=htmlspecialchars($relayent['descr']);?>&nbsp;</td>
			<td class="list" nowrap><a
				href="firewall_relayd_relays_edit.php?id=<?=$i;?>"><img
				src="images/e.gif" title="edit relay" width="17" height="17"
				border="0"></a> <a
				href="firewall_relayd_relays.php?act=del&id=<?=$i;?>"
				onclick="return confirm('Do you really want to delete this relay?')"><img
				src="images/x.gif" title="delete relay" width="17" height="17"
				border="0"></a> <a
				href="firewall_relayd_relays_edit.php?dup=<?=$i;?>"><img
				src="images/plus.gif" title="add a new relay based on this one"
				width="17" height="17" border="0"></a></td>
		</tr>
		<?php $nrelays++; endfor; ?>
		<?php if ($nrelays == 0): ?>
		<td class="listlr" colspan="4" align="center" valign="middle"><span
			class="gray"> No Relays are currently defined.<br>
		<br>
		<br>
		Click the <a href="firewall_relayd_relays_edit.php"><img
			src="images/plus.gif" title="add new rule" border="0" width="17"
			height="17" align="absmiddle"></a> button to add a new relay.</span>
		</td>
		<?php endif; ?>

		<tr>
			<td class="list" colspan="4"></td>
			<td class="list"><a href="firewall_relayd_relays_edit.php"><img
				src="images/plus.gif" title="add relay" width="17" height="17"
				border="0"></a></td>
		</tr>
		</td>
		</tr>
	</table>
	</form>
	<?php include("fend.inc"); ?>