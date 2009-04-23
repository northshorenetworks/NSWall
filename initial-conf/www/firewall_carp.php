#!/bin/php
<?php 
/*
	$Id: firewall_carp.php,v 1.1 2009/03/25 19:33:26 jrecords Exp $
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

$pgtitle = array("Firewall", "CARP", "Global Settings");
require("guiconfig.inc");

$pconfig['carpenable'] = isset($config['carp']['carpenable']);
$pconfig['preemptenable'] = isset($config['carp']['preemptenable']);
$pconfig['logenable'] = isset($config['carp']['logenable']);
$pconfig['arpbalance'] = isset($config['carp']['arpbalance']);

$a_carp = &$config['carp'];

if ($_POST) {

	$pconfig = $_POST;
	if (!$input_errors) {
                $config['carp']['carpenable'] = $_POST['carpenable'] ? true : false;
                $config['carp']['preemptenable'] = $_POST['preemptenable'] ? true : false;
                $config['carp']['logenable'] = $_POST['logenable'] ? true : false;
                $config['carp']['arpbalance'] = $_POST['arpbalance'] ? true : false;
	}
	write_config();
	
	$retval = 0;
	$savemsg = get_std_save_message($retval);
}

?>
<?php include("fbegin.inc"); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="firewall_carp.php" method="post">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php
$tabs = array('Configuration' => 'firewall_carp.php',
		'Virtual Host IDs' => 'firewall_carp_vid.php',
		'PFSync' => 'firewall_pfsync.php',
		'Config Sync Hosts' => 'firewall_carp_sync_hosts.php');

  dynamic_tab_menu($tabs);
?>    
  </ul>
  </td>
  </tr>
  </table>
	<table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                 <tr>
                  <td width="22%" valign="top" class="vncell">Enable CARP</td>
                  <td width="78%" class="vtable">
                  <input name="carpenable" type="checkbox" value="carpenable" <?php if ($pconfig['carpenable']) echo "checked"; ?>>
		  Accept incoming CARP packets<br>
                </td>
                </tr>
	        <tr>
                  <td width="22%" valign="top" class="vncell">Enable Preemption</td>
                  <td width="78%" class="vtable">
                  <input name="preemptenable" type="checkbox" value="preemptenable" <?php if ($pconfig['preemptenable']) echo "checked"; ?>>
                  Allow hosts within a redundancy group that have a better advbase and advskew to preempt the master. In addition, this option also enables failing over all interfaces in the event that one interface goes down. If one physical CARP-enabled interface goes down, CARP will change advskew to 240 on all other CARP-enabled interfaces, in essence, failing itself over.<br>
		</td>
		</tr>
		<tr>
                  <td width="22%" valign="top" class="vncell">Logging</td>
                  <td width="78%" class="vtable">
                  <input name="logenable" type="checkbox" value="logenable" <?php if ($pconfig['logenable']) echo "checked"; ?>>
	  	  Log bad CARP packets.<br> 
		</td>
                </tr>
		 <tr>
                  <td width="22%" valign="top" class="vncell">ARP Balance</td>
                  <td width="78%" class="vtable">
                  <input name="arpbalance" type="checkbox" value="arpbalance" <?php if ($pconfig['arpbalance']) echo "checked"; ?>>
                  Load balance traffic across multiple redundancy group hosts.<br> 
		</td>
                </tr>
		<tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="Save"> 
                  </td>
                </tr>
</table>
</form>
<?php include("fend.inc"); ?>
