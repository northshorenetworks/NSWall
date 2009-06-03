#!/bin/php
<?php 
/*
	$Id: interfaces_opt.php,v 1.8 2008/09/15 21:29:19 jrecords Exp $
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

require("guiconfig.inc");

unset($index);
if ($_GET['index'])
	$index = $_GET['index'];
else if ($_POST['index'])
	$index = $_POST['index'];
	
if (!$index)
	exit;

$optcfg = &$config['interfaces']['opt' . $index];

/* Wireless interface? */
if (isset($optcfg['wireless'])) {
	require("interfaces_wlan.inc");
	wireless_config_init();
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
	
		/* description unique? */
		for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
			if ($i != $index) {
				if ($config['interfaces']['opt' . $i]['descr'] == $_POST['descr']) {
					$input_errors[] = "An interface with the specified description already exists.";
				}
			}
		}
		
	}
	
	/* Wireless interface? */
	if (isset($optcfg['wireless'])) {
		$wi_input_errors = wireless_config_post();
		if ($wi_input_errors) {
			$input_errors = array_merge($input_errors, $wi_input_errors);
		}
	}
	
	if (!$input_errors) {
		$optcfg['descr'] = $_POST['descr'];
		$optcfg['ipaddr'] = $_POST['ipaddr'];
		$optcfg['subnet'] = $_POST['subnet'];
		unset($optcfg['aliaslist']);
		$aliaslist = explode(',', $_POST['memberslist']);
                for($i=0;$i<sizeof($aliaslist); $i++) {
                      $alias = 'alias'."$i";
                      $prop = preg_replace("/ /", "", $aliaslist[$i]);
                      $optcfg['aliaslist'][$alias] = $prop;
                }
		$optcfg['enable'] = $_POST['enable'] ? true : false;
		$optcfg['gateway'] = $_POST['gateway'];

		write_config();
		
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = interfaces_optional_configure();
			
			/* is this the captive portal interface? */
			if (isset($config['captiveportal']['enable']) && 
				($config['captiveportal']['interface'] == ('opt' . $index))) {
				captiveportal_configure();
			}
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
	}
}

$pconfig['descr'] = $optcfg['descr'];
$pconfig['ipaddr'] = $optcfg['ipaddr'];
$pconfig['subnet'] = $optcfg['subnet'];
$pconfig['aliaslist'] = $optcfg['aliaslist'];
$pconfig['enable'] = isset($optcfg['enable']);
$pconfig['gateway'] = $optcfg['gateway'];

$pgtitle = array("Interfaces", "Optional $index (" . htmlspecialchars($optcfg['descr']) . ")");
?>

<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
function enable_change(enable_over) {
	var endis;
	endis = !(document.iform.enable.checked || enable_over);
	document.iform.descr.disabled = endis;
	document.iform.ipaddr.disabled = endis;
	document.iform.subnet.disabled = endis;
	document.iform.srchost.disabled = endis;
	document.iform.ALIASES.disabled = endis;
	document.iform.addbtn.disabled = endis;
        document.iform.removebtn.disabled = endis;

	if (document.iform.mode) {
		 document.iform.mode.disabled = endis;
		 document.iform.ssid.disabled = endis;
		 document.iform.channel.disabled = endis;
		 document.iform.stationname.disabled = endis;
		 document.iform.wep_enable.disabled = endis;
		 document.iform.key1.disabled = endis;
		 document.iform.key2.disabled = endis;
		 document.iform.key3.disabled = endis;
		 document.iform.key4.disabled = endis;
	}
}

function gen_bits(ipaddr) {
    if (ipaddr.search(/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/) != -1) {
        var adr = ipaddr.split(/\./);
        if (adr[0] > 255 || adr[1] > 255 || adr[2] > 255 || adr[3] > 255)
            return 0;
        if (adr[0] == 0 && adr[1] == 0 && adr[2] == 0 && adr[3] == 0)
            return 0;
		
		if (adr[0] <= 127)
			return 23;
		else if (adr[0] <= 191)
			return 15;
		else
			return 7;
    }
    else
        return 0;
}
function ipaddr_change() {
	document.iform.subnet.selectedIndex = gen_bits(document.iform.ipaddr.value);
}
//-->
</script>
<script language="javascript" src="/nss.js"></script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if ($optcfg['if']): ?>
            <form action="interfaces_opt.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	      <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td width="22%" valign="top" class="vtable">&nbsp;</td>
                  <td width="78%" class="vtable">
		  <input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false);bridge_change(false)">
		  <strong>Enable Optional <?=$index;?> interface</strong></td>
				</tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">Description</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="30" value="<?=htmlspecialchars($pconfig['descr']);?>">
					<br> <span class="vexpl">Enter a description (name) for the interface here.</span>
				 </td>
				</tr>
                <tr> 
                  <td colspan="2" valign="top" height="16"></td>
				</tr>
				<tr> 
                  <td colspan="2" valign="top" class="listtopic">IP configuration</td>
				</tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">IP address</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="ipaddr" type="text" class="formfld" id="ipaddr" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>" onchange="ipaddr_change()">
                    /
                	<select name="subnet" class="formfld" id="subnet">
					<?php for ($i = 31; $i > 0; $i--): ?>
					<option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>><?=$i;?></option>
					<?php endfor; ?>
                    </select>
				 </td>
				<tr>
                 <td width="22%" valign="top" class="vncellreq">Aliases</td>
                 <td width="78%" class="vtable">
                 <select name="MEMBERS" style="width: 150px; height: 100px" id="MEMBERS" multiple>
		 <?php for ($i = 0; $i<sizeof($pconfig['aliaslist']); $i++): ?>
                 <option value="<?=$pconfig['aliaslist']["alias$i"];?>">
                 <?=$pconfig['aliaslist']["alias$i"];?>
                 </option>
		 <?php endfor; ?>
                 <input type=button onClick="removeOptions(MEMBERS)"; name='removebtn'; value='Remove Selected'><br><br>
                  <strong>Address</strong>
                   <?=$mandfldhtml;?><input name="srchost" type="text" class="formfld" id="srchost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                   <input type=button onClick="addOption('MEMBERS',document.iform.srchost.value + '/32','host' + ':' + document.iform.srchost.value + '/32')"; name='addbtn'; value='Add'>
                     </select>
		   <input name="memberslist" type="hidden" value="">		
				</tr>
				<?php /* Wireless interface? */
				if (isset($optcfg['wireless']))
					wireless_config_print();
				?>
		<?php if ($optcfg['type'] == 'WAN'): ?>
    		<tr>
                  <td valign="top" class="vncellreq">Gateway</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>">
                  </td>
		</tr><?php endif; ?>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="index" type="hidden" value="<?=$index;?>"> 
				  <input name="Submit" type="submit" class="formbtn" value="Save" onclick="enable_change(true);bridge_change(true)"> 
                  </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"><span class="vexpl"><span class="red"><strong>Note:<br>
                    </strong></span>be sure to add firewall rules to permit traffic 
                    through the interface. Firewall rules for an interface in 
                    bridged mode have no effect on packets to hosts other than 
                    m0n0wall itself, unless &quot;Enable filtering bridge&quot; 
                    is checked on the <a href="system_advanced.php">System: 
                    Advanced functions</a> page.</span></td>
                </tr>
              </table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
bridge_change(false);
//-->
</script>
<?php else: ?>
<strong>Optional <?=$index;?> has been disabled because there is no OPT<?=$index;?> interface.</strong>
<?php endif; ?>
<?php include("fend.inc"); ?>
