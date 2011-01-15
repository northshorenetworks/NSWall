#!/bin/php
<?php

require("guiconfig.inc");

if (!is_array($config['system']['certmgr']['cert']))
    $config['system']['certmgr']['cert'] = array();

$a_cert =& $config['system']['certmgr']['cert'];

?>

<style type="text/css">
    #certsortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #certsortable li { padding: 0.1em; margin-left: 0; padding-left: 1em; font-size: 1.4em; height: 90px; border:1px solid #E4E4E4;  font-size:1em; }
    #certsortable li span.col1 { position:relative; float:left; width:10%; }
    #certsortable li span.col2 { position:relative; float:left; width:5%; }
    #certsortable li span.col3 { position:relative; float:left; width:5%; }
    #certsortable li span.col4 { position:relative; float:left; width:10%; }
    #certsortable li span.col5 { position:relative; float:left; width:10%; }
    #certsortable li span.col6 { position:relative; float:left; width:60%; }

</style>


<script type="text/javascript">

// Hide the Save Changes Button
$(document).ready(function() {
});

// Make the list of rules for this interface sortable
$("#certsortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)',
   update: function(event, ui) {
   }
});

// When a user clicks on the rule edit button, load firewall_rules_edittabs.php?id=$id
$(".col4 a, #newcert a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_dynamic_nat_edit.php?id=$id
 $(".col3 a").click(function () {
        if (confirm('Are you sure you want to delete this cert?')){
		displayProcessingDiv();
		var id = $(this).attr('href');
        	$.post("forms/system_form_submit.php", id, function(output) {
            	$("#save_config").html(output);
            	if(output.match(/SUBMITSUCCESS/))
                	setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                	setTimeout(function(){ $('#content').load('interfaces_cert_tabs.php'); }, 1250);
        	});
    		return false;
		}    
});

</script>

<div class="demo">
<ul id="certsortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled" style="height: 18px;">
<span class="col1">Name</span> 
<span class="col2">Export</span>
<span class="col3">Delete</span>
<span class="col4">Update CSR</span>
<span class="col5">CA</span>
<span class="col6">Common Name</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_cert[$i]); $i++):
$certent = $a_cert[$i]; 
?>
<li id="listItem_<?=$i;?>">
<span class="col1"><span class="col4"><?php echo $certent['name'];?></span></span>

<span class="col2">
<?php    if ($certent['crt']): ?>
<a href="system_certmanager_edit.php?act=exp&id=<?=$i;?>">
<span title="export this cert" class="ui-icon ui-icon-circle-arrow-s"></span>
</a>
<?php else: ?>
*
<?php endif; ?>
</span>

<span class="col3">
 <a href="id=<?=$i;?>&formname=system_cert_delete">
<span title="delete this cert" class="ui-icon ui-icon-circle-close"></span>
</a>
</span>
<span class="col4">
<?php    if ($certent['csr']): ?>
 <a href="system_certmanager_edit.php?act=csr&id=<?=$i;?>">
<span title="update this csr" class="ui-icon ui-icon-circle-zoomin"></span>
</a>
<?php else: ?>
*
<?php endif; ?>
</span>
<?php
$ca = lookup_ca($certent['caref']);
if ($ca)
    $caname = $ca['name'];
?>
<span class="col5">
<?=$caname;?>
</span>

<?php
list($CN, $email, $org, $city, $state, $country, $extra) =
     split(",", htmlspecialchars(cert_get_subject($certent['crt'])));
?>
<span class="col6"><?php if (isset($certent['crt'])) echo "$CN,<br> $email,<br> $org,<br> $city,<br> $state,<br> $country<br>";?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
<div id="newcert"><center><a href="system_certmanager_edit.php?act=new"><span title="add a new cert" class="ui-icon ui-icon-circle-plus"></span></a></center></div>
</div>
