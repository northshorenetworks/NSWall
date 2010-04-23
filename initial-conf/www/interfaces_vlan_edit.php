#!/bin/php
<?php
$pgtitle = array("Interfaces", "Assign network ports", "Edit VLAN");
require("guiconfig.inc");

if (!is_array($config['vlans']['vlan']))
	$config['vlans']['vlan'] = array();

vlans_sort();
$a_vlans = &$config['vlans']['vlan'];

$portlist = get_interface_list();

/* add Trunk interfaces */
if (is_array($config['trunks']['trunk']) && count($config['trunks']['trunk'])) {
        $i = 0;
        foreach ($config['trunks']['trunk'] as $trunk) {
                $trunkport = $trunk['trunkport'];
                $portlist[$trunkport] = $trunk;
                $portlist[$trunkport]['istrunk'] = true;
                $i++;
        }
}

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_vlans[$id]) {
	$pconfig['if'] = $a_vlans[$id]['if'];
	$pconfig['tag'] = $a_vlans[$id]['tag'];
	$pconfig['descr'] = $a_vlans[$id]['descr'];
	$pconfig['ipaddr'] = $a_vlans[$id]['ipaddr'];
	$pconfig['subnet'] = $a_vlans[$id]['subnet'];
} 
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
        		setTimeout(function(){ $('#content').load('interfaces_vlan_tabs.php'); }, 1250);
		});
    return false;
    });
	 // When a user clicks on the src alias add button, add the selected value.
    $("#aliasaddbutton").click(function () {
         var alias = $("#aliashost");
     $('#ALIASADDR').append("<option value='" + alias.val() + "'>" + alias.val() + '</option>');
         return false;
    });

});
</script>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/interfaces_form_submit.php" method="post" name="iform" id="iform">
              <input name="formname" type="hidden" value="interface_vlan">
			  <input name="id" type="hidden" value="<?=$id;?>">
			  <input name="oldtag" type="hidden" value="<?=$pconfig['tag'];?>">
			  <input name="oldif" type="hidden" value="<?=$pconfig['if'];?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
		<div>
			     <label for="descr">Interface Name</label>
                             <input name="descr" type="text" class="formfld" id="descr" size="30" value="<?=htmlspecialchars($pconfig['descr']);?>">
                             <p class="note">The name of the interface (not parsed)</p>
                </div>
                <div>
                   <label for "tag">Tag</label>
                   <input name="tag" type="text" class="formfld" id="tag" size="20" value="<?=htmlspecialchars($pconfig['tag']);?>">
                   <p class="note">802.1Q VLAN tag (between 1 and 4094)</p>
                </div>         
                <div id='staticdiv'>
            <div>
                             <label for="ipaddr">IP address</label>
                             <input name="ipaddr" type="text" class="formfld" id="ipaddr" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
                    /
                    <select name="subnet" class="formfld" id="subnet">
                      <?php for ($i = 31; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
            </div>
                <div>
                <label for="interface">Parent Interface</label>
			   	<select name="if" class="formfld">
                      <?php
					  foreach ($portlist as $ifn => $ifinfo): ?>
                      <option value="<?=$ifn;?>" <?php if ($ifn == $pconfig['if']) echo "selected"; ?>> 
                      <?=htmlspecialchars($ifn . " (" . $ifinfo['mac'] . ")");?>
                      </option>
                      <?php endforeach; ?>
                    </select>  
				<p class="note">Choose which interface this rule applies to.  Hint: in most cases, you'll want to use WAN here..</p>
			</div>	
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
</div><!-- /wrapper -->
