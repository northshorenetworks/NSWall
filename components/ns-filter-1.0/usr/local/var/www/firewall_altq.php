#!/bin/php
<?php

$pgtitle = array("Firewall", "ALTQ");
require("guiconfig.inc");
include("ns-begin.inc");
$pconfig['enable'] = isset($config['altq']['enable']);
if (isset($config['altq']['enable'])) {
	$pconfig['bandwidth'] = $config['altq']['bandwidth'];
}
?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/firewall_form_submit.php", QueryString, function(output) {
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

<form action="forms/firewall_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="firewall_altq">

<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="enable">Enable ALTQ</label> <input id="enable"
	type="checkbox" name="enable" value="Yes"
	<?php if ($pconfig['enable']) echo "checked"; ?> /></div>
<div><label for="bandwidth">Uplink Speed</label> <input id="bandwidth"
	type="text" name="bandwidth"
	value="<?=htmlspecialchars($pconfig['bandwidth']);?>" />
<p class="note">Uplink speed of WAN interface in Kilobits/s.</p>
</div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->
