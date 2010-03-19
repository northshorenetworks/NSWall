#!/bin/php
<?php
 
$pgtitle = array("Firewall", "NAT", "Edit Dynamic NAT");
require("guiconfig.inc");
include("ns-begin.inc");
if (!is_array($config['nat']['advancedoutbound']['rule']))
    $config['nat']['advancedoutbound']['rule'] = array();

$a_out = &$config['nat']['advancedoutbound']['rule'];
nat_out_rules_sort();

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

function network_to_pconfig($adr, &$padr, &$pmask, &$pnot) {

    if (isset($adr['any']))
        $padr = "any";
    else if ($adr['network']) {
        list($padr, $pmask) = explode("/", $adr['network']);
        if (!$pmask)
            $pmask = 32;
    }

    if (isset($adr['not']))
        $pnot = 1;
    else
        $pnot = 0;
}

if (isset($id) && $a_out[$id]) {
    list($pconfig['source'],$pconfig['source_subnet']) = explode('/', $a_out[$id]['source']['network']);
    network_to_pconfig($a_out[$id]['destination'], $pconfig['destination'],
       $pconfig['destination_subnet'], $pconfig['destination_not']);
    $pconfig['target'] = $a_out[$id]['target'];
    $pconfig['interface'] = $a_out[$id]['interface'];
    if (!$pconfig['interface'])
        $pconfig['interface'] = "wan";
    $pconfig['descr'] = $a_out[$id]['descr'];
    $pconfig['noportmap'] = isset($a_out[$id]['noportmap']);
} else {
    $pconfig['source_subnet'] = 24;
    $pconfig['destination'] = "any";
    $pconfig['destination_subnet'] = 24;
    $pconfig['interface'] = "wan";
    $pconfig['noportmap'] = false;
}

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
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
              <input name="formname" type="hidden" value="firewall_nat">
			  <input name="id" type="hidden" value="<?=$id;?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="interface">Interface</label>
                             <select name="interface" class="formfld">
                        <?php
                        $interfaces = array('wan' => 'WAN', 'pptp' => 'PPTP');
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
                             <label for="source">Source</label>
                             <input name="source" type="text" class="formfld" id="source" size="20" value="<?=htmlspecialchars($pconfig['source']);?>" /> /
                             <select name="source_subnet" class="formfld" id="source_subnet">
                             <?php for ($i = 32; $i >= 0; $i--): ?>
                             <option value="<?=$i;?>" <?php if ($i == $pconfig['source_subnet']) echo "selected"; ?>>
                             <?=$i;?>
                             </option>
                             <?php endfor; ?>
                             </select>
			     <p class="note">Enter the source network for the outbound NAT mapping.</p>
			</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
</div><!-- /wrapper -->
