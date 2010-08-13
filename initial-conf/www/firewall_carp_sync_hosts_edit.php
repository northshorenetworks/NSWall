#!/bin/php
<?php 

$pgtitle = array("Firewall", "CARP", "Edit Config Sync Host");
require("guiconfig.inc");
include("ns-begin.inc");

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

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_general">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="name">Name</label>
                             <input id="name" type="text" name="name" value="<?=htmlspecialchars($pconfig['name']);?>" />
                             <p class="note">Name of the virtual host, for your reference</p>
            </div>
			<div>
                             <label for="email">Description</label>
                             <input id="descr" type="text" name="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>" />
			     			 <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
			<div>
                             <label for="ip">Config Sync Host IP</label>
            				 <input name="ip" type="text" class="formfld" id="ip" size="16" value="<?=htmlspecialchars($pconfig['ip']);?>">
			</div>
           	<div>
                             <label for="username">Username</label>
                             <input name="username" type="text" class="formfld" id="username" size="16" value="<?=htmlspecialchars($pconfig['username']);?>">
							 <p class="note">The authentication username to use when talking to this config sync host. This user must be configured on the host</p>
            </div> 
			<div>
                             <label for="password">Password</label>
                             <input id="password" type="password" name="password" value="" />
                             <p class="note">The authentication password to use when talking to this config sync host. This password must be configured on the host.</p>
			</div>
			<div>
                             <label for="port">Port</label>
            				 <input name="port" type="text" class="formfld" id="port" size="4" value="<?=htmlspecialchars($pconfig['port']);?>">
							 <p class="note">The port that the remote config sync host uses for configuration.</p>
			</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
