#!/bin/php
<?php
 
$pgtitle = array("Firewall", "ALTQ");
require("guiconfig.inc");

$pconfig['enable'] = isset($config['altq']['enable']);
if (isset($config['altq']['enable'])) {
    $pconfig['bandwidth'] = $config['altq']['bandwidth'];
}
?> 

<script type="text/javascript">

// pre-submit callback 
function showRequest(formData, jqForm, options) { 
    displayProcessingDiv(); 
    return true; 
}

// post-submit callback 
function showResponse(responseText, statusText)  {
    if(responseText.match(/SUBMITSUCCESS/)) {  
           setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 2000);
    }
} 

        // wait for the DOM to be loaded
    $(document).ready(function() {
            $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
            var options = {
                        target:        '#save_config',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback 
                        success:       showResponse  // post-submit callback
            };

           // bind form using 'ajaxForm'
           $('#iform').ajaxForm(options);
    });
</script> 

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="firewall_altq">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="enable">Enable ALTQ</label>
                             <input id="enable" type="checkbox" name="enable" value="Yes" <?php if ($pconfig['enable']) echo "checked"; ?> />
			</div>
                        <div>
                             <label for="bandwidth">Uplink Speed</label>
                             <input id="bandwidth" type="text" name="bandwidth" value="<?=htmlspecialchars($pconfig['bandwidth']);?>" />
			     <p class="note">Uplink speed of WAN interface in Kilobits/s.</p>
			</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" value="Save" class="button" />

		<input type="button" value="Discard" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
	<p id="copyright">Created by <a href="http://nidahas.com/">Prabhath Sirisena</a>. This stuff is in public domain.</p>
	
</div><!-- /wrapper -->
