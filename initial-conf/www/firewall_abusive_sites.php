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

$pgtitle = array("Firewall", "Abusive Hosts");
require("guiconfig.inc");

$pconfig['abusiveslist'] =  $config['filter']['abusivehosts']['abusiveslist'];

if ($_POST) {

	if ($_POST['submit'] == "Save") {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if (!$input_errors) {
		unset($config['filter']['abusivehosts']['abusiveslist']);
	        $abusiveslist = array_reverse(explode(',', $_POST['memberslist']));
		for($i=0;$i<sizeof($abusiveslist); $i++) {
			$member = 'abuser'."$i";
			$source = preg_replace("/ /", "", $abusiveslist[$i]);
			$config['filter']['abusivehosts']['abusiveslist'][$member] = $source;
		}
        	write_config();
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
<script language="javascript" src="/nss.js"></script>

            <?php if ($input_errors) print_input_errors($input_errors); ?>
            <?php if ($savemsg) print_info_box($savemsg); ?>
            <p><span class="vexpl"><span class="red"><strong>Note: </strong></span>the 
              options on this page are intended for use by advanced users only.</span></p>
            <form action="firewall_abusive_sites.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
            <input name="memberslist" type="hidden" value="">
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr> 
                  <td colspan="2" valign="top" class="listtopic">Abusive Sites</td>
                </tr>
		<tr>
                <td width="22%" valign="top" class="vncellreq">Abusive Sites</td>
                <td width="78%" class="vtable">
                <SELECT style="width: 150px; height: 100px" id="MEMBERS" NAME="MEMBERS" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['abusiveslist']); $i++): ?>
                <option value="<?=$pconfig['abusiveslist']["abuser$i"];?>">
                <?=$pconfig['abusiveslist']["abuser$i"];?>
                </option>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(MEMBERS)"; value='Remove Selected'><br><br>
                  <strong>Type</strong>
                    <select name="srctype" class="formfld" id="srctype" onChange="switchsrcid(document.iform.srctype.value)">
                      <option value="srchost" selected>Host</option>
                      <option value="srcnet" >Network</option>
                      <option value="srcalias" >Alias</option>
                    </select><br><br>
                <div id='srchost' style="display:block;">
                 <strong>Address</strong>
                  <?=$mandfldhtml;?><input name="srchost" type="text" class="formfld" id="srchost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                <input type=button onClick="addOption('MEMBERS',document.iform.srchost.value + '/32','host' + ':' + document.iform.srchost.value + '/32')"; value='Add'>
		</div>
                <div id='srcnet' style="display:none;">
                 <strong>Address</strong>
                  <?=$mandfldhtml;?><input name="srcnet" type="text" class="formfld" id="srcnet" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                   <strong>/</strong>
                    <select name="srcmask" class="formfld" id="srcmask">
                      <?php for ($i = 30; $i >= 1; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
                <input type=button onClick="addOption('MEMBERS',document.iform.srcnet.value + '/' + document.iform.srcmask.value,'net' + ':' + document.iform.srcnet.value + '/' + document.iform.srcmask.value)"; value='Add'>
		</div>
                <div id='srcalias' style="display:none;">
                <strong>Alias</strong>
                    <select name="srcalias" class="formfld" id="srcalias">
                      <?php
                       $defaults = filter_system_aliases_names_generate();
                       $defaults = split(' ', $defaults);
                       foreach( $defaults as $i): ?>
                      <option value="<?='$' . $i;?>"><?=$i;?>
                      </option>
                      <?php endforeach; ?>
                      <?php foreach($config['aliases']['alias'] as $i): ?>
                      <option value="<?='$' . $i['name'];?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
		<input type=button onClick="addOption('MEMBERS',document.iform.srcalias.value + '/32','net' + ':' + document.iform.srcalias.value + '/32')"; value='Add'>
		</div>
                </td>
                </tr>
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
