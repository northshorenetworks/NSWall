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

require("guiconfig.inc");

function is_timezone($elt) {
        return !preg_match("/\/$/", $elt);
}

exec('/bin/tar -tzf /usr/share/zoneinfo.tgz', $timezonelist);
$timezonelist = array_filter($timezonelist, 'is_timezone');
sort($timezonelist);

if ($_POST) {
 
  unset($input_errors);
  $pconfig = $_POST;
 
  /*
  if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
    $input_errors[] = "A valid IP address must be specified.";
  }
  if (($_POST['subnet'] && !is_numeric($_POST['subnet']))) {
    $input_errors[] = "A valid subnet bit count must be specified.";
  }
  if (($_POST['gateway'] && !is_ipaddr($_POST['gateway']))) {
    $input_errors[] = "A valid gateway must be specified.";
  }
  if (($_POST['provider'] && !is_domain($_POST['provider']))) {
    $input_errors[] = "The service name contains invalid characters.";
  }
  if (($_POST['pppoe_idletimeout'] != "") && !is_numericint($_POST['pppoe_idletimeout'])) {
    $input_errors[] = "The idle timeout value must be an integer.";
  }
  */

  /* Wireless interface? */
  if (isset($optcfg['wireless'])) {
    $wi_input_errors = wireless_config_post();
    if ($wi_input_errors) {
      $input_errors = array_merge($input_errors, $wi_input_errors);
    }
  }

  if (!$input_errors) {
    $wancfg = $config['interfaces']['wan'];
     
    unset($wancfg['ipaddr']);
    unset($wancfg['subnet']);
    unset($wancfg['gateway']);
    unset($wancfg['dhcphostname']);
    unset($config['pppoe']['username']);
    unset($config['pppoe']['password']);
    unset($config['pppoe']['provider']);
    unset($config['pppoe']['ondemand']);
    unset($config['pppoe']['timeout']);
 
    if ($_POST['wantype'] == "Static") {
      $wancfg['ipaddr'] = $_POST['staticipaddr'];
      $wancfg['subnet'] = $_POST['staticsubnet'];
      $wancfg['gateway'] = $_POST['staticgateway'];
      $config['system']['general']['dnsserver'][] = $_POST['staticdnsserver'];
    } else if ($_POST['wantype'] == "DHCP") {
      $wancfg['ipaddr'] = "dhcp";
      $wancfg['dhcphostname'] = $_POST['dhcphostname'];
    } else if ($_POST['wantype'] == "PPPoE") {
      $wancfg['ipaddr'] = "pppoe";
      $config['pppoe']['username'] = $_POST['pppoeusername'];
      $config['pppoe']['password'] = $_POST['pppoepassword'];
      $config['pppoe']['provider'] = $_POST['pppoeprovider'];
      $config['pppoe']['ondemand'] = $_POST['pppoe_dialondemand'] ? true : false;
      $config['pppoe']['timeout'] = $_POST['pppoe_idletimeout'];
    }

    $config['interfaces']['lan']['ipaddr'] = $_POST['lanipaddr'];
    $config['interfaces']['lan']['subnet'] = $_POST['lansubnet'];
     
    $config['system']['hostname'] = $_POST['hostname'];
    $config['system']['username'] = $_POST['username'];
    $config['system']['password'] = crypt($_POST['password']);
    $config['system']['general']['timezone'] = $_POST['timezone'];
     
    write_config();
	
    system_reboot();
  
    $rebootmsg = "The system is rebooting now.  Please wait 1 minute and redirect your browser to the LAN ip address.";	
  }
}

?>

<link href="gui.css" rel="stylesheet" type="text/css">

<script language="JavaScript">
<!--

var tabs=new Array('WANTYPE', 'Static', 'DHCP', 'PPPoE', 'LAN', 'SETTINGS', 'OVERVIEW');

function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			switchtab(radioObj[i].value);
		}
	}
	return "";
}


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
            <form action="wizard_initial.php" method="post" name="iform" id="iform">
	    <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td colspan="2" valign="top" class="wizheader">NSWall Initial Setup Wizard</td>
                  <?php if ($rebootmsg) print_info_box($rebootmsg); ?>
		</tr>
             </table>
<div id="WANTYPE" style="display:block">
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
		  <td valign="top" class="vncellreq">WAN Connection Type</td> 
		</tr>
                <tr>
		  <td valign="top"><center>Select the method your ISP uses to connect to the Internet</center></td> 
		</tr>
                <tr>
                  <td class="vtable">
                    <input type="radio" name="wantypes" value="Static"> Static<br><br>
		    <input type="radio" name="wantypes" value="DHCP" checked> DHCP<br><br>
                    <input type="radio" name="wantypes" value="PPPoE"> PPPoE<br><br>
		  </td>
                </tr>
                <tr>
                  <td class="wiznavbtn">
                    <INPUT TYPE="button" NAME="wtnext" VALUE="Next" onClick="getCheckedValue(document.iform.elements['wantypes'])">
                  </td>	
		</tr>
              </table>
             </div>
	     <?php 
                  if ($rebootmsg) {
                    echo "<script language=javascript>hidealltabs();</script>";
                    //echo "<script language=javascript>setTimeout" . '(\'history.back()\', 45000)</script>';
		    
		  } 
                ?>
	  <div id="Static" style="display:none">
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="top" class="vncellreq" colspan="2">Static IP Configuration</td>
                </tr>
                <tr>
                  <td valign="top" colspan="2"><center>Enter the IP address, default gateway, and primary DNS server for the WAN interface.</center></td>
                </tr>
		<tr>
                  <td width="100" valign="top" class="vncellreq">IP address</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="staticipaddr" type="text" class="formfld" id="staticipaddr" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
                    /
                    <select name="staticsubnet" class="formfld" id="staticsubnet">
                    <?php
                      if (isset($wancfg['ispointtopoint']))
                        $snmax = 32;
                      else
                        $snmax = 31;
                      for ($i = $snmax; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == 24) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select></td>
                <tr>
                  <td valign="top" class="vncellreq">Gateway</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="staticgateway" type="text" class="formfld" id="staticgateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>">
                  </td>
                </tr>
                 <tr>
                  <td valign="top" class="vncellreq">DNS Server</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="staticdnsserver" type="text" class="formfld" id="staticdnsserver" size="20" value="<?=htmlspecialchars($pconfig['dnsserver']);?>">
                  </td>
                </tr> 
		<tr>
                  <td class="wiznavbtn">
		    <INPUT TYPE="button" NAME="staticback" VALUE="Back" onClick="switchtab('WANTYPE')">
		    <INPUT TYPE="button" NAME="staticnext" VALUE="Next" onClick="switchtab('LAN')">
                  </td>
		</tr>
		</table>
             </div>	
             <div id="DHCP" style="display:none">
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="top" class="vncellreq" colspan="2">DHCP Configuration</td>
                </tr>
                <tr>
                  <td valign="top" colspan="2"><center>The value in this field is sent as the DHCP client identifier and hostname when requesting a DHCP lease. Some ISPs may require this (for client identification).<br></center></td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Hostname</td>
                  <td class="vtable"> <input name="dhcphostname" type="text" class="formfld" id="dhcphostname" size="40" value="<?=htmlspecialchars($pconfig['dhcphostname']);?>"><i>(Optional)</i>   
                 </td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
         	<tr>
                  <td class="wiznavbtn">
                    <INPUT TYPE="button" NAME="dhcpback" VALUE="Back" onClick="switchtab('WANTYPE')">
                    <INPUT TYPE="button" NAME="dhcpnext" VALUE="Next" onClick="switchtab('LAN')">
                  </td>
		</tr>
              </table>	
		</div>
	     <div id="PPPoE" style="display:none">
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="top" class="vncellreq" colspan="2">PPPoE Configuration</td>
                </tr>
		<tr>
                  <td valign="top" class="vncellreq">Username</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="pppoeusername" type="text" class="formfld" id="pppoeusername" size="20" value="<?=htmlspecialchars($pconfig['username']);?>">
                  </td>
                </tr>
                <tr>
                  <td valign="top" class="vncellreq">Password</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="pppoepassword" type="text" class="formfld" id="pppoepassword" size="20" value="<?=htmlspecialchars($pconfig['password']);?>">
                  </td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Service name</td>
                  <td class="vtable"><input name="pppoeprovider" type="text" class="formfld" id="pppoeprovider" size="20" value="<?=htmlspecialchars($pconfig['provider']);?>">
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
                  <td class="wiznavbtn">
                    <INPUT TYPE="button" NAME="pppoeback" VALUE="Back" onClick="switchtab('WANTYPE')">
                    <INPUT TYPE="button" NAME="pppoenext" VALUE="Next" onClick="switchtab('LAN')">
		  </td>
                </tr>
              </table>
             </div>
	     <div id="LAN" style="display:none">		
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="top" class="vncellreq" colspan="2">LAN Interface Configuration</td>
                </tr>
                <tr>
                  <td valign="top" colspan="2"><center>Enter the IP address for the LAN interface.</center></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">IP address</td>
                  <td width="78%" class="vtable">
                    <?=$mandfldhtml;?><input name="lanipaddr" type="text" class="formfld" id="lanipaddr" size="20" value="<?=htmlspecialchars($config['interfaces']['lan']['ipaddr']);?>" onchange="ipaddr_change()">
                    /
                    <select name="lansubnet" class="formfld" id="lansubnet">
                      <?php for ($i = 31; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $config['interfaces']['lan']['subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select></td>
                </tr>
	        <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr>
                  <td class="wiznavbtn">
                    <INPUT TYPE="button" NAME="staticback" VALUE="Back" onClick="getCheckedValue(document.iform.elements['wantypes'])">
                    <INPUT TYPE="button" NAME="lannext" VALUE="Next" onClick="switchtab('SETTINGS')">
		  </td>
                </tr>
              </table>
             </div>
	     <div id="SETTINGS" style="display:none">
                 <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="top" class="vncellreq" colspan="2">General Settings</td>
                </tr>
                <tr>
                  <td valign="top" colspan="2"><center>Enter the Hostname, Domain, Admin Username/Password, and Timezone for your NSWall appliance.</center></td>
                </tr>
		<tr> 
                  <td width="22%" valign="top" class="vncellreq">Hostname</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="hostname" type="text" class="formfld" id="hostname" size="40" value="<?=htmlspecialchars($pconfig['hostname']);?>"> 
                    <br> <span class="vexpl">name of the firewall host, without 
                    domain part<br>
                    e.g. <em>firewall</em></span></td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">Username</td>
                  <td class="vtable"> <input name="username" type="text" class="formfld" id="username" size="20" value="<?=$pconfig['username'];?>">
                    <br>
                     <span class="vexpl">If you want 
                    to change the username for accessing the webGUI, enter it 
                    here.</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Password</td>
                  <td width="78%" class="vtable"> <input name="password" type="password" class="formfld" id="password" size="20"> 
                    <br> <input name="password2" type="password" class="formfld" id="password2" size="20"> 
                    &nbsp;(confirmation) <br> <span class="vexpl">If you want 
                    to change the password for accessing the webGUI, enter it 
                    here twice.</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Time zone</td>
                  <td width="78%" class="vtable"> <select name="timezone" id="timezone">
                      <?php foreach ($timezonelist as $value): ?>
                      <option value="<?=htmlspecialchars($value);?>" <?php if ($value == $config['system']['general']['timezone']) echo "selected"; ?>> 
                      <?=htmlspecialchars($value);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">Select the location closest 
                    to you</span></td>
                  </td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr>
                  <td class="wiznavbtn">
        	   <INPUT TYPE="button" NAME="setback" VALUE="Back" onClick="switchtab('LAN')">
		   <INPUT TYPE="button" NAME="setnext" VALUE="Next" onClick="switchtab('OVERVIEW')">
                  </td>
                </tr> 
	     </table> 
	   </div>   
            <div id="OVERVIEW" style="display:none">
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td valign="top" class="vncellreq" colspan="2">Submit Changes and Reboot</td>
                </tr>
                <tr>
                  <td valign="top" colspan="2"><center>Click Submit to commit the changes and reboot.</center></td>
                </tr>
                  <tr>
                  <td width="100%" valign="top">
                    <center><input name="Submit" type="submit" class="formbtn" value="Submit"></center>
                </tr>
                <tr>
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr>
                  <td class="wiznavbtn">
                    <INPUT TYPE="button" NAME="overback" VALUE="Back" onClick="switchtab('SETTINGS')">
                  </td>
                </tr>
              </table>
             </div>
 	    </table>
	</form>
	<table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                <td colspan="2" valign="top" class="wizfooter">NSWall is � 2009 by Northshore Software Inc. All rights reserved.</td>
                </tr> 
        </table>