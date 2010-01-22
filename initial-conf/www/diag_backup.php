#!/bin/php
<?php 

$pgtitle = array("Diagnostics", "Backup/Restore");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
	 $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');

});

</script> 

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">
 
             <form action="forms/diagnostics_form_submit.php" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="diag_backup">
             <div id="tabAddress">
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
        </div>
</form>
</div>
</div>
</div>
