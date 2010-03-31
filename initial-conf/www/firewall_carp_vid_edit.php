#!/bin/php
<?php 

$pgtitle = array("Firewall", "CARP", "Edit Virtual Host");
require("guiconfig.inc");
include("ns-begin.inc");

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

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_general">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="name">Name</label>
                             <input id="name" type="text" name="name" value="<?=htmlspecialchars($pconfig['name']);?>" />
                             <p class="note">Name of the virtual host, for your reference</p>
                        </div>
			<div>
                             <label for="email">Description</label>
                             <input id="descr" type="text" name="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>" />
			     			 <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
			<div>
                             <label for="ip">Virtual Host IP</label>
            				 <input name="ip" type="text" class="formfld" id="ip" size="16" value="<?=htmlspecialchars($pconfig['ip']);?>">
                   			 <strong>/</strong>
                    		 <select name="subnet" class="formfld" id="subnet">
                      		 <?php for ($i = 30; $i >= 1; $i--): ?>
                      			<option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>>
                      				<?=$i;?>
                      			</option>
                      		 <?php endfor; ?>
                    		 </select>	
			</div>
			<div>
                             <label for="interface">Interface</label>
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
							 <p class="note">Choose on which interface the Virtual Host will exist.</p>
			</div>
                        <div>
                             <label for="password">Password</label>
                             <input id="password" type="password" name="password" value="" />
                             <p class="note">Authentication password to use when talking to other CARP-enabled hosts in this redundancy group. This must be the same on all members of the group.</p>
			</div>
			<div>
                             <label for="carpmode">CARP Mode</label>
                             <input name="carpmode" type="radio" value="activestandby" <?php if ($pconfig['carpmode'] == "activestandby") echo "checked"; ?>>
                             Active/Standby &nbsp;&nbsp;&nbsp; <input type="radio" name="activeactive" value="activeactive" <?php if ($pconfig['carpmode'] == "activeactive") echo "checked"; ?>>Active/Active
            </div>
            <div>
                             <label for="carphostmode">CARP Host Mode</label>
							 <input name="carphostmode" type="radio" value="active" <?php if ($pconfig['carphostmode'] == "active") echo "checked"; ?>>
                  			 Active &nbsp;&nbsp;&nbsp; <input type="radio" name="standby" value="standby" <?php if ($pconfig['carphostmode'] == "standby") echo "checked"; ?>>Standby
			</div> 
			<div>
                             <label for="activemember">Cluster Member</label>
            				 <input name="activemember" type="text" class="formfld" id="activemember" size="4" value="<?=htmlspecialchars($pconfig['activemember']);?>"> of 
                			 <input name="activenodes" type="text" class="formfld" id="activenodes" size="4" value="<?=htmlspecialchars($pconfig['activenodes']);?>"> Nodes
			</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
