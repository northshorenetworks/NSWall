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

relays_sort();
$a_relays = &$config['relays']['relay'];

$id = $_GET['id'];
if (isset($_POST['id']))
$id = $_POST['id'];

if (isset($id) && $a_relays[$id]) {
	$pconfig['name'] = $a_relays[$id]['name'];
	$pconfig['descr'] = $a_relays[$id]['descr'];
	$pconfig['listener'] = $a_relays[$id]['listener'];
	$pconfig['pool'] = $a_relays[$id]['pool'];
	$pconfig['healthcheck'] = $a_relays[$id]['healthcheck'];
	$pconfig['alg'] = $a_relays[$id]['alg'];
	$pconfig['proto'] = $a_relays[$id]['proto'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "name");
	$reqdfieldsn = explode(",", "Name,");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	$relay = array();
	$relay['name'] = $_POST['name'];
	$relay['descr'] = $_POST['descr'];
	$relay['listener'] = $_POST['listener'];
	$relay['pool'] = $_POST['pool'];
	$relay['healthcheck'] = $_POST['healthcheck'];
	$relay['alg'] = $_POST['alg'];
	$relay['proto'] = $_POST['proto'];
	if (isset($id) && $a_relays[$id])
	$a_relays[$id] = $relay;
	else
	$a_relays[] = $relay;

	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);

	if (filter_parse_config($xmlconfig)) {
		$input_errors[] = "Could not parse the generated config file";
		$input_errors[] = "See log file for details";
		$input_errors[] = "XML Config file not modified";
	}

	if (!$input_errors) {

		write_config();

		header("Location: firewall_relays.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
var portsenabled = 1;

function addOption(selectbox,text,value)
{
var optn = document.createElement("OPTION");
document.getElementById(selectbox).options.add(optn);
text = text.replace(/\/32/g, "");
value = value.replace(/\/32/g, "");
text = text.replace(/:$/, "");
value = value.replace(/:$/, "");
optn.text = text;
optn.value = value;
document.iform.srchost.value="";
document.iform.srcnet.value="";
document.iform.srctable.value="";

if (document.getElementById(selectbox).name=="MEMBERS") {
   document.iform.members.value="";
}
}

function removeOptions(selectbox)
{
var i;
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
selectbox.remove(i);
}
}

function selectAllOptions(selectbox)
{
var i; 
for(i=selectbox.options.length-1;i>=0;i--)
{
selectbox.options[i].selected = true;
}
}

function createProp(selectbox)
{
var i;
var prop = '';
var rdrprop ='';
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
prop += selectbox.options[i].value + ', ';   
}
}
prop = prop.replace(/, $/,"");
prop = prop.replace(/host:/g,"");
prop = prop.replace(/net:/g,"");
prop = prop.replace(/table:/g,'$');
rdrprop = rdrprop.replace(/snat:/g,"");
if (selectbox.name=="MEMBERS") {
   document.iform.members.value=prop
   }
}

function prepareSubmit()
{
selectAllOptions(MEMBERS);
createProp(MEMBERS);
}

var ids=new Array('srchost','srcnet','srctable');

function switchsrcid(id){       
        hideallsrcids();
        showdiv(id);
}

function hideallsrcids(){
        //loop through the array and hide each element by id
        for (var i=0;i<ids.length;i++){
                if(ids[i].match( /^src/ )) {
                        hidediv(ids[i]);
                }
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
                document.getElementById(id).style.display = 'block';
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
<form action="firewall_relays_edit.php"
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
		<td width="22%" valign="top" class="vncellreq">Listener</td>
		<td width="78%" class="vtable"><?=$mandfldhtml;?><input
			name="listener" type="text" class="formfld" id="listener" size="16"
			value="<?=htmlspecialchars($pconfig['listener']);?>">
	
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Server Pool</td>
		<td width="78%" class="vtable"><select name="pool" class="formfld"
			id="pool">
			<?php foreach($config['aliases']['alias'] as $i): ?>
			<option value="<?='$' . $i['name'];?>"
			<?php if ($i == $pconfig['pool']) echo "selected"; ?>><?=$i['name'];?>
			</option>
			<?php endforeach; ?>
		</select><br>
		</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Pool Health Checking</td>
		<td width="78%" class="vtable"><select name="healthcheck"
			class="formfld">
			<?php foreach ($healthchecks as $healthcheck => $healthcheckname): ?>
			<option value="<?=$healthcheck;?>"
			<?php if ($healthcheck == $pconfig['healthcheck']) echo "selected"; ?>>
				<?=htmlspecialchars($healthcheckname);?></option>
				<?php endforeach; ?>
		</select></td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Scheduling Algorithm</td>
		<td width="78%" class="vtable"><select name="alg" class="formfld">
		<?php foreach ($relayalgs as $alg => $algname): ?>
			<option value="<?=$alg;?>"
			<?php if ($alg == $pconfig['alg']) echo "selected"; ?>><?=htmlspecialchars($algname);?>
			</option>
			<?php endforeach; ?>
		</select></td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">Protocol</td>
		<td width="78%" class="vtable"><select name="proto" class="formfld"
			id="proto">
			<?php foreach($config['relays']['protocol'] as $i): ?>
			<option value="<?='$' . $i['name'];?>"
			<?php if ($i == $pconfig['proto']) echo "selected"; ?>><?=$i['name'];?>
			</option>
			<?php endforeach; ?>
		</select><br>
		</td>
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
<!--
//-->
</script> <?php include("fend.inc"); ?>