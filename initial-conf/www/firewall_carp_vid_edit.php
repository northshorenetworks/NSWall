#!/bin/php
<?php 
/*
	$Id: firewall_carp_vid_edit.php,v 1.1 2009/04/20 06:56:53 jrecords Exp $
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

$pgtitle = array("Firewall", "CARP", "Edit Virtual Host");
require("guiconfig.inc");

if (!is_array($config['carp']['virtualhost']))
        $config['carp']['virtualhost'] = array();


virtualhosts_sort();
$a_virtualhost = &$config['carp']['virtualhost'];

$id = $_GET['id'];
if (isset($_POST['id']))
        $id = $_POST['id'];

if (isset($_POST['after']))
        $after = $_POST['after'];

if (isset($_GET['dup'])) {
        $id = $_GET['dup'];
        $after = $_GET['dup'];
}

if (isset($id) && $a_virtualhost[$id]) {
        $pconfig['name'] = $a_virtualhost[$id]['name'];
        $pconfig['descr'] = $a_virtualhost[$id]['descr'];
        $pconfig['ip'] = $a_virtualhost[$id]['ip'];
        $pconfig['subnet'] = $a_virtualhost[$id]['subnet'];
        $pconfig['interface'] = $a_virtualhost[$id]['interface'];
        $pconfig['password'] = $a_virtualhost[$id]['password'];
	$pconfig['carpmode'] = $a_virtualhost[$id]['carpmode'];
	$pconfig['carphostmode'] = $a_virtualhost[$id]['carphostmode'];
	$pconfig['activemember'] = $a_virtualhost[$id]['activemember'];
	$pconfig['activenodes'] = $a_virtualhost[$id]['activenodes'];
}

if (isset($_GET['dup']))
        unset($id);

if ($_POST) {

        unset($input_errors);
        unset($virtualhost['name']);
        unset($virtualhost['descr']);
        unset($virtualhost['ip']);
        unset($virtualhost['subnet']);
        unset($virtualhost['interface']);
        unset($virtualhost['password']);
	unset($virtualhost['carpmode']);
	unset($virtualhost['carphostmode']);
        unset($virtualhost['activemember']);
	unset($virtualhost['activenodes']);
	$pconfig = $_POST;

        /* input validation */
        $reqdfields = explode(" ", "name");
        $reqdfieldsn = explode(",", "Name,");

        do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

        $virtualhostent = array();
        $virtualhostent['name'] = $_POST['name'];
        $virtualhostent['descr'] = $_POST['descr'];
        $virtualhostent['ip'] = $_POST['ip'];
        $virtualhostent['subnet'] = $_POST['subnet'];
        $virtualhostent['interface'] = $_POST['interface'];
        $virtualhostent['password'] = $_POST['password'];
        $virtualhostent['carpmode'] = $_POST['carpmode'];
	$virtualhostent['carphostmode'] = $_POST['carphostmode'];
 	$virtualhostent['activemember'] = $_POST['activemember'];
	$virtualhostent['activenodes'] = $_POST['activenodes'];

	if (isset($id) && $a_virtualhost[$id])
                $a_virtualhost[$id] = $virtualhostent;
        else
                $a_virtualhost[] = $virtualhostent;
 
        if (!$input_errors) {

                write_config();

                header("Location: firewall_carp_vid.php");
                exit;
        }
}

?>
<?php include("fbegin.inc"); ?>
<form action="firewall_carp_vid_edit.php" method="post">
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
                  <td valign="top" class="vncell">Virtual Host IP</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="ip" type="text" class="formfld" id="ip" size="16" value="<?=htmlspecialchars($pconfig['ip']);?>">
                   <strong>/</strong>
                    <select name="subnet" class="formfld" id="subnet">
                      <?php for ($i = 30; $i >= 1; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
                </td>    
                </tr>
		 <tr>
                  <td width="22%" valign="top" class="vncellreq">Interface</td>
                  <td width="78%" class="vtable">
                                        <select name="interface" class="formfld">
                      <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
                                          for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                                                $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                                          }
                                          foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">Choose on which interface the Virtual Host will exist.</span></td> 
                </tr>
		<tr>
                  <td valign="top" class="vncell">Password</td>
                  <td class="vtable"> <input name="password" type="password" class="formfld" id="password" size="20" value="<?=htmlspecialchars($pconfig['password']);?>">
                    <br>
                     The authentication password to use when talking to other CARP-enabled hosts in this redundancy group. This must be the same on all members of the group
		</tr>
		 <tr> 
                  <td width="22%" valign="top" class="vncell">CARP Mode</td>
                  <td width="78%" class="vtable"> <input name="carpmode" type="radio" value="activestandby" <?php if ($pconfig['carpmode'] == "activestandby") echo "checked"; ?>>
                    Active/Standby &nbsp;&nbsp;&nbsp; <input type="radio" name="carpmode" value="activeactive" <?php if ($pconfig['carpmode'] == "activeactive") echo "checked"; ?>>
                    Active/Active</td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncell">CARP Host Mode</td>
                  <td width="78%" class="vtable"> <input name="carphostmode" type="radio" value="active" <?php if ($pconfig['carphostmode'] == "active") echo "checked"; ?>>
                  Active &nbsp;&nbsp;&nbsp; <input type="radio" name="standby" value="standby" <?php if ($pconfig['carphostmode'] == "standby") echo "checked"; ?>>
                    Standby</td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Cluster Member</td>
                  <td width="78%" class="vtable"> Member
                    <input name="activemember" type="text" class="formfld" id="activemember" size="4" value="<?=htmlspecialchars($pconfig['activemember']);?>"> of 
                <input name="activenodes" type="text" class="formfld" id="activenodes" size="4" value="<?=htmlspecialchars($pconfig['activenodes']);?>"> Nodes
		</tr>
		</table>
		<table>
			<br><input name="Submit" type="submit" class="formbtn" value="Save"><br><br>
			<?php if (isset($id) && $a_virtualhost[$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
			<p><span class="vexpl"><strong><span class="red">Warning:</span><br>
			</strong>After you click &quot;Save&quot;, you must reboot the firewall to make the changes take effect. You may also have to do one or more of the following steps before you can access your firewall again: </span></p>

        </tr>
</table>
</form>
<?php include("fend.inc"); ?>
