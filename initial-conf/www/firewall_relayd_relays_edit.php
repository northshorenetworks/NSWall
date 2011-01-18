#!/bin/php
<?php
/*
 $Id: firewall_relays_edit.php,v 1.7 2008/10/20 22:42:56 jrecords Exp $
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
 OR CONowing conditions are met:

 1. Redistributions of source code must retain the above copyright notice,
 this list of conditions and the following disclaimer.

 2. Redistributions in binary form must reproduce the above copyright
 notice, this list of conditions and the following disclaimer in the
 documentation and/or othWARE, EVEN IF ADVISED OF THE
 POSSIBILITY OF SUCH DAMAGE.
 */

$pgtitle = array("Firewall", "Relays", "Edit Relay");
require("guiconfig.inc");

if (!is_array($config['relays']['relay']))
$config['relays']['relay'] = array();
if (!is_array($config['relays']['proxyaction']))
$config['relays']['proxyaction'] = array();


relays_sort();
$a_relays = &$config['relays']['relay'];

$id = $_GET['id'];
if (isset($_POST['id']))
$id = $_POST['id'];

if (isset($_POST['after']))
$after = $_POST['after'];

if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
	$after = $_GET['dup'];
}

if (isset($id) && $a_relays[$id]) {
	$pconfig['name'] = $a_relays[$id]['name'];
	$pconfig['descr'] = $a_relays[$id]['descr'];
	$pconfig['listenertype'] = $a_relays[$id]['listenertype'];
	$pconfig['listenerip'] = $a_relays[$id]['listenerip'];
	$pconfig['listeneralias'] = $a_relays[$id]['listeneralias'];
	$pconfig['listenerport'] = $a_relays[$id]['listenerport'];
	$pconfig['forward'] = $a_relays[$id]['forward'];
	$pconfig['pool'] = $a_relays[$id]['pool'];
	$pconfig['addressip'] = $a_relays[$id]['addressip'];
	$pconfig['addressalias'] = $a_relays[$id]['addressalias'];
	$pconfig['addrtype'] = $a_relays[$id]['addrtype'];
	$pconfig['transparent'] = $a_relays[$id]['transparent'];
	$pconfig['internalport'] = $a_relays[$id]['internalport'];
	$pconfig['healthcheck'] = $a_relays[$id]['healthcheck'];
	$pconfig['path'] = $a_relays[$id]['path'];
	$pconfig['code'] = $a_relays[$id]['code'];
	$pconfig['alg'] = $a_relays[$id]['alg'];
	$pconfig['proto'] = $a_relays[$id]['proxyaction'];
	$pconfig['cert'] = $a_relays[$id]['cert'];
	if($a_relays[$id]['timeout']) {
		$pconfig['timeout'] = $a_relays[$id]['timeout'];
	} else {
		$pconfig['timeout'] = "600";
	}
}

if (isset($_GET['dup']))
unset($id);

if ($_POST) {

	unset($input_errors);
	unset($relay['name']);
	unset($relay['descr']);
	unset($relay['listenertype']);
	unset($relay['listenerip']);
	unset($relay['listeneralias']);
	unset($relay['listenerport']);
	unset($relay['forward']);
	unset($relay['pool']);
	unset($relay['addrtype']);
	unset($relay['addressip']);
	unset($relay['addressalias']);
	unset($relay['transparent']);
	unset($relay['internalport']);
	unset($relay['healthcheck']);
	unset($relay['path']);
	unset($relay['code']);
	unset($relay['alg']);
	unset($relay['proxyaction']);
	unset($relay['cert']);
	unset($relay['timeout']);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "name");
	$reqdfieldsn = explode(",", "Name,");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	$relay = array();
	$relay['name'] = $_POST['name'];
	$relay['descr'] = $_POST['descr'];
	$relay['listenertype'] = $_POST['listenertype'];
	if($_POST['listenertype'] == 'lsnralias')
	$relay['listeneralias'] = $_POST['listeneralias'];
	if($_POST['listenertype'] == 'lsnraddr')
	$relay['listenerip'] = $_POST['listenerip'];
	$relay['listenerport'] = $_POST['listenerport'];
	$relay['forward'] = $_POST['forward'];
	if($_POST['forward'] == 'serverpool') {
		$relay['pool'] = $_POST['pool'];
		$relay['internalport'] = $_POST['poolinternalport'];
		$relay['healthcheck'] = $_POST['healthcheck'];
		$relay['path'] = $_POST['path'];
		$relay['code'] = $_POST['code'];
		$relay['alg'] = $_POST['alg'];
	}
	if($_POST['forward'] == 'address') {
		$relay['transparent'] = $_POST['transparent'];
		if($_POST['addrtype'] == 'addraddr')
		$relay['addressip'] = $_POST['addressip'];
		if($_POST['addrtype'] == 'addralias')
		$relay['addressalias'] = $_POST['addressalias'];
		$relay['addrtype'] = $_POST['addrtype'];
		$relay['internalport'] = $_POST['addressinternalport'];
	}
	$relay['proto'] = $_POST['proto'];
	$relay['cert'] = $_POST['cert'];
	$relay['timeout'] = $_POST['timeout'];
	if (isset($id) && $a_relays[$id])
	$a_relays[$id] = $relay;
	else
	$a_relays[] = $relay;

	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);

	if (relay_relayd_parse($xmlconfig)) {
		$input_errors[] = "Could not parse the generated config file";
		$input_errors[] = "See log file for details";
		$input_errors[] = "XML Config file not modified";
	}

	if (!$input_errors) {

		write_config();
		touch($d_relaydconfdirty_path);
		header("Location: firewall_relayd_relays.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
var portsenabled = 1;

var ids=new Array('httphttps');
var forwards=new Array('address', 'serverpool');

function switchforward(id){       
        if(id == 'nat lookup') {
        	hideallforwards();
        }
	else {
		hideallforwards();
		showdiv(id);  
	}
}

function switchaddress(id){       
        if(id == 'addraddr') {
                hidediv('addralias');
        	showdiv('addraddr');
        }
	else {
		hidediv('addraddr');
		showdiv('addralias');  
	}
}

function switchlistener(id){
        if(id == 'lsnraddr') {
                hidediv('lsnralias');
                showdiv('lsnraddr');
        }
        else {
                hidediv('lsnraddr');
                showdiv('lsnralias');
        }
}

function switchid(id){       
        hideallids();
        if(id == 'http' || id == 'https') {
		showdiv('httphttps');
	}	
}

function hideallforwards(){
        //loop through the array and hide each element by id        
        for (var i=0;i<forwards.length;i++){
                hidediv(forwards[i]);        
        }                 
}

function hideallids(){
        //loop through the array and hide each element by id
        for (var i=0;i<ids.length;i++){
        	hidediv(ids[i]);	
	}                 
}

function hidediv(id) {
        //safe function to hide an element with a specified id
        if (document.getElementById) { // DOM3 = IE5, NS6
                document.getElementById(id).style.display = 'none';
        }
        else {
                if (document.layers) { // Netscape 4
                        document.id.display = 'none';
                }
                else { // IE 4
                        document.all.id.style.display = 'none';
                }
        }
}

function showdiv(id) {
        //safe function to show an element with a specified id
                  
        if (document.getElementById) { // DOM3 = IE5, NS6
                document.getElementById(id).style.display = 'inline';
        }
        else {
                if (document.layers) { // Netscape 4
                        document.id.display = 'block';
                }
                else { // IE 4
                        document.all.id.style.display = 'block';
                }
        }
}
-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<center><id="Address">
<form action="firewall_relayd_relays_edit.php"
	onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
<table width="100%" border="0" cellpadding="6" cellspacing="0">
	<tr>
		<td width="22%" valign="top" class="vncellreq">Name</td>
		<td width="78%" class="vtable"><input name="name" type="text"
			class="formfld" id="name" size="20"
			value="<?=htmlspecialchars($pconfig['name']);?>"> <input
			name="members" type="hidden" value="">
	
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Description</td>
		<td width="78%" class="vtable"><input name="descr" type="text"
			class="formfld" id="descr" size="40"
			value="<?=htmlspecialchars($pconfig['descr']);?>"> <br>
		<span class="vexpl">You may enter a description here for your
		reference (not parsed).</span></td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Protocol</td>
		<td width="78%" class="vtable"><?php if (sizeof($config['relays']['proxyaction']) != 0): ?>
		<select name="proto" class="formfld" id="proto">
		<?php foreach($config['relays']['proxyaction'] as $i): ?>
			<option value="<?=$i['name'];?>"
			<?php if ($i == $pconfig['proto']['name']) echo "selected"; ?>><?=$i['name'];?>
			</option>
			<?php endforeach; ?>
		</select><br>
		<?php else: ?> <span class="red"><strong>No protocols defined, you
		must define a protocol before you can configure a relay!</span></td>
		<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Listener</td>
		<td width="78%" class="vtable"><br>
		<br>
		<select name="listenertype" class="formfld" id="listenertype"
			onChange="switchlistener(document.iform.listenertype.value)
">
			<option value="lsnralias"
			<?php if ('lsrnalias' == $pconfig['listenertype']) echo "selected"; ?>>Alias</option>
			<option value="lsnraddr"
			<?php if ('lsrnaddr' == $pconfig['listenertype']) echo "selected"; ?>>Address</option>
		</select>
		<div id='lsnraddr' style="display: none;"><input name="listenerip"
			type="text" class="formfld" id="listenerip" size="15"
			value="<?=htmlspecialchars($pconfig['listenerip']);?>"></div>
		<div id='lsnralias' style="display: none;"><select
			name="listeneralias" class="formfld" id="listeneralias">
			<?php
			$defaults = filter_system_aliases_names_generate();
			$defaults = split(' ', $defaults);
			foreach( $defaults as $i): ?>
			<option value="<?='$' . $i;?>"
			<?php if ('$' . $i == $pconfig['listeneralias']) echo "selected"; ?>>
				<?=$i;?></option>
				<?php endforeach; ?>
				<?php foreach($config['aliases']['alias'] as $i): ?>
			<option value="<?='$' . $i['name'];?>"
			<?php if ('$' . $i['name'] == $pconfig['listeneralias']) echo "selected"; ?>>
				<?=$i['name'];?></option>
				<?php endforeach; ?>
		</select></div>
		Port:<?=$mandfldhtml;?><input name="listenerport" type="text"
			class="formfld" id="listenerport" size="5"
			value="<?=htmlspecialchars($pconfig['listenerport']);?>"></td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Forward To</td>
		<td width="78%" class="vtable"><select name="forward" class="formfld"
			id="forward" onChange="switchforward(document.iform.forward.value)">
			<option value="nat lookup"
			<?php if ('nat lookup' == $pconfig['forward']) echo "selected"; ?>>nat
			lookup</option>
			<option value="serverpool"
			<?php if ('serverpool' == $pconfig['forward']) echo "selected"; ?>>server
			pool</option>
			<option value="address"
			<?php if ('address' == $pconfig['forward']) echo "selected"; ?>>address</option>
		</select>
		<div id='address' style="display: none;"><br>
		<input name="transparent" type="checkbox" value="transparent"
		<?php if ($pconfig['transparent']) echo "checked"; ?>>Transparent Mode
		<br>
		<br>
		<select name="addrtype" class="formfld" id="addrtype"
			onChange="switchaddress(document.iform.addrtype.value)
">
			<option value="addralias"
			<?php if ('addralias' == $pconfig['addrtype']) echo "selected"; ?>>Alias</option>
			<option value="addraddr"
			<?php if ('addraddr' == $pconfig['addrtype']) echo "selected"; ?>>Address</option>
		</select>
		<div id='addraddr' style="display: none;"><input name="addressip"
			type="text" class="formfld" id="addressip" size="15"
			value="<?=htmlspecialchars($pconfig['addressip']);?>"></div>
		<div id='addralias' style="display: none;"><select name="addressalias"
			class="formfld" id="addressalias">
			<?php
			$defaults = filter_system_aliases_names_generate();
			$defaults = split(' ', $defaults);
			foreach( $defaults as $i): ?>
			<option value="<?='$' . $i;?>"
			<?php if ('$' . $i == $pconfig['addressalias']) echo "selected"; ?>>
				<?=$i;?> <?php endforeach; ?> <?php foreach($config['aliases']['alias'] as $i): ?>
			<option value="<?='$' . $i['name'];?>"
			<?php if ('$' . $i['name'] == $pconfig['addressalias']) echo "selected"; ?>>
				<?=$i['name'];?></option>
				<?php endforeach; ?>
		
		</select></div>
		Internal Port: <?=$mandfldhtml;?><input name="addressinternalport"
			type="text" class="formfld" id="addressinternalport" size="5"
			value="<?=htmlspecialchars($pconfig['internalport']);?>"></div>
		<div id='serverpool' style="display: none;"><br>
		Server Pool: <select name="pool" class="formfld" id="pool">
		<?php foreach($config['aliases']['alias'] as $i): ?>
			<option value="<?=$i;?>"
			<?php if ($i == $pconfig['pool']) echo "selected"; ?>><?=$i;?></option>
			<?php endforeach; ?>
		</select> <br>
		<br>
		Internal Port: <?=$mandfldhtml;?><input name="poolinternalport"
			type="text" class="formfld" id="poolinternalport" size="5"
			value="<?=htmlspecialchars($pconfig['internalport']);?>"> <br>
		<br>
		Health Check: <select name="healthcheck" class="formfld"
			id="healthcheck"
			onChange="switchid(document.iform.healthcheck.value)">
			<?php foreach ($healthchecks as $healthcheck => $healthcheckname): ?>
			<option value="<?=$healthcheckname;?>"
			<?php if ($healthcheckname == $pconfig['healthcheck']) echo "selected"; ?>>
				<?=htmlspecialchars($healthcheckname);?></option>
				<?php endforeach; ?>
		</select>
		<div id='httphttps' style="display: none;"><br>
		path:<input name="path" type="text" class="formfld" id="path"
			size="25" value="<?=htmlspecialchars($pconfig['path']);?>"> code:<input
			name="code" type="text" class="formfld" id="code" size="5"
			value="<?=htmlspecialchars($pconfig['code']);?>"></div>
		<br>
		<br>
		Load Balancing Algorithm: <select name="alg" class="formfld" id="alg">
		<?php foreach ($relayalgs as $alg => $algname): ?>
			<option value="<?=$algname;?>"
			<?php if ($algname == $pconfig['alg']) echo "selected"; ?>><?=htmlspecialchars($algname);?>
			</option>
			<?php endforeach; ?>
		</select></div>
		</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Session Timeout</td>
		<td width="78%" class="vtable"><?=$mandfldhtml;?><input name="timeout"
			type="text" class="formfld" id="listenerip" size="6"
			value="<?=htmlspecialchars($pconfig['timeout']);?>"></td>
	</tr>
	<td width="22%" valign="top">&nbsp;</td>
	<td width="78%"><input name="Submit" type="submit" class="formbtn"
		value="Save"> <?php if (isset($id) && $a_relays[$id]): ?> <input
		name="id" type="hidden" value="<?=$id;?>"> <?php endif; ?> <input
		name="after" type="hidden" value="<?=$after;?>"></td>
	</tr>
	</tr>
</table>
<script language="JavaScript">
switchforward(document.iform.forward.value)
switchaddress(document.iform.addrtype.value)
switchlistener(document.iform.listenertype.value)
switchid(document.iform.healthcheck.value)
</script> <?php include("fend.inc"); ?>