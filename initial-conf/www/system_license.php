#!/bin/php
<?php
$pgtitle = array("System", "License");
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
               if(responseText.match(/SUBMITSUCCESS/)) {
                   setTimeout(function(){ $('#upload_firmware').dialog('close'); }, 2000);
               }
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
           $('#iform').ajaxForm(options);
    });
</script> 

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_license">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
		<div>
           <label for="serial_no">Serial Number</label>
           <?php echo sg_get_const("SERIAL")?>
        </div>
		<div>
           <label for="ipsec_gateways">IPSEC Gateways</label>
		   <?php echo sg_get_const("IPSEC_GATEWAYS")?>
		</div>
	    <div>
           <label for="managed_device">Managed Device</label>
           <?php echo sg_get_const("MANAGED_DEVICE")?>
        </div>
        <div>
           <label for="carp">CARP</label>
           <?php echo sg_get_const("CARP")?>
        </div>
        <div>
           <label for="enterprise_routing">Enterprise Routing</label>
           <?php echo sg_get_const("ENTERPRISE_ROUTING")?>
        </div>
        <div>
		   <label for="update_license">Update License</label>
		   <input name="ulfile" type="file" class="button" id="ulfile">
        </div> 
	</fieldset>
	<div class="buttonrow">
	<input type="submit" id="submitbutton" value="Update License" class="button" />
	</div>
	</form>
	</div><!-- /form-container -->
</div><!-- /wrapper -->
