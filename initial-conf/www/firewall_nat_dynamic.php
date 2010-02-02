#!/bin/php
<?php
$pgtitle = array("Firewall", "NAT", "Dynamic");
require("guiconfig.inc");
include("ns-begin.inc");
if (!is_array($config['nat']['advancedoutbound']['rule']))
    $config['nat']['advancedoutbound']['rule'] = array();
    
$a_dnat = &$config['nat']['advancedoutbound']['rule'];
nat_out_rules_sort();
?>

<style type="text/css">
    #dnatsortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #dnatsortable li { padding: 0.1em; margin-left: 0; padding-left: 1.5em; font-size: 1.4em; height: 18px; border:1px solid #E4E4E4;  font-size:1em; }
    #dnatsortable li span.col1 { position:relative; float:left; width:2.5%; }
    #dnatsortable li span.col2 { position:relative; float:left; width:15%; }
    #dnatsortable li span.col3 { position:relative; float:left; width:2.5%; }
    #dnatsortable li span.col4 { position:relative; float:left; width:2.5%; }
    #dnatsortable li span.col5 { position:relative; float:left; width:60%; }
</style>

<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
        //hidediv("<?=$if . 'saveneworder';?>");
});

// Make the list of rules for this interface sortable
$("#dnatsortable").sortable({
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
    var order = $("#dnatsortable").sortable("serialize");
});

// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$(".col3 a, #newrule a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
$(".col4 a").click(function () {
        if (confirm('Are you sure you want to delete this rule?')){  
             displayProcessingDiv();
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("#dnatsortable").sortable('refresh');
             setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);
        }
        return false;
});

</script>

<div class="demo">
<ul id="dnatsortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Id</span>
<span class="col2">Rule Name</span>
<span class="col3">Action</span>
<span class="col4">&nbsp</span>
<span class="col5">Description</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_dnat[$i]); $i++):
$filterent = $a_dnat[$i]; ?>
<li id="listItem_<?=$i;?>">
<span class="col1"><span class="ui-icon ui-icon-triangle-2-n-s"></span></span>
<span class="col2"><?php if (isset($filterent['source']['network'])) echo strtoupper($filterent['source']['network']); else echo "*"; ?><?=$textse;?></span>
<span class="col3">
<a href="firewall_nat_dynamic_edit.php?id=<?=$i;?>">
<span title="edit this rule" class="ui-icon ui-icon-circle-zoomin"></span>
</a>
</span>
<span class="col4">
<a href="forms/firewall_form_submit.php?id=<?=$i;?>&action=delete&type=dnat">
<span title="delete this rule" class="ui-icon ui-icon-circle-close"></span>
</a>
</span>
<span class="col5"><?php if (isset($filterent['descr'])) echo $filterent['descr'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
<div id="newrule"><center><a href="firewall_nat_dynamic_edit.php"><span title="add a new rule" class="ui-icon ui-icon-circle-plus"></span></a></center></div>
<div id="<?=$if . 'saveneworder';?>"><center>SAVE NEW ORDER LINK</center></div>
</div>
