#!/bin/php
<?php 
/*
	$Id: firewall_protocols_edit.php,v 1.7 2008/10/20 22:42:56 jrecords Exp $
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

$pgtitle = array("Firewall", "Relays", "Edit Protocol");
require("guiconfig.inc");

if (!is_array($config['relays']['relay-protocol']))
	$config['relays']['relay-protocol'] = array();

protocols_sort();
$a_protocol = &$config['relays']['relay-protocol'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_protocol[$id]) {
	$pconfig['name'] = $a_protocol[$id]['name'];
	$pconfig['actionlist'] = preg_replace("/Comment:/", '# ', $a_protocol[$id]['actionlist']);
	$pconfig['descr'] = $a_protocol[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "name actions");
	$reqdfieldsn = explode(",", "Name,Members");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	$protocol = array();
        $protocol['name'] = $_POST['name'];
        $protocol['descr'] = $_POST['descr'];
	$actionslist = array_reverse(explode(',', $_POST['actions']));
        for($i=0;$i<sizeof($actionslist); $i++) {
                $action = 'action'."$i";
                $prop = preg_replace("/ /", " ", $actionslist[$i]);
                $protocol['actionlist'][$action] = $prop;
        } 
        if (isset($id) && $a_protocol[$id])
                $a_protocol[$id] = $protocol;
        else
                $a_protocol[] = $protocol;

	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);
        
        if (filter_parse_config($xmlconfig)) {
		$input_errors[] = "Could not parse the generated config file";
		$input_errors[] = "See log file for details";
		$input_errors[] = "XML Config file not modified";
	}

	if (!$input_errors) {
		
		write_config();
		
		header("Location: firewall_protocols.php");
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
optn.text = text;
optn.value = value;
if (document.getElementById(selectbox).options[0].text == "") {
         document.getElementById(selectbox).remove(0);
}
document.iform.comment.value="";
document.iform.appendto.value="";
document.iform.appendfrom.value="";
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
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
prop += selectbox.options[i].value + ',';   
}
}
if (selectbox.name=="ACTIONS") {
   prop = prop.replace(/,$/,"");
   prop = prop.replace(/# /g,"Comment:");
   document.iform.actions.value=prop
   }
}

function prepareSubmit()
{
selectAllOptions(ACTIONS);
createProp(ACTIONS);
}

var protocolids=new Array('http', 'tcp','dns');

function switchprotocol(id){       
	hideprotocolids();
        showdiv(id);
        }
}

function hideprotocolids(){
        //loop through the array and hide each element by id
        for (var i=0;i<protocolids.length;i++){
                        hidediv(protocolids[i]);
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
<center>
	<id="Address">
             <form action="firewall_protocols_edit.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	      <table width="100%" border="0" cellpadding="6" cellspacing="0">
        	<tr>
                  <td width="22%" valign="top" class="vncellreq">Name</td>
                  <td width="78%" class="vtable">
                    <input name="name" type="text" class="formfld" id="name" size="20" value="<?=htmlspecialchars($pconfig['name']);?>">
                    <input name="actions" type="hidden" value="">
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">Description</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">You may enter a description here 
                    for your reference (not parsed).</span></td>
                </tr>
		<tr> 
                  <td width="22%" valign="top" class="vncellreq">Protocol</td>
                  <td width="78%" class="vtable">
        	  <select name="proto" class="formfld" id="proto" onChange="switchprotocol(document.iform.proto.value)">
                      <?php foreach ($relayprotos as $proto => $protoname): ?>
                      <option value="<?=$protoname;?>" <?php if ($proto == $pconfig['alg']) echo "selected"; ?>> 
                      <?=htmlspecialchars($protoname);?>
                      </option>
                      <?php endforeach; ?>
                    </select> 
                  </td>
                </tr>
		<tr>
                <td width="22%" valign="top" class="vncellreq">Profiles</td>
                <td width="78%" class="vtable">
                <SELECT style="width: 375px; height: 125px" id="ACTIONS" NAME="ACTIONS" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['actionlist']); $i++): ?>
                <option value="<?=$pconfig['actionlist']["action$i"];?>">
                <?=$pconfig['actionlist']["action$i"];?>
                </option>
                <?php endfor; ?>
		<input type=button onClick="removeOptions(ACTIONS)"; value='Remove Selected'><br><br>
		</td>
                </tr>	
		<tr> 
                  <td width="22%" valign="top" class="vncellreq">Action</td>
                  <td width="78%" class="vtable">
                 <select name="act1" class="formfld" id="act1" onChange="switchact1id(document.iform.act1.value)">
                      <?php foreach ($protoaction1 as $act1 => $act1name): ?>
                      <option value="<?=$act1name;?>" <?php if ($act1 == $pconfig['act1']) echo "selected"; ?>> 
                      <?=htmlspecialchars($act1name);?>
                      </option>
                      <?php endforeach; ?>
                </select>  
		<div id='response' style="display:none;">
                header 
                <select name="responseact3" class="formfld" id="responseact3" onChange="switchact3id(document.iform.responseact3.value)">
		      <?php foreach ($protoaction3 as $act3 => $act3name): ?>
                      <option value="<?=$act3name;?>" <?php if ($act3 == $pconfig['act3']) echo "selected"; ?>> 
                      <?=htmlspecialchars($act3name);?>
                      </option>
                      <?php endforeach; ?>
                </select> 
		</div>
		<div id='request' style="display:block;">
                <select name="act2" class="formfld">
                      <?php foreach ($protoaction2 as $act2 => $act2name): ?>
                      <option value="<?=$act2name;?>" <?php if ($act2 == $pconfig['act2']) echo "selected"; ?>> 
                      <?=htmlspecialchars($act2name);?>
                      </option>
                      <?php endforeach; ?>
                </select> 
                      <select name="requestact3" class="formfld" id="requestact3" onChange="switchact3id(document.iform.requestact3.value)">
	       	      <?php foreach ($protoaction3 as $act3 => $act3name): ?>
                      <option value="<?=$act3name;?>" <?php if ($act3 == $pconfig['act3']) echo "selected"; ?>> 
                      <?=htmlspecialchars($act3name);?>
                      </option>
                      <?php endforeach; ?>
                </select> 
                </div>
		<div id='comment' style="display:none;">
		Comment:<input name="comment" type="text" class="formfld" id="comment" size="70" value="<?=htmlspecialchars($pconfig['from']);?>"><br>
		<input type=button onClick="addOption('ACTIONS','# ' + document.iform.comment.value,'# ' + document.iform.comment.value)"; value='Add'>
		</div>
		<div id='linereturn' style="display:none;">
                <input type=button onClick="addOption('ACTIONS', '','')"; value='Add Line Return'>
                </div>
		<div id='return error' style="display:none;">
                <input type=button onClick="addOption('ACTIONS', 'return error','return error')"; value='Add'>
                </div>	
		<div id='label' style="display:none;">
                label<input name="label" type="text" class="formfld" id="label" size="70" value="<?=htmlspecialchars($pconfig['from']);?>"><br>
                <input type=button onClick="addOption('ACTIONS','Label ' + document.iform.label.value,'Lable ' + document.iform.label.value)"; value='Add'>
                </div>
		<div id='log' style="display:none;">
                log<input name="log" type="text" class="formfld" id="log" size="70" value="<?=htmlspecialchars($pconfig['from']);?>"><br>
                <input type=button onClick="addOption('ACTIONS','log ' + document.iform.log.value,'log ' + document.iform.log.value)"; value='Add'>
                </div>
		<div id='append' style="display:block;">
                <input name="appendfrom" type="text" class="formfld" id="appendfrom" size="25" value="">to
                <input name="appendto" type="text" class="formfld" id="appendto" size="25" value=""><br>
		<input type=button onClick="addOption('ACTIONS',document.iform.act1.value + ' ' + document.iform.act2.value + ' append ' + document.iform.appendfrom.value + ' to ' + document.iform.appendto.value,document.iform.act1.value + ' ' + document.iform.act2.value + ' append ' + document.iform.appendfrom.value + ' to ' + document.iform.appendto.value)"; value='Add'>
                </div>
		<div id='change' style="display:none;">
                <input name="changefrom" type="text" class="formfld" id="changefrom" size="25" value="">to
                <input name="changeto" type="text" class="formfld" id="changeto" size="25" value=""><br>
                <input type=button onClick="addOption('ACTIONS',document.iform.act1.value + ' ' + document.iform.act2.value + ' change ' + document.iform.changefrom.value + ' to ' + document.iform.changeto.value,document.iform.act1.value + ' ' + document.iform.act2.value + ' change ' + document.iform.changefrom.value + ' to ' + document.iform.changeto.value)"; value='Add'>
                </div>
		<div id='expect' style="display:none;">
                <input name="expectfrom" type="text" class="formfld" id="expectfrom" size="25" value="">from
                <input name="expectto" type="text" class="formfld" id="expectto" size="25" value=""><br>
                <input type=button onClick="addOption('ACTIONS',document.iform.act1.value + ' ' + document.iform.act2.value + ' expect ' + document.iform.expectfrom.value + ' from ' + document.iform.expectto.value,document.iform.act1.value + ' ' + document.iform.act2.value + ' expect ' + document.iform.expectfrom.value + ' from ' + document.iform.expectto.value)"; value='Add'>
                </div>
		<div id='expect digest' style="display:none;">
                <input name="expectdigestfrom" type="text" class="formfld" id="expectdigestfrom" size="25" value="">from
                <input name="expectdigestto" type="text" class="formfld" id="expectdigestto" size="25" value=""><br>
                <input type=button onClick="addOption('ACTIONS',document.iform.act1.value + ' ' + document.iform.act2.value + ' expect digest ' + document.iform.expectdigestfrom.value + ' from ' + document.iform.expectdigestto.value,document.iform.act1.value + ' ' + document.iform.act2.value + ' expect digest' + document.iform.expectdigestfrom.value + ' from ' + document.iform.expectdigestto.value)"; value='Add'>
                </div>
		<div id='filter' style="display:none;">
                <input name="filterfrom" type="text" class="formfld" id="filterfrom" size="25" value="">from
                <input name="filterto" type="text" class="formfld" id="filterto" size="25" value=""><br>
                <input type=button onClick="addOption('ACTIONS',document.iform.act1.value + ' ' + document.iform.act2.value + ' filter ' + document.iform.filterfrom.value + ' from ' + document.iform.filterto.value,document.iform.act1.value + ' ' + document.iform.act2.value + ' filter ' + document.iform.filterfrom.value + ' from ' + document.iform.filterto.value)"; value='Add'>
                </div>
                <div id='filter digest' style="display:none;">
                <input name="filterdigestfrom" type="text" class="formfld" id="filterdigestfrom" size="25" value="">from
                <input name="filterdigestto" type="text" class="formfld" id="filterdigestto" size="25" value=""><br>
                <input type=button onClick="addOption('ACTIONS',document.iform.act1.value + ' ' + document.iform.act2.value + ' filter digest ' + document.iform.filterdigestfrom.value + ' from ' + document.iform.filterdigestto.value,document.iform.act1.value + ' ' + document.iform.act2.value + ' filter digest' + document.iform.filterdigestfrom.value + ' from ' + document.iform.filterdigestto.value)"; value='Add'>
		</div>
		<div id='hash' style="display:none;">
                hash<input name="hash" type="text" class="formfld" id="hash" size="70" value="<?=htmlspecialchars($pconfig['from']);?>"><br>
                <input type=button onClick="addOption('ACTIONS','hash ' + document.iform.hash.value,'hash ' + document.iform.hash.value)"; value='Add'>
                </div>
		<div id='remove' style="display:none;">
                remove<input name="remove" type="log" class="formfld" id="remove" size="70" value="<?=htmlspecialchars($pconfig['from']);?>"><br>
                <input type=button onClick="addOption('ACTIONS','remove ' + document.iform.remove.value,'remove ' + document.iform.remove.value)"; value='Add'>
                </div>
		</td>
		</tr>
                <tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_protocol[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>
		</tr>
              </table>
<script language="JavaScript">
<!--
//-->
</script>
<?php include("fend.inc"); ?>
