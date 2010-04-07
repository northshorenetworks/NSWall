#!/bin/php
<?php

$pgtitle = array("Firewall", "CARP", "Global Settings");
require("guiconfig.inc");
include("ns-begin.inc"); 

$pconfig['carpenable'] = isset($config['carp']['carpenable']);
$pconfig['preemptenable'] = isset($config['carp']['preemptenable']);
$pconfig['logenable'] = isset($config['carp']['logenable']);
$pconfig['arpbalance'] = isset($config['carp']['arpbalance']);
 
$a_carp = &$config['carp'];

?> 

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/interfaces_form_submit.php", QueryString, function(output) {
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

	<form action="forms/interfaces_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="interface_carp">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                 <label for="carpenable">Enable CARP</label>
                 <input id="carpenable" type="checkbox" name="carpenable" value="Yes" <?php if ($pconfig['carpenable']) echo "checked"; ?> />
			     <p class="note">Accept incoming CARP packets</p>
			</div>
            <div>
                 <label for=="preemptenable">Enable Preemption</label>
                 <input id="preemptenable" type="checkbox" name="preemptenable" value="Yes" <?php if ($pconfig['preemptenable']) echo "checked"; ?> />
			     <p class="note">Allow hosts within a redundancy group that have a better advbase and advskew to preempt the master. In addition, this option also enables failing over all interfaces in the event that one interface goes down. If one physical CARP-enabled interface goes down, CARP will change advskew to 240 on all other CARP-enabled interfaces, in essence, failing itself over.</p>
			</div>
			<div>
                 <label for=="logenable">CARP Logging</label>
                 <input id="logenable" type="checkbox" name="logenable" value="Yes" <?php if ($pconfig['logenable']) echo "checked"; ?> />
                 <p class="note">Log Bad CARP packets</p>
            </div>
			<div>
                 <label for="arpbalance">ARP Balance</label>
                 <input id="arpbalance" type="checkbox" name="arpbalance" value="Yes" <?php if ($pconfig['arpbalance']) echo "checked"; ?> />
			     <p class="note">Load balance traffic across multiple redundancy group hosts.</p>
			</div> 
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
