#!/bin/php
<?php
 
$pgtitle = array("Firewall", "NAT", "Edit Dynamic NAT");
require("guiconfig.inc");
include("ns-begin.inc");

if (!is_array($config['nat']['onetoone'])) {
    $config['nat']['onetoone'] = array();
}
nat_1to1_rules_sort();
$a_1to1 = &$config['nat']['onetoone'];

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

if (isset($id) && $a_1to1[$id]) {
    $pconfig['external'] = $a_1to1[$id]['external'];
    $pconfig['internal'] = $a_1to1[$id]['internal'];
    $pconfig['interface'] = $a_1to1[$id]['interface'];
    if (!$pconfig['interface'])
        $pconfig['interface'] = "wan";
    $pconfig['descr'] = $a_1to1[$id]['descr'];
} else {
    $pconfig['interface'] = "wan";
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
              <input name="formname" type="hidden" value="firewall_nat_1to1">
			  <input name="id" type="hidden" value="<?=$id;?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="interface">Interface</label>
                             <select name="interface" class="formfld">
                        <?php
                        $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP');
                        for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                            $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                        }
                        foreach ($interfaces as $iface => $ifacename): ?>
                        <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
                        <?=htmlspecialchars($ifacename);?>
                        </option>
                        <?php endforeach; ?>
                    </select>
			     <p class="note">Choose which interface this rule applies to.  Hint: in most cases, you'll want to use WAN here..</p>
			</div>
                        <div>
                             <label for="descr">Description</label>
                             <input id="descr" type="text" size="50" name="descr" value="<?=htmlspecialchars($pconfig['descr']);?>" />
			     			 <p class="note">You may enter a description here for your reference (not parsed).</p>
			 			</div>
                        <div>
                             <label for="internal">Internal IP</label>
                             <input name="internal" type="text" class="formfld" id="internal" size="20" value="<?=htmlspecialchars($pconfig['internal']);?>" />
			     			 <p class="note">Enter the internal ip address for the 1 to 1 mapping.</p>
						</div>
						<div>
                             <label for="external">External IP</label>
                             <input name="external" type="text" class="formfld" id="external" size="20" value="<?=htmlspecialchars($pconfig['external']);?>" />
                 			 <p class="note">Enter the external ip address for the 1 to 1 mapping.</p>
            			</div>			
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
</div><!-- /wrapper -->

