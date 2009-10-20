#!/bin/php
<?php 
/*
	$Id: firewall_protocols.php,v 1.8 2008/10/20 19:19:46 jrecords Exp $
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

$pgtitle = array("Firewall", "Relayd", "Protocols");
require("guiconfig.inc");

if (!is_array($config['relays']['proxyaction'])) {
	$config['relays']['proxyaction'] = array();
}
$a_protocol = &$config['relays']['proxyaction'];
proxyactions_sort();

if ($_POST) {

        $pconfig = $_POST;

        if ($_POST['apply']) {
                $retval = 0;
                if (!file_exists($d_relaydconfdirty_path)) {
                        config_lock();
                        $retval = relay_relayd_configure();
                        config_unlock();
                }   
                $savemsg = get_std_save_message($retval);
                if ($retval == 0) {
                        if (file_exists($d_relaydconfdirty_path))
                                unlink($d_relaydconfdirty_path);
                }   
        }   
}

if ($_GET['act'] == "del") {
	if ($a_protocol[$_GET['id']]) {
		unset($a_protocol[$_GET['id']]);
		write_config();
		header("Location: firewall_proxyactions.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="firewall_proxyactions.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
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
  </td></tr>
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
              </table>
              <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr> 
		  <td width="20%" class="listhdrr">Name</td>
                  <td width="70%" class="listhdr">Description</td>
                  <td width="10%" class="list"></td>
		</tr>
		  <?php $i = 0; foreach ($a_protocol as $protocolent): ?>
                <tr> 
                  <td class="listr"> 
                    <?php echo $protocolent['name']; ?>
                  </td>
                  <td class="listbg"> 
                    <?=htmlspecialchars($protocolent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap> <a href="firewall_relayd_protocols_edit.php?id=<?=$i;?>"><img src="images/e.gif" title="edit proxy action" width="17" height="17" border="0"></a>
                    <a href="firewall_relayd_protocols.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this proxy action?')"><img src="images/x.gif" title="delete protocol" width="17" height="17" border="0"></a>
		    <a href="firewall_relayd_protocols_edit.php?dup=<?=$i;?>"><img src="images/plus.gif" title="add a new protocol based on this one" width="17" height="17" border="0"></a></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="4"></td>
                  <td class="list"> <a href="firewall_relayd_protocols_edit.php"><img src="images/plus.gif" title="add mapping" width="17" height="17" border="0"></a></td>
				</tr>
</td>
</tr>
</table>
</form>
<?php include("fend.inc"); ?>
