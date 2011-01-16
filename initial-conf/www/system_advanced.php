#!/bin/php
<?php

$pgtitle = array("System", "Advanced setup");
require("guiconfig.inc");
include("ns-begin.inc");

$pconfig['disableconsolemenu'] = isset($config['system']['disableconsolemenu']);
$pconfig['disablefirmwarecheck'] = isset($config['system']['disablefirmwarecheck']);
$pconfig['multiwansupport'] = isset($config['system']['advanced']['multiwansupport']);
$pconfig['bypassstaticroutes'] = isset($config['filter']['bypassstaticroutes']);
$pconfig['noantilockout'] = isset($config['system']['webgui']['noantilockout']);
?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/system_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        });
    return false;
    });
});
</script>

<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">

<form action="forms/system_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="system_advanced">

<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="disableconsolemenu">Disable console menu</label> <input
	id="disableconsolemenu" type="checkbox" name="disableconsolemenu"
	value="Yes"
	<?php if ($pconfig['disableconsolemenu']) echo "checked"; ?> />
<p class="note">Changes to this option will take effect after a reboot.</p>
</div>
<div><label for=="disablefirmwarecheck">Disable firmware version check</label>
<input id="disablefirmwarecheck" type="checkbox"
	name="disablefirmwarecheck" value="Yes"
	<?php if ($pconfig['disablefirmwarecheck']) echo "checked"; ?> />
<p class="note">This will cause NSWall not to check for newer firmware
versions when the <a href="system_firmware.php">System: Firmware</a>
page is viewed.</p>
</div>
	<?php if (sg_get_const("ENTERPRISE_ROUTING") == 'ENABLED'): ?>
<div><label for=="multiwansupport">Enable MultiWAN Support</label> <input
	id="multiwansupport" type="checkbox" name="multiwansupport" value="Yes"
	<?php if ($pconfig['multiwansupport']) echo "checked"; ?> />
<p class="note">Enable MultiWAN support for optional interfaces</p>
</div>
	<?php endif; ?>
<div><label for="bypassstaticroutes">Static route filtering</label> <input
	id="bypassstaticroutes" type="checkbox" name="bypassstaticroutes"
	value="Yes"
	<?php if ($pconfig['bypassstaticroutes']) echo "checked"; ?> />
<p class="note"><strong>Bypass firewall rules for traffic on the same
interface</strong> This option only applies if you have defined one or
more static routes. If it is enabled, traffic that enters and leaves
through the same interface will not be checked by the firewall. This may
be desirable in some situations where multiple subnets are connected to
the same interface.</p>
</div>
<div><label for="noantilockout">webGUI anti-lockout</label> <input
	id="noantilockout" type="checkbox" name="noantilockout" value="Yes"
	<?php if ($pconfig['noantilockout']) echo "checked"; ?> />
<p class="note">By default, access to the webGUI on the LAN interface is
always permitted, regardless of the user-defined filter rule set. Enable
this feature to control webGUI access (make sure to have a filter rule
in place that allows you in, or you will lock yourself out!).<br>
Hint: the &quot;set LAN IP address&quot; option in the console menu
resets this setting as well.</p>
</div>

</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->
