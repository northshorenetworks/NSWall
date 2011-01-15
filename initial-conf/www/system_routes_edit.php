#!/bin/php
<?php
 
$pgtitle = array("System", "Static routes", "Edit");
require("guiconfig.inc");
include("ns-begin.inc");

if (!is_array($config['staticroutes']['route']))
    $config['staticroutes']['route'] = array();

staticroutes_sort();
$a_routes = &$config['staticroutes']['route'];

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

if (isset($id) && $a_routes[$id]) {
    $pconfig['interface'] = $a_routes[$id]['interface'];
    list($pconfig['network'],$pconfig['network_subnet']) =
        explode('/', $a_routes[$id]['network']);
    $pconfig['gateway'] = $a_routes[$id]['gateway'];
    $pconfig['rtable'] = $a_routes[$id]['rtable'];
    $pconfig['descr'] = $a_routes[$id]['descr'];
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
        });
    return false;
    });
});
</script>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
              <input name="formname" type="hidden" value="system_routes">
			  <input name="id" type="hidden" value="<?=$id;?>">
              <input name="oldif" type="hidden" value="<?=$pconfig['interface'];?>">
              <input name="olddst" type="hidden" value="<?=$pconfig['network'];?>">
              <input name="oldgw" type="hidden" value="<?=$pconfig['gateway'];?>">
              <input name="oldrtable" type="hidden" value="<?=$pconfig['rtable'];?>">
    <fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
                        <div>
                             <label for="descr">Description</label>
                             <input id="descr" type="text" size="50" name="descr" value="<?=htmlspecialchars($pconfig['descr']);?>" />
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="network">Destination</label>
                             <input name="network" type="text" class="formfld" id="network" size="20" value="<?=htmlspecialchars($pconfig['network']);?>">
                  /
                    <select name="network_subnet" class="formfld" id="network_subnet">
                      <?php for ($i = 32; $i >= 1; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['network_subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
			     <p class="note">Destination network for this static route.</p>
			</div>
                        <div>
                             <label for="gateway">Gateway</label>
                             <input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>">
                             <p class="note">Gateway to be used to reach the destination network.</p>
                        </div>
                         <div>
                <label for="rtable">Route Table</label>
                <select name="rtable" class="formfld">
                      <option value="DEFAULT" <?php if ($rtable['name'] == "DEFAULT") echo "selected"; ?>>DEFAULT</option>
                      <?php
                      foreach ($config['system']['routetables']['routetable'] as $rtable): ?>
                      <option value="<?=$rtable['name'];?>" <?php if ($rtable['name'] == $pconfig['rtable']) echo "selected"; ?>><?=$rtable['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                <p class="note">Choose which route table this vlan will use</p>
            </div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
