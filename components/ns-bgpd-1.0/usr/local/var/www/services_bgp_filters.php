#!/bin/php
<?php

require("guiconfig.inc");

    bgp_filter_rules_sort();

    if (!is_array($config['bgpd']['filter']['rule']))
        $config['bgpd']['filter']['rule'] = array();

    $a_filter = &$config['bgpd']['filter']['rule'];

    $if = $_GET['if'];

    if ($_POST['if'])
        $if = $_POST['if'];

    $iflist = array("wan" => "WAN","lan" => "LAN");

    if ($config['pptpd']['mode'] == "server")
        $iflist['pptp'] = "PPTP VPN";

    for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
        if ($config['interfaces']['opt' . $i]['wireless']['ifmode'] != 'lanbridge' && $config['interfaces']['wireless']['ifmode'] != 'dmzbridge')
            $iflist['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
    }

     for ($i = 0; isset($config['vlans']['vlan'][$i]); $i++) {
         $iflist['vlan' . $config['vlans']['vlan'][$i]['tag']] = "VLAN{$config['vlans']['vlan'][$i]['tag']}";
     }


if (!$if || !isset($iflist[$if]))
    $if = "wan";

$ifsortable = '#' . $if . 'sortable';
$ifsaveneworder = '"#' . $if . 'saveneworder"';
?>

<style type="text/css">
    <?= $ifsortable; ?> { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    <?= $ifsortable; ?> li { padding: 0.1em; margin-left: 0; padding-left: 1; font-size: 1.4em; height: 18px; border:1px solid #E4E4E4;  font-size:1em; }
    <?= $ifsortable; ?> li span.col1 { position:relative; float:left; width:5em; }
    <?= $ifsortable; ?> li span.col2 { position:relative; float:left; width:5em; }
    <?= $ifsortable; ?> li span.col3 { position:relative; float:left; width:7.5em; }
    <?= $ifsortable; ?> li span.col4 { position:relative; float:left; width:12.5em; }
    <?= $ifsortable; ?> li span.col5 { position:relative; float:left; width:30em; }
</style>

<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
        $(<?=$ifsaveneworder;?>).hide();
});

// Make the list of rules for this interface sortable
$("<?= $ifsortable; ?>").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)',
   update: function(event, ui) {
        $(<?=$ifsaveneworder;?>).show();
   }
});

// When a user clicks the save new order button submit the order to the backend processing
$(<?=$ifsaveneworder;?>).click(function () {
    displayProcessingDiv();
    var order = $("<?= $ifsortable; ?>").sortable("serialize");
    $("#currentorder").load("process_rule_sortable.php?"+order+"&sortif=<?=$if?>");
        $("<?= $ifsortable; ?>").sortable('refresh');
        $(<?=$ifsaveneworder;?>).hide();
        setTimeout(function(){ $('#save_config').dialog('close'); }, 2500);
    });

// When a user clicks on the rule edit button, load services_bgp_filters_edittabs.php?id=$id
$(".col2 a, #newrule a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load services_bgp_filters_edittabs.php?id=$id
$(".col3 a").click(function () {
        if (confirm('Are you sure you want to delete this rule?')){  
             displayProcessingDiv();
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("<?= $ifsortable; ?>").sortable('refresh');
             setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        }
        return false;
});

</script>

<div class="demo">
<ul id="<?=$if . 'sortable';?>">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Order</span>
<span class="col2">Edit</span>
<span class="col3">Delete</span>
<span class="col4">Name</span>
<span class="col5">Description</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_filter[$i]); $i++):
$filterent = $a_filter[$i]; ?>
<li id="listItem_<?=$i;?>">
<span class="col1"><span class="ui-icon ui-icon-triangle-2-n-s"></span></span>
<span class="col2">
<a href="services_bgp_filters_edittabs.php?id=<?=$i;?>">
<span title="edit this rule" class="ui-icon ui-icon-circle-zoomin"></span>
</a>
</span>
<span class="col3">
<a href="forms/services_form_submit.php?id=<?=$i;?>&action=delete&type=filter">
<span title="delete this rule" class="ui-icon ui-icon-circle-close"></span>
</a>
</span>
<span class="col4"><?php if (isset($filterent['name'])) echo strtoupper($filterent['name']); else echo "*"; ?><?=$textse;?></span>
<span class="col5"><?php if (isset($filterent['descr'])) echo $filterent['descr'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
<div id="newrule"><center><a href="services_bgp_filters_edittabs.php?if=<?=$if;?>"><span title="add a new rule" class="ui-icon ui-icon-circle-plus"></span></a></center></div>
<div id=<?=$if . 'saveneworder';?>><center><input type="submit" value="Save new order" class="button" /></center></div>
</div>