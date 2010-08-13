#!/bin/php
<?php
$pgtitle = array("System", "Edit Route Table");
require("guiconfig.inc");

if (!is_array($config['system']['routetables']['routetable']))
    $config['system']['routetables']['routetable'] = array();

route_tables_sort();
$a_rtables = &$config['system']['routetables']['routetable'];

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

if (isset($id) && $a_rtables[$id]) {
    $pconfig['name'] = $a_rtables[$id]['name'];
    $pconfig['rtableid'] = $a_rtables[$id]['rtableid'];
}
?>

<style type="text/css">
    #vlansortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #vlansortable li { padding: 0.1em; margin-left: 0; padding-left: 1em; font-size: 1.4em; height: 18px; border:1px solid #E4E4E4;  font-size:1em; }
    #vlansortable li span.col1 { position:relative; float:left; width:4.5%; }
    #vlansortable li span.col2 { position:relative; float:left; width:5.5%; }
    #vlansortable li span.col3 { position:relative; float:left; width:5.5%; }
    #vlansortable li span.col4 { position:relative; float:left; width:5.5%; }
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
        if (confirm('Are you sure you want to delete this Route Table?')){
        displayProcessingDiv();
        var id = $(this).attr('href');
            $.post("forms/system_form_submit.php", id, function(output) {
                $("#save_config").html(output);
                if(output.match(/SUBMITSUCCESS/))
                    setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                    setTimeout(function(){ $('#content').load('system_routes_tabs.php'); }, 1250);
            });
            return false;
        }    
});

</script>

<div class="demo">
<ul id="vlansortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Name</span> 
<span class="col2">Edit</span>
<span class="col3">Delete</span>
<span class="col4">ID</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_rtables[$i]); $i++):
$rtable_ent = $a_rtables[$i]; 
?>
<li id="listItem_<?=$i;?>">
<span class="col1"><span class="col4"><?php echo $rtable_ent['name'];?></span></span>
<span class="col2">
<a href="system_route_table_edit.php?id=<?=$i;?>">
<span title="edit this route table" class="ui-icon ui-icon-circle-zoomin"></span>
</a>
</span>
<span class="col3">
 <a href="id=<?=$i;?>&formname=system_route_table_delete">
<span title="delete this route table" class="ui-icon ui-icon-circle-close"></span>
</a>
</span>
<span class="col4"><?php if (isset($rtable_ent['rtableid'])) echo $rtable_ent['rtableid'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
<div id="newvlan"><center><a href="system_route_table_edit.php"><span title="add a new route table" class="ui-icon ui-icon-circle-plus"></span></a></center></div>
</div>
