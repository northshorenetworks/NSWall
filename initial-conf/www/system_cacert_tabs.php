#!/bin/php
<?php 
$pgtitle = array("System", "CA", "Management");
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
    #casortable li { margin: 0px 3px 3px 3px; padding: 0.1em; margin-left: 0; padding-left: 1.5em; font-size: 1.4em;
height: 18px; border-style:solid;
border-color:#CCCCCC; font-size:0.8em; }
    #casortable li span.col1 { position:relative; float:left; width:2.5%; }
    #casortable li span.col2 { position:relative; float:left; width:15%; }
    #casortable li span.col3 { position:relative; float:left; width:2.5%; }
    #casortable li span.col4 { position:relative; float:left; width:2.5%; }
    #casortable li span.col5 { position:relative; float:left; width:60%; }
</style>

<style type="text/css">

/* Form styles */
div.form-container { padding: 5px; background-color: #FFF; border: #EEE 1px solid; }

p.legend { margin-bottom: 1em; }
p.legend em { color: #C00; font-style: normal; }

div.errors { margin: 0 0 10px 0; padding: 5px 10px; border: #FC6 1px solid; background-color: #FFC; }
div.errors p { margin: 0; }
div.errors p em { color: #C00; font-style: normal; font-weight: bold; }

div.form-container form p { margin: 0; }
div.form-container form p.note { margin-left: 170px; font-size: 70%; font-style: italic; color: #333; }
div.form-container form fieldset { margin: 10px 0; padding: 10px; border: #DDD 1px solid; }
div.form-container form legend { font-weight: bold; color: #666; }
div.form-container form fieldset div { padding: 0.5em 0; margin: 0.5em; }
div.form-container label, 
div.form-container span.label { margin-right: 10px; padding-right: 10px; width: 150px; display: block; float: left; text-align: right; position: relative; font-size: .9em; font-style: bold;}
div.form-container label.error, 
div.form-container span.error { color: #C00; }
div.form-container label em, 
div.form-container span.label em { position: absolute; right: 0; font-size: 120%; font-style: normal; color: #C00; }
div.form-container input.error { border-color: #C00; background-color: #FEF; }
div.form-container input:focus,
div.form-container input.error:focus, 
div.form-container textarea:focus {	background-color: #FFC; border-color: #FC6; }
div.form-container div.controlset label, 
div.form-container div.controlset input { display: inline; float: none; }
div.form-container div.controlset div { margin-left: 170px; }
div.form-container div.buttonrow { margin-left: 180px; }

</style>


<script type="text/javascript">

// Make the list of rules for this interface sortable
$("casortable").sortable({
   axis: 'y',
   containment: 'parent',
   items: 'li:not(.ui-state-disabled)'
});

// When a user clicks on the rule edit button, load firewall_rules_edittabs.php?id=$id
$(".col3 a").click(function () {
    var toLoad = $(this).attr('href');
        clearInterval(refreshId);
        $('#content').load(toLoad);
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
</script>
<script type="text/javascript">
    $(function() {
        $("#firewalloptionstabs").tabs();
    });
</script>
<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-widget-content ui-corner-content">
 
<div id="firewalloptionstabs">
    <ul>
        <li><a href="#tabCas">CA Managment</a></li>
        <li><a href="#tabCerts">Certificate Managment</a></li>
    </ul>
         <form action="forms/firewall_form_submit.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="firewall_options">
             <div id="tabCas">
             <fieldset>
	     <legend><?=join(": ", $pgtitle);?></legend>
             <ul id="casortable">
<li id="element_<?=$i;?>" class="connectedSortable ui-state-disabled">
<span class="col1">Id</span>
<span class="col2">Rule Name</span>
<span class="col3">Action</span>
<span class="col4">&nbsp</span>
<span class="col5">Description</span>
</li>
<?php $nrules = 0; for ($i = 0; isset($a_ca[$i]); $i++):
$filterent = $a_ca[$i]; ?>
<li id="listItem_<?=$i;?>" class="ui-corner-all">
<span class="col1"><?=$i;?></span>
<span class="col2"><?php if (isset($filterent['name'])) echo strtoupper($filterent['name']); else echo "*";?><?=$textse;?></span>
<span class="col3">
<a href="firewall_nat_edit.php?id=<?=$i;?>">
<img src="images/e.gif" title="edit rule" width="17" height="17" border="0">
</a>
</span>
<span class="col4">
<img src="images/x.gif" title="delete rule" width="17" height="17" border="0">
</a>
</span>
<span class="col5"><?php if (isset($filterent['descr'])) echo $filterent['descr'];?></span>
</li>
<?php $nrules++; endfor; ?>
</ul>
	     </fieldset>
	</div>
        <div id="tabCerts">
        </div>
</form>
</div>
</div>
</div>
</div>
