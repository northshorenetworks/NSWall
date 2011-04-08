#!/bin/php
<?php

$pgtitle = array("Dignostics", "Remote Syslog");
require("guiconfig.inc");
include("ns-begin.inc");

$pconfig['remoteserver'] = $config['syslog']['remoteserver'];
$pconfig['enable'] = isset($config['syslog']['enable']);

?>
<script type="text/javascript">
// wait for the DOM to be loaded
$(document).ready(function() {
	$('#livediag').hide();
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

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">

<form action="forms/system_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="system_syslog">

<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="syslogenabled">Enable Remote Syslog</label> <input
	id="syslogenabled" type="checkbox" name="syslogenabled"
	value="<?php if ($pconfig['syslogenabled']) echo "checked"; ?>" />
<p class="note">Enable sending syslog messages to remote server.</p>
</div>
<div><label for="remoteserver">Syslog Server</label> <input
	id="remoteserver" type="text" name="remoteserver"
	value="<?=htmlspecialchars($pconfig['remoteserver']);?>" size="20" /></div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->

