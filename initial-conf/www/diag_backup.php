#!/bin/php
<?php 

$pgtitle = array("Diagnostics", "Backup/Restore");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
    
    // pre-submit callback
    function showRequest(formData, jqForm, options) {
        $('#upload_firmware').dialog('open');
        return true;
    }

    // post-submit callback
    function showResponse(responseText, statusText)  {
          $("#upload_firmware").html(responseText);
          if(output.match(/SUBMITSUCCESS/))
              setTimeout(function(){ $('#upload_firmware').dialog('close'); }, 2000);
          return false;
    }

        // wait for the DOM to be loaded
    $(document).ready(function() {
            var options = {
                        target:        '#upload_firmware',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback
                        success:       showResponse  // post-submit callback
            };

           // bind form using 'ajaxForm'
           $("#restore").click(function () {
               $('#iform').ajaxForm(options);
           });
    });


</script>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">
 
<form action="forms/diagnostics_form_submit.php" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="diagnostic_backuprestore">
	        <fieldset>
	            <legend><?=join(": ", $pgtitle);?></legend>
                <div>
    	            <label for="backup">Backup Configuration</label>
                    <input name="Submit" type="submit" class="formbtn" id="backup" value="Download configuration">
		    <p class="note">Click this button to download the system configuration in XML format.</p>
		</div>
                <div>
                    <label for="restore">Restore Configuration</label>
                    <input name="conffile" type="file" class="formfld" id="conffile" size="40"><br>
                    <input name="Submit" type="submit" class="formbtn" id="restore" value="Restore configuration">
	            <p class="note">Open a NSWall configuration XML file and click the button below to restore the configuration.</p>
		</div>
            </fieldset>
</form>
</div>
</div>
</div>
