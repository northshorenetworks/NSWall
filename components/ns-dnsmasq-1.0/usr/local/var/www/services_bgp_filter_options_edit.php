#!/bin/php
<?php
 
$pgtitle = array("Services", "BGPd", "Filter Options");
require("guiconfig.inc");
include("ns-begin.inc");
$pconfig['defaultroute'] = isset($config['bgpd']['filter']['options']['defaultroute']);
$pconfig['rfc5735'] = isset($config['bgpd']['filter']['options']['rfc5735']);
$pconfig['prefixfilter'] = isset($config['bgpd']['filter']['options']['prefixfilter']);

?> 

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#filteroptsubmitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#filteroptiform").serialize();
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

	<form action="forms/services_form_submit.php" method="post" name="filteroptiform" id="filteroptiform">
        <input name="formname" type="hidden" value="service_bgp_filter_options">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
	            		<label for="defaultroute">Do not accept a default route</label>
    	        		<input id="defaultroute" type="checkbox" name="defaultroute" value="Yes" <?php if ($pconfig['defaultroute']) echo "checked"; ?> />
			</div>
			<br>
			<div>
                                <label for="rfc5735">Filter bogus networks according to RFC5735</label>
                                <input id="rfc5735" type="checkbox" name="rfc5735" value="Yes" <?php if ($pconfig['rfc5735']) echo "checked"; ?> />
                        </div>
			<br>
                        <div>
                                <label for="prefixfilter">Filter out prefixes longer than 24 or shorter than 8 bits</label>
                                <input id="prefixfilter" type="checkbox" name="prefixfilter" value="Yes" <?php if ($pconfig['prefixfilter']) echo "checked"; ?> />
                        </div>

	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="filteroptsubmitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
