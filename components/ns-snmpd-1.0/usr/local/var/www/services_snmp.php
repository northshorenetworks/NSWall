#!/bin/php
<?php

$pgtitle = array("Services", "SNMP Daemon");
require("guiconfig.inc");
include("ns-begin.inc");
$pconfig['enable'] = isset($config['snmp']['enable']);

if (!is_array($config['snmpd'])) {
	$config['snmpd'] = array();
	$config['snmpd']['rocommunity'] = "public";
}

$pconfig['syslocation'] = $config['snmpd']['syslocation'];
$pconfig['syscontact'] = $config['snmpd']['syscontact'];
$pconfig['rocommunity'] = $config['snmpd']['rocommunity'];
$pconfig['enable'] = isset($config['snmpd']['enable']);
$pconfig['bindlan'] = isset($config['snmpd']['bindlan']);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "rocommunity");
		$reqdfieldsn = explode(",", "Community");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if (!$input_errors) {
		$config['snmpd']['syslocation'] = $_POST['syslocation'];
		$config['snmpd']['syscontact'] = $_POST['syscontact'];
		$config['snmpd']['rocommunity'] = $_POST['rocommunity'];
		$config['snmpd']['enable'] = $_POST['enable'] ? true : false;
		$config['snmpd']['bindlan'] = $_POST['bindlan'] ? true : false;
			
		write_config();

		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = services_snmpd_configure();
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
	}
}

?>
<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#formbtn").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("services_snmp.php", QueryString, function(output) {
            $("#save_config").html();
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        });
    return false;
    });
});
</script>
<script language="JavaScript">
<!--
function enable_change(enable_change) {
	var endis;
	endis = !(document.iform.enable.checked || enable_change);
	document.iform.syslocation.disabled = endis;
	document.iform.syscontact.disabled = endis;
	document.iform.rocommunity.disabled = endis;
	document.iform.bindlan.disabled = endis;
}
//-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="services_snmp.php" method="post" name="iform" id="iform">
<table width="100%" border="0" cellpadding="6" cellspacing="0">
	<tr>
		<td width="22%" valign="top" class="vtable">&nbsp;</td>
		<td width="78%" class="vtable"><input name="enable" type="checkbox"
			value="yes" <?php if ($pconfig['enable']) echo "checked"; ?>
			onClick="enable_change(false)"> <strong>Enable SNMP agent</strong></td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">System location</td>
		<td width="78%" class="vtable"><input name="syslocation" type="text"
			class="formfld" id="syslocation" size="40"
			value="<?=htmlspecialchars($pconfig['syslocation']);?>"></td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">System contact</td>
		<td width="78%" class="vtable"><input name="syscontact" type="text"
			class="formfld" id="syscontact" size="40"
			value="<?=htmlspecialchars($pconfig['syscontact']);?>"></td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncellreq">RO Community</td>
		<td width="78%" class="vtable"><?=$mandfldhtml;?><input
			name="rocommunity" type="text" class="formfld" id="rocommunity"
			size="40" value="<?=htmlspecialchars($pconfig['rocommunity']);?>"> <br>
		This should be a secret string.</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vtable"></td>
		<td width="78%" class="vtable"><input name="bindlan" type="checkbox"
			value="yes" <?php if ($pconfig['bindlan']) echo "checked"; ?>> <strong>Bind
		to LAN interface only</strong> <br>
		This option can be useful when trying to access the SNMP agent by the
		LAN interface's IP address through a VPN tunnel terminated on the WAN
		interface.</td>
	</tr>
	<tr>
		<td width="22%" valign="top">&nbsp;</td>
		<td width="78%"><input name="Submit" type="submit" id="formbtn" class="formbtn"
			value="Save" onClick="enable_change(true)"></td>
	</tr>
</table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
