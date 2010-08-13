#!/bin/php
<?php
 
$pgtitle = array("Firewall", "ALTQ");
require("guiconfig.inc");
include("ns-begin.inc");
$pconfig['enable'] = isset($config['altq']['enable']);
if (isset($config['altq']['enable'])) {
    $pconfig['bandwidth'] = $config['altq']['bandwidth'];
}
?> 

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/firewall_form_submit.php", QueryString, function(output) {
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

	<form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="firewall_altq">

	<fieldset>
		<legend>User : Registration</legend>
			<div>
                             <label for="firstname">First Name</label>
                             <input id="firstname" type="text" name="firstname" value="" />
                        </div>
			<div>
                             <label for="lastname">Last Name</label>
                             <input id="lastname" type="text" name="lastname" value="" />
                        </div>
                        <div>
                             <label for="email">Email</label>
                             <input id="email" type="text" name="email" value="" />
                        </div>
                        <div>
                             <label for="username">Username</label>
                             <input id="username" type="text" name="username" value="" />
                        </div>
                        <div>
                             <label for="password">Password</label>
                             <input id="password" type="password" name="password" value="" />
                        </div>
                        <div>
                             <label for="company">Company</label>
                             <input id="company" type="text" name="company" value="" />
                        </div>
                        <div>
                             <label for="phone">Phone Number</label>
                             <input id="phone" type="text" name="phone" value="" />
                        </div>
                        <div>
                             <label for="mobile">Mobile Number</label>
                             <input id="mobile" type="text" name="mobile" value="" />
                        </div>
                        <div>
                             <label for="address">Address</label>
                             <input id="address" type="text" name="address" value="" />
                        </div>
                        <div>
                             <label for="city">City</label>
                             <input id="city" type="text" name="city" value="" />
                        </div>
                        <div>
                             <label for="state">State</label>
                             <input id="state" type="text" name="state" value="" />
                        </div>
                        <div>
                             <label for="zip">Zipcode</label>
                             <input id="zip" type="text" name="zip" value="" />
                        </div>
                        <div>
                             <label for="country">Country</label>
                             <input id="country" type="text" name="country" value="" />
                        </div>
	</fieldset>		
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
