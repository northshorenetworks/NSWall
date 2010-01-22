#!/bin/php
<?php
$pgtitle = array("Diagnostics", "Reboot");

require("guiconfig.inc");

?>

<script type="text/javascript">
// when a user changes the type of memeber, change the related div to sytle = display: block and hide all others

// wait for the DOM to be loaded
$(document).ready(function() {
     $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');

     // When a user clicks on the submit button, post the form.
     $("#submitbutton").click(function () {
     $("#reboot_nswall").dialog("open");
     setTimeout(function(){ $("#reboot_nswall").dialog("close"); window.location = "/login.htm"; }, 75000); 
	  var QueryString = $("#rebootform").serialize()+'&reboot=yes';
      $.post("forms/system_form_submit.php", QueryString, function(output) {
      });
      return false;
     });

});
</script>

<div id="wrapper">
    <div class="form-container ui-tabs ui-widget ui-corner-all">
        <form action="forms/system_form_submit.php" method="post" name="rebootform" id="rebootform">
        <input name="formname" type="hidden" value="system_reboot">
    <fieldset>
    <legend><?=join(": ", $pgtitle);?></legend>
        <div>
            <label for="name">Reboot</label>
            <input name="submitbutton" id="submitbutton" type="submit" class="buttonrow" value=" Yes ">
        </div>
    </fieldset>
    </form>
    </div><!-- /form-container -->
</div><!-- /wrapper -->
