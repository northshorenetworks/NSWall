#!/bin/php
<?php
$pgtitle = array("System", "Firmware", "Upload");
 
require("guiconfig.inc");

?> 

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
     $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');

     // When a user clicks on the submit button, post the form.
     $(".buttonrow").click(function () {
	  $("#upload_firmware").html('');
          $('#upload_firmware').dialog('open');
	  var QueryString = $("#iform").serialize()+'&Submit=Upgrade Firmware';
          alert(QueryString);
	  $.post("forms/system_firmware_form_submit.php", QueryString, function(output) {	  
               $("#upload_firmware").html(output);
               if(output.match(/SUBMITSUCCESS/))
                   setTimeout(function(){ $('#upload_firmware').dialog('close'); }, 2000);         
	  });
	  return false;
     });
  
});
</script> 

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-widget-content ui-corner-all">

	<form action="forms/system_firmware_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_firmware">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
                        <div>
                             <label for=""></label>
                             <p class="note">Choose the image file (<?=$g['fullplatform'];?>-*.img)
                             to be uploaded.<br>Click &quot;Upgrade firmware&quot;
                             to start the upgrade process.</p>
			</div>
			<div>
                             <label for="imagefile">Image File</label>
                             <input name="ulfile" type="file">
			</div>
                        <div>
                             <label for=""></label>
			     <p class="note"></strong></span>DO NOT abort the firmware upgrade once it
                             has started. The firewall will reboot automatically after
                             storing the new firmware. The configuration will be maintained.</span> .</p>
			</div>
	</fieldset>
	<div class="buttonrow">
                <input name="Submit" id="Submit" type="submit" value="Upgrade firmware">
	</div>
	</form>
	</div><!-- /form-container -->	
</div><!-- /wrapper -->
