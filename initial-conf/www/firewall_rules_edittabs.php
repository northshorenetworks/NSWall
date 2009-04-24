#!/bin/php
<?php 
/*
	$Id: firewall_rules_edittabs.php,v 1.31 2009/03/18 07:34:47 jrecords Exp $
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

$pgtitle = array("Firewall", "Rules", "Edit");
require("guiconfig.inc");

$specialsrcdst = explode(" ", "any wanip lan pptp");

if (!is_array($config['filter']['rule'])) {
	$config['filter']['rule'] = array();
}
filter_rules_sort();
$a_filter = &$config['filter']['rule'];

$id = $_GET['id'];
if (is_numeric($_POST['id']))
	$id = $_POST['id'];
	
$after = $_GET['after'];

if (isset($_POST['after']))
	$after = $_POST['after'];

if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
	$after = $_GET['dup'];
}

function is_specialnet($net) {
	global $specialsrcdst;
	
	if (in_array($net, $specialsrcdst) || (strstr($net, "opt") && !is_alias($net)))
		return true;
	else
		return false;
}

if (isset($id) && $a_filter[$id]) {
	$pconfig['name'] = $a_filter[$id]['name'];
	$pconfig['descr'] = $a_filter[$id]['descr'];
	$pconfig['type'] = $a_filter[$id]['type'];
	$pconfig['proto'] = $a_filter[$id]['protocol'];
	$pconfig['interface'] = $a_filter[$id]['interface'];
	$pconfig['srclist'] = $a_filter[$id]['srclist'];
	$pconfig['dstlist'] = $a_filter[$id]['dstlist'];
 	$pconfig['dstrelay'] = $a_filter[$id]['dstrelay'];
	$pconfig['portforward'] = isset($a_filter[$id]['portforward']);
	$pconfig['tcplist'] = $a_filter[$id]['tcplist'];
        $pconfig['udplist'] = $a_filter[$id]['udplist'];	
	$pconfig['disabled'] = isset($a_filter[$id]['disabled']);
	$pconfig['log'] = isset($a_filter[$id]['log']);
	$pconfig['altqbucket'] = $a_filter[$id]['options']['altqbucket'];
	$pconfig['altqlowdelay'] = isset($a_filter[$id]['options']['altqlowdelay']);
	$pconfig['state'] = $a_filter[$id]['options']['state'];
	$pconfig['maxstates'] = $a_filter[$id]['options']['maxstates'];
	$pconfig['srctrack'] = $a_filter[$id]['options']['srctrack'];
	$pconfig['maxsrcnodes'] = $a_filter[$id]['options']['maxsrcnodes'];
	$pconfig['maxsrcstates'] = $a_filter[$id]['options']['maxsrcstates'];
	$pconfig['maxsrcconns'] = $a_filter[$id]['options']['maxsrcconns'];
	$pconfig['maxsrcconnrate'] = $a_filter[$id]['options']['maxsrcconnrate'];
	$pconfig['overload'] = isset($a_filter[$id]['options']['overload']);
	$pconfig['flush'] = isset($a_filter[$id]['options']['flush']);
} else {
	/* defaults */
	if ($_GET['if'])
		$pconfig['interface'] = $_GET['if'];
	$pconfig['type'] = "pass";
	$pconfig['srclist']['src0'] = "any";
	$pconfig['dstlist']['dst0'] = "any";
}

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */ 
	$reqdfields = explode(" ", "type interface srclist dstlist");
	$reqdfieldsn = explode(",", "Type,Interface,Source,Destination");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
		$filterent = array();
		$filterent['name'] = $_POST['name'];
		$filterent['descr'] = $_POST['descr'];
 		$filterent['type'] = $_POST['type'];
		$filterent['interface'] = $_POST['interface'];
		if ($_POST['rdrlist']) {
			$filterent['rdrlist'] = $_POST['rdrlist'];
		}
		$srclist = array_reverse(explode(',', $_POST['srclist']));
		for($i=0;$i<sizeof($srclist); $i++) {
			$member = 'src'."$i";
			$source = preg_replace("/ /", "", $srclist[$i]);
			$filterent['srclist'][$member] = $source;
		}
		$dstlist = array_reverse(explode(',', $_POST['dstlist']));
                for($i=0;$i<sizeof($dstlist); $i++) {
                        $member = 'dst'."$i";
                        $dest = preg_replace("/ /", "", $dstlist[$i]);
                        $filterent['dstlist'][$member] = $dest;
                }
		if ($_POST['tcpports']) {
                	$tcplist = array_reverse(explode(',', $_POST['tcpports']));
			for($i=0;$i<sizeof($tcplist); $i++) {
                        	$member = 'tcp'."$i";
                        	$tcp = preg_replace("/ /", "", $tcplist[$i]);
                        	$filterent['tcplist'][$member] = $tcp;
                	}
		}
		if ($_POST['udpports']) {
                        $udplist = array_reverse(explode(',', $_POST['udpports']));
			for($i=0;$i<sizeof($udplist); $i++) {
                                $member = 'udp'."$i";
                                $udp = preg_replace("/ /", "", $udplist[$i]);
                                $filterent['udplist'][$member] = $udp;
                        }
                }
               	if ($_POST['ipprotos']) {
                        $ipprotolist = array_reverse(explode(',', $_POST['ipprotos']));
			for($i=0;$i<sizeof($ipprotolist); $i++) {
                                $member = 'ip'."$i";
                                $ip = preg_replace("/ /", "", $ipprotolist[$i]);
                                $filterent['ipprotolist'][$member] = $ip;
                        }
                }
		$filterent['disabled'] = $_POST['disabled'] ? true : false;
		if ($_POST['portforward']) {
			$filterent['portforward'] = $_POST['portforward'] ? true : false;
			$filterent['dstrelay'] = $_POST['dstrelay'];
		}
		$filterent['log'] = $_POST['log'] ? true : false;
		/* options stuff */
                if ($_POST['altqbucket']) {
                        $filterent['options']['altqbucket'] = $_POST['altqbucket'];
                }
                if ($_POST['altqlowdelay']) {
                        $filterent['options']['altqlowdelay'] = $_POST['altqlowdelay'] ? true : false;
                }
                if ($_POST['state']) {
                        $filterent['options']['state'] = $_POST['state'];
                }
                if ($_POST['maxstates']) {
                        $filterent['options']['maxstates'] = $_POST['maxstates'];
                }
                if ($_POST['srctrack']) {
                        $filterent['options']['srctrack'] = $_POST['srctrack'];
                }
                if ($_POST['maxsrcnodes']) {
                        $filterent['options']['maxsrcnodes'] = $_POST['maxsrcnodes'];
                }
                if ($_POST['maxsrcstates']) {
                        $filterent['options']['maxsrcstates'] = $_POST['maxsrcstates'];
                }
                if ($_POST['maxsrcconns']) {
                        $filterent['options']['maxsrcconns'] = $_POST['maxsrcconns'];
                }
                if ($_POST['maxsrcconnrate']) {
                        $filterent['options']['maxsrcconnrate'] = $_POST['maxsrcconnrate'];
                }
                if ($_POST['overload']) {
                        $filterent['options']['overload'] = $_POST['overload'] ? true : false;
                }
                if ($_POST['flush']) {
                        $filterent['options']['flush'] = $_POST['flush'] ? true : false;
                }
                if (isset($id) && $a_filter[$id])
                        $a_filter[$id] = $filterent;
                else {
                        if (is_numeric($after))
                                array_splice($a_filter, $after+1, 0, array($filterent));
                        else
                                $a_filter[] = $filterent;
                }	
	
	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);

        if (filter_parse_config($config)) {
                $input_errors[] = "Could not parse the generated config file";
                $input_errors[] = "See log file for details";
                $input_errors[] = "XML Config file not modified";
        }

	if (!$input_errors) {
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_rules.php?if=" . $_POST['interface']);
		Exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--

function verifyIP (IPvalue) {
errorString = "";
theName = "IPaddress";

var ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
var ipArray = IPvalue.match(ipPattern);

if (IPvalue == "0.0.0.0")
errorString = errorString + theName + ': '+IPvalue+' is a special IP address and cannot be used here.';
else if (IPvalue == "255.255.255.255")
errorString = errorString + theName + ': '+IPvalue+' is a special IP address and cannot be used here.';
if (ipArray == null)
errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
else {
for (i = 0; i < 4; i++) {
thisSegment = ipArray[i];
if (thisSegment > 255) {
errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
i = 4;
}
if ((i == 0) && (thisSegment > 255)) {
errorString = errorString + theName + ': '+IPvalue+' is a special IP address and cannot be used here.';
i = 4;
      }
   }
}
extensionLength = 3;
if (errorString == "") {
return 0;
}
else {
alert (errorString);
return 1;
}
}

function addOption(selectbox,text,value)
{
var optn = document.createElement("OPTION");
text = text.replace(/\/32/g, "");
value = value.replace(/\/32/g, "");
text = text.replace(/:$/, "");
value = value.replace(/:$/, "");
if(value.match(/^host/)) {
   if(verifyIP(text) == 0) {
      document.getElementById(selectbox).options.add(optn);
      optn.text = text;
      optn.value = value;
   }
}
else {
      document.getElementById(selectbox).options.add(optn);
      optn.text = text;
      optn.value = value;
}
if (document.getElementById(selectbox).name=="SRCADDR") {
   document.iform.srchost.value="";
   document.iform.srcnet.value="";
   document.iform.srcalias.value="";
      if (document.getElementById(selectbox).options[0].text == "any") {
         document.getElementById(selectbox).remove(0);
      }
}
if (document.getElementById(selectbox).name=="DSTADDR") {
   document.iform.dsthost.value="";
   document.iform.dstnet.value="";
   document.iform.snatint.value="";
   document.iform.snatext.value="";
   document.iform.dstalias.value="";
      if (optn.text != "any") {
        if (document.getElementById(selectbox).options[0].text == "any") {
           document.getElementById(selectbox).remove(0);
        }
      }
}
}

function removeOptions(selectbox)
{
var i;
for(i=selectbox.options.length-1;i>=0;i--)
{
   if(selectbox.options[i].selected)
      selectbox.remove(i);
      if(selectbox.options.length == 0 && selectbox.name != 'PROTOLIST') {
         var optn = document.createElement("OPTION");
         document.getElementById(selectbox.name).options.add(optn);
         optn.text = 'any';
         optn.value = 'any';
   }
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
prop += selectbox.options[i].value + ', ';   
}
}
prop = prop.replace(/, $/,"");
prop = prop.replace(/snat:/g,"");
prop = prop.replace(/host:/g,"");
prop = prop.replace(/net:/g,"");
prop = prop.replace(/alias:/g,'$');
if (selectbox.name=="SRCADDR") {
   document.iform.srclist.value=prop
   }
if (selectbox.name=="DSTADDR") {
   document.iform.dstlist.value=prop
   }
}

function createProtoProps(selectbox)
{
var i;
var tcp = '';
var udp = '';
var ip = '';
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
if(selectbox.options[i].value.match( /^tcp/ )) {
   tcp += selectbox.options[i].value + ', ';
}  
if(selectbox.options[i].value.match( /^udp/ )) {
   udp += selectbox.options[i].value + ', ';
}
if(selectbox.options[i].value.match( /^ip/ )) {
   ip += selectbox.options[i].value + ', ';
}
}
}
tcp = tcp.replace(/, $/,"");
udp = udp.replace(/, $/,"");
ip = ip.replace(/, $/,"");
tcp = tcp.replace(/tcp\//g, "");
udp = udp.replace(/udp\//g, "");
ip = ip.replace(/ip\//g, "");
document.iform.tcpports.value=tcp;
document.iform.udpports.value=udp;
document.iform.ipprotos.value=ip;
}


function prepareSubmit()
{
selectAllOptions(SRCADDR);
createProp(SRCADDR);
selectAllOptions(DSTADDR);
createProp(DSTADDR);
selectAllOptions(PROTOLIST);
createProtoProps(PROTOLIST);
}

var ids=new Array('srchost','srcnet','srcalias', 'dsthost', 'dstnet', 'dstalias', 'dstsnat', 'dstredir', 'dstrelay');
var tabs=new Array('tabAddress', 'tabProtocol', 'tabOptions');

function switchsrcid(id){	
	hideallsrcids();
	showdiv(id);
}

function switchdstid(id){       
        hidealldstids();
        showdiv(id);
}

function switchtab(tab){       
        hidealltabs();
        showdiv(tab);
}

function hidealltabs(){
        //loop through the array and hide each element by id
        for (var i=0;i<tabs.length;i++){
                if(tabs[i].match( /^tab/ )) {
                        hidediv(tabs[i]);
                }        }                 
}

function hideallsrcids(){
	//loop through the array and hide each element by id
	for (var i=0;i<ids.length;i++){
		if(ids[i].match( /^src/ )) {
			hidediv(ids[i]);
		}
	}		  
}

function hidealldstids(){
        //loop through the array and hide each element by id
        for (var i=0;i<ids.length;i++){
                if(ids[i].match( /^dst/ )) {
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
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php
        $tabs = array('Addresses' => 'javascript:switchtab(\'tabAddress\')',
'Protocol' => 'javascript:switchtab(\'tabProtocol\')',
'Options' => 'javascript:switchtab(\'tabOptions\')'
);
        dynamic_tab_menu($tabs);
?>
  </ul>
  </td></tr>
  <tr>
    <td class="tabcont">
<center>
	<div id="tabAddress" style="display:block;"> 
             <form action="firewall_rules_edittabs.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	      <table width="100%" border="0" cellpadding="6" cellspacing="0">
        	<tr>
                  <td width="22%" valign="top" class="vncellreq">Name</td>
                  <td width="78%" class="vtable">
                    <input name="name" type="text" class="formfld" id="name" size="16" value="<?=htmlspecialchars($pconfig['name']);?>">
                    <input name="srclist" type="hidden" value="">
		    <input name="dstlist" type="hidden" value="">
		    <input name="tcpports" type= "hidden" value="">
		    <input name="udpports" type= "hidden" value="">
 		    <input name="rdrlist" type= "hidden" value="">
		    <input name="ipprotos" type= "hidden" value="">
		    <br> <span class="vexpl">You may enter a nameiption here
                    for your reference (not parsed).</span></td>
                </tr>
      		<tr> 
                  <td width="22%" valign="top" class="vncell">Description</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">You may enter a description here 
                    for your reference (not parsed).</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Action</td>
                  <td width="78%" class="vtable">
                                        <select name="type" class="formfld">
                      <?php $types = explode(" ", "Pass Block Reject"); foreach ($types as $type): ?>
                      <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['type'])) echo "selected"; ?>>
                      <?=htmlspecialchars($type);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                </tr>
                <tr>	
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Interface</td>
                  <td width="78%" class="vtable">
                                        <select name="interface" class="formfld">
                      <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP');
                                          for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                                                $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                                          }
                                          foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">Choose on which interface packets must
                    come in to match this rule.</span></td>
                </tr>
                  <tr>
                <td width="22%" valign="top" class="vncellreq">Source</td>
                <td width="78%" class="vtable">
                <SELECT style="width: 150px; height: 100px" id="SRCADDR" NAME="SRCADDR" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['srclist']); $i++): ?>
                <option value="<?=$pconfig['srclist']["src$i"];?>">
                <?=$pconfig['srclist']["src$i"];?>
                </option>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(SRCADDR)"; value='Remove Selected'><br><br>
                  <strong>Type</strong>
                    <select name="srctype" class="formfld" id="srctype" onChange="switchsrcid(document.iform.srctype.value)">
                      <option value="srchost" selected>Host</option>
                      <option value="srcnet" >Network</option>
                      <option value="srcalias" >Alias</option>
                    </select><br><br>
                <div id='srchost' style="display:block;">
                 <strong>Address</strong>
                  <?=$mandfldhtml;?><input name="srchost" type="text" class="formfld" id="srchost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                <input type=button onClick="addOption('SRCADDR',document.iform.srchost.value + '/32','host' + ':' + document.iform.srchost.value + '/32')"; value='Add'>
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
                <input type=button onClick="addOption('SRCADDR',document.iform.srcnet.value + '/' + document.iform.srcmask.value,'net' + ':' + document.iform.srcnet.value + '/' + document.iform.srcmask.value)"; value='Add'>
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
		<input type=button onClick="addOption('SRCADDR',document.iform.srcalias.value + '/32','net' + ':' + document.iform.srcalias.value + '/32')"; value='Add'>
		</div>
                </td>
                </tr> 
		<tr>
                <td width="22%" valign="top" class="vncellreq">Destination</td>
                <td width="78%" class="vtable">
                <SELECT style="width: 150px; height: 100px" id="DSTADDR" NAME="DSTADDR" MULTIPLE size=6 width=30>
       		<?php for ($i = 0; $i<sizeof($pconfig['dstlist']); $i++): ?>
                <option value="<?=$pconfig['dstlist']["dst$i"];?>">
                <?=$pconfig['dstlist']["dst$i"];?>
                </option>
                <?php endfor; ?>
		<input type=button onClick="removeOptions(DSTADDR)"; value='Remove Selected'><br><br>
		<strong>Type</strong>
                    <select name="dsttype" class="formfld" id="dsttype" onChange="switchdstid(document.iform.dsttype.value)">
                      <option value="dsthost" selected>Host</option>
                      <option value="dstnet" >Network</option>
                      <option value="dstalias" >Alias</option>
               	      <option value="dstsnat" >SNAT</option> 
		      <option value="dstredir" >Redirect</option>
                      <option value="dstrelay" >Relay</option>
		</select><br><br>
                <div id='dsthost' style="display:block;">
                 <strong>Address</strong>
                  <?=$mandfldhtml;?><input name="dsthost" type="text" class="formfld" id="dsthost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                <input type=button onClick="addOption('DSTADDR',document.iform.dsthost.value + '/32','host' + ':' + document.iform.dsthost.value + '/32')"; value='Add'>
                </div>
                <div id='dstnet' style="display:none;">
                 <strong>Address</strong>
                  <?=$mandfldhtml;?><input name="dstnet" type="text" class="formfld" id="dstnet" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                   <strong>/</strong>
                    <select name="dstmask" class="formfld" id="dstmask"><?php for ($i = 30; $i >= 1; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
                <input type=button onClick="addOption('DSTADDR',document.iform.dstnet.value + '/' + document.iform.dstmask.value,'net' + ':' + document.iform.dstnet.value + '/' + document.iform.dstmask.value)"
; value='Add'>
                </div>
                <div id='dstalias' style="display:none;">
                 <strong>Alias</strong>
                    <select name="dstalias" class="formfld" id="dstalias">
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
                <input type=button onClick="addOption('DSTADDR',document.iform.dstalias.value + '/32','net' + ':' + document.iform.dstalias.value + '/32')"; value='Add'>
                </div>
		<div id='dstsnat' style="display:none;">
                 <strong>External</strong>
                  <?=$mandfldhtml;?><input name="snatext" type="text" class="formfld" id="snatext" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                   <strong>Internal</strong>
		<?=$mandfldhtml;?><input name="snatint" type="text" class="formfld" id="snatint" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                <input type=button onClick="addOption('DSTADDR',document.iform.snatext.value + ':' + document.iform.snatint.value,'snat' + ':' + document.iform.snatext.value + ':' + document.iform.snatint.value)"
; value='Add'>
                </div>
                <div id='dstredir' style="display:none;">
                 <strong>Listener</strong>
                  <?=$mandfldhtml;?><input name="redirext" type="text" class="formfld" id="redirext" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                <strong>Server Pool</strong>
                    <select name="dstredir" class="formfld" id="dstredir">
                      <?php foreach($config['aliases']['alias'] as $i): ?>
                      <option value="<?='$' . $i['name'];?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>> 
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
		<input type=button onClick="addOption('DSTADDR',document.iform.redirext.value + ':' + document.iform.redirint.value,'snat' + ':' + document.iform.redirext.value + ':' + document.iform.redirint.value)"
; value='Add'>
                </div>
		<div id='dstrelay' style="display:none;">
                <strong>Relay</strong>
                    <select name="dstrelayname" class="formfld" id="dstrelayname">
                    <?php foreach($config['relays']['relay'] as $i): ?>
                    	<?php if ($i['forward'] != 'nat lookup'): ?>    
                    		<option value="<?= $i['name'];?>" <?php if ($i == $pconfig['dstrelay']) echo "selected"; ?>> 
                    		<?=$i['name'];?>
                    		</option>
                    	<?php endif; ?>
                    <?php endforeach; ?>
		    </select>
                <input type=button onClick="addOption('DSTADDR',document.iform.dstrelayname.value,'relay:' + document.iform.dstrelayname.value)"; value='Add'>
                </div>
		</td>
		<tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save"> 
                    <?php if (isset($id) && $a_filter[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>"> 
                  </td>
                </tr>
              </table>
	</div>
        <div id="tabProtocol" style="display:none;"> 
	<table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                <td width="22%" valign="top" class="vncellreq">Protocol</td>
                <td width="78%" class="vtable">
                <br><br><input name="portforward" type="checkbox" id="portforward" value="yes" <?php if ($pconfig['portforward']) echo "checked"; ?>> 
                <strong>Forward Connections on this port to a nat lookup relay</strong><br>
                <div id='dstrelay' style="display:block;">
                <strong>Relay</strong>
                    <select name="dstrelay" class="formfld" id="dstrelay">
                      <?php foreach($config['relays']['relay'] as $i): ?>
                                <?php if ($i['forward'] == 'nat lookup'): ?>    
                                <option value="<?= $i['name'];?>" <?php if ($i == $pconfig['dstrelay']) echo "selected"; ?>> 
                                <?=$i['name'];?>
                                </option>
                                <?php endif; ?>
                        <?php endforeach; ?>
                    </select><br><br>
		<SELECT style="width: 150px; height: 100px" id="PROTOLIST" NAME="PROTOLIST" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['tcplist']); $i++): ?>
                <option value="tcp/<?=$pconfig['tcplist']["tcp$i"];?>">
                tcp/<?=$pconfig['tcplist']["tcp$i"];?>
                </option>
                <?php endfor; ?>
		<?php for ($i = 0; $i<sizeof($pconfig['udplist']); $i++): ?>
                <option value="udp/<?=$pconfig['udplist']["udp$i"];?>">
                udp/<?=$pconfig['udplist']["udp$i"];?>
                </option>
                <?php endfor; ?>
		<input type=button onClick="removeOptions(PROTOLIST)"; value='Remove Selected'><br><br>
                    <select name="proto" class="formfld" id="proto">
                      <option value="tcp" selected>TCP</option>
                      <option value="udp">UDP</option>
		      <option value="ip">IP</option>
                    </select>
                  <input name="fromport" type="text" class="formfld" id="fromport" size="5" value="">
                   <strong> To </strong>
                    <input name="toport" type="text" class="formfld" id="toport" size="5" value="">
                    <input type=button onClick="addOption('PROTOLIST',document.iform.proto.value + '/' + document.iform.fromport.value + ':' + document.iform.toport.value,document.iform.proto.value + '/' + document.iform.fromport.value + ':' + document.iform.toport.value)"; value='Add'>
                </div>
		</td>
		</tr>  
	 	<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_filter[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>	
		</table>
	</div>
	<div id="tabOptions" style="display:none;"> 
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Log</td>
                  <td width="78%" class="vtable"> 
                    <input name="log" type="checkbox" id="log" value="yes" <?php if ($pconfig['log']) echo "checked"; ?>>
                    <strong>Log packets that are handled by this rule</strong><br>
                    </td>
                </tr>
		<tr>
                <td width="22%" valign="top" class="vncellreq">ALTQ</td>
                <td width="78%" class="vtable">
                    <select name="altqbucket" class="formfld" id="altqbucket">
	 	    <?php foreach ($altqbuckets as $bucket): ?>
                      <option value="<?=$bucket;?>" <?php if ($bucket == $pconfig['altqbucket']) echo "selected"; ?>>
                      <?=htmlspecialchars($bucket);?>
                      </option>
                      <?php endforeach; ?>
                    
		<strong>Add packetes handled by this rule to ALTQ Bucket</strong><br>
		</td>
		<tr>
                <tr>
                <td width="22%" valign="top" class="vncellreq">ALTQ Lowdelay</td>
                <td width="78%" class="vtable">
                    <input name="altqlowdelay" type="checkbox" id="altqlowdelay" value="yes" <?php if ($pconfig['altqlowdelay']) echo "checked"; ?>>
                </tr>
                <tr>	
		<tr>
                <td width="22%" valign="top" class="vncellreq">State</td>
                <td width="78%" class="vtable">
                    <select name="state" class="formfld" id="state">
		    <?php foreach ($statetypes as $statetype): ?>
                      <option value="<?=$statetype;?>" <?php if ($statetype == $pconfig['state']) echo "selected"; ?>>
                      <?=htmlspecialchars($statetype);?>
                      </option>
                      <?php endforeach; ?>
		</td>
		<tr>
                <td width="22%" valign="top" class="vncellreq">Max States</td>
                <td width="78%" class="vtable">
                <input name="maxstates" type="text" class="formfld" id="maxstates" size="5" value="<?=htmlspecialchars($pconfig['maxstates']);?>">
		</td>
		</tr>
                <tr>
                <td width="22%" valign="top" class="vncellreq">Source Tracking</td>
                <td width="78%" class="vtable">
                    <select name="srctrack" class="formfld" id="srctrack">
                    <?php foreach ($srctracktypes as $srctracktype): ?>
                      <option value="<?=$srctracktype;?>" <?php if ($srctracktype == $pconfig['srctrack']) echo "selected"; ?>>
                      <?=htmlspecialchars($srctracktype);?>
                      </option>
                      <?php endforeach; ?> 	
		</td>
		<tr>
                <td width="22%" valign="top" class="vncellreq">Max Src Nodes</td>
                <td width="78%" class="vtable">
                <input name="maxsrcnodes" type="text" class="formfld" id="maxsrcnodes" size="5" value="<?=htmlspecialchars($pconfig['maxsrcnodes']);?>">
                </td>
                </tr>
		<tr>
                <td width="22%" valign="top" class="vncellreq">Max Src States</td>
                <td width="78%" class="vtable">
                <input name="maxsrcstates" type="text" class="formfld" id="maxsrcstates" size="5" value="<?=htmlspecialchars($pconfig['maxsrcstates']);?>">
                </td>
                </tr>
		<tr>
                <td width="22%" valign="top" class="vncellreq">Max Src Conns</td>
                <td width="78%" class="vtable">
                <input name="maxsrcconns" type="text" class="formfld" id="maxsrcconns" size="5" value="<?=htmlspecialchars($pconfig['maxsrcconns']);?>">
                </td>
                </tr>
		<tr>
                <td width="22%" valign="top" class="vncellreq">Max Src Conn Rate</td>
                <td width="78%" class="vtable">
                <input name="maxsrcconnrate" type="text" class="formfld" id="maxsrcconrate" size="5" value="<?=htmlspecialchars($pconfig['maxsrcconnrate']);?>">
                </td>
                </tr> 
		<tr>
                <td width="22%" valign="top" class="vncellreq">Overload</td>
                <td width="78%" class="vtable">
                    <input name="overload" type="checkbox" id="overload" value="yes" <?php if ($pconfig['overload']) echo "checked"; ?>>
		</tr>
                <tr>
                <tr>
                <td width="22%" valign="top" class="vncellreq">Flush</td>
                <td width="78%" class="vtable">
                    <input name="flush" type="checkbox" id="flush" value="yes" <?php if ($pconfig['flush']) echo "checked"; ?>>
                </tr>
                <tr>	
		  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="Save"> 
                    <?php if (isset($id) && $a_filter[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>"> 
                    <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>"> 
                  </td>
                </tr>
              </table>
	</form>
	</div>
</center>
<script language="JavaScript">
<!--
</script>
<?php include("fend.inc"); ?>
