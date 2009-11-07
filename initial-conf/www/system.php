#!/bin/php
<?php 
/*
	$Id: system.php,v 1.6 2009/04/20 06:59:37 jrecords Exp $
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

$pgtitle = array("System", "General setup");
require("guiconfig.inc");

$pconfig['cert'] = $config['system']['general']['webgui']['certificate'];
$pconfig['hostname'] = $config['system']['hostname'];
$pconfig['domain'] = $config['system']['general']['domain'];
list($pconfig['dns1'],$pconfig['dns2'],$pconfig['dns3']) = $config['system']['general']['dnsserver'];
$pconfig['dnsallowoverride'] = isset($config['system']['general']['dnsallowoverride']);
$pconfig['username'] = $config['system']['username'];
if (!$pconfig['username'])
	$pconfig['username'] = "admin";
$pconfig['webguiproto'] = $config['system']['general']['webgui']['protocol'];
if (!$pconfig['webguiproto'])
	$pconfig['webguiproto'] = "http";
$pconfig['webguiport'] = $config['system']['general']['webgui']['port'];
$pconfig['timezone'] = $config['system']['general']['timezone'];
$pconfig['timeupdateinterval'] = $config['system']['general']['time-update-interval'];
$pconfig['timeservers'] = $config['system']['general']['timeservers'];
$pconfig['sshdenabled'] = isset($config['system']['general']['sshd']['enabled']);
$pconfig['symonenabled'] = isset($config['system']['general']['symon']['enabled']);
$pconfig['muxip'] = $config['system']['general']['symon']['muxip'];

if (!isset($pconfig['timeupdateinterval']))
	$pconfig['timeupdateinterval'] = 300;
if (!$pconfig['timezone'])
	$pconfig['timezone'] = "Etc/UTC";
if (!$pconfig['timeservers'])
	$pconfig['timeservers'] = "pool.ntp.org";
	
function is_timezone($elt) {
	return !preg_match("/\/$/", $elt);
}

exec('/bin/tar -tzf /usr/share/zoneinfo.tgz', $timezonelist);
$timezonelist = array_filter($timezonelist, 'is_timezone');
sort($timezonelist);

?>

<script type="text/javascript">

// pre-submit callback 
function showRequest(formData, jqForm, options) { 
    displayProcessingDiv(); 
    return true; 
}

// post-submit callback 
function showResponse(responseText, statusText)  {
    if(responseText.match(/SUBMITSUCCESS/)) {  
           setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 2000);
    }
} 

        // wait for the DOM to be loaded
    $(document).ready(function() {
            var options = {
                        target:        '#save_config',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback 
                        success:       showResponse  // post-submit callback
            };

           // bind form using 'ajaxForm'
           $('#iform').ajaxForm(options);
    });
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
	<form action="form_submit.php" method="post" name="iform" id="iform">    
	   <input name="formname" type="hidden" value="system_general">	
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Hostname</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="hostname" type="text" class="formfld" id="hostname" size="40" value="<?=htmlspecialchars($pconfig['hostname']);?>"> 
                    <br> <span class="vexpl">name of the firewall host, without 
                    domain part<br>
                    e.g. <em>firewall</em></span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Domain</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="domain" type="text" class="formfld" id="domain" size="40" value="<?=htmlspecialchars($pconfig['domain']);?>"> 
                    <br> <span class="vexpl">e.g. <em>mycorp.com</em> </span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">DNS servers</td>
                  <td width="78%" class="vtable">
                      <input name="dns1" type="text" class="formfld" id="dns1" size="20" value="<?=htmlspecialchars($pconfig['dns1']);?>">
                      <br>
                      <input name="dns2" type="text" class="formfld" id="dns2" size="20" value="<?=htmlspecialchars($pconfig['dns2']);?>">
                      <br>
                      <input name="dns3" type="text" class="formfld" id="dns3" size="20" value="<?=htmlspecialchars($pconfig['dns3']);?>">
                      <br>
                      <span class="vexpl">IP addresses; these are also used for 
                      the DHCP service, DNS forwarder and for PPTP VPN clients<br>
                      <br>
                      <input name="dnsallowoverride" type="checkbox" id="dnsallowoverride" value="yes" <?php if ($pconfig['dnsallowoverride']) echo "checked"; ?>>
                      <strong>Allow DNS server list to be overridden by DHCP/PPP 
                      on WAN</strong><br>
                      If this option is set, NSWall will use DNS servers assigned 
                      by a DHCP/PPP server on WAN for its own purposes (including 
                      the DNS forwarder). They will not be assigned to DHCP and 
                      PPTP VPN clients, though.</span></td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">Username</td>
                  <td class="vtable"> <input name="username" type="text" class="formfld" id="username" size="20" value="<?=$pconfig['username'];?>">
                    <br>
                     <span class="vexpl">If you want 
                    to change the username for accessing the webGUI, enter it 
                    here.</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">Password</td>
                  <td width="78%" class="vtable"> <input name="password" type="password" class="formfld" id="password" size="20"> 
                    <br> <input name="password2" type="password" class="formfld" id="password2" size="20"> 
                    &nbsp;(confirmation) <br> <span class="vexpl">If you want 
                    to change the password for accessing the webGUI, enter it 
                    here twice.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">webGUI Certificate</td>
                  <td width="78%" class="vtable">
                    <select name="cert" class="formfld" id="cert">
                      <?php foreach($config['system']['certmgr']['cert'] as $i): ?>
                      <option value="<?=$i['name'];?>" <?php if ($i == $pconfig['cert']) echo "selected"; ?>>
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select><br>
                  </td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">webGUI port</td>
                  <td class="vtable"> <input name="webguiport" type="text" class="formfld" id="webguiport" size="5" value="<?=htmlspecialchars($pconfig['webguiport']);?>"> 
                    <br>
                    <span class="vexpl">Enter a custom port number for the webGUI 
                    above if you want to override the default (443 
                    ).</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncell">SSH Server</td>
                  <td width="78%" class="vtable"> <input name="sshdenabled" type="checkbox" value="sshdenabled" <?php if ($pconfig['sshdenabled']) echo "checked"; ?>>
                    Enable SSH Server &nbsp;&nbsp;&nbsp;</td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Symon Logging</td>
                  <td width="78%" class="vtable"> <input name="symonenabled" type="checkbox" value="symonenabled" <?php if ($pconfig['symonenabled']) echo "checked"; ?>>
                    Enable Symon Logging &nbsp;&nbsp;&nbsp;<br><br>
                  <span class="vexpl">Symux Server</span>
                  <input name="muxip" type="text" class="formfld" id="muxip" size="25" value="<?=htmlspecialchars($pconfig['muxip']);?>">
                </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">Time zone</td>
                  <td width="78%" class="vtable"> <select name="timezone" id="timezone">
                      <?php foreach ($timezonelist as $value): ?>
                      <option value="<?=htmlspecialchars($value);?>" <?php if ($value == $pconfig['timezone']) echo "selected"; ?>> 
                      <?=htmlspecialchars($value);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">Select the location closest 
                    to you</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">Time update interval</td>
                  <td width="78%" class="vtable"> <input name="timeupdateinterval" type="text" class="formfld" id="timeupdateinterval" size="4" value="<?=htmlspecialchars($pconfig['timeupdateinterval']);?>"> 
                    <br> <span class="vexpl">Minutes between network time sync.; 
                    300 recommended, or 0 to disable </span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">NTP time server</td>
                  <td width="78%" class="vtable"> <input name="timeservers" type="text" class="formfld" id="timeservers" size="40" value="<?=htmlspecialchars($pconfig['timeservers']);?>"> 
                    <br> <span class="vexpl">Use a space to separate multiple 
                    hosts (only one required). Remember to set up at least one 
                    DNS server if you enter a host name here!</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="Save"> 
                  </td>
                </tr>
              </table>
</form>
