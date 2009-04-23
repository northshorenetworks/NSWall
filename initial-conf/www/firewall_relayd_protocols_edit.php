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
	$actionslist = array_reverse(explode(',', $_POST['requestcookieactionlist'])); 
	for($i=0;$i<sizeof($actionslist); $i++) {
                $actionnumber = 'action'."$i";
                $actionslist[$i];
                if(preg_match('/(^.+?)\s/', $actionslist[$i], $matches)) { 
                        $action = $matches[1]; 
                        $protocol['requestcookieactions'][$actionnumber]['action'] = $action;
                }   
                if(preg_match('/#(.+?)\#/', $actionslist[$i], $matches)) {
                        $from = $matches[1];
                        $protocol['requestcookieactions'][$actionnumber]['from'] = $from;
                }   
                if(preg_match('/to#(.+?)#/', $actionslist[$i], $matches)) {
                        $to = $matches[1];
                        $protocol['requestcookieactions'][$actionnumber]['to'] = $to;
                }   
        }
	$actionslist = array_reverse(explode(',', $_POST['requestqueryactionlist']));
        for($i=0;$i<sizeof($actionslist); $i++) {
                $actionnumber = 'action'."$i";
                $actionslist[$i];
                if(preg_match('/(^.+?)\s/', $actionslist[$i], $matches)) {
                        $action = $matches[1];
                        $protocol['requestqueryactions'][$actionnumber]['action'] = $action;
                }
                if(preg_match('/#(.+?)\#/', $actionslist[$i], $matches)) {
                        $from = $matches[1];
                        $protocol['requestqueryactions'][$actionnumber]['from'] = $from;
                }
                if(preg_match('/to#(.+?)#/', $actionslist[$i], $matches)) {
                        $to = $matches[1];
                        $protocol['requestqueryactions'][$actionnumber]['to'] = $to;
                }
        }
        $actionslist = array_reverse(explode(',', $_POST['requesturlactionlist']));
        for($i=0;$i<sizeof($actionslist); $i++) {
                $actionnumber = 'action'."$i";
                $actionslist[$i];
                if(preg_match('/(^.+?)\s/', $actionslist[$i], $matches)) {
                        $action = $matches[1];
                        $protocol['requesturlactions'][$actionnumber]['action'] = $action;
                }
                if(preg_match('/#(.+?)\#/', $actionslist[$i], $matches)) {
                        $from = $matches[1];
                        $protocol['requesturlactions'][$actionnumber]['from'] = $from;
                }
                if(preg_match('/to#(.+?)#/', $actionslist[$i], $matches)) {
                        $to = $matches[1];
                        $protocol['requesturlactions'][$actionnumber]['to'] = $to;
                }
        }
	$actionslist = array_reverse(explode(',', $_POST['responseheaderactionlist'])); 
        for($i=0;$i<sizeof($actionslist); $i++) {
                $actionnumber = 'action'."$i";
                $actionslist[$i];
                if(preg_match('/(^.+?)\s/', $actionslist[$i], $matches)) { 
                        $action = $matches[1]; 
                        $protocol['responseheaderactions'][$actionnumber]['action'] = $action;
                }   
                if(preg_match('/#(.+?)\#/', $actionslist[$i], $matches)) {
                        $from = $matches[1];
                        $protocol['responseheaderactions'][$actionnumber]['from'] = $from;
                }   
                if(preg_match('/to#(.+?)#/', $actionslist[$i], $matches)) {
                        $to = $matches[1];
                        $protocol['responseheaderactions'][$actionnumber]['to'] = $to;
                }   
        }
	//Webfilter categories
	if(isset($_POST['catads']))
                $protocol['categories']['ads'] = $_POST['catads'];
	if(isset($_POST['catadult']))
                $protocol['categories']['adult'] = $_POST['catadult'];
	if(isset($_POST['cataggess']))
                $protocol['categories']['aggressive'] = $_POST['cataggressive'];
	if(isset($_POST['catantispy']))
                $protocol['categories']['antispy'] = $_POST['catantispy'];
	if(isset($_POST['catartnudes']))
                $protocol['categories']['artnudes'] = $_POST['catartnudes'];
	if(isset($_POST['catastrology']))
                $protocol['categories']['astrology'] = $_POST['catastrology'];
	if(isset($_POST['cataudio-video']))
                $protocol['categories']['audio-video'] = $_POST['cataudio-video'];
	if(isset($_POST['catbanking']))
                $protocol['categories']['banking'] = $_POST['catbanking'];
	if(isset($_POST['catbeerliquorinfo']))
                $protocol['categories']['beerliquorinfo'] = $_POST['catbeerliquorinfo'];
	if(isset($_POST['catbeerliquorsale']))
                $protocol['categories']['beerliquorsale'] = $_POST['catbeerliquorsale'];
	if(isset($_POST['catblog']))
                $protocol['categories']['blog'] = $_POST['catblog'];
	if(isset($_POST['catbooks']))
                $protocol['categories']['books'] = $_POST['catbooks'];
	if(isset($_POST['catcelebrity']))
                $protocol['categories']['celebrity'] = $_POST['catcelebrity'];
	if(isset($_POST['catcellphones']))
                $protocol['categories']['cellphones'] = $_POST['catcellphones'];
	if(isset($_POST['catchat']))
                $protocol['categories']['chat'] = $_POST['catchat'];
	if(isset($_POST['catchild']))
                $protocol['categories']['child'] = $_POST['catchild'];
	if(isset($_POST['catchildcare']))
                $protocol['categories']['childcare'] = $_POST['catchildcare'];
	if(isset($_POST['catcleaning']))
                $protocol['categories']['cleaning'] = $_POST['catcleaning'];
	if(isset($_POST['catclothing']))
                $protocol['categories']['clothing'] = $_POST['catclothing'];
	if(isset($_POST['catculinary']))
                $protocol['categories']['culinary'] = $_POST['catculinary'];
	if(isset($_POST['catdating']))
                $protocol['categories']['dating'] = $_POST['catdating'];
	if(isset($_POST['catdesktopsillies']))
                $protocol['categories']['desktopsillies'] = $_POST['catdesktopsillies'];
	if(isset($_POST['catdialers']))
                $protocol['categories']['dialers'] = $_POST['catdialers'];
	if(isset($_POST['catdrugs']))
                $protocol['categories']['drugs'] = $_POST['catdrugs'];
	if(isset($_POST['catecommerce']))
                $protocol['categories']['ecommerce'] = $_POST['catecommerce'];
	if(isset($_POST['catentertainment']))
                $protocol['categories']['entertainment'] = $_POST['catentertainment'];
	if(isset($_POST['catfilehosting']))
                $protocol['categories']['filehosting'] = $_POST['catfilehosting'];
	if(isset($_POST['catfilesharing']))
                $protocol['categories']['filesharing'] = $_POST['catfilesharing'];
	if(isset($_POST['catfrencheducation']))
                $protocol['categories']['frencheducation'] = $_POST['catfrencheducation'];
	if(isset($_POST['catgambling']))
                $protocol['categories']['gambling'] = $_POST['catgambling'];
	if(isset($_POST['catgames']))
                $protocol['categories']['games'] = $_POST['catgames'];
	if(isset($_POST['catgardening']))
                $protocol['categories']['gardening'] = $_POST['catgardening'];
	if(isset($_POST['catgovernment']))
                $protocol['categories']['government'] = $_POST['catgovernment'];
	if(isset($_POST['catguns']))
                $protocol['categories']['guns'] = $_POST['catguns'];

	if (isset($id) && $a_protocol[$id])
                $a_protocol[$id] = $protocol;
        else
                $a_protocol[] = $protocol;

	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);
        
        if (relay_relayd_parse($xmlconfig)) {
		$input_errors[] = "Could not parse the generated config file";
		$input_errors[] = "See log file for details";
		$input_errors[] = "XML Config file not modified";
	}

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
                  <td width="22%" valign="top" class="vncellreq">Request Actions</td>
                  <td width="78%" class="vtable">
                    <br> <span class="vexpl">The following set of actions apply to the data stream from the client to the server</span></td>
                </tr>
<!--		<tr>
                  <td width="22%" valign="top" class="vncellreq">Web Filtering Categories</td>
                  <td width="78%" class="vtable">
    <table width="100%" align="left" border="0">
    <tr>
    <td align="left" width="33%"><input name="catads" type="checkbox" value="catads" <?php if (isset($pconfig['categories']['ads'])) echo "checked"; ?>>Advertisements</font></td>
    <td align="left" width="33%"><input name="catgames" type="checkbox" value="catgames" <?php if (isset($pconfig['categories']['games'])) echo "checked"; ?>>Games</font></td>
    <td align="left" width="33%"><input name="catreaffected" type="checkbox" value="catreaffected" <?php if (isset($pconfig['categories']['reaffected'])) echo "checked"; ?>>Reaffected</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catadult" type="checkbox" value="catadult" <?php if (isset($pconfig['categories']['adult'])) echo "checked"; ?>>Adult</font></td>
    <td align="left" width="33%"><input name="catgardening" type="checkbox" value="catgardening" <?php if (isset($pconfig['categories']['gardening'])) echo "checked"; ?>>Gardening</font></td>
    <td align="left" width="33%"><input name="catreligion" type="checkbox" value="catreligion" <?php if (isset($pconfig['categories']['religion'])) echo "checked"; ?>>Religion</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="cataggressive" type="checkbox" value="cataggressive" <?php if (isset($pconfig['categories']['aggressive'])) echo "checked"; ?>>Aggressive</font></td>
    <td align="left" width="33%"><input name="catgovernment" type="checkbox" value="catgovernment" <?php if (isset($pconfig['categories']['government'])) echo "checked"; ?>>Government</font></td>
    <td align="left" width="33%"><input name="catsearchengines" type="checkbox" value="catsearchengines" <?php if (isset($pconfig['categories']['searchengines'])) echo "checked"; ?>>Search Engines</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catantispyware" type="checkbox" value="catantispyware" <?php if (isset($pconfig['categories']['antispyware'])) echo "checked"; ?>>Anti-Spyware</font></td>
    <td align="left" width="33%"><input name="catguns" type="checkbox" value="catguns" <?php if (isset($pconfig['categories']['guns'])) echo "checked"; ?>>Guns</font></td>
    <td align="left" width="33%"><input name="catsect" type="checkbox" value="catsect" <?php if (isset($pconfig['categories']['sect'])) echo "checked"; ?>>Sect</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catartnudes" type="checkbox" value="catartnudes" <?php if (isset($pconfig['categories']['artnudes'])) echo "checked"; ?>>Art Nudes</font></td>
    <td align="left" width="33%"><input name="cathacking" type="checkbox" value="cathacking" <?php if (isset($pconfig['categories']['hacking'])) echo "checked"; ?>>Hacking</font></td>
    <td align="left" width="33%"><input name="catsexuality" type="checkbox" value="catsexuality" <?php if (isset($pconfig['categories']['sexuality'])) echo "checked"; ?>>Sexuality</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catastrology" type="checkbox" value="catastrology" <?php if (isset($pconfig['categories']['astrology'])) echo "checked"; ?>>Astrology</font></td>
    <td align="left" width="33%"><input name="cathomerepair" type="checkbox" value="cathomerepair" <?php if (isset($pconfig['categories']['homerepair'])) echo "checked"; ?>>Home Repair</font></td>
    <td align="left" width="33%"><input name="catshopping" type="checkbox" value="catshopping" <?php if (isset($pconfig['categories']['shopping'])) echo "checked"; ?>>Shopping</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="cataudio-video" type="checkbox" value="cataudio-video" <?php if (isset($pconfig['categories']['audio-video'])) echo "checked"; ?>>Audio-Video</font></td>
    <td align="left" width="33%"><input name="cathumor" type="checkbox" value="cathumor" <?php if (isset($pconfig['categories']['humor'])) echo "checked"; ?>>Humor</font></td>
    <td align="left" width="33%"><input name="catsocialnetworking" type="checkbox" value="catsocialnetworking" <?php if (isset($pconfig['categories']['socialnetworking'])) echo "checked"; ?>>Social Networking</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catbanking" type="checkbox" value="catbanking" <?php if (isset($pconfig['categories']['banking'])) echo "checked"; ?>>Banking</font></td>
    <td align="left" width="33%"><input name="cathygiene" type="checkbox" value="cathygiene" <?php if (isset($pconfig['categories']['hygiene'])) echo "checked"; ?>>Hygiene</font></td>
    <td align="left" width="33%"><input name="catsportsnews" type="checkbox" value="catsportsnews" <?php if (isset($pconfig['categories']['sportsnews'])) echo "checked"; ?>>Sports News</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catbeerliquorinfo" type="checkbox" value="catbeerliquorinfo" <?php if (isset($pconfig['categories']['beerliquorinfo'])) echo "checked"; ?>>Beer-Liquor Info</font></td>
    <td align="left" width="33%"><input name="catinstantmessaging" type="checkbox" value="catinstantmessaging" <?php if (isset($pconfig['categories']['instantmessaging'])) echo "checked"; ?>>Instant Messaging</font></td>
    <td align="left" width="33%"><input name="catsports" type="checkbox" value="catsports" <?php if (isset($pconfig['categories']['sports'])) echo "checked"; ?>>Sports</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catbeerliquorsales" type="checkbox" value="catbeerliquorsales" <?php if (isset($pconfig['categories']['beerliquorsales'])) echo "checked"; ?>>Beer-Liquor Sales</font></td>
    <td align="left" width="33%"><input name="catjewelry" type="checkbox" value="catjewelry" <?php if (isset($pconfig['categories']['jewelry'])) echo "checked"; ?>>Jewelry</font></td>
    <td align="left" width="33%"><input name="catspyware" type="checkbox" value="catspyware" <?php if (isset($pconfig['categories']['spyware'])) echo "checked"; ?>>Spyware</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catblog" type="checkbox" value="catblog" <?php if (isset($pconfig['categories']['blog'])) echo "checked"; ?>>Blogs</font></td>
    <td align="left" width="33%"><input name="catjobsearch" type="checkbox" value="catjobsearch" <?php if (isset($pconfig['categories']['jobsearch'])) echo "checked"; ?>>Job Searching</font></td>
    <td align="left" width="33%"><input name="catupdatesites" type="checkbox" value="catupdatesites" <?php if (isset($pconfig['categories']['updatesites'])) echo "checked"; ?>>Update Sites</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catbooks" type="checkbox" value="catbooks" <?php if (isset($pconfig['categories']['books'])) echo "checked"; ?>>Books</font></td>
    <td align="left" width="33%"><input name="catkidswastingtime" type="checkbox" value="catkidswastingtime" <?php if (isset($pconfig['categories']['ads'])) echo "checked"; ?>>Kids Wasting Time</font></td>
    <td align="left" width="33%"><input name="catvacation" type="checkbox" value="catvacation" <?php if (isset($pconfig['categories']['vacation'])) echo "checked"; ?>>Vacation</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catcelebrity" type="checkbox" value="catcelebrity" <?php if (isset($pconfig['categories']['celebrity'])) echo "checked"; ?>>Celebritynts</font></td>
    <td align="left" width="33%"><input name="catmagazines" type="checkbox" value="catmagazines" <?php if (isset($pconfig['categories']['magazines'])) echo "checked"; ?>>Magazines</font></td>
    <td align="left" width="33%"><input name="catverisign" type="checkbox" value="catverisign" <?php if (isset($pconfig['categories']['verisign'])) echo "checked"; ?>>Verisign</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catcellphones" type="checkbox" value="catcellphones" <?php if (isset($pconfig['categories']['cellphones'])) echo "checked"; ?>>Cell Phones</font></td>
    <td align="left" width="33%"><input name="catmail" type="checkbox" value="catmail" <?php if (isset($pconfig['categories']['mail'])) echo "checked"; ?>>Mail</font></td>
    <td align="left" width="33%"><input name="catviolence" type="checkbox" value="catviolence" <?php if (isset($pconfig['categories']['violence'])) echo "checked"; ?>>Violence</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catchat" type="checkbox" value="catchat" <?php if (isset($pconfig['categories']['chat'])) echo "checked"; ?>>Chat</font></td>
    <td align="left" width="33%"><input name="catmalware" type="checkbox" value="catmalware" <?php if (isset($pconfig['categories']['malware'])) echo "checked"; ?>>Malware</font></td>
    <td align="left" width="33%"><input name="catvirusinfected" type="checkbox" value="catvirusinfected" <?php if (isset($pconfig['categories']['virusinfected'])) echo "checked"; ?>>Virus Infected</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catchild" type="checkbox" value="catchild" <?php if (isset($pconfig['categories']['child'])) echo "checked"; ?>>Child</font></td>
    <td align="left" width="33%"><input name="catmanga" type="checkbox" value="catmanga" <?php if (isset($pconfig['categories']['manga'])) echo "checked"; ?>>Manga</font></td>
    <td align="left" width="33%"><input name="catwarez" type="checkbox" value="catwarez" <?php if (isset($pconfig['categories']['warez'])) echo "checked"; ?>>Warez</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catchildcare" type="checkbox" value="catchildcare" <?php if (isset($pconfig['categories']['childcare'])) echo "checked"; ?>>Childcare</font></td>
    <td align="left" width="33%"><input name="catmarketingware" type="checkbox" value="catmarketingware" <?php if (isset($pconfig['categories']['marketingware'])) echo "checked"; ?>>Marketing Ware</font></td>
    <td align="left" width="33%"><input name="catweapons" type="checkbox" value="catweapons" <?php if (isset($pconfig['categories']['weapons'])) echo "checked"; ?>>Weapons</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catcleaning" type="checkbox" value="catcleaning" <?php if (isset($pconfig['categories']['ads'])) echo "checked"; ?>>Cleaning</font></td>
    <td align="left" width="33%"><input name="catmedical" type="checkbox" value="catmedical" <?php if (isset($pconfig['categories']['medical'])) echo "checked"; ?>>Medical</font></td>
    <td align="left" width="33%"><input name="catweather" type="checkbox" value="catweather" <?php if (isset($pconfig['categories']['weather'])) echo "checked"; ?>>Weather</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catclothing" type="checkbox" value="catclothing" <?php if (isset($pconfig['categories']['clothing'])) echo "checked"; ?>>Clothing</font></td>
    <td align="left" width="33%"><input name="catmixed-adult" type="checkbox" value="catmixed-adult" <?php if (isset($pconfig['categories']['mixed-adult'])) echo "checked"; ?>>Mixed-Adult</font></td>
    <td align="left" width="33%"><input name="catwebmail" type="checkbox" value="catwebmail" <?php if (isset($pconfig['categories']['webmail'])) echo "checked"; ?>>Webmail</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catcullinary" type="checkbox" value="catcullinary" <?php if (isset($pconfig['categories']['cullinary'])) echo "checked"; ?>>Cullinary</font></td>
    <td align="left" width="33%"><input name="catmobile-phone" type="checkbox" value="catmobile-phone" <?php if (isset($pconfig['categories']['mobile-phone'])) echo "checked"; ?>>Mobile-Phone</font></td>
    <td align="left" width="33%"><input name="catwhitelist" type="checkbox" value="catwhitelist" <?php if (isset($pconfig['categories']['whitelist'])) echo "checked"; ?>>Whitelists</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catdating" type="checkbox" value="catdating" <?php if (isset($pconfig['categories']['dating'])) echo "checked"; ?>>Dating</font></td>
    <td align="left" width="33%"><input name="catnaturism" type="checkbox" value="catnaturism" <?php if (isset($pconfig['categories']['naturism'])) echo "checked"; ?>>Naturism</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catdesktopsillies" type="checkbox" value="catdesktopsillies" <?php if (isset($pconfig['categories']['desktopsillies'])) echo "checked"; ?>>Desktop Sillies</font></td>
    <td align="left" width="33%"><input name="catnews" type="checkbox" value="catnews" <?php if (isset($pconfig['categories']['news'])) echo "checked"; ?>>News</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catdialers" type="checkbox" value="catdialers" <?php if (isset($pconfig['categories']['dialers'])) echo "checked"; ?>>Dialers</font></td>
    <td align="left" width="33%"><input name="catonlineauctions" type="checkbox" value="catonlineauctions" <?php if (isset($pconfig['categories']['onlineauctions'])) echo "checked"; ?>>Online Auctions</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catdrugs" type="checkbox" value="catdrugs" <?php if (isset($pconfig['categories']['drugs'])) echo "checked"; ?>>Drugs</font></td>
    <td align="left" width="33%"><input name="catonlinegames" type="checkbox" value="catonlinegames" <?php if (isset($pconfig['categories']['onlinegames'])) echo "checked"; ?>>Online Games</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catecommerce" type="checkbox" value="catecommerce" <?php if (isset($pconfig['categories']['ecommerce'])) echo "checked"; ?>>Ecommerce</font></td>
    <td align="left" width="33%"><input name="catonlinepayment" type="checkbox" value="catonlinepayment" <?php if (isset($pconfig['categories']['onlinepayment'])) echo "checked"; ?>>Online Payment</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catentertainment" type="checkbox" value="catentertainment" <?php if (isset($pconfig['categories']['entertainment'])) echo "checked"; ?>>Entertainment</font></td>
    <td align="left" width="33%"><input name="catpersonalfinance" type="checkbox" value="catpersonalfinance" <?php if (isset($pconfig['categories']['personalfinance'])) echo "checked"; ?>>Personal Finance</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catfilehosting" type="checkbox" value="catfilehosting" <?php if (isset($pconfig['categories']['filehosting'])) echo "checked"; ?>>File Hosting</font></td>
    <td align="left" width="33%"><input name="catpets" type="checkbox" value="catpets" <?php if (isset($pconfig['categories']['pets'])) echo "checked"; ?>>Pets</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catfilesharing" type="checkbox" value="catfilesharing" <?php if (isset($pconfig['categories']['filesharing'])) echo "checked"; ?>>File Sharing</font></td>
    <td align="left" width="33%"><input name="catphishing" type="checkbox" value="catphishing" <?php if (isset($pconfig['categories']['phishing'])) echo "checked"; ?>>Phishing</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catfinancial" type="checkbox" value="catfinancial" <?php if (isset($pconfig['categories']['financial'])) echo "checked"; ?>>Financial</font></td>
    <td align="left" width="33%"><input name="catporn" type="checkbox" value="catport" <?php if (isset($pconfig['categories']['porn'])) echo "checked"; ?>>Porn</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catfrencheducation" type="checkbox" value="catfrencheducation" <?php if (isset($pconfig['categories']['frencheducation'])) echo "checked"; ?>>French Education</font></td>
    <td align="left" width="33%"><input name="catproxy" type="checkbox" value="catproxy" <?php if (isset($pconfig['categories']['proxy'])) echo "checked"; ?>>Proxies</font></td>
    </tr>
    <tr>
    <td align="left" width="33%"><input name="catgambling" type="checkbox" value="catgambling" <?php if (isset($pconfig['categories']['gambling'])) echo "checked"; ?>>Gambling</font></td>
    <td align="left" width="33%"><input name="catradio" type="checkbox" value="catradio" <?php if (isset($pconfig['categories']['raido'])) echo "checked"; ?>>Radio</font></td>
    </tr>
    </table> 
</td>
</tr>-->
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
                <td width="22%" valign="top" class="vncellreq">Cookies</td>
                <td width="78%" class="vtable">
                <SELECT name="requestcookieactions" style="width: 350px; height: 150px" id="requestcookieactions" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['requestcookieactions']); $i++): ?>
                <?php if($pconfig['requestcookieactions']["action$i"]['action'] == 'append' || $pconfig['requestcookieactions']["action$i"]['action'] == 'change' ): ?>
                        <?$dir = 'to';?>
                        <option value="<?=$pconfig['requestcookieactions']["action$i"]['action'];?> #<?=$pconfig['requestcookieactions']["action$i"]['from'];?># to#<?=$pconfig['requestcookieactions']["action$i"]['to'];?>#">
                <?=$pconfig['requestcookieactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestcookieactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requestcookieactions']["action$i"]['to'];?>&quot;
                </option>
                <?elseif($pconfig['requestcookieactions']["action$i"]['action'] == 'expect' || $pconfig['requestcookieactions']["action$i"]['action'] == 'filter' ): ?>                        <?$dir = 'from';?>
                        <option value="<?=$pconfig['requestcookieactions']["action$i"]['action'];?> #<?=$pconfig['requestcookieactions']["action$i"]['from'];?># to#<?=$pconfig['requestcookieactions']["action$i"]['to'];?>#">
                <?=$pconfig['requestcookieactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestcookieactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requestcookieactions']["action$i"]['to'];?>&quot;
                </option>
                <?else:?>
                <option value="<?=$pconfig['requestcookieactions']["action$i"]['action'];?> #<?=$pconfig['requestcookieactions']["action$i"]['from'];?>"><?=$pconfig['requestcookieactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestcookieactions']["action$i"]['from'];?>&quot;
                </option>
                <?php endif; ?>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(requestcookieactions)"; value='Remove Selected'><br><br>
                <input name="requestcookieactionlist" type="hidden" value="">
                </select>
                      <select name="requestcookieaction" class="formfld" id="requestcookieaction" onChange="switchaction('requestcookie.' + document.iform.requestcookieaction.value)">
                      <?php foreach ($protoaction3 as $act3 => $act3name): ?>
                      <option value="<?=$act3name;?>" <?php if ($act3 == $pconfig['act3']) echo "selected"; ?>>
                      <?=htmlspecialchars($act3name);?>
                      </option>
                      <?php endforeach; ?>
                </select>
                <div id='requestcookieappend' style="display:block;">
                <input name="requestcookieappendfrom" type="text" class="formfld" id="requestcookieappendfrom" size="25" value="">To:
                <input name="requestcookieappendto" type="text" class="formfld" id="requestcookieappendto" size="25" value=""><br>
                <input type=button onClick="addOption('requestcookieactions','append &quot' + document.iform.requestcookieappendfrom.value + '&quot to &quot' + document.iform.requestcookieappendto.value + '&quot','append #' + document.iform.requestcookieappendfrom.value + '# to#' + document.iform.requestcookieappendto.value + '#')"; value='Add'>
                </div>
                <div id='requestcookiechange' style="display:none;">
                <input name="requestcookiechangefrom" type="text" class="formfld" id="requestcookiechangefrom" size="25" value="">To:
                <input name="requestcookiechangeto" type="text" class="formfld" id="requestcookiechangeto" size="25" value=""><br>
                <input type=button onClick="addOption('requestcookieactions','change &quot' + document.iform.requestcookiechangefrom.value + '&quot to &quot' + document.iform.requestcookiechangeto.value + '&quot','change #' + document.iform.requestcookiechangefrom.value + '# to#' + document.iform.requestcookiechangeto.value + '#')"; value='Add'>
                </div>
                <div id='requestcookieexpect' style="display:none;">
                <input name="requestcookieexpectfrom" type="text" class="formfld" id="requestcookieexpectfrom" size="25" value="">From:
                <input name="requestcookieexpectto" type="text" class="formfld" id="requestcookieexpectto" size="25" value=""><br>
                <input type=button onClick="addOption('requestcookieactions','expect &quot' + document.iform.requestcookieexpectfrom.value + '&quot from &quot' + document.iform.requestcookieexpectto.value + '&quot','expect #' + document.iform.requestcookieexpectfrom.value + '# to#' + document.iform.requestcookieexpectto.value + '#')"; value='Add'>
                </div>
                <div id='requestcookiefilter' style="display:none;">
                <input name="requestcookiefilterfrom" type="text" class="formfld" id="requestcookiefilterfrom" size="25" value="">From:
                <input name="requestcookiefilterto" type="text" class="formfld" id="requestcookiefilterto" size="25" value=""><br>
                <input type=button onClick="addOption('requestcookieactions','filter &quot' + document.iform.requestcookiefilterfrom.value + '&quot from &quot' + document.iform.requestcookiefilterto.value + '&quot','filter #' + document.iform.requestcookiefilterfrom.value + '# to#' + document.iform.requestcookiefilterto.value + '#')"; value='Add'>
                </div>
                <div id='requestcookiehash' style="display:none;"> 
                <input name="requestcookiehashfrom" type="text" class="formfld" id="requestcookiehashfrom" size="25" value="">
                <input type=button onClick="addOption('requestcookieactions','hash &quot' + document.iform.requestcookiehashfrom.value + '&quot','hash #' + document.iform.requestcookiehashfrom.value + '#')" value='Add'>
                </div>
                <div id='requestcookieremove' style="display:none;">
                <input name="requestcookieremovefrom" type="text" class="formfld" id="requestcookieremovefrom" size="25" value="">
                <input type=button onClick="addOption('requestcookieactions','remove &quot' + document.iform.requestcookieremovefrom.value + '&quot','remove #' + document.iform.requestcookieremovefrom.value + '#')" value='Add'>
		</div>
                <div id='requestcookielog' style="display:none;">
                <input name="requestcookielogfrom" type="text" class="formfld" id="requestcookielogfrom" size="25" value="">
                <input type=button onClick="addOption('requestcookieactions','log &quot' + document.iform.requestcookielogfrom.value + '&quot','log #' + document.iform.requestcookielogfrom.value + '#')" value='Add'>
                </div>
                </td>
                </tr>
                <tr>
                <td width="22%" valign="top" class="vncellreq">Query</td>
                <td width="78%" class="vtable">
                <SELECT name="requestqueryactions" style="width: 350px; height: 150px" id="requestqueryactions" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['requestqueryactions']); $i++): ?>                <?php if($pconfig['requestqueryactions']["action$i"]['action'] == 'append' || $pconfig['requestqueryactions']["action$i"]['action'] == 'change' ): ?>
                        <?$dir = 'to';?>
                        <option value="<?=$pconfig['requestqueryactions']["action$i"]['action'];?> #<?=$pconfig['requestqueryactions']["action$i"]['from'];?># to#<?=$pconfig['requestqueryactions']["action$i"]['to'];?>#">
                <?=$pconfig['requestqueryactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestqueryactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requestqueryactions']["action$i"]['to'];?>&quot;
                </option>
                <?elseif($pconfig['requestqueryactions']["action$i"]['action'] == 'expect' || $pconfig['requestqueryactions']["action$i"]['action'] == 'filter' ): ?>                        <?$dir = 'from';?>
                        <option value="<?=$pconfig['requestqueryactions']["action$i"]['action'];?> #<?=$pconfig['requestqueryactions']["action$i"]['from'];?># to#<?=$pconfig['requestqueryactions']["action$i"]['to'];?>#">
                <?=$pconfig['requestqueryactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestqueryactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requestqueryactions']["action$i"]['to'];?>&quot;
                </option>
                <?else:?>
                <option value="<?=$pconfig['requestqueryactions']["action$i"]['action'];?> #<?=$pconfig['requestqueryactions']["action$i"]['from'];?>">
                <?=$pconfig['requestqueryactions']["action$i"]['action'];?> &quot;<?=$pconfig['requestqueryactions']["action$i"]['from'];?>&quot;
                </option>
                <?php endif; ?>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(requestqueryactions)"; value='Remove Selected'><br><br>
                <input name="requestqueryactionlist" type="hidden" value="">
                </select>
                      <select name="requestqueryaction" class="formfld" id="requestqueryaction" onChange="switchaction('requestquery.' + document.iform.requestqueryaction.value)">
                      <?php foreach ($protoaction3 as $act3 => $act3name): ?>
                      <option value="<?=$act3name;?>" <?php if ($act3 == $pconfig['act3']) echo "selected"; ?>>
                      <?=htmlspecialchars($act3name);?>
                      </option>
                      <?php endforeach; ?>
                </select>
                <div id='requestqueryappend' style="display:block;">
                <input name="requestqueryappendfrom" type="text" class="formfld" id="requestqueryappendfrom" size="25" value="">To:
                <input name="requestqueryappendto" type="text" class="formfld" id="requestqueryappendto" size="25" value=""><br>
                <input type=button onClick="addOption('requestqueryactions','append &quot' + document.iform.requestqueryappendfrom.value + '&quot to &quot' + document.iform.requestqueryappendto.value + '&quot','append #' + document.iform.requestqueryappendfrom.value + '# to#' + document.iform.requestqueryappendto.value + '#')"; value='Add'>
                </div>
                <div id='requestquerychange' style="display:none;">
                <input name="requestquerychangefrom" type="text" class="formfld" id="requestquerychangefrom" size="25" value="">To:
                <input name="requestquerychangeto" type="text" class="formfld" id="requestquerychangeto" size="25" value=""><br>
                <input type=button onClick="addOption('requestqueryactions','change &quot' + document.iform.requestquerychangefrom.value + '&quot to &quot' + document.iform.requestquerychangeto.value + '&quot','change #' + document.iform.requestquerychangefrom.value + '# to#' + document.iform.requestquerychangeto.value + '#')"; value='Add'>
                </div>
                <div id='requestqueryexpect' style="display:none;">
                <input name="requestqueryexpectfrom" type="text" class="formfld" id="requestqueryexpectfrom" size="25" value="">From:
                <input name="requestqueryexpectto" type="text" class="formfld" id="requestqueryexpectto" size="25" value=""><br>
                <input type=button onClick="addOption('requestqueryactions','expect &quot' + document.iform.requestqueryexpectfrom.value + '&quot from &quot' + document.iform.requestqueryexpectto.value + '&quot','expect #' + document.iform.requestqueryexpectfrom.value + '# to#' + document.iform.requestqueryexpectto.value + '#')"; value='Add'>
                </div>
                <div id='requestqueryfilter' style="display:none;">
                <input name="requestqueryfilterfrom" type="text" class="formfld" id="requestqueryfilterfrom" size="25" value="">From:
                <input name="requestqueryfilterto" type="text" class="formfld" id="requestqueryfilterto" size="25" value=""><br>
                <input type=button onClick="addOption('requestqueryactions','filter &quot' + document.iform.requestqueryfilterfrom.value + '&quot from &quot' + document.iform.requestqueryfilterto.value + '&quot','filter #' + document.iform.requestqueryfilterfrom.value + '# to#' +document.iform.requestqueryfilterto.value + '#')"; value='Add'>
                </div>
                <div id='requestqueryhash' style="display:none;">                <input name="requestqueryhashfrom" type="text" class="formfld" id="requestqueryhashfrom" size="25" value="">
                <input type=button onClick="addOption('requestqueryactions','hash &quot' + document.iform.requestqueryhashfrom.value + '&quot','hash #' + document.iform.requestqueryhashfrom.value + '#')" value='Add'>
                </div>
                <div id='requestqueryremove' style="display:none;">
                <input name="requestqueryremovefrom" type="text" class="formfld" id="requestqueryremovefrom" size="25" value="">
                <input type=button onClick="addOption('requestqueryactions','remove &quot' + document.iform.requestqueryremovefrom.value + '&quot','remove #' + document.iform.requestqueryremovefrom.value + '#')" value='Add'>                </div>
                <div id='requestquerylog' style="display:none;">
                <input name="requestquerylogfrom" type="text" class="formfld" id="requestquerylogfrom" size="25" value="">
                <input type=button onClick="addOption('requestqueryactions','log &quot' + document.iform.requestquerylogfrom.value + '&quot','log #' + document.iform.requestquerylogfrom.value + '#')" value='Add'>
                </div>
                </td>
                </tr>
		<tr>
                <td width="22%" valign="top" class="vncellreq">URL</td>
                <td width="78%" class="vtable">
                <SELECT name="requesturlactions" style="width: 350px; height: 150px" id="requesturlactions" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['requesturlactions']); $i++): ?>                <?php if($pconfig['requesturlactions']["action$i"]['action'] == 'append' || $pconfig['requesturlactions']["action$i"]['action'] == 'change' ): ?> 
                        <?$dir = 'to';?>
                        <option value="<?=$pconfig['requesturlactions']["action$i"]['action'];?> #<?=$pconfig['requesturlactions']["action$i"]['from'];?># to#<?=$pconfig['requesturlactions']["action$i"]['to'];?>#">
                <?=$pconfig['requesturlactions']["action$i"]['action'];?> &quot;<?=$pconfig['requesturlactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requesturlactions']["action$i"]['to'];?>&quot;
                </option>
                <?elseif($pconfig['requesturlactions']["action$i"]['action'] == 'expect' || $pconfig['requesturlactions']["action$i"]['action'] == 'filter' ): ?>                        <?$dir = 'from';?>
                        <option value="<?=$pconfig['requesturlactions']["action$i"]['action'];?> #<?=$pconfig['requesturlactions']["action$i"]['from'];?># to#<?=$pconfig['requesturlactions']["action$i"]['to'];?>#">
                <?=$pconfig['requesturlactions']["action$i"]['action'];?> &quot;<?=$pconfig['requesturlactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['requesturlactions']["action$i"]['to'];?>&quot;
                </option>
                <?else:?>
                <option value="<?=$pconfig['requesturlactions']["action$i"]['action'];?> #<?=$pconfig['requesturlactions']["action$i"]['from'];?>">
                <?=$pconfig['requesturlactions']["action$i"]['action'];?> &quot;<?=$pconfig['requesturlactions']["action$i"]['from'];?>&quot;
                </option>
                <?php endif; ?>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(requesturlactions)"; value='Remove Selected'><br><br>
                <input name="requesturlactionlist" type="hidden" value="">
                </select>
                      <select name="requesturlaction" class="formfld" id="requesturlaction" onChange="switchaction('requesturl.' + document.iform.requesturlaction.value)">
                      <?php foreach ($protoaction3 as $act3 => $act3name): ?>
                      <option value="<?=$act3name;?>" <?php if ($act3 == $pconfig['act3']) echo "selected"; ?>>
                      <?=htmlspecialchars($act3name);?>
                      </option>
                      <?php endforeach; ?>
                </select>
                <div id='requesturlappend' style="display:block;">
                <input name="requesturlappendfrom" type="text" class="formfld" id="requesturlappendfrom" size="25" value="">To:
                <input name="requesturlappendto" type="text" class="formfld" id="requesturlappendto" size="25" value=""><br>
                <input type=button onClick="addOption('requesturlactions','append &quot' + document.iform.requesturlappendfrom.value + '&quot to &quot' + document.iform.requesturlappendto.value + '&quot','append #' + document.iform.requesturlappendfrom.value + '# to#' + document.iform.requesturlappendto.value + '#')"; value='Add'>
                </div>
                <div id='requesturlchange' style="display:none;">
                <input name="requesturlchangefrom" type="text" class="formfld" id="requesturlchangefrom" size="25" value="">To:
                <input name="requesturlchangeto" type="text" class="formfld" id="requesturlchangeto" size="25" value=""><br>
                <input type=button onClick="addOption('requesturlactions','change &quot' + document.iform.requesturlchangefrom.value + '&quot to &quot' + document.iform.requesturlchangeto.value + '&quot','change #' + document.iform.requesturlchangefrom.value + '# to#' + document.iform.requesturlchangeto.value + '#')"; value='Add'>
                </div>
                <div id='requesturlexpect' style="display:none;">
                <input name="requesturlexpectfrom" type="text" class="formfld" id="requesturlexpectfrom" size="25" value="">From:
                <input name="requesturlexpectto" type="text" class="formfld" id="requesturlexpectto" size="25" value=""><br>
                <input type=button onClick="addOption('requesturlactions','expect &quot' + document.iform.requesturlexpectfrom.value + '&quot from &quot' + document.iform.requesturlexpectto.value + '&quot','expect #' + document.iform.requesturlexpectfrom.value + '# to#' + document.iform.requesturlexpectto.value + '#')"; value='Add'>
                </div>
                <div id='requesturlfilter' style="display:none;">
                <input name="requesturlfilterfrom" type="text" class="formfld" id="requesturlfilterfrom" size="25" value="">From:
                <input name="requesturlfilterto" type="text" class="formfld" id="requesturlfilterto" size="25" value=""><br>
                <input type=button onClick="addOption('requesturlactions','filter &quot' + document.iform.requesturlfilterfrom.value + '&quot from &quot' + document.iform.requesturlfilterto.value + '&quot','filter #' + document.iform.requesturlfilterfrom.value + '# to#' + document.iform.requesturlfilterto.value + '#')"; value='Add'>
                </div>
                <div id='requesturlhash' style="display:none;">                
		<input name="requesturlhashfrom" type="text" class="formfld" id="requesturlhashfrom" size="25" value="">
                <input type=button onClick="addOption('requesturlactions','hash &quot' + document.iform.requesturlhashfrom.value + '&quot','hash #' + document.iform.requesturlhashfrom.value + '#')" value='Add'>
                </div>
                <div id='requesturlremove' style="display:none;">
                <input name="requesturlremovefrom" type="text" class="formfld" id="requesturlremovefrom" size="25" value="">
                <input type=button onClick="addOption('requesturlactions','remove &quot' + document.iform.requesturlremovefrom.value + '&quot','remove #' + document.iform.requesturlremovefrom.value + '#')" value='Add'>                </div>
                <div id='requesturllog' style="display:none;">
                <input name="requesturllogfrom" type="text" class="formfld" id="requesturllogfrom" size="25" value="">
                <input type=button onClick="addOption('requesturlactions','log &quot' + document.iform.requesturllogfrom.value + '&quot','log #' + document.iform.requesturllogfrom.value + '#')" value='Add'>
                </div>
                </td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Response Actions</td>
                  <td width="78%" class="vtable">
                    <br> <span class="vexpl">The following set of actions apply to the data stream from the server to the client</span></td>
                </tr>
		   <tr>
                <td width="22%" valign="top" class="vncellreq">Headers</td>
                <td width="78%" class="vtable">
                <SELECT name="responseheaderactions" style="width: 350px; height: 150px" id="responseheaderactions" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['responseheaderactions']); $i++): ?>
		<?php if($pconfig['responseheaderactions']["action$i"]['action'] == 'append' || $pconfig['responseheaderactions']["action$i"]['action'] == 'change' ): ?>
                        <?$dir = 'to';?>
                        <option value="<?=$pconfig['responseheaderactions']["action$i"]['action'];?> #<?=$pconfig['responseheaderactions']["action$i"]['from'];?># to#<?=$pconfig['responseheaderactions']["action$i"]['to'];?>#">
                <?=$pconfig['responseheaderactions']["action$i"]['action'];?> &quot;<?=$pconfig['responseheaderactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['responseheaderactions']["action$i"]['to'];?>&quot;
                </option>
                <?elseif($pconfig['responseheaderactions']["action$i"]['action'] == 'expect' || $pconfig['responseheaderactions']["action$i"]['action'] == 'filter' ): ?>                        <?$dir = 'from';?>
                        <option value="<?=$pconfig['responseheaderactions']["action$i"]['action'];?> #<?=$pconfig['responseheaderactions']["action$i"]['from'];?># to#<?=$pconfig['responseheaderactions']["action$i"]['to'];?>#">
                <?=$pconfig['responseheaderactions']["action$i"]['action'];?> &quot;<?=$pconfig['responseheaderactions']["action$i"]['from'];?>&quot; <?=$dir?>  &quot;<?=$pconfig['responseheaderactions']["action$i"]['to'];?>&quot;
                </option>
                <?else:?>
                <option value="<?=$pconfig['responseheaderactions']["action$i"]['action'];?> #<?=$pconfig['responseheaderactions']["action$i"]['from'];?>">                
		<?=$pconfig['responseheaderactions']["action$i"]['action'];?> &quot;<?=$pconfig['responseheaderactions']["action$i"]['from'];?>&quot;
                </option>
                <?php endif; ?>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(responseheaderactions)"; value='Remove Selected'><br><br>
                <input name="responseheaderactionlist" type="hidden" value="">
                </select>
                      <select name="responseheaderaction" class="formfld" id="responseheaderaction" onChange="switchaction('responseheader.' + document.iform.responseheaderaction.value)">
                      <?php foreach ($protoaction3 as $act3 => $act3name): ?>
                      <option value="<?=$act3name;?>" <?php if ($act3 == $pconfig['act3']) echo "selected"; ?>>
                      <?=htmlspecialchars($act3name);?>
                      </option>
                      <?php endforeach; ?>
                </select>
                <div id='responseheaderappend' style="display:block;">
                <input name="responseheaderappendfrom" type="text" class="formfld" id="responseheaderappendfrom" size="25" value="">To:
                <input name="responseheaderappendto" type="text" class="formfld" id="responseheaderappendto" size="25" value=""><br>
                <input type=button onClick="addOption('responseheaderactions','append &quot' + document.iform.responseheaderappendfrom.value + '&quot to &quot' + document.iform.responseheaderappendto.value + '&quot','append #' + document.iform.responseheaderappendfrom.value + '# to#' + document.iform.responseheaderappendto.value + '#')"; value='Add'>
                </div>
                <div id='responseheaderchange' style="display:none;">
                <input name="responseheaderchangefrom" type="text" class="formfld" id="responseheaderchangefrom" size="25" value="">To:
                <input name="responseheaderchangeto" type="text" class="formfld" id="responseheaderchangeto" size="25" value=""><br>
                <input type=button onClick="addOption('responseheaderactions','change &quot' + document.iform.responseheaderchangefrom.value + '&quot to &quot' + document.iform.responseheaderchangeto.value + '&quot','change #' + document.iform.responseheaderchangefrom.value + '# to#' + document.iform.responseheaderchangeto.value + '#')"; value='Add'>
                </div>
                <div id='responseheaderexpect' style="display:none;">
                <input name="responseheaderexpectfrom" type="text" class="formfld" id="responseheaderexpectfrom" size="25" value="">From:
                <input name="responseheaderexpectto" type="text" class="formfld" id="responseheaderexpectto" size="25" value=""><br>
                <input type=button onClick="addOption('responseheaderactions','expect &quot' + document.iform.responseheaderexpectfrom.value + '&quot from &quot' + document.iform.responseheaderexpectto.value + '&quot','expect #' + document.iform.responseheaderexpectfrom.value + '# to#' + document.iform.responseheaderexpectto.value + '#')"; value='Add'>
                </div>
                <div id='responseheaderfilter' style="display:none;">
                <input name="responseheaderfilterfrom" type="text" class="formfld" id="responseheaderfilterfrom" size="25" value="">From:
                <input name="responseheaderfilterto" type="text" class="formfld" id="responseheaderfilterto" size="25" value=""><br>
                <input type=button onClick="addOption('responseheaderactions','filter &quot' + document.iform.responseheaderfilterfrom.value + '&quot from &quot' + document.iform.responseheaderfilterto.value + '&quot','filter #' + document.iform.responseheaderfilterfrom.value + '# to#' + document.iform.responseheaderfilterto.value + '#')"; value='Add'>
                </div>
                <div id='responseheaderhash' style="display:none;">                <input name="responseheaderhashfrom" type="text" class="formfld" id="responseheaderhashfrom" size="25" value="">
                <input type=button onClick="addOption('responseheaderactions','hash &quot' + document.iform.responseheaderhashfrom.value + '&quot','hash #' + document.iform.responseheaderhashfrom.value + '#')" value='Add'>
                </div>
                <div id='responseheaderremove' style="display:none;">
                <input name="responseheaderremovefrom" type="text" class="formfld" id="responseheaderremovefrom" size="25" value="">
                <input type=button onClick="addOption('responseheaderactions','remove &quot' + document.iform.responseheaderremovefrom.value + '&quot','remove #' + document.iform.responseheaderremovefrom.value + '#')" value='Add'>                </div>
                <div id='responseheaderlog' style="display:none;">
                <input name="responseheaderlogfrom" type="text" class="formfld" id="responseheaderlogfrom" size="25" value="">
                <input type=button onClick="addOption('responseheaderactions','log &quot' + document.iform.responseheaderlogfrom.value + '&quot','log #' + document.iform.responseheaderlogfrom.value + '#')" value='Add'>
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
