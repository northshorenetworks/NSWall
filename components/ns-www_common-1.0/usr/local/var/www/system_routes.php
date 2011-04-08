#!/bin/php
<?php
$pgtitle = array("System", "Static routes");
require("guiconfig.inc");
include("ns-begin.inc");
if (!is_array($config['staticroutes']['route']))
$config['staticroutes']['route'] = array();
staticroutes_sort();
$a_routes = &$config['staticroutes']['route'];
?>

<style type="text/css">
#aliassortable {
	list-style-type: none;
	margin: auto auto 1em;
	padding: 0;
	width: 95%;
}

#aliassortable li {
	padding: 0.1em;
	margin-left: 0;
	padding-left: 1em;
	font-size: 1.4em;
	height: 18px;
	border: 1px solid #E4E4E4;
	font-size: 1em;
}

#aliassortable li span.col1 {
	position: relative;
	float: left;
	width: 5em;
}

#aliassortable li span.col2 {
	position: relative;
	float: left;
	width: 5em;
}

#aliassortable li span.col3 {
	position: relative;
	float: left;
	width: 10em;
}

#aliassortable li span.col4 {
	position: relative;
	float: left;
	width: 10em;
}

#aliassortable li span.col5 {
	position: relative;
	float: left;
	width: 30em;
}
</style>

<script type="text/javascript">

// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$(".col1 a, #newrule a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
$(".col2 a").click(function () {
        if (confirm('Are you sure you want to delete this route')){  
           $("#save_config").html('<center>Saving Configuration File<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
           $(".ui-dialog-titlebar").css('display','block');
           $('#save_config').dialog('open');
			 var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("#aliassortable").sortable('refresh');
        	 setTimeout(function(){ $('#save_config').dialog('close'); }, 2500);
		}
        return false;
});

</script>

<div class="demo">
<ul id="aliassortable">
	<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
	<span class="col1">Edit</span> <span class="col2">Delete</span> <span
		class="col3">Destination</span> <span class="col4">Gateway</span> <span
		class="col5">Description</span></li>
		<?php $nrules = 0; for ($i = 0; isset($a_routes[$i]); $i++):
		$aliasent = $a_routes[$i]; ?>
	<li id="listItem_<?=$i;?>"><span class="col1"> <a
		href="system_routes_edit.php?id=<?=$i;?>"> <span
		title="edit this route" class="ui-icon ui-icon-circle-zoomin"></span>
	</a> </span> <span class="col2"> <a
		href="forms/system_form_submit.php?id=<?=$i;?>&action=delete&type=routes">
	<span title="delete this route" class="ui-icon ui-icon-circle-close"></span>
	</a> </span> <span class="col3"><?php echo strtoupper($aliasent['network']) ?><?=$textse;?></span>
	<span class="col4"><?php echo strtoupper($aliasent['gateway']) ?><?=$textse;?></span>
	<span class="col5"><?php if (isset($aliasent['descr'])) echo $aliasent['descr'];?></span>
	</li>
	<?php $nrules++; endfor; ?>
</ul>
<div id="newrule">
<center><a href="system_routes_edit.php"><span title="add a new route"
	class="ui-icon ui-icon-circle-plus"></span></a></center>
</div>
</div>
