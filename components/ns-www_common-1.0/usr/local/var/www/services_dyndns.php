#!/bin/php
<?php

$pgtitle = array("Services", "Dynamic DNS");
require("guiconfig.inc");
include("ns-begin.inc");

$pconfig['username'] = $config['dyndns']['username'];
$pconfig['password'] = $config['dyndns']['password'];
$pconfig['host'] = $config['dyndns']['host'];
$pconfig['server'] = $config['dyndns']['server'];
$pconfig['port'] = $config['dyndns']['port'];
$pconfig['mx'] = $config['dyndns']['mx'];
$pconfig['type'] = $config['dyndns']['type'];
$pconfig['enable'] = isset($config['dyndns']['enable']);
$pconfig['wildcard'] = isset($config['dyndns']['wildcard']);

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/services_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        });
    return false;
    });
});
</script>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">

<form action="forms/services_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="service_dyndns">
<input name="id" type="hidden" value="<?=$id;?>">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="enable">Enable Dyndns</label> <input id="enable"
	type="checkbox" name="enable" value="Yes"
	<?php if ($pconfig['enable']) echo "checked"; ?> /></div>
<div><label for="type">Service Type</label> <select name="type"
	class="formfld" id="type">
	<?php $types = explode(",", "DynDNS,DHS,ODS,DyNS,HN.ORG,ZoneEdit,GNUDip,DynDNS (static),DynDNS (custom),easyDNS,EZ-IP,TZO");
	$vals = explode(" ", "dyndns dhs ods dyns hn zoneedit gnudip dyndns-static dyndns-custom easydns ezip tzo");
	$j = 0; for ($j = 0; $j < count($vals); $j++): ?>
	<option value="<?=$vals[$j];?>"
	<?php if ($vals[$j] == $pconfig['type']) echo "selected";?>><?=htmlspecialchars($types[$j]);?>
	</option>
	<?php endfor; ?>
</select>
<p class="note">You may enter a description here for your reference (not
parsed).</p>
</div>
<div><label for="host">Hostname</label> <input id="host" type="text"
	name="host" value="<?=htmlspecialchars($pconfig['host']);?>" /></div>
<div><label for="server">Server</label> <input id="server" type="text"
	name="server" value="<?=htmlspecialchars($pconfig['server']);?>" />
<p class="note">Special server to connect to. This can usually be left
blank.</p>
</div>
<div><label for="port">Port</label> <input id="port" type="text"
	name="port" value="<?=htmlspecialchars($pconfig['port']);?>" />
<p class="note">Special server port to connect to. This can usually be
left blank.</p>
</div>
<div><label for="mx">MX</label> <input id="mx" type="text" name="mx"
	value="<?=htmlspecialchars($pconfig['mx']);?>" />
<p class="note">Set this option only if you need a special MX record.
Not all services support this.</p>
</div>
<div><label for="wildcard">Wildcards</label> <input name="wildcard"
	type="checkbox" id="wildcard" value="yes"
	<?php if ($pconfig['wildcard']) echo "checked"; ?>>
<p class="note">Enable Wildcard</p>
</div>
<div><label for="username">Username</label> <input id="username"
	type="text" name="username"
	value="<?=htmlspecialchars($pconfig['username']);?>" /></div>
<div><label for="password">Password</label> <input id="password"
	type="password" name="password"
	value="<?=htmlspecialchars($pconfig['password']);?>" /></div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->
