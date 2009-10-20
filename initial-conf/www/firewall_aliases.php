#!/bin/php
<?php 
/*
	$Id: firewall_aliases.php,v 1.9 2009/04/20 06:56:53 jrecords Exp $
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

$pgtitle = array("Firewall", "Aliases");
require("guiconfig.inc");

if (!is_array($config['aliases']['alias'])) {
	$config['aliases']['alias'] = array();
}
$a_alias = &$config['aliases']['alias'];
aliases_sort();

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval |= filter_configure();
			$retval |= services_proxyarp_configure();
			config_unlock();
			push_config('aliases');
		}
		$savemsg = get_std_save_message($retval);
                if ($retval == 0) {
                        if (file_exists($d_natconfdirty_path))
                                unlink($d_natconfdirty_path);
                        if (file_exists($d_filterconfdirty_path))
                                unlink($d_filterconfdirty_path);
                }
		header("Location: firewall_aliases.php");
	}
}

if ($_GET['act'] == "del") {
	if ($a_alias[$_GET['id']]) {
		unset($a_alias[$_GET['id']]);
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_aliases.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="firewall_aliases.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_filterconfdirty_path)): ?><p>
<?php print_info_box_np("The firewall rule configuration has been changed.<br>You must apply the changes in order for them to
 take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
  <?php
  $tabs = array('Aliases' => 'firewall_aliases.php');
        dynamic_tab_menu($tabs);
  ?>    
  </ul>
</td></tr>
                <tr> 
		  <td width="20%" class="listhdrr">Name</td>
                  <td width="70%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
		</tr>
		  <?php $i = 0; foreach ($a_alias as $aliasent): ?>
                <tr> 
                  <td class="listr"> 
                    <?php echo $aliasent['name']; ?>
                  </td>
                  <td class="listbg"> 
                    <?=htmlspecialchars($aliasent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap> <a href="firewall_aliases_edit.php?id=<?=$i;?>"><img src="images/e.gif" title="edit alias" width="17" height="17" border="0"></a>
                     &nbsp;<a href="firewall_aliases.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this alias?')"><img src="images/x.gif" title="delete alias" width="17" height="17" border="0"></a></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="4"></td>
                  <td class="list"> <a href="firewall_aliases_edit.php"><img src="images/plus.gif" title="add mapping" width="17" height="17" border="0"></a></td>
				</tr>
</table><br>
</td>
</tr>
</alias>
</form>
<?php include("fend.inc"); ?>
