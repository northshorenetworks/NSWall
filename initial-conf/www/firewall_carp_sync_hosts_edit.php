#!/bin/php
<?php 
/*
	$Id: firewall_carp_sync_hosts_edit.php,v 1.1 2009/04/20 06:56:53 jrecords Exp $
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

$pgtitle = array("Firewall", "CARP", "Edit Config Sync Host");
require("guiconfig.inc");

if (!is_array($config['carp']['configsynchost']))
        $config['carp']['configsynchost'] = array();


configsynchosts_sort();
$a_configsynchost = &$config['carp']['configsynchost'];

$id = $_GET['id'];
if (isset($_POST['id']))
        $id = $_POST['id'];

if (isset($_POST['after']))
        $after = $_POST['after'];

if (isset($_GET['dup'])) {
        $id = $_GET['dup'];
        $after = $_GET['dup'];
}

if (isset($id) && $a_configsynchost[$id]) {
        $pconfig['name'] = $a_configsynchost[$id]['name'];
        $pconfig['descr'] = $a_configsynchost[$id]['descr'];
        $pconfig['ip'] = $a_configsynchost[$id]['ip'];
	$pconfig['username'] = $a_configsynchost[$id]['username']; 
        $pconfig['password'] = $a_configsynchost[$id]['password'];
	$pconfig['port'] = $a_configsynchost[$id]['port'];
}

if (isset($_GET['dup']))
        unset($id);

if ($_POST) {

        unset($input_errors);
        unset($configsynchost['name']);
        unset($configsynchost['descr']);
        unset($configsynchost['ip']);
	unset($configsynchost['username']);
        unset($configsynchost['password']);
	unset($configsynchost['port']);
        $pconfig = $_POST;

        /* input validation */
        $reqdfields = explode(" ", "name");
        $reqdfieldsn = explode(",", "Name,");

        do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

        $configsynchost = array();
        $configsynchost['name'] = $_POST['name'];
        $configsynchost['descr'] = $_POST['descr'];
        $configsynchost['ip'] = $_POST['ip'];
 	$configsynchost['username'] = $_POST['username'];
        $configsynchost['password'] = $_POST['password'];
        $configsynchost['port'] = $_POST['port'];

	if (isset($id) && $a_configsynchost[$id])
                $a_configsynchost[$id] = $configsynchost;
        else
                $a_configsynchost[] = $configsynchost;
 
        if (!$input_errors) {

                write_config();


                header("Location: firewall_carp_sync_hosts.php");
                exit;
        }
}

?>
<form action="firewall_carp_sync_hosts_edit.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
                  <td colspan="2" valign="top" class="listtopic">Clustering configuration</td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Name</td>
                  <td width="78%" class="vtable">
                    <input name="name" type="text" class="formfld" id="name" size="20" value="<?=htmlspecialchars($pconfig['name']);?>">
                    <input name="members" type="hidden" value="">
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncell">Description</td>
                  <td width="78%" class="vtable">
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">You may enter a description here
                    for your reference (not parsed).</span></td>
                </tr>
		<tr>
                  <td valign="top" class="vncell">Config Sync Host IP</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="ip" type="text" class="formfld" id="ip" size="16" value="<?=htmlspecialchars($pconfig['ip']);?>">
                </td>    
                </tr>
		<tr>
                  <td valign="top" class="vncell">Username</td>
                  <td class="vtable"> <input name="username" type="text" class="formfld" id="username" size="16" value="<?=htmlspecialchars($pconfig['username']);?>">
                    <br>
                     The authentication username to use when talking to this config sync host. This user must be configured on the host. 
                </tr>
		<tr>
                  <td valign="top" class="vncell">Password</td>
                  <td class="vtable"> <input name="password" type="password" class="formfld" id="password" size="16" value="<?=htmlspecialchars($pconfig['password']);?>">
                    <br>
		     The authentication password to use when talking to this config sync host. This password must be configured on the host.
		</tr>
  		<tr>
                  <td valign="top" class="vncell">Port</td>
                  <td class="vtable"> <input name="port" type="text" class="formfld" id="port" size="4" value="<?=htmlspecialchars($pconfig['port']);?>">
                    <br>
		     The port that the remote config sync host uses for configuration.
		</tr>
		</table>
		<table>
			<br><input name="Submit" type="submit" class="formbtn" value="Save"><br><br>
			<?php if (isset($id) && $a_configsynchost[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
			<p><span class="vexpl"><strong><span class="red">Warning:</span><br>
			</strong>After you click &quot;Save&quot;, you must reboot the firewall to make the changes take effect. You may also have to do one or more of the following steps before you can access your firewall again: </span></p>

        </tr>
</table>
</form>
