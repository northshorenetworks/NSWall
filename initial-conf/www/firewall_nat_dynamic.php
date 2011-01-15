#!/bin/php
<?php
$pgtitle = array("Firewall", "NAT", "Dynamic");
require("guiconfig.inc");
include("ns-begin.inc");
if (!is_array($config['nat']['advancedoutbound']['rule']))
    $config['nat']['advancedoutbound']['rule'] = array();
    
$a_dnat = &$config['nat']['advancedoutbound']['rule'];

//nat_out_rules_sort();

?>

<style type="text/css">
    #dnatsortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #dnatsortable li { padding: 0.1em; margin-left: 0; padding-left: 1em; font-size: 1.4em; height: 18px; border:1px solid #E4E4E4;  font-size:1em; }
    #dnatsortable li span.col1 { position:relative; float:left; width:4.5%; }
    #dnatsortable li span.col2 { position:relative; float:left; width:5.5%; }
    #dnatsortable li span.col3 { position:relative; float:left; width:5.5%; }
    #dnatsortable li span.col4 { position:relative; float:left; width:7.5%; }
    #dnatsortable li span.col5 { position:relative; float:left; width:10.5%; }
    #dnatsortable li span.col6 { position:relative; float:left; width:58%; }
</style>


<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
        $("#dnatsaveneworder").hide();
});

// Make the list of rules for this interface sortable
$("#dnatsortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)',
   update: function(event, ui) {
        $("#dnatsaveneworder").show();
   }
});

// When a user clicks the save new order button submit the order to the backend processing                                                                      
$("#dnatsaveneworder").click(function () {                                    
    displayProcessingDiv();                                                     
    var order = $("#dnatsortable").sortable("serialize");                 
    $("#currentorder").load("process_nat_sortable.php?"+order+"&sort=dnat");
        $("#dnatsortable").sortable('refresh');                           
        $("#dnatsaveneworder").hide();                                        
        setTimeout(function(){ $('#save_config').dialog('close'); }, 2500);     
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
             $("#dnatsortable").sortable('refresh');
             setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);
        }
        return false;
});

</script>

<div class="demo">
<ul id="dnatsortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Order</span> 
<span class="col2">Edit</span>
<span class="col3">Delete</span>
<span class="col4">Interface</span>
<span class="col5">Source</span>
<span class="col6">Description</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_dnat[$i]); $i++):
$filterent = $a_dnat[$i]; 
?>
<li id="listItem_<?=$i;?>">
<span class="col1"><span class="ui-icon ui-icon-triangle-2-n-s"></span></span>
<span class="col2">
<a href="firewall_nat_dynamic_edit.php?id=<?=$i;?>">
<span title="edit this nat rule" class="ui-icon ui-icon-circle-zoomin"></span>
</a>
</span>
<span class="col3">
<a href="forms/firewall_form_submit.php?id=<?=$i;?>&action=delete&type=dnat">
<span title="delete this nat rule" class="ui-icon ui-icon-circle-close"></span>
</a>
</span>
<span class="col4"><?php if (isset($filterent['interface'])) echo $filterent['interface'];?></span>
<span class="col5"><?php if (isset($filterent['source']['network'])) echo strtoupper($filterent['source']['network']); else echo "*"; ?><?=$textse;?></span>
<span class="col6"><?php if (isset($filterent['descr'])) echo $filterent['descr'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
<div id="newrule"><center><a href="firewall_nat_dynamic_edit.php"><span title="add a new rule" class="ui-icon ui-icon-circle-plus"></span></a></center></div>
<div id="dnatsaveneworder"><center><input type="submit" value="Save new order" class="button" /></center></div>
</div>
