#!/bin/php
<?php 
/*
	$Id: firewall_nat.php,v 1.1.1.1 2008/08/01 07:56:19 root Exp $
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

$pgtitle = array("Interfaces", "Trunks", "Assign");
require("guiconfig.inc");

if (!is_array($config['trunks']['trunk']))
    $config['trunks']['trunk'] = array();

$a_out = &$config['trunks']['trunk'];
trunks_sort();

if ($_POST) {

    $pconfig = $_POST;

    $config['nat']['advancedoutbound']['enable'] = ($_POST['enable']) ? true : false;
    write_config();
    
    $retval = 0;
    
    if (!file_exists($d_sysrebootreqd_path)) {
		config_lock();
        $retval |= filter_configure();
		config_unlock();
    }
    $savemsg = get_std_save_message($retval);
    
    if ($retval == 0) {
        if (file_exists($d_natconfdirty_path))
            unlink($d_natconfdirty_path);
        if (file_exists($d_filterconfdirty_path))
            unlink($d_filterconfdirty_path);
    }
}

if ($_GET['act'] == "del") {
    if ($a_out[$_GET['id']]) {
	foreach ( $config['interfaces'] as $interface ) {
                if($a_out[$_GET['id']]['trunkport'] == $interface['if']) { 
			exit;		
		}
	}	 
        unset($a_out[$_GET['id']]);
        write_config();
        header("Location: interfaces_trunk.php");
        exit;
    }
}

?>
<?php include("fbegin.inc"); ?>
<form action="interfaces_trunk.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_natconfdirty_path)): ?><p>
<?php print_info_box_np("The NAT configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
    <li class="tabinact"><a href="interfaces_assign.php">Interface assignments</a></li>
    <li class="tabinact"><a href="interfaces_vlan.php">VLANs</a></li>
    <li class="tabact">Trunks</li>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr> 
                  <td width="10%" class="listhdrr">Name</td>
                  <td width="50%" class="listhdr">Description</td>
                  <td width="5%" class="list"></td>
                </tr>
              <?php $i = 0; foreach ($a_out as $natent): ?>
                <tr valign="top">
                  <td class="listbg"> 
                    <?=htmlspecialchars($natent['name']);?>&nbsp;
                  </td>
                  <td class="listbg"> 
                    <?=htmlspecialchars($natent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap> <a href="trunks_edit.php?id=<?=$i;?>"><img src="e.gif" title="edit mapping" width="17" height="17" border="0"></a>
                     &nbsp;<a href="interfaces_trunk.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this trunk?')"><img src="x.gif" title="delete mapping" width="17" height="17" border="0"></a></td>
                </tr>
              <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="5"></td>
                  <td class="list"> <a href="trunks_edit.php"><img src="plus.gif" title="add mapping" width="17" height="17" border="0"></a></td>
                </tr>
              </table>
</td>
  </tr>
</table>
            </form>
<?php include("fend.inc"); ?>
