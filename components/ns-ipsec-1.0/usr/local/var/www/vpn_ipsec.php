#!/bin/php
<?php
$pgtitle = array("IPSec", "Gateways");
require("guiconfig.inc");
include("ns-begin.inc");
if (!is_array($config['ipsec']['gw'])) {
	$config['ipsec']['gw'] = array();
}

vpn_ipsec_gateway_sort();
$a_gw = &$config['ipsec']['gw'];

?>

<style type="text/css">
#ipsecsortable {
	list-style-type: none;
	margin: auto auto 1em;
	padding: 0;
	width: 95%;
}

#ipsecsortable li {
	padding: 0.1em;
	margin-left: 0;
	padding-left: 1em;
	font-size: 1.4em;
	height: 18px;
	border: 1px solid #E4E4E4;
	font-size: 1em;
}

#ipsecsortable li span.col1 {
	position: relative;
	float: left;
	width: 5%;
}

#ipsecsortable li span.col2 {
	position: relative;
	float: left;
	width: 5%;
}

#ipsecsortable li span.col3 {
	position: relative;
	float: left;
	width: 15%;
}

#ipsecsortable li span.col4 {
	position: relative;
	float: left;
	width: 50%;
}
</style>

<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
        //hidediv("<?=$if . 'saveneworder';?>");
});

// Make the list of rules for this interface sortable
$("#ipsecsortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)',
   update: function(event, ui) {
        //showdiv("<?=$if . 'saveneworder';?>");
   }
});

// When a user clicks the save new order button submit the order to the backend processing
$("#saveneworder").click(function () {
    displayProcessingDiv();
    var order = $("#ipsecsortable").sortable("serialize");
});

// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$("#newrule a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});


// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$(".col1 a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
       	$('#content').load(toLoad);
       	return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
$(".col2 a").click(function () {
        if (confirm('Are you sure you want to delete this ipsec gw?')){  
             displayProcessingDiv();
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
             setTimeout(function(){ $('#content').load('vpn_ipsec_tabs.php'); }, 1250);
		}
        return false;
});

</script>

<div class="demo">
<ul id="ipsecsortable">
	<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
	<span class="col1">Edit</span> <span class="col2">Delete</span> <span
		class="col3">Name</span> <span class="col4">Description</span></li>
		<?php $nrules = 0; for ($i = 0; isset($a_gw[$i]); $i++):
		$ipsecent = $a_gw[$i]; ?>
	<li id="listItem_<?=$i;?>"><span class="col1"> <a
		href="vpn_ipsec_edit.php?id=<?=$i;?>"> <span
		title="edit this ipsec gateway" class="ui-icon ui-icon-circle-zoomin"></span>
	</a> </span> <span class="col2"> <a
		href="forms/vpn_form_submit.php?id=<?=$i;?>&action=delete&type=ipsec_gw">
	<span title="delete this gateway" class="ui-icon ui-icon-circle-close"></span>
	</a> </span> <span class="col3"><?php if (isset($ipsecent['name'])) echo strtoupper($ipsecent['name']); else echo "*"; ?><?=$textse;?></span>
	<span class="col4"><?php if (isset($ipsecent['descr'])) echo $ipsecent['descr'];?></span>
	</li>
	<?php $nrules++; endfor; ?>
</ul>
<div id="newrule">
<center><a href="vpn_ipsec_edit.php"><span
	title="add a new ipsec gateway" class="ui-icon ui-icon-circle-plus"></span></a></center>
</div>
<div id="<?=$if . 'saveneworder';?>">
<center>SAVE NEW ORDER LINK</center>
</div>
</div>
