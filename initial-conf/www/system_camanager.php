#!/bin/php
<?php

require("guiconfig.inc");

if (!is_array($config['system']['certmgr']['ca']))
    $config['system']['certmgr']['ca'] = array();

$a_ca =& $config['system']['certmgr']['ca'];

if (!is_array($config['system']['certmgr']['cert']))
    $config['system']['certmgr']['cert'] = array();

$a_cert =& $config['system']['certmgr']['cert'];

?>

<style type="text/css">
    #casortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #casortable li { padding: 0.1em; margin-left: 0; padding-left: 1em; font-size: 1.4em; height: 90px; border:1px solid #E4E4E4;  font-size:1em; }
    #casortable li span.col1 { position:relative; float:left; width:10%; }
    #casortable li span.col2 { position:relative; float:left; width:5%; }
    #casortable li span.col3 { position:relative; float:left; width:5%; }
    #casortable li span.col4 { position:relative; float:left; width:5%; }
    #casortable li span.col5 { position:relative; float:left; width:15%; }
    #casortable li span.col6 { position:relative; float:left; width:60%; }

</style>


<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
});

// Make the list of rules for this interface sortable
$("#casortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)',
   update: function(event, ui) {
   }
});

// When a user clicks on the rule edit button, load firewall_rules_edittabs.php?id=$id
$("#newca a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
 $(".col3 a").click(function () {
        if (confirm('Are you sure you want to delete this ca?')){
		displayProcessingDiv();
		var id = $(this).attr('href');
        	$.post("forms/system_form_submit.php", id, function(output) {
            	$("#save_config").html(output);
            	if(output.match(/SUBMITSUCCESS/))
                	setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                	setTimeout(function(){ $('#content').load('interfaces_ca_tabs.php'); }, 1250);
        	});
    		return false;
		}    
});

</script>

<div class="demo">
<ul id="casortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled" style="height: 18px;">
<span class="col1">Name</span> 
<span class="col2">Export</span>
<span class="col3">Delete</span>
<span class="col4">Internal</span>
<span class="col5">Certificates</span>
<span class="col6">Common Name</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_ca[$i]); $i++):
$caent = $a_ca[$i]; 
?>
<li id="listItem_<?=$i;?>">
<span class="col1"><span class="col4"><?php echo $caent['name'];?></span></span>
<span class="col2">
<a href="system_camanager_edit.php?act=exp&id=<?=$i;?>">
<span title="export this CA" class="ui-icon ui-icon-circle-arrow-s"></span>
</a>
</span>
<span class="col3">
 <a href="id=<?=$i;?>&formname=system_ca_delete">
<span title="delete this ca" class="ui-icon ui-icon-circle-close"></span>
</a>
</span>
<?php
   $i = 0;
   foreach($a_ca as $ca):
       $name = htmlspecialchars($ca['name']);
       $subj = cert_get_subject($ca['crt']);
       $subj = htmlspecialchars($subj);
       $certcount = 0;

       if($ca['prv']) {
           $caimg = "cert.png";
           $internal = "YES";

           foreach ($a_cert as $cert)
               if ($cert['caref'] == $ca['refid'])
                   $certcount++;
               } else {
                   $caimg = "cert.png";
                   $internal = "NO";
               }
    $i++;
    endforeach;
?>
<span class="col4">
<?=$internal;?>
</span>
<span class="col5">
<?=$certcount;?>
</span>
<?php
list($CN, $email, $org, $city, $state, $country, $extra) =
     split(",", htmlspecialchars(cert_get_subject($caent['crt'])));
?>
<span class="col6"><?php if (isset($caent['crt'])) echo "$CN,<br> $email,<br> $org,<br> $city,<br> $state,<br> $country<br>";?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
<div id="newca"><center><a href="system_camanager_edit.php"><span title="add a new ca" class="ui-icon ui-icon-circle-plus"></span></a></center></div>
</div>
