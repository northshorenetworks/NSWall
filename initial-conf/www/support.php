#!/bin/php
<?php

require("guiconfig.inc");
$pgtitle = array("Diagnostics", "Support");

?>

<script type="text/javascript">

        // When a user clicks on the submit button, post the form.
          $("#support_login_button").click(function () {
               $('#support_login').dialog('close');
               $("#support").html('<center>Submitting troubleshooting ticket to support.<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
               $('#support').dialog('open');
               var QueryString = $("#supportlogin").serialize()+'&'+$("#supportform").serialize();   
               $.post("forms/system_form_submit.php", QueryString, function(output) {
                    $("#support").html(output);
               });
               return false;
          });

$("#support_submit_button").click(function () {
    $(".ui-dialog-titlebar").css('display','block');
    $('#support_login').dialog('open');
    return false;
});

// wait for the DOM to be loaded
</script>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">
<form method="post" name="supportform" id="supportform"><input
	name="formname" type="hidden" value="system_submit_support_ticket">
<div id="tabAddress">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="caseid">Case Number</label> <input id="caseid"
	type="text" name="caseid" value="" />
<p class="note">If you have a case number enter it here, leave blank to
create a new case</p>
</div>
<div><label for="classify1">Category</label> <select id="classify1"
	name="classify1">
	<option value="system">System</option>
	<option value="interfaces">Interfaces</option>
	<option value="services">Services</option>
	<option value="firewall">Firewall</option>
	<option value="vpn">VPN</option>
</select
                             <p class="note">Select a Category that relates to your issue</p>   
               </div>
<div><label for="notes">Case Notes</label> <textarea name="notes"
	cols="60" rows="7" id="notes" class="notes"></textarea>
<p class="note">Enter any relevant information here to be read by a
support technician</p>
</div>
<div><label for="notes">Support Access</label> <input id="all"
	type="checkbox" name="all" value="" checked />
<p class="note">Create rule to allow support to connect to this
appliance</p>
<br>
</div>
<div><label for="all">Debug Info</label> <input id="debuginfo"
	type="checkbox" name="debuginfo" value="" checked />
<p class="note">Attach debug information to support case</p>
<br>
</div>
</fieldset>
<div class="buttonrow"><input type="submit" id="support_submit_button"
	value="Submit" class="button" /></div>

</form>
</div>
</div>
</div>
