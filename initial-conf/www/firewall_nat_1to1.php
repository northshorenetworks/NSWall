#!/bin/php
<?php
$pgtitle = array("Firewall", "NAT", "1:1");
require("guiconfig.inc");

if (!is_array($config['nat']['onetoone'])) {
	$config['nat']['onetoone'] = array();
}
$a_1to1 = &$config['nat']['onetoone'];
nat_1to1_rules_sort();
?>

<style type="text/css">
#binatsortable {
	list-style-type: none;
	margin: auto auto 1em;
	padding: 0;
	width: 95%;
}

#binatsortable li {
	padding: 0.1em;
	margin-left: 0;
	padding-left: 1em;
	font-size: 1.4em;
	height: 18px;
	border: 1px solid #E4E4E4;
	font-size: 1em;
}

#binatsortable li span.col1 {
	position: relative;
	float: left;
	width: 4.5%;
}

#binatsortable li span.col2 {
	position: relative;
	float: left;
	width: 5.5%;
}

#binatsortable li span.col3 {
	position: relative;
	float: left;
	width: 5.5%;
}

#binatsortable li span.col4 {
	position: relative;
	float: left;
	width: 5.5%;
}

#binatsortable li span.col5 {
	position: relative;
	float: left;
	width: 10.5%;
}

#binatsortable li span.col6 {
	position: relative;
	float: left;
	width: 10.5%;
}

#binatsortable li span.col7 {
	position: relative;
	float: left;
	width: 55%;
}
</style>

<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
        //hidediv("<?=$if . 'saveneworder';?>");
});

// Make the list of rules for this interface sortable
$("#binatsortable").sortable({
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
    var order = $("#binatsortable").sortable("serialize");
});

// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$(".col2 a, #newrule a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
$(".col3 a").click(function () {
        if (confirm('Are you sure you want to delete this rule?')){  
             displayProcessingDiv();
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("#binatsortable").sortable('refresh');
             setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);
        }
        return false;
});

</script>

<div class="demo">
<ul id="binatsortable">
	<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
	<span class="col1">Order</span> <span class="col2">Edit</span> <span
		class="col3">Delete</span> <span class="col4">Interface</span> <span
		class="col5">Internal</span> <span class="col6">External</span> <span
		class="col7">Description</span></li>
		<?php $nrules = 0; for ($i = 0; isset($a_1to1[$i]); $i++):
		$filterent = $a_1to1[$i]; ?>
	<li id="listItem_<?=$i;?>"><span class="col1"><span
		class="ui-icon ui-icon-triangle-2-n-s"></span></span> <span
		class="col2"> <a href="firewall_nat_1to1_edit.php?id=<?=$i;?>"> <span
		title="edit this nat rule" class="ui-icon ui-icon-circle-zoomin"></span>
	</a> </span> <span class="col3"> <a
		href="forms/firewall_form_submit.php?id=<?=$i;?>&action=delete&type=1to1nat">
	<span title="delete this nat rule" class="ui-icon ui-icon-circle-close"></span>
	</a> </span> <span class="col4"><?php if (isset($filterent['interface'])) echo $filterent['interface'];?></span>
	<span class="col5"><?php if (isset($filterent['internal'])) echo strtoupper($filterent['internal']); else echo "*"; ?><?=$textse;?></span>
	<span class="col6"><?php if (isset($filterent['external'])) echo strtoupper($filterent['external']); else echo "*"; ?><?=$textse;?></span>
	<span class="col7"><?php if (isset($filterent['descr'])) echo $filterent['descr'];?></span>
	</li>
	<?php $nrules++; endfor; ?>
</ul>
<div id="newrule">
<center><a href="firewall_nat_1to1_edit.php"><span
	title="add a new nat rule" class="ui-icon ui-icon-circle-plus"></span></a></center>
</div>
<div id="<?=$if . 'saveneworder';?>">
<center><input type="submit" value="Save new order" class="button" /></center>
</div>
</div>
