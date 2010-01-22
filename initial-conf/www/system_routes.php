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
    #routessortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #routessortable li { margin: 0px 3px 3px 3px; padding: 0.1em; margin-left: 0; padding-left: 1.5em; font-size: 1.4em;
height: 18px; border-style:solid;
border-color:#CCCCCC; font-size:0.8em; }
    #routessortable li span.col1 { position:relative; float:left; width:2.5%; }
    #routessortable li span.col2 { position:relative; float:left; width:15%; }
    #routessortable li span.col3 { position:relative; float:left; width:2.5%; }
    #routessortable li span.col4 { position:relative; float:left; width:2.5%; }
    #routessortable li span.col5 { position:relative; float:left; width:60%; }
</style>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
	
	$(function() {
        $("#firewalloptionstabs").tabs();
    });

	// Make the list of rules for this interface sortable
	$("routessortable").sortable({
   		axis: 'y',
   		containment: 'parent',
   		items: 'li:not(.ui-state-disabled)'
	});


	// When a user clicks on the rule edit button, load firewall_rules_edittabs.php?id=$id
	$(".col3 a, #newroute a").click(function () {
    	var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
	});

	// When a user clicks on the rule delete button, load firewall_rules_edittabs.php?id=$id
	$(".col4 a").click(function () {
        if (confirm('Are you sure you want to delete this rule?')){
             displayProcessingDiv();
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("#routessortable").sortable('refresh');
             setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);
        }
        return false;
	});

});
</script>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-widget-content ui-corner-content">
 
<div id="firewalloptionstabs">
    <ul>
        <li><a href="#tabRoutes">Static Routes</a></li>
    </ul>
         <form action="forms/firewall_form_submit.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="firewall_options">
             <div id="tabRoutes">
             <fieldset>
	     <legend><?=join(": ", $pgtitle);?></legend>
             <ul id="routessortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Id</span>
<span class="col2">Rule Name</span>
<span class="col3">Action</span>
<span class="col4">&nbsp</span>
<span class="col5">Description</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_routes[$i]); $i++):
$filterent = $a_routes[$i]; ?>
<li id="listItem_<?=$i;?>" class="ui-corner-all">
<span class="col1"><?=$i;?></span>
<span class="col2"><?php if (isset($filterent['name'])) echo strtoupper($filterent['name']); else echo "*";?><?=$textse;?></span>
<span class="col3">
<a href="system_routes_edit.php?id=<?=$i;?>">
<img src="images/e.gif" title="edit rule" width="17" height="17" border="0">
</a>
</span>
<span class="col4">
<a href="forms/system_form_submit.php?id=<?=$i;?>&action=delete&type=routes">
<img src="images/x.gif" title="delete rule" width="17" height="17" border="0">
</a>
</span>
<span class="col5"><?php if (isset($filterent['descr'])) echo $filterent['descr'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
</div>
<div id="newroute"><center><a href="system_routes_edit.php"><img src="images/plus.gif" title="add new routes" width="17" height="17" border="0"></a></center></div>
	     </fieldset>
</form>
</div>
</div>
</div>
</div>
<div id="currentorder"></div>
