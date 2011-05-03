#!/bin/php
<?php
$pgtitle = array("Interfaces", "Trunks", "Edit Trunk");

require("guiconfig.inc");
include("ns-begin.inc");

if (!is_array($config['trunks']['trunk']))
$config['trunks']['trunk'] = array();

trunks_sort();
$a_trunks = &$config['trunks']['trunk'];

$id = $_GET['id'];

if (isset($_POST['id']))
$id = $_POST['id'];

if (isset($id) && $a_trunks[$id]) {
	$pconfig['name'] = $a_trunks[$id]['name'];
	$pconfig['descr'] = $a_trunks[$id]['descr'];
	$pconfig['childiflist'] = $a_trunks[$id]['childiflist'];
	$pconfig['type'] = $a_trunks[$id]['type'];
	$pconfig['trunkport'] = $a_trunks[$id]['trunkport'];
} else{
	/* find the next availible trunk interface and use it */
	for($i=0;$i<100; $i++) {
		foreach($a_trunks as $trunk) {
			if($trunk['trunkport'] == 'trunk' . "$i") {
				continue 2;
			}
		}
		$pconfig['trunkport'] = 'trunk' . "$i";
		break;
	}
}

/* get list without VLAN interfaces */
$portlist = get_interface_list();

/* Find an unused port for this interface */
foreach ($portlist as $portname => $portinfo) {
	$portused = false;
	foreach ($config['interfaces'] as $ifname => $ifdata) {
		if ($ifdata['if'] == $portname) {
			$portused = true;
			break;
		}
	}
}

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {

     // When a user clicks on the add button, validate and add the host.
     $("#addbutton").click(function () {
          var ip = $("#childifs");
	  $('#MEMBERS').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          return false;
     });

     // When a user highlights an item and clicks remove, remove it
          $('#removebtn').click(function() {  
          return !$('#MEMBERS option:selected').remove();  
     });

     // When a user clicks on the submit button, post the form.
     $("#submitbutton").click(function () {
	  displayProcessingDiv();
	  var Options = $.map($('#MEMBERS option'), function(e) { return $(e).val(); } );
	  var str = Options.join(' ');
	  var QueryString = $("#iform").serialize()+'&children='+str;
	  $.post("forms/interfaces_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);	  
	  		   setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
			   setTimeout(function(){ $('#content').load('interfaces_trunks_tabs.php'); }, 1250);
	  });
	  return false;
     });
  
});

</script>

<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">

<form action="forms/firewall_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="interface_trunk">
<input name="id" type="hidden" value="<?=$id;?>"> <input
	name="trunkport" type="hidden"
	value="<?=htmlspecialchars($pconfig['trunkport']);?>">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="name">Name</label> <input id="name" type="text"
	name="name" value="<?=htmlspecialchars($pconfig['name']);?>" /></div>
<div><label for="descr">Description</label> <input id="descr"
	type="text" size="50" name="descr"
	value="<?=htmlspecialchars($pconfig['descr']);?>" />
<p class="note">You may enter a description here for your reference (not
parsed).</p>
</div>
<div><label for="type">Trunk Protocol</label> <select name="type"
	class="formfld">
	<?php $types = explode(" ", "roundrobin failover lacp loadbalance broadcast none"); foreach ($types as $type): ?>
	<option value="<?=strtolower($type);?>"
	<?php if (strtolower($type) == strtolower($pconfig['type'])) echo "selected"; ?>>
		<?=htmlspecialchars($type);?></option>
		<?php endforeach; ?>
</select></div>
<div>
<div><label for="MEMBERS">Child Interfaces</label> <select
	style="width: 150px; height: 100px" id="MEMBERS" NAME="MEMBERS"
	MULTIPLE size=6 width=30>
	<?php for ($i = 0; $i<sizeof($pconfig['childiflist']); $i++): ?>
	<option value="<?=$pconfig['childiflist']["childif$i"];?>"><?=$pconfig['childiflist']["childif$i"];?>
	</option>
	<?php endfor; ?>
	<input type=button id='removebtn' value='Remove Selected'>
	<br>
	<br>
	<div><label for="childifs">Interfaces</label> <select name="childifs"
		class="formfld" id="childifs">
		<?php foreach ($portlist as $portname => $portinfo): ?>
		<option value="<?=$portname;?>"
		<?php if ($portname == $iface['if']) echo "selected";?>><?php if ($portinfo['isvlan']) {
			$descr = "VLAN {$portinfo['tag']} on {$portinfo['if']}";
			if ($portinfo['descr'])
			$descr .= " (" . $portinfo['descr'] . ")";
			echo htmlspecialchars($descr);
		} else
		echo htmlspecialchars($portname);
		?></option>

		<?php endforeach; ?>
	</select> <input type=button id='addbutton' value='Add'></div></div>
</div>

</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->
