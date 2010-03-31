#!/bin/php
<?php

$pgtitle = array("Firewall", "Sync", "Settings");
require("guiconfig.inc");
include("ns-begin.inc");
 
$pconfig['pfsyncenable'] = isset($config['pfsync']['pfsyncenable']);
$pconfig['interface'] = $config['pfsync']['interface'];
 
$a_pfsync = &$config['pfsync'];
 
?> 

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/system_form_submit.php", QueryString, function(output) {
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

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_advanced">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			 <div>
                 <label for="pfsyncenable">Enable PFSync</label>
                 <input id="pfsyncenable" type="checkbox" name="pfsyncenable" value="Yes" <?php if ($pconfig['pfsyncenable']) echo "checked"; ?> />
                 <p class="note">Accept incoming PFSync packets</p>
            </div>	
			<div>
                 <label for="pfsyncenable">Interface</label>
				 <select name="interface" class="formfld">
                      <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
                                          for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                                                $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                                          }
                                          foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> 
			</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
