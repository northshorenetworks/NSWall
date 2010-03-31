#!/bin/php  
<?php   
$pgtitle = array("Firewall", "Aliases");      
require("guiconfig.inc");      
include("ns-begin.inc");       
if (!is_array($config['aliases']['alias'])) {      
    $config['aliases']['alias'] = array();      
}      
$a_alias = &$config['aliases']['alias'];      
aliases_sort();   
?>

<style type="text/css">
    #aliassortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #aliassortable li { padding: 0.1em; margin-left: 0; padding-left: 1em; font-size: 1.4em; height: 18px; border:1px solid #E4E4E4;  font-size:1em; }
    #aliassortable li span.col1 { position:relative; float:left; width:5%; }
    #aliassortable li span.col2 { position:relative; float:left; width:5%; }
    #aliassortable li span.col3 { position:relative; float:left; width:15%; }
    #aliassortable li span.col4 { position:relative; float:left; width:50%; }
</style>

<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
        //hidediv("<?=$if . 'saveneworder';?>");
});

// Make the list of rules for this interface sortable
$("#aliassortable").sortable({
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
    var order = $("#aliassortable").sortable("serialize");
});

// When a user clicks on the rule edit button, load firewall_nat_dynamic_edit.php?id=$id
$(".col1 a, #newrule a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
$(".col2 a").click(function () {
        if (confirm('Are you sure you want to delete this alias?')){  
             displayProcessingDiv();
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("#aliassortable").sortable('refresh');
             setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);
        }
        return false;
});

</script>

<div class="demo">
<ul id="aliassortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Edit</span>
<span class="col2">Delete</span>
<span class="col3">Name</span>
<span class="col4">Description</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_alias[$i]); $i++):
$aliasent = $a_alias[$i]; ?>
<li id="listItem_<?=$i;?>">
<n class="col1">
<a href="firewall_aliases_edit.php?id=<?=$i;?>">
<span title="edit this rule" class="ui-icon ui-icon-circle-zoomin"></span>
</a>
</span>/span>
<span class="col2">
<a href="forms/firewall_form_submit.php?id=<?=$i;?>&action=delete&type=alias">
<span title="delete this rule" class="ui-icon ui-icon-circle-close"></span>
</a>
</span>
<span class="col3"><?php if (isset($aliasent['name'])) echo strtoupper($aliasent['name']); else echo "*"; ?><?=$textse;?></span>
<span class="col4"><?php if (isset($aliasent['descr'])) echo $aliasent['descr'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
<div id="newrule"><center><a href="firewall_carp_vid_edit.php"><span title="add a new alias" class="ui-icon ui-icon-circle-plus"></span></a></center></div>
<div id="<?=$if . 'saveneworder';?>"><center>SAVE NEW ORDER LINK</center></div>
</div>
