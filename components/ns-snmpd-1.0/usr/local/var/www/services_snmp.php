#!/bin/php
<?php

$pgtitle = array("Services", "DNS forwarder");
require("guiconfig.inc");
include("ns-begin.inc");

$pconfig['syslocation'] = $config['snmpd']['syslocation'];
$pconfig['syscontact'] = $config['snmpd']['syscontact'];
$pconfig['rocommunity'] = $config['snmpd']['rocommunity'];
$pconfig['enable'] = isset($config['snmpd']['enable']);
$pconfig['bindlan'] = isset($config['snmpd']['bindlan']);

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/snmpd_form_submit.php", QueryString, function(output) {
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

<form action="forms/snmpd_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden"
	value="service_snmpd">

<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="enable">Enable SNMPD</label> <input id="enable"
	type="checkbox" name="enable" value="Yes"
	<?php if ($pconfig['enable']) echo "checked"; ?> />
</div>

<div>
     <label for="syslocation">System Location</label>
     <input name="syslocation" type="text" class="formfld" id="syslocation" size="30" value="<?=htmlspecialchars($pconfig['syslocation']);?>">
     <p class="note"></p>
</div>

<div>
     <label for="syscontact">System Contact</label>
     <input name="syscontact" type="text" class="formfld" id="syscontact" size="30" value="<?=htmlspecialchars($pconfig['syscontact']);?>">
     <p class="note"></p>
</div>

<div>
     <label for="rocommunity">RO Community</label>
     <input name="rocommunity" type="text" class="formfld" id="rocommunity" size="30" value="<?=htmlspecialchars($pconfig['rocommunity']);?>">
     <p class="note"></p>
</div>

<div><label for="bindlan">Bind to LAN interface only</label>
	<input id="bindlan"
        type="checkbox" name="bindlan" value="Yes"
        <?php if ($pconfig['bindlan']) echo "checked"; ?> />
</div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->

