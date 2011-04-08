#!/bin/php
<?php

$pgtitle = array("Services", "DNS forwarder");
require("guiconfig.inc");
include("ns-begin.inc");
$pconfig['enable'] = isset($config['dnsmasq']['enable']);

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


<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">

<form action="forms/services_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden"
	value="service_dnsforwarder">

<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="enable">Enable DNS Forwarder</label> <input id="enable"
	type="checkbox" name="enable" value="Yes"
	<?php if ($pconfig['enable']) echo "checked"; ?> /></div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->
