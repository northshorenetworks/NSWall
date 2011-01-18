#!/bin/php
<?php
$pgtitle = array("Interfaces", "Trunks");
require("guiconfig.inc");
include("ns-begin.inc");
if (!is_array($config['trunks']['trunk'])) {
	$config['trunks']['trunk'] = array();
}
$a_trunk = &$config['trunks']['trunk'];
trunks_sort();
?>

<style type="text/css">
#trunksortable {
	list-style-type: none;
	margin: auto auto 1em;
	padding: 0;
	width: 95%;
}

#trunksortable li {
	padding: 0.1em;
	margin-left: 0;
	padding-left: 1em;
	font-size: 1.4em;
	height: 18px;
	border: 1px solid #E4E4E4;
	font-size: 1em;
}

#trunksortable li span.col1 {
	position: relative;
	float: left;
	width: 5%;
}

#trunksortable li span.col2 {
	position: relative;
	float: left;
	width: 5%;
}

#trunksortable li span.col3 {
	position: relative;
	float: left;
	width: 15%;
}

#trunksortable li span.col4 {
	position: relative;
	float: left;
	width: 50%;
}
</style>

<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
});

// Make the list of rules for this interface sortable
$("#trunksortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)',
   update: function(event, ui) {
        //showdiv("<?=$if . 'saveneworder';?>");
   }
});

// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$(".col1 a, #newtrunk a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
 $(".col2 a").click(function () {
        if (confirm('Are you sure you want to delete this trunk?')){
        displayProcessingDiv();
        var id = $(this).attr('href');
            $.post("forms/interfaces_form_submit.php", id, function(output) {
                $("#save_config").html(output);
                if(output.match(/SUBMITSUCCESS/))
                    setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                    setTimeout(function(){ $('#content').load('interfaces_trunks_tabs.php'); }, 1250);
            });
            return false;
        }
});

</script>

<div class="demo">
<ul id="trunksortable">
	<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
	<span class="col1">Edit</span> <span class="col2">Delete</span> <span
		class="col3">Name</span> <span class="col4">Description</span></li>
		<?php $nrules = 0; for ($i = 0; isset($a_trunk[$i]); $i++):
		$trunkent = $a_trunk[$i]; ?>
	<li id="listItem_<?=$i;?>"><span class="col1"> <a
		href="interfaces_trunk_edit.php?id=<?=$i;?>"> <span
		title="edit this rule" class="ui-icon ui-icon-circle-zoomin"></span> </a>
	</span> <span class="col2"> <a
		href="id=<?=$i;?>&formname=interface_trunk_delete"> <span
		title="delete this rule" class="ui-icon ui-icon-circle-close"></span>
	</a> </span> <span class="col3"><?php if (isset($trunkent['name'])) echo strtoupper($trunkent['name']); else echo "*"; ?><?=$textse;?></span>
	<span class="col4"><?php if (isset($trunkent['descr'])) echo $trunkent['descr'];?></span>
	</li>
	<?php $nrules++; endfor; ?>
</ul>
<div id="newtrunk">
<center><a href="interfaces_trunk_edit.php"><span
	title="add a new trunk" class="ui-icon ui-icon-circle-plus"></span></a></center>
</div>
</div>
