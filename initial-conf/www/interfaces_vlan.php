#!/bin/php
<?php

$pgtitle = array("Interfaces", "VLANs");
require("guiconfig.inc");

if (!is_array($config['vlans']['vlan']))
$config['vlans']['vlan'] = array();

vlans_sort();
$a_vlans = &$config['vlans']['vlan'] ;

function vlan_inuse($num) {
	global $config, $g;

	if ($config['interfaces']['lan']['if'] == "vlan{$num}")
	return true;
	if ($config['interfaces']['wan']['if'] == "vlan{$num}")
	return true;

	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		if ($config['interfaces']['opt' . $i]['if'] == "vlan{$num}")
		return true;
	}

	return false;
}

function renumber_vlan($if, $delvlan) {
	if (!preg_match("/^vlan/", $if))
	return $if;

	$vlan = substr($if, 4);
	if ($vlan > $delvlan)
	return "vlan" . ($vlan - 1);
	else
	return $if;
}

?>

<style type="text/css">
#vlansortable {
	list-style-type: none;
	margin: auto auto 1em;
	padding: 0;
	width: 95%;
}

#vlansortable li {
	padding: 0.1em;
	margin-left: 0;
	padding-left: 1em;
	font-size: 1.4em;
	height: 18px;
	border: 1px solid #E4E4E4;
	font-size: 1em;
}

#vlansortable li span.col1 {
	position: relative;
	float: left;
	width: 4.5%;
}

#vlansortable li span.col2 {
	position: relative;
	float: left;
	width: 5.5%;
}

#vlansortable li span.col3 {
	position: relative;
	float: left;
	width: 5.5%;
}

#vlansortable li span.col4 {
	position: relative;
	float: left;
	width: 7.5%;
}

#vlansortable li span.col5 {
	position: relative;
	float: left;
	width: 60%;
}
</style>


<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
        //hidediv("<?=$if . 'saveneworder';?>");
});

// Make the list of rules for this interface sortable
$("#vlansortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)',
   update: function(event, ui) {
        //showdiv("<?=$if . 'saveneworder';?>");
   }
});

// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$(".col2 a, #newvlan a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
 $(".col3 a").click(function () {
        if (confirm('Are you sure you want to delete this vlan?')){
		displayProcessingDiv();
		var id = $(this).attr('href');
        	$.post("forms/interfaces_form_submit.php", id, function(output) {
            	$("#save_config").html(output);
            	if(output.match(/SUBMITSUCCESS/))
                	setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                	setTimeout(function(){ $('#content').load('interfaces_vlan_tabs.php'); }, 1250);
        	});
    		return false;
		}    
});

</script>

<div class="demo">
<ul id="vlansortable">
	<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
	<span class="col1">Tag</span> <span class="col2">Edit</span> <span
		class="col3">Delete</span> <span class="col4">Interface</span> <span
		class="col5">Description</span></li>
		<?php $nrules = 0; for ($i = 0; isset($a_vlans[$i]); $i++):
		$vlanent = $a_vlans[$i];
		?>
	<li id="listItem_<?=$i;?>"><span class="col1"><span class="col4"><?php echo $vlanent['tag'];?></span></span>
	<span class="col2"> <a href="interfaces_vlan_edit.php?id=<?=$i;?>"> <span
		title="edit this nat rule" class="ui-icon ui-icon-circle-zoomin"></span>
	</a> </span> <span class="col3"> <a
		href="id=<?=$i;?>&formname=interface_vlan_delete"> <span
		title="delete this vlan" class="ui-icon ui-icon-circle-close"></span>
	</a> </span> <span class="col4"><?php if (isset($vlanent['if'])) echo $vlanent['if'];?></span>
	<span class="col5"><?php if (isset($vlanent['descr'])) echo $vlanent['descr'];?></span>
	</li>
	<?php $nrules++; endfor; ?>
</ul>
<div id="newvlan">
<center><a href="interfaces_vlan_edit.php"><span title="add a new vlan"
	class="ui-icon ui-icon-circle-plus"></span></a></center>
</div>
</div>
