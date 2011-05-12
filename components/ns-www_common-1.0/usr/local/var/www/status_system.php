#!/bin/php
<?php
$pgtitle = array("System", "Status");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">
$(document).ready(function() {
     // When a user clicks on the submit button, post the form.
     $("#submitbutton").click(function () {
	  displayProcessingDiv();
	  var QueryString = $("#iform").serialize();
	  $.post("forms/system_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);	  
               setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);            
	  });
	  return false;
     }); 
});
</script>

<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">

<form action="forms/system_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="system_status">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="hostname">Host Name</label> <?php echo $config['system']['hostname'] . "." . $config['system']['general']['domain'];?>
</div>
<div><label for="version">Version</label> <?php readfile("/etc/version"); ?>
B<?php readfile("/etc/version.build"); ?> built on <?php readfile("/etc/version.buildtime"); ?>
</div>
<div><label for="platform">Platform</label> <?php readfile("/etc/hwplatform"); ?>
</div>
<div><label for="cpuusage">CPU Usage</label> <a
	href="#status_graph_cpu">view graph</a></div>
<?php /* <div><label for="serial_no">Serial Number</label> <?php echo sg_get_const("SERIAL")?> </div>*/ ?>
<div><label for="cpuusage">System Notes</label> <textarea name="notes"
	cols="75" rows="7" id="notes" class="notes"><?=htmlspecialchars(base64_decode($config['system']['notes']));?></textarea>
</div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->
