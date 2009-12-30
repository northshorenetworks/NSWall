#!/bin/php
<?php 
$pgtitle = array("Firewall", "Aliases");    
require("guiconfig.inc");    
     
if (!is_array($config['aliases']['alias'])) {    
    $config['aliases']['alias'] = array();    
}    
$a_alias = &$config['aliases']['alias'];    
aliases_sort(); 
?>

<style type="text/css">
    #aliassortable { list-style-type: none; margin: auto auto 1em; padding: 0; width: 95%; }
    #aliassortable li { margin: 0px 3px 3px 3px; padding: 0.1em; margin-left: 0; padding-left: 1.5em; font-size: 1.4em;
height: 18px; border-style:solid;
border-color:#CCCCCC; font-size:0.8em; }
    #aliassortable li span.col1 { position:relative; float:left; width:2.5%; }
    #aliassortable li span.col2 { position:relative; float:left; width:15%; }
    #aliassortable li span.col3 { position:relative; float:left; width:2.5%; }
    #aliassortable li span.col4 { position:relative; float:left; width:2.5%; }
    #aliassortable li span.col5 { position:relative; float:left; width:60%; }
</style>

<script type="text/javascript">

// Make the list of rules for this interface sortable
$("aliassortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)'
});


// When a user clicks on the rule edit button, load firewall_rules_edittabs.php?id=$id
$(".col3 a, #newalias a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
        return false;
});

// When a user clicks on the rule delete button, load firewall_rules_edittabs.php?id=$id
$(".col4 a").click(function () {
        if (confirm('Are you sure you want to delete this alias?')){  
             displayProcessingDiv();
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("#aliassortable").sortable('refresh');
             setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);
        }
        return false;
});

// pre-submit callback
function showRequest(formData, jqForm, options) {
    displayProcessingDiv();
    return true;
}

// post-submit callback
function showResponse(responseText, statusText)  {
    if(responseText.match(/SUBMITSUCCESS/)) {
        setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);
    }
}

        // wait for the DOM to be loaded
    $(document).ready(function() {
            $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
            var options = {
                        target:        '#save_config',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback
                        success:       showResponse  // post-submit callback
            };

           // bind form using 'ajaxForm'
           $('#iform').ajaxForm(options);
    });
    
	$(function() {
        $("#firewalloptionstabs").tabs();
    });
</script>
<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-widget-content ui-corner-content">
 
<div id="firewalloptionstabs">
    <ul>
        <li><a href="#tabTimeouts">Aliases</a></li>
    </ul>
         <form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="firewall_options">
             <div id="tabTimeouts">
             <fieldset>
	     <legend><?=join(": ", $pgtitle);?></legend>
             <ul id="aliassortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Id</span>
<span class="col2">Rule Name</span>
<span class="col3">Action</span>
<span class="col4">&nbsp</span>
<span class="col5">Description</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_alias[$i]); $i++):
$filterent = $a_alias[$i]; ?>
<li id="listItem_<?=$i;?>" class="ui-corner-all">
<span class="col1"><?=$i;?></span>
<span class="col2"><?php if (isset($filterent['name'])) echo strtoupper($filterent['name']); else echo "*";?><?=$textse;?></span>
<span class="col3">
<a href="firewall_aliases_edit.php?id=<?=$i;?>">
<img src="images/e.gif" title="edit rule" width="17" height="17" border="0">
</a>
</span>
<span class="col4">
<a href="forms/firewall_form_submit.php?id=<?=$i;?>&action=delete&type=alias">
<img src="images/x.gif" title="delete alias" width="17" height="17" border="0">
</a>
</span>
<span class="col5"><?php if (isset($filterent['descr'])) echo $filterent['descr'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
</div>
<div id="newalias"><center><a href="firewall_aliases_edit.php"><img src="images/plus.gif" title="add new alias" width="17" height="17" border="0"></a></center></div>
	     </fieldset>
	
</form>
</div>
</div>
</div>
</div>
<div id="currentorder"></div>
