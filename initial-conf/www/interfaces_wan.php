#!/bin/php
<?php
/*
	$Id: vpn_ipsec_edit.php,v 1.16 2009/04/20 06:59:38 jrecords Exp $
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

$pgtitle = array("Interfaces", "WAN");
require("guiconfig.inc");

$optcfg = &$config['interfaces']['wan'];
$wancfg = &$config['interfaces']['wan'];

if ($_POST) {
 
  unset($input_errors);
  $pconfig = $_POST;
 
  /* input validation */
  if ($_POST['type'] == "Static") {
    $reqdfields = explode(" ", "ipaddr subnet gateway");
    $reqdfieldsn = explode(",", "IP address,Subnet bit count,Gateway");
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  } else if ($_POST['type'] == "PPPoE") {
    if ($_POST['pppoe_dialondemand']) {
      $reqdfields = explode(" ", "username password pppoe_dialondemand pppoe_idletimeout");
      $reqdfieldsn = explode(",", "PPPoE username,PPPoE password,Dial on demand,Idle timeout value");
    } else {
      $reqdfields = explode(" ", "username password");
      $reqdfieldsn = explode(",", "PPPoE username,PPPoE password");
    }
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  } else if ($_POST['type'] == "PPTP") {
    if ($_POST['pptp_dialondemand']) {
      $reqdfields = explode(" ", "pptp_username pptp_password pptp_local pptp_subnet pptp_remote pptp_dialondemand pptp_idletimeout");
      $reqdfieldsn = explode(",", "PPTP username,PPTP password,PPTP local IP address,PPTP subnet,PPTP remote IP address,Dial on demand,Idle timeout value");
    } else {
      $reqdfields = explode(" ", "pptp_username pptp_password pptp_local pptp_subnet pptp_remote");
      $reqdfieldsn = explode(",", "PPTP username,PPTP password,PPTP local IP address,PPTP subnet,PPTP remote IP address");
    }
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  } else if ($_POST['type'] == "BigPond") {
    $reqdfields = explode(" ", "bigpond_username bigpond_password");
    $reqdfieldsn = explode(",", "BigPond username,BigPond password");
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
  }
  
  $_POST['spoofmac'] = str_replace("-", ":", $_POST['spoofmac']);
  
  if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
    $input_errors[] = "A valid IP address must be specified.";
  }
  if (($_POST['subnet'] && !is_numeric($_POST['subnet']))) {
    $input_errors[] = "A valid subnet bit count must be specified.";
  }
  if (($_POST['gateway'] && !is_ipaddr($_POST['gateway']))) {
    $input_errors[] = "A valid gateway must be specified.";
  }
  if (($_POST['pointtopoint'] && !is_ipaddr($_POST['pointtopoint']))) {
    $input_errors[] = "A valid point-to-point IP address must be specified.";
  }
  if (($_POST['provider'] && !is_domain($_POST['provider']))) {
    $input_errors[] = "The service name contains invalid characters.";
  }
  if (($_POST['pppoe_idletimeout'] != "") && !is_numericint($_POST['pppoe_idletimeout'])) {
    $input_errors[] = "The idle timeout value must be an integer.";
  }
  if (($_POST['spoofmac'] && !is_macaddr($_POST['spoofmac']))) {
    $input_errors[] = "A valid MAC address must be specified.";
  }
  if ($_POST['mtu'] && (($_POST['mtu'] < 576) || ($_POST['mtu'] > 1500))) {
    $input_errors[] = "The MTU must be between 576 and 1500 bytes.";
  }
  
  /* Wireless interface? */
  if (isset($optcfg['wireless'])) {
    $wi_input_errors = wireless_config_post();
    if ($wi_input_errors) {
      $input_errors = array_merge($input_errors, $wi_input_errors);
    }
  }
 
  if (!$input_errors) {
  
    unset($wancfg['ipaddr']);
    unset($wancfg['subnet']);
    unset($wancfg['gateway']);
    unset($wancfg['aliaslist']);
    unset($wancfg['pointtopoint']);
    unset($wancfg['dhcphostname']);
    unset($config['pppoe']['username']);
    unset($config['pppoe']['password']);
    unset($config['pppoe']['provider']);
    unset($config['pppoe']['ondemand']);
    unset($config['pppoe']['timeout']);
    unset($config['pptp']['username']);
    unset($config['pptp']['password']);
    unset($config['pptp']['local']);
    unset($config['pptp']['subnet']);
    unset($config['pptp']['remote']);
    unset($config['pptp']['ondemand']);
    unset($config['pptp']['timeout']);
    unset($config['bigpond']['username']);
    unset($config['bigpond']['password']);
    unset($config['bigpond']['authserver']);
    unset($config['bigpond']['authdomain']);
    unset($config['bigpond']['minheartbeatinterval']);
  
    if ($_POST['type'] == "Static") {
      $wancfg['ipaddr'] = $_POST['ipaddr'];
      $wancfg['subnet'] = $_POST['subnet'];
      $wancfg['gateway'] = $_POST['gateway'];
      $aliaslist = explode(',', $_POST['memberslist']);
      for($i=0;$i<sizeof($aliaslist); $i++) {
      	$alias = 'alias'."$i";
      	$prop = preg_replace("/ /", "", $aliaslist[$i]);
      	$wancfg['aliaslist'][$alias] = $prop;
      }
      if (isset($wancfg['ispointtopoint']))
        $wancfg['pointtopoint'] = $_POST['pointtopoint'];
    } else if ($_POST['type'] == "DHCP") {
      $wancfg['ipaddr'] = "dhcp";
      $wancfg['dhcphostname'] = $_POST['dhcphostname'];
    } else if ($_POST['type'] == "PPPoE") {
      $wancfg['ipaddr'] = "pppoe";
      $config['pppoe']['username'] = $_POST['username'];
      $config['pppoe']['password'] = $_POST['password'];
      $config['pppoe']['provider'] = $_POST['provider'];
      $config['pppoe']['ondemand'] = $_POST['pppoe_dialondemand'] ? true : false;
      $config['pppoe']['timeout'] = $_POST['pppoe_idletimeout'];
    }
    
    $wancfg['spoofmac'] = $_POST['spoofmac'];
    $wancfg['mtu'] = $_POST['mtu'];
  
    write_config();
    
    $retval = 0;
    if (!file_exists($d_sysrebootreqd_path)) {
      config_lock();
      $retval = interfaces_wan_configure();
      config_unlock();
    }
    $savemsg = get_std_save_message($retval);
  }
}

$pconfig['username'] = $config['pppoe']['username'];
$pconfig['password'] = $config['pppoe']['password'];
$pconfig['provider'] = $config['pppoe']['provider'];
$pconfig['pppoe_dialondemand'] = isset($config['pppoe']['ondemand']);
$pconfig['pppoe_idletimeout'] = $config['pppoe']['timeout'];

$pconfig['pptp_username'] = $config['pptp']['username'];
$pconfig['pptp_password'] = $config['pptp']['password'];
$pconfig['pptp_local'] = $config['pptp']['local'];
$pconfig['pptp_subnet'] = $config['pptp']['subnet'];
$pconfig['pptp_remote'] = $config['pptp']['remote'];
$pconfig['pptp_dialondemand'] = isset($config['pptp']['ondemand']);
$pconfig['pptp_idletimeout'] = $config['pptp']['timeout'];

$pconfig['bigpond_username'] = $config['bigpond']['username'];
$pconfig['bigpond_password'] = $config['bigpond']['password'];
$pconfig['bigpond_authserver'] = $config['bigpond']['authserver'];
$pconfig['bigpond_authdomain'] = $config['bigpond']['authdomain'];
$pconfig['bigpond_minheartbeatinterval'] = $config['bigpond']['minheartbeatinterval'];

$pconfig['dhcphostname'] = $wancfg['dhcphostname'];

if ($wancfg['ipaddr'] == "dhcp") {
        $pconfig['type'] = "DHCP";
} else if ($wancfg['ipaddr'] == "pppoe") {
        $pconfig['type'] = "PPPoE";
} else if ($wancfg['ipaddr'] == "pptp") {
        $pconfig['type'] = "PPTP";
} else if ($wancfg['ipaddr'] == "bigpond") {
        $pconfig['type'] = "BigPond";
} else {
        $pconfig['type'] = "Static";
        $pconfig['ipaddr'] = $wancfg['ipaddr'];
        $pconfig['subnet'] = $wancfg['subnet'];
        $pconfig['gateway'] = $wancfg['gateway'];
        $pconfig['pointtopoint'] = $wancfg['pointtopoint'];
}

$pconfig['blockpriv'] = isset($wancfg['blockpriv']);
$pconfig['spoofmac'] = $wancfg['spoofmac'];
$pconfig['mtu'] = $wancfg['mtu'];
$pconfig['aliaslist'] = $wancfg['aliaslist'];

/* Find next free carp interface */
for($i=0;$i<100; $i++) {
        if($config['interfaces']['wan']['carp']['carpif'] == 'carp' . "$i")
                        continue;
        if($config['interfaces']['lan']['carp']['carpif'] == 'carp' . "$i")
                        continue;
$pconfig['carpif'] = 'carp' . "$i";
break;
}

/* Wireless interface? */
if (isset($optcfg['wireless'])) {
        require("interfaces_wlan.inc");
        wireless_config_init();
}

?>
<?php include("fbegin.inc"); ?>
<script language="javascript" src="/nss.js"></script>
<script language="javascript">
<!--
var tabs=new Array('Static', 'DHCP', 'PPPoE');
 
function switchtab(tab){
hidealltabs();
showdiv(tab);
}
 
function hidealltabs(){
//loop through the array and hide each element by id
for (var i=0;i<tabs.length;i++){
  hidediv(tabs[i]);
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
<table width="100%" id="navigator" border="0" cellpadding="0" cellspacing="0">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
            <form action="interfaces_wan.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
            <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="middle"><strong>Type</strong></td>
                  <td><select name="type" class="formfld" id="type" onchange="switchtab(document.iform.type.value)";>
                      <?php $opts = split(" ", "Static DHCP PPPoE");
                                foreach ($opts as $opt): ?>
                      <option value="<?=htmlspecialchars($opt);?>"<?php if ($opt == $pconfig['type']) echo " selected";?>>
                      <?=htmlspecialchars($opt);?>
                      </option>
                      <?php endforeach; ?>
                    </select></td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="4"></td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" class="listtopic">General configuration</td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">MAC address</td>
                  <td class="vtable"> <input name="spoofmac" type="text" class="formfld" id="spoofmac" size="30" value="<?=htmlspecialchars($pconfig['spoofmac']);?>">
                    <br>
                    This field can be used to modify (&quot;spoof&quot;) the MAC
                    address of the WAN interface<br>
                    (may be required with some cable connections)<br>
                    Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx
                    or leave blank</td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">MTU</td>
                  <td class="vtable"> <input name="mtu" type="text" class="formfld" id="mtu" size="8" value="<?=htmlspecialchars($pconfig['mtu']);?>">
                    <br>
                    If you enter a value in this field, then MSS clamping for
                    TCP connections to the value entered above minus 40 (TCP/IP
                    header size) will be in effect. If you leave this field blank,
                    an MTU of 1492 bytes for PPPoE and 1500 bytes for all other
                    connection types will be assumed.</td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
             </table>

<div id="Static" style="display:block">
<?php if ($input_errors) print_input_errors($input_errors); ?>
             <table width="100%" border="0" cellpadding="6" cellspacing="0">
	      <tr>
                  <td colspan="2" valign="top" class="listtopic">Static IP configuration</td>
                </tr>
                <tr>
                  <td width="100" valign="top" class="vncellreq">IP address</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="ipaddr" type="text" class="formfld" id="ipaddr" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
                    /
                    <select name="subnet" class="formfld" id="subnet">
                    <?php
                        if (isset($wancfg['ispointtopoint']))
                        $snmax = 32;
                      else
                        $snmax = 31;
                      for ($i = $snmax; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select></td>
                </tr><?php if (isset($wancfg['ispointtopoint'])): ?>
                <tr>
                  <td valign="top" class="vncellreq">Point-to-point IP address </td>
                  <td class="vtable">
                    <?=$mandfldhtml;?><input name="pointtopoint" type="text" class="formfld" id="pointtopoint" size="20" value="<?=htmlspecialchars($pconfig['pointtopoint']);?>">
                  </td>
                </tr><?php endif; ?>
                <tr>
                  <td valign="top" class="vncellreq">Gateway</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>">
                  </td>
                </tr>
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
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_ipsec[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
 		</div>
	</table>
	</div>
	<div id="DHCP" style="display:none">
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr>
                  <td colspan="2" valign="top" class="listtopic">DHCP client configuration</td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Hostname</td>
                  <td class="vtable"> <input name="dhcphostname" type="text" class="formfld" id="dhcphostname" size="40" value="<?=htmlspecialchars($pconfig['dhcphostname']);?>">
                    <br>
                    The value in this field is sent as the DHCP client identifier
                    and hostname when requesting a DHCP lease. Some ISPs may require
                    this (for client identification).</td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_ipsec[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
                </tr>
		</table>
        	</div>
		<div id="PPPoE" style="display:none">
        	<table width="100%" border="0" cellpadding="6" cellspacing="0">	
	     	<tr>
                <div id='PPPOE' style="display:none;">
                  <td colspan="2" valign="top" class="listtopic">PPPoE configuration</td>
                </tr>
                <tr>
                  <td valign="top" class="vncellreq">Username</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="username" type="text" class="formfld" id="username" size="20" value="<?=htmlspecialchars($pconfig['username']);?>">
                  </td>
                </tr>
                <tr>
                  <td valign="top" class="vncellreq">Password</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="password" type="text" class="formfld" id="password" size="20" value="<?=htmlspecialchars($pconfig['password']);?>">
                  </td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Service name</td>
                  <td class="vtable"><input name="provider" type="text" class="formfld" id="provider" size="20" value="<?=htmlspecialchars($pconfig['provider']);?>">
                    <br> <span class="vexpl">Hint: this field can usually be left
                    empty</span></td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Dial on demand</td>
                  <td class="vtable"><input name="pppoe_dialondemand" type="checkbox" id="pppoe_dialondemand" value="enable" <?php if ($pconfig['pppoe_dialondemand']) echo "checked"; ?> onClick="en
able_change(false)" >
                    <strong>Enable Dial-On-Demand mode</strong><br>
                    This option causes the interface to operate in dial-on-demand mode, allowing you to have a <i>virtual full time</i> connection. The interface is configured, but the actual conne
ction of the link is delayed until qualifying outgoing traffic is detected.</td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Idle timeout</td>
                  <td class="vtable">
                    <input name="pppoe_idletimeout" type="text" class="formfld" id="pppoe_idletimeout" size="8" value="<?=htmlspecialchars($pconfig['pppoe_idletimeout']);?>">
                    seconds<br>
    If no qualifying outgoing packets are transmitted for the specified number of seconds, the connection is brought down. An idle timeout of zero disables this feature.</td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_ipsec[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                    <?php endif; ?>
                  </td>
		</tr>
		</table>
	    </form>
	</div>
<script language="JavaScript">
<!--
javascript:switchtab(document.iform.type.value)
//-->
</script>
<?php include("fend.inc"); ?>
