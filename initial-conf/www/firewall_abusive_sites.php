#!/bin/php
<?php 
/*
	$Id: firewall_abusive_sites.php,v 1.1 2009/04/20 06:56:52 jrecords Exp $
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

$pgtitle = array("Firewall", "Aliases", "Edit Alias");
require("guiconfig.inc");

if (!is_array($config['filter']['abusivehosts']['abusivehost']))
	$config['filter']['abusivehosts']['abusivehost'] = array();

$a_abusivehostes = &$config['filter']['abusivehost'];

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	$memberslist = array_reverse(explode(',', $_POST['members']));
        for($i=0;$i<sizeof($memberslist); $i++) {
                $member = 'member'."$i";
                $prop = preg_replace("/ /", "", $memberslist[$i]);
                $abusivehost['memberlist'][$member] = $prop;
       	} 
        $a_abusivehostes[] = $abusivehost;

	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);
        
        if (filter_parse_config($xmlconfig)) {
		$input_errors[] = "Could not parse the generated config file";
		$input_errors[] = "See log file for details";
		$input_errors[] = "XML Config file not modified";
	}

	if (!$input_errors) {
		
		write_config();
	 	touch($d_filterconfdirty_path);	
		header("Location: firewall_abusive_sites.php");
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
<center>
	<id="Address">
             <form action="firewall_abusive_sites.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	      <table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr>
                <td width="22%" valign="top" class="vncellreq">Members</td>
                <td width="78%" class="vtable">
                <SELECT style="width: 150px; height: 100px" id="MEMBERS" NAME="MEMBERS" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['memberlist']); $i++): ?>
                <option value="<?=$pconfig['memberlist']["member$i"];?>">
                <?=$pconfig['memberlist']["member$i"];?>
                </option>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(MEMBERS)"; value='Remove Selected'><br><br>
                  <strong>Type</strong>
                    <select name="srctype" class="formfld" id="srctype" onChange="switchsrcid(document.iform.srctype.value)">
                      <option value="srchost" selected>Host</option>
                      <option value="srcnet" >Network</option>
                      <option value="srctable" >Alias</option>
                    </select>
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
                <div id='srctable' style="display:none;">
                 <strong>Alias</strong>
                    <select name="srctable" class="formfld" id="srctable">
                      <?php foreach($config['tablees']['table'] as $i): ?>
                      <option value="<?='$' . $i['name'];?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                <input type=button onClick="addOption('MEMBERS',document.iform.srctable.value + '/32','net' + ':' + document.iform.srctable.value + '/32')"; value='Add'>
                </div>
                </td>
                </tr>
                <tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_abusivehostes[$id]): ?>
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

