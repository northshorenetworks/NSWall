#!/bin/php
<?php

require("guiconfig.inc");
include("ns-begin.inc");
$pgtitle = array("Support", "Device", "Registration");
?>

<script type="text/javascript">

        // When a user clicks on the submit button, post the form.
          $("#support_login_button").click(function () {
               $('#support_login').dialog('close');
               $("#support_diag").html('<center>Contacting registration server.<br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner"></center>');
			   
               $('#support_diag').dialog('open');
               var QueryString = $("#supportlogin").serialize()+'&'+$("#supportform").serialize();   
               $.post("forms/system_form_submit.php", QueryString, function(output) {
                    $("#support_diag").html(output);
               });
               return false;
          });

$("#support_submit_button").click(function () {
    $(".ui-dialog-titlebar").css('display','block');
    $('#support_login').dialog('open');
    return false;
});

</script>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">
<form method="post" name="supportform" id="supportform">
             <input name="formname" type="hidden" value="system_register_device">
			 <div id="tabAddress">
            <fieldset>
                        <legend><?=join(": ", $pgtitle);?></legend>
						<center>This page can be used to automatically register this appliance to your support account.  Your device will need to be regisered in order to receive technical support.  If you have not created a user account, you can do so by going to <a href="http://www.northshoresoftware.com/newcustomer.php">Registration Center</a> You can also register this device manually on the Registration center if you choose to do so. By clicking the register button you will be prompted for your support login credentials please email registration@northshoresoftware.com with any issues.</center> 
            </fieldset>
                <div class="buttonrow">
        <center><input type="submit" id="support_submit_button" value="Register" class="button" /></center>
    </div>

</form>
</div>
</div>
</div>
