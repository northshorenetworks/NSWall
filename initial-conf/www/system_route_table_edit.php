#!/bin/php
<?php
$pgtitle = array("Interfaces", "System", "Edit Route Table");
require("guiconfig.inc");

if (!is_array($config['system']['routetables']['routetable']))
	$config['system']['routetables']['routetable'] = array();

route_tables_sort();
$a_rtables = &$config['system']['routetables']['routetable'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_rtables[$id]) {
	$pconfig['name'] = $a_rtables[$id]['name'];
	$pconfig['rtableid'] = $a_rtables[$id]['rtableid'];
} 
?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/system_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        		setTimeout(function(){ $('#content').load('system_routes_tabs.php'); }, 1250);
		});
    return false;
    });
});
</script>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
              <input name="formname" type="hidden" value="system_route_table">
	      <input name="id" type="hidden" value="<?=$id;?>">
			 
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
		<div>
			     <label for="name">Route Table Name</label>
                             <input name="name" type="text" class="formfld" id="name" size="30" value="<?=htmlspecialchars($pconfig['name']);?>">
                             <p class="note">The name of the route table (not parsed)</p>
                </div>
                <div>
                   <label for "tag">ID</label>
                   <input name="rtableid" type="text" class="formfld" id="rtableid" size="20" value="<?=htmlspecialchars($pconfig['rtableid']);?>">
                   <p class="note">Route Table ID (between 1 and 254)</p>
                </div>          
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
</div><!-- /wrapper -->
