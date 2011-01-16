#!/bin/php
<?php

$pgtitle = array("Firewall", "CARP", "Edit Virtual Host");
require("guiconfig.inc");
include("ns-begin.inc");

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

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/interfaces_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                setTimeout(function(){  
                     document.location = '#services_carp_tab_vid';
                     $('#content').load('services_carp.php');
                }, 1250);
        });
    return false;
    });
});
</script>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">

<form action="forms/interfaces_form_submit.php" method="post"
	name="iform" id="iform"><input name="formname" type="hidden"
	value="interface_carp_vid"> <input name="id" type="hidden"
	value="<?=$id;?>">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="name">Name</label> <input id="name" type="text"
	name="name" value="<?=htmlspecialchars($pconfig['name']);?>" />
<p class="note">Name of the virtual host, for your reference</p>
</div>
<div><label for="email">Description</label> <input id="descr"
	type="text" name="descr" size="40"
	value="<?=htmlspecialchars($pconfig['descr']);?>" />
<p class="note">You may enter a description here for your reference (not
parsed).</p>
</div>
<div><label for="ip">Virtual Host IP</label> <input name="ip"
	type="text" class="formfld" id="ip" size="16"
	value="<?=htmlspecialchars($pconfig['ip']);?>"> <strong>/</strong> <select
	name="subnet" class="formfld" id="subnet">
	<?php for ($i = 30; $i >= 1; $i--): ?>
	<option value="<?=$i;?>"
	<?php if ($i == $pconfig['subnet']) echo "selected"; ?>><?=$i;?></option>
	<?php endfor; ?>
</select></div>
<div><label for="interface">Interface</label> <select name="interface"
	class="formfld">
	<?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
	}
	for ($i = 0; isset($config['vlans']['vlan'][$i]); $i++) {
		$interfaces['vlan' . $config['vlans']['vlan'][$i]['tag']] = "VLAN{$config['vlans']['vlan'][$i]['tag']}";
	}
	foreach ($interfaces as $iface => $ifacename): ?>
	<option value="<?=$iface;?>"
	<?php if ($iface == $pconfig['interface']) echo "selected"; ?>><?=htmlspecialchars($ifacename);?>
	</option>
	<?php endforeach; ?>
</select>
<p class="note">Choose on which interface the Virtual Host will exist.</p>
</div>
<div><label for="password">Password</label> <input id="password"
	type="password" name="password"
	value="<?=htmlspecialchars($pconfig['password']);?>" />
<p class="note">Authentication password to use when talking to other
CARP-enabled hosts in this redundancy group. This must be the same on
all members of the group.</p>
</div>
<div><label for="carpmode">CARP Mode</label> <select name="carpmode"
	class="formfld">
	<?php $carpmodes = array('activestandby' => 'Active/Standby', 'activeactive' => 'Active/Active');
	foreach ($carpmodes as $mode => $modename): ?>
	<option value="<?=$mode;?>"
	<?php if ($mode == $pconfig['carpmode']) echo "selected"; ?>><?=htmlspecialchars($modename);?>
	</option>
	<?php endforeach; ?>
</select></div>
<div><label for="carphostmode">CARP Host Mode</label> <select
	name="carphostmode" class="formfld">
	<?php $hostmodes = array('active' => 'Active', 'standby' => 'Standby');
	foreach ($hostmodes as $mode => $modename): ?>
	<option value="<?=$mode;?>"
	<?php if ($mode == $pconfig['carphostmode']) echo "selected"; ?>><?=htmlspecialchars($modename);?>
	</option>
	<?php endforeach; ?>
</select></div>
<div><label for="activemember">Cluster Member</label> <input
	name="activemember" type="text" class="formfld" id="activemember"
	size="2" value="<?=htmlspecialchars($pconfig['activemember']);?>"> of <input
	name="activenodes" type="text" class="formfld" id="activenodes"
	size="2" value="<?=htmlspecialchars($pconfig['activenodes']);?>"> Nodes
</div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->