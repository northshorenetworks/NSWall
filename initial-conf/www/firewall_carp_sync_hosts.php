#!/bin/php
<?php 
/*
	$Id: firewall_carp_sync_hosts.php,v 1.1 2009/04/20 06:56:53 jrecords Exp $
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

$pgtitle = array("Firewall", "CARP", "Config Sync Hosts");
require("guiconfig.inc");

if (!is_array($config['carp']['configsynchost'])) {
	$config['carp']['configsynchost'] = array();
}
$a_configsynchost = &$config['carp']['configsynchost'];
configsynchosts_sort();

if ($_POST) {

        $pconfig = $_POST;

        if ($_POST['apply']) {
                $retval = 0;
                if (!file_exists($d_carpconf_path)) {
                        config_lock();
                        config_unlock();
                }   
                $savemsg = get_std_save_message($retval);
                if ($retval == 0) {
                        if (file_exists($d_carpconfdirty_path))
                                unlink($d_carpconfdirty_path);
                }   
        }   
}

if ($_GET['act'] == "del") {
	if ($a_configsynchost[$_GET['id']]) {
		unset($a_configsynchost[$_GET['id']]);
		write_config();
		header("Location: firewall_carp_sync_hosts.php");
		exit;
	}
}
?>
<form action="firewall_carp_sync_hosts.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
  </ul>
  </ul>
  </td></tr>
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
              </table>
              <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr> 
		  <td width="20%" class="listhdrr">Name</td>
                  <td width="80%" class="listhdr">Description</td>
		</tr>
		<?php $nconfigsynchosts = 0; for ($i = 0; isset($a_configsynchost[$i]); $i++):
                                        $configsynchostent = $a_configsynchost[$i];
                ?> 
                <tr> 
                  <td class="listr"> 
                    <?php echo $configsynchostent['name']; ?>
                  </td>
                  <td class="listbg"> 
                    <?=htmlspecialchars($configsynchostent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap> <a href="firewall_carp_sync_hosts_edit.php?id=<?=$i;?>"><img src="images/e.gif" title="edit Config Sync Host" width="17" height="17" border="0"></a>
                     <a href="firewall_carp_sync_hosts.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this Config Sync Host?')"><img src="images/x.gif" title="delete Config Sync Host" width="17" height="17" border="0"></a>
		     <a href="firewall_carp_sync_hosts_edit.php?dup=<?=$i;?>"><img src="images/plus.gif" title="add a new Config Sync Host based on this one" width="17" height="17" border="0"></a></td>
		</tr>
                	<?php $nconfigsynchosts++; endfor; ?>
                        <?php if ($nconfigsynchosts == 0): ?>
                        <td class="listlr" colspan="4" align="center" valign="middle">
                          <span class="gray">
                          No Config Sync Hosts are currently defined.<br>
                          <br><br>
                          Click the <a href="firewall_carp_sync_hosts_edit.php"><img src="images/plus.gif" title="add new rule" border="0" width="17" height="17" align="absmiddle"></a> button to add a new Config Sync Host.</span>
                          </td>
                          <?php endif; ?>

		<tr> 
                  <td class="list" colspan="4"></td>
                  <td class="list"> <a href="firewall_carp_vid_edit.php"><img src="images/plus.gif" title="add Virtual Host" width="17" height="17" border="0"></a></td>
				</tr>
</td>
</tr>
</table>
</form>
