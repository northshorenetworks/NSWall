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

$pgtitle = array("Firewall", "Relays", "Edit Proxy Action");
require("guiconfig.inc");

if (!is_array($config['relays']['proxyaction']))
	$config['relays']['proxyaction'] = array();

proxyactions_sort();
$a_protocol = &$config['relays']['proxyaction'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($_POST['after']))
        $after = $_POST['after'];

if (isset($_GET['dup'])) {
        $id = $_GET['dup'];
        $after = $_GET['dup'];
}

if (isset($id) && $a_protocol[$id]) {
	$pconfig['name'] = $a_protocol[$id]['name'];
	$pconfig['descr'] = $a_protocol[$id]['descr'];
	$pconfig['proto'] = $a_protocol[$id]['proto'];
	$pconfig['returnerror'] = $a_protocol[$id]['returnerror'];
	$pconfig['tcpoptions'] = $a_protocol[$id]['tcpoptions'];
	$pconfig['ssloptions'] = $a_protocol[$id]['ssloptions'];
	$pconfig['requestheaderactions'] = $a_protocol[$id]['requestheaderactions'];
	$pconfig['requestcookieactions'] = $a_protocol[$id]['requestcookieactions'];
	$pconfig['requestqueryactions'] = $a_protocol[$id]['requestqueryactions'];
	$pconfig['requesturlactions'] = $a_protocol[$id]['requesturlactions'];
	$pconfig['responseheaderactions'] = $a_protocol[$id]['responseheaderactions'];
	$pconfig['categories'] = $a_protocol[$id]['categories'];
}

if (isset($_GET['dup']))
        unset($id);

if ($_POST) {

	unset($input_errors);
	unset($a_protocol[$id]['tcpoptions']);
	unset($a_protocol[$id]['ssloptions']);
        unset($a_protocol[$id]['requestheaderactions']);
        unset($a_protocol[$id]['requestcookieactions']);
        unset($a_protocol[$id]['requestqueryactions']);
        unset($a_protocol[$id]['requesturlactions']);
        unset($a_protocol[$id]['responseheaderactions']);
	unset($a_protocol[$id]['categories']);

	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "name");
	$reqdfieldsn = explode(",", "Name");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	$protocol = array();
        $protocol['name'] = $_POST['name'];
        $protocol['descr'] = $_POST['descr'];
	$protocol['proto'] = $_POST['proto'];
	$protocol['returnerror'] = $_POST['returnerror'];
	if(isset($_POST['enabletcp']))
                $protocol['tcpoptions']['enabletcp'] = $_POST['enabletcp'];
	if(isset($_POST['tcpbacklog']) && $_POST['tcpbacklog'] != '')
		$protocol['tcpoptions']['tcpbacklog'] = $_POST['tcpbacklog'];
	if(isset($_POST['tcpminttl']) && $_POST['tcpminttl'] != '')
		$protocol['tcpoptions']['tcpminttl'] = $_POST['tcpminttl'];
	if(isset($_POST['tcpipttl']) && $_POST['tcpipttl'] != '')	
		$protocol['tcpoptions']['tcpipttl'] = $_POST['tcpipttl'];
	if(isset($_POST['tcpnodelay']))	
		$protocol['tcpoptions']['tcpnodelay'] = $_POST['tcpnodelay'];
	if(isset($_POST['tcpsack']))
		$protocol['tcpoptions']['tcpsack'] = $_POST['tcpsack'];
	if(isset($_POST['tcpsockbuffer']) && $_POST['tcpsockbuffer'] != '')
		$protocol['tcpoptions']['tcpsockbuffer'] = $_POST['tcpsockbuffer'];
	if(isset($_POST['enablessl']))
                $protocol['ssloptions']['enablessl'] = $_POST['enablessl'];
	if(isset($_POST['sslciphers']))
                $protocol['ssloptions']['sslciphers'] = $_POST['sslciphers'];
        if(isset($_POST['sslsessioncache']))
                $protocol['ssloptions']['sslsessioncache'] = $_POST['sslsessioncache'];
        if(isset($_POST['sslsslv2']))   
                $protocol['ssloptions']['sslv2'] = $_POST['sslsslv2'];
        if(isset($_POST['sslsslv3'])) 
                $protocol['ssloptions']['sslv3'] = $_POST['sslsslv3'];
        if(isset($_POST['ssltlsv1']))
                $protocol['ssloptions']['tlsv1'] = $_POST['ssltlsv1'];
	$actionslist = array_reverse(explode(',', $_POST['requestheaderactionlist']));
        for($i=0;$i<sizeof($actionslist); $i++) {
                $actionnumber = 'action'."$i";
		$actionslist[$i];
                if(preg_match('/(^.+?)\s/', $actionslist[$i], $matches)) { 
                	$action = $matches[1]; 
			$protocol['requestheaderactions'][$actionnumber]['action'] = $action;
		}
		if(preg_match('/#(.+?)\#/', $actionslist[$i], $matches)) {
                	$from = $matches[1];
			$protocol['requestheaderactions'][$actionnumber]['from'] = $from;
		}
		if(preg_match('/to#(.+?)#/', $actionslist[$i], $matches)) {
			$to = $matches[1];
			$protocol['requestheaderactions'][$actionnumber]['to'] = $to;
		}
	}

	if (isset($id) && $a_protocol[$id])
                $a_protocol[$id] = $protocol;
        else
                $a_protocol[] = $protocol;


	if (!$input_errors) {
		
		write_config();
		 header("Location: firewall_relayd_relays.php");
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
document.iform.requestheaderappendto.value="";
document.iform.requestheaderappendfrom.value="";
document.iform.requestheaderchangeto.value="";
document.iform.requestheaderchangefrom.value="";
document.iform.requestheaderexpectto.value="";
document.iform.requestheaderexpectfrom.value="";
document.iform.requestheaderfilterto.value="";
document.iform.requestheaderfilterfrom.value="";
document.iform.requestheaderhashfrom.value="";
document.iform.requestheaderremovefrom.value="";
document.iform.requestheaderlogfrom.value="";
}

function removeOptions(selectbox)
{
var i;
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
selectbox.remove(i);
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
prop += selectbox.options[i].value + ',';   
}
}
prop = prop.replace(/,$/,"");
if(selectbox.name == "requestheaderactions") {
	document.iform.requestheaderactionlist.value = prop;
}
if(selectbox.name == "requestcookieactions") {
	document.iform.requestcookieactionlist.value = prop;
}
if(selectbox.name == "requestqueryactions") {
	document.iform.requestqueryactionlist.value = prop;
}
if(selectbox.name == "requesturlactions") {
	document.iform.requesturlactionlist.value = prop;
}
if(selectbox.name == "responseheaderactions") {
        document.iform.responseheaderactionlist.value = prop;
}
}

function prepareSubmit()
{
selectAllOptions(requestheaderactions);
createProp(requestheaderactions);
selectAllOptions(requestcookieactions);
createProp(requestcookieactions);
selectAllOptions(requestqueryactions);
createProp(requestqueryactions);
selectAllOptions(requesturlactions);
createProp(requesturlactions);
selectAllOptions(responseheaderactions);
createProp(responseheaderactions);
}

var protocolids=new Array('http', 'tcp','dns');

var actionids=new Array('append', 'change','expect',"filter","hash","remove","log");

function switchaction(id){  
	var type = id.replace(/\..+$/,"");     
        var action = id.replace(/^.+\./,"");
	hideactions(type);
	showdiv(type + action);
}

function hideactions(type){
        //loop through the array and hide each element by id
        for (var i=0;i<actionids.length;i++){
                        hidediv(type + actionids[i]);
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

function switchprotocol(id){       
        if(id=='tcp' || id=='dns') {
                disablenonhttp();
        }	
	else {
		enablehttp();
        }
}

function disablenonhttp(){
        //loop through the array and hide each element by id
        if (document.getElementById) { // DOM3 = IE5, NS6
                document.iform.cookies1.disabled = 'disabled';
        	document.iform.cookies2.disabled = 'disabled';
		document.iform.cookies3.disabled = 'disabled';
        	document.iform.path1.disabled = 'disabled';
		document.iform.path2.disabled = 'disabled';
		document.iform.path3.disabled = 'disabled';
		document.iform.query1.disabled = 'disabled';
		document.iform.query2.disabled = 'disabled';
		document.iform.query3.disabled = 'disabled';
		document.iform.url1.disabled = 'disabled';
		document.iform.url2.disabled = 'disabled';
		document.iform.url3.disabled = 'disabled';
	}
        else {
                if (document.layers) { // Netscape 4
                        document.id.disabled = 'disabled';
                }
                else { // IE 4
                        document.all.id.style.disabled = 'disabled';
                }
        }
}

function enablehttp(){
        //loop through the array and hide each element by id
        if (document.getElementById) { // DOM3 = IE5, NS6
                document.iform.cookies1.disabled = '';
                document.iform.cookies2.disabled = '';
                document.iform.cookies3.disabled = '';
		document.iform.path1.disabled = '';
                document.iform.path2.disabled = '';
                document.iform.path3.disabled = '';
		document.iform.query1.disabled = '';
                document.iform.query2.disabled = '';
                document.iform.query3.disabled = '';
		document.iform.url1.disabled = '';
                document.iform.url2.disabled = '';
                document.iform.url3.disabled = '';

        }
        else {
                if (document.layers) { // Netscape 4
                        document.id.disabled = 'disabled';
                }
                else { // IE 4
                        document.all.id.style.disabled = 'disabled';
                }
        }
}
-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<center>
	<id="Address">
             <form action="firewall_relayd_protocols_edit.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	      <table width="175%" border="0" cellpadding="6" cellspacing="0">
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
                      <option value="<?=$protoname;?>" <?php if ($protoname == $pconfig['proto']) echo "selected"; ?>> 
                      <?=htmlspecialchars($protoname);?>
                      </option>
                      <?php endforeach; ?>
                    </select> 
                  </td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Return Errors to client</td>
                  <td width="78%" class="vtable">
		    <input name="returnerror" type="checkbox" value="returnerror" <?php if ($pconfig['returnerror']) echo "checked"; ?>> 
                    Return Errors &nbsp;&nbsp;&nbsp;<br><span class="vexpl">Return an error reponse to the client if an internal operation or
                    the forward connection to the client failed.  By default, the connection will be silently dropped.  The effect of this option
                    depends on the protocol: HTTP will send an error header and page to the client before closing the connection. </span></td>
		 <tr> 
                  <td width="22%" valign="top" class="vncellreq">TCP Options</td>
                  <td width="78%" class="vtable">
                      <input name="enabletcp" type="checkbox" value="enabletcp" <?php if ($pconfig['tcpoptions']['enabletcp']) echo "checked"; ?>>Set the TCP options and session settings.<br>
		      TCP Backlog Queue:<input name="tcpbacklog" type="text" class="formfld" id="tcpbacklog" size="5" value="<?=htmlspecialchars($pconfig['tcpoptions']['tcpbacklog']);?>">
                      <br>
                      Min TTL:<input name="tcpminttl" type="text" class="formfld" id="tcpminttl" size="5" value="<?=htmlspecialchars($pconfig['tcpoptions']['tcpminttl']);?>">
                      <br>
                      Change TTL To:<input name="tcpipttl" type="text" class="formfld" id="tcpipttl" size="5" value="<?=htmlspecialchars($pconfig['tcpoptions']['tcpipttl']);?>">
                      <br>
                      <input name="tcpnodelay" type="checkbox" value="tcpnodelay" <?php if ($pconfig['tcpoptions']['tcpnodelay']) echo "checked"; ?>>TCP NODELAY
		      <span class="vexpl">Enable the TCP NODELAY option for this connection.  This
                     is recommended to avoid delays in the relayed data
                     stream, e.g. for SSH connections.<br>
                      <input name="tcpsack" type="checkbox" value="tcpsack" <?php if ($pconfig['tcpoptions']['tcpsack']) echo "checked"; ?>>TCP Selective ACK's
                      <span class="vexpl">Use selective acknowledgements for this connection.<br>
		      <br>
                      TCP Socket Buffer:<input name="tcpsockbuffer" type="text" class="formfld" id="tcpsockbuffer" size="5" value="<?=htmlspecialchars($pconfig['tcpoptions']['tcpsockbuffer']);?>">
                      <br>
		  </td>
                </tr>
		     <tr> 
                  <td width="22%" valign="top" class="vncellreq">SSL Options</td>
                  <td width="78%" class="vtable">
		      <input name="enablessl" type="checkbox" value="enablessl" <?php if ($pconfig['ssloptions']['enablessl']) echo "checked"; ?>>Set the SSL options and session settings.<br>
		      Ciphers string:<input name="sslciphers" type="text" class="formfld" id="sslciphers" size="20" value="<?=htmlspecialchars($pconfig['ssloptions']['sslciphers']);?>">
                      <br>
                      Session Cache:<input name="sslsessioncache" type="text" class="formfld" id="sslsessioncache" size="5" value="<?=htmlspecialchars($pconfig['ssloptions']['sslsessioncache']);?>">
                      <br>
                      <input name="sslsslv2" type="checkbox" value="sslsslv2" <?php if ($pconfig['ssloptions']['sslv2']) echo "checked"; ?>>Enable SSLv2<br>
		      <input name="sslsslv3" type="checkbox" value="sslsslv3" <?php if ($pconfig['ssloptions']['sslv3']) echo "checked"; ?>>Enable SSLv3<br>
                      <input name="ssltlsv1" type="checkbox" value="ssltlsv1" <?php if ($pconfig['ssloptions']['tlsv1']) echo "checked"; ?>>Enable TLSv1<br>
		      <br>
                  </td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Actions</td>
                  <td width="78%" class="vtable">
                    <br> <span class="vexpl">The following set of actions apply to the data stream from the client to the server</span></td>
                </tr>
     <tr>
                <td width="22%" valign="top" class="vncellreq">Headers</td>
                <td width="78%" class="vtable">
                <SELECT name="requestheaderactions" style="width: 350px; height: 150px" id="requestheaderactions" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['requestheaderactions']); $i++): ?>
		<?php if($pconfig['requestheaderactions']["action$i"]['action'] == 'append' || $pconfig['requestheaderactions']["action$i"]['action'] == 'change' ): ?>
                        <?$dir = 'to';?>
                	<option value="<?=$pconfig['requestheaderactions']["action$i"]['action'];?> #<?=$pconfig['requestheaderactions']["action$i"]['from'];?># to#<?=$pconfig['requestheaderactions']["action$i"]['to'];?>#">
                <?=$pconfig['requestheaderactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestheaderactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requestheaderactions']["action$i"]['to'];?>&quot;
                </option>
		<?elseif($pconfig['requestheaderactions']["action$i"]['action'] == 'expect' || $pconfig['requestheaderactions']["action$i"]['action'] == 'filter' ): ?> 
                        <?$dir = 'from';?>
			<option value="<?=$pconfig['requestheaderactions']["action$i"]['action'];?> #<?=$pconfig['requestheaderactions']["action$i"]['from'];?># to#<?=$pconfig['requestheaderactions']["action$i"]['to'];?>#">
                <?=$pconfig['requestheaderactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestheaderactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requestheaderactions']["action$i"]['to'];?>&quot;
                </option>
		<?else:?>
                <option value="<?=$pconfig['requestheaderactions']["action$i"]['action'];?> #<?=$pconfig['requestheaderactions']["action$i"]['from'];?>">
                <?=$pconfig['requestheaderactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestheaderactions']["action$i"]['from'];?>&quot;
                </option>
		<?php endif; ?>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(requestheaderactions)"; value='Remove Selected'><br><br>
                <input name="requestheaderactionlist" type="hidden" value="">
		</select> 
                      <select name="requestheaderaction" class="formfld" id="requestheaderaction" onChange="switchaction('requestheader.' + document.iform.requestheaderaction.value)">
                      <?php foreach ($protoaction3 as $act3 => $act3name): ?>
                      <option value="<?=$act3name;?>" <?php if ($act3 == $pconfig['act3']) echo "selected"; ?>> 
                      <?=htmlspecialchars($act3name);?>
                      </option>
                      <?php endforeach; ?>
                </select>
		<div id='requestheaderappend' style="display:block;">
                <input name="requestheaderappendfrom" type="text" class="formfld" id="requestheaderappendfrom" size="25" value="">To:
                <input name="requestheaderappendto" type="text" class="formfld" id="requestheaderappendto" size="25" value=""><br>
                <input type=button onClick="addOption('requestheaderactions','append &quot' + document.iform.requestheaderappendfrom.value + '&quot to &quot' + document.iform.requestheaderappendto.value + '&quot','append #' + document.iform.requestheaderappendfrom.value + '# to#' + document.iform.requestheaderappendto.value + '#')"; value='Add'>
                </div>
		<div id='requestheaderchange' style="display:none;">
                <input name="requestheaderchangefrom" type="text" class="formfld" id="requestheaderchangefrom" size="25" value="">To:
                <input name="requestheaderchangeto" type="text" class="formfld" id="requestheaderchangeto" size="25" value=""><br>
                <input type=button onClick="addOption('requestheaderactions','change &quot' + document.iform.requestheaderchangefrom.value + '&quot to &quot' + document.iform.requestheaderchangeto.value + '&quot','change #' + document.iform.requestheaderchangefrom.value + '# to#' + document.iform.requestheaderchangeto.value + '#')"; value='Add'>
                </div>
		<div id='requestheaderexpect' style="display:none;">
                <input name="requestheaderexpectfrom" type="text" class="formfld" id="requestheaderexpectfrom" size="25" value="">From:
                <input name="requestheaderexpectto" type="text" class="formfld" id="requestheaderexpectto" size="25" value=""><br>
                <input type=button onClick="addOption('requestheaderactions','expect &quot' + document.iform.requestheaderexpectfrom.value + '&quot from &quot' + document.iform.requestheaderexpectto.value + '&quot','expect #' + document.iform.requestheaderexpectfrom.value + '# to#' + document.iform.requestheaderexpectto.value + '#')"; value='Add'>
                </div>
		<div id='requestheaderfilter' style="display:none;">
                <input name="requestheaderfilterfrom" type="text" class="formfld" id="requestheaderfilterfrom" size="25" value="">From:
                <input name="requestheaderfilterto" type="text" class="formfld" id="requestheaderfilterto" size="25" value=""><br>
                <input type=button onClick="addOption('requestheaderactions','filter &quot' + document.iform.requestheaderfilterfrom.value + '&quot from &quot' + document.iform.requestheaderfilterto.value + '&quot','filter #' + document.iform.requestheaderfilterfrom.value + '# to#' + document.iform.requestheaderfilterto.value + '#')"; value='Add'>
                </div>
		<div id='requestheaderhash' style="display:none;">
                <input name="requestheaderhashfrom" type="text" class="formfld" id="requestheaderhashfrom" size="25" value="">
                <input type=button onClick="addOption('requestheaderactions','hash &quot' + document.iform.requestheaderhashfrom.value + '&quot','hash #' + document.iform.requestheaderhashfrom.value + '#')" value='Add'>
                </div>
		<div id='requestheaderremove' style="display:none;">
                <input name="requestheaderremovefrom" type="text" class="formfld" id="requestheaderremovefrom" size="25" value="">
                <input type=button onClick="addOption('requestheaderactions','remove &quot' + document.iform.requestheaderremovefrom.value + '&quot','remove #' + document.iform.requestheaderremovefrom.value + '#')" value='Add'>
                </div>
		<div id='requestheaderlog' style="display:none;">
                <input name="requestheaderlogfrom" type="text" class="formfld" id="requestheaderlogfrom" size="25" value="">
                <input type=button onClick="addOption('requestheaderactions','log &quot' + document.iform.requestheaderlogfrom.value + '&quot','log #' + document.iform.requestheaderlogfrom.value + '#')" value='Add'>
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
