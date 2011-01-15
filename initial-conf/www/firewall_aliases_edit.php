#!/bin/php
<?php
$pgtitle = array("Firewall", "Aliases", "Edit Alias");
 
require("guiconfig.inc");
include("ns-begin.inc");

if (!is_array($config['aliases']['alias']))
    $config['aliases']['alias'] = array();

aliases_sort();
$a_aliases = &$config['aliases']['alias'];

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

if (isset($id) && $a_aliases[$id]) {
    $pconfig['name'] = $a_aliases[$id]['name'];
    $pconfig['memberlist'] = $a_aliases[$id]['memberlist'];
    $pconfig['descr'] = $a_aliases[$id]['descr'];
}

?> 

<script type="text/javascript">
// when a user changes the type of memeber, change the related div to sytle = display: block and hide all others
$(function(){
     $("#srctype").change(function() {
          var val = $(this).val();
          switch(val){
		case 'srchostdiv':
			$("#srchostdiv").show();
			$("#srcnetdiv").hide();
            $("#srctablediv").hide();
            $("#srcuserdiv").hide();
			break;
		case 'srcnetdiv':
			$("#srcnetdiv").show();
			$("#srchostdiv").hide();
            $("#srctablediv").hide();
            $("#srcuserdiv").hide();
			break;
        case 'srctablediv':
			$("#srctablediv").show();
			$("#srchostdiv").hide();
            $("#srcnetdiv").hide();
            $("#srcuserdiv").hide();
			break;
        case 'srcuserdiv':
            $("#srcuserdiv").show();
			$("#srctablediv").hide();
			$("#srchostdiv").hide();
            $("#srcnetdiv").hide();
			break;
		}  
     });
}); 

// wait for the DOM to be loaded
$(document).ready(function() {
     // When a user clicks on the host add button, validate and add the host.
     $("#hostaddbutton").click(function () {
          var ip = $("#srchost");
	  $('#MEMBERS').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          ip.val("");
          return false;
     });

     // When a user clicks on the net add button, validate and add the host.
     $("#netaddbutton").click(function () {
          var ip = $("#srcnet");
          var netmask = $("#srcmask");
	  $('#MEMBERS').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
          ip.val("");
          return false;
     });

     // When a user highlights an item and clicks remove, remove it
          $('#remove').click(function() {  
          return !$('#MEMBERS option:selected').remove();  
     });

      $("#srchost, #srcnet").focus(function() {
         $(this).css({"background-color": "#FFFFCC"});
     });

     $("#srchost, #srcnet").blur(function() {
         value = $(this).val();
         if (verifyIP(value) == 0)
             $(this).css({"background-color": "#FFFFFF"});
         else 
             $(this).css({"background-color": "#FFAEAE"});
     });

     $("#srchost, #srcnet").keyup(function() {
        value = $(this).val();
          $(this).css({"background-color": "#FFAEAE"});
        if (verifyIP(value) == 0)
          $(this).css({"background-color": "#CDFECD"});
     }); 

     // When a user clicks on the submit button, post the form.
     $("#submitbutton").click(function () {
	  displayProcessingDiv();
	  var Options = $.map($('#MEMBERS option'), function(e) { return $(e).val(); } );
	  var str = Options.join(' ');
	  var QueryString = $("#iform").serialize()+'&memberslist='+str;
	  $.post("forms/firewall_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);	  
	  		   setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
			   setTimeout(function(){ $('#content').load('firewall_aliases_tabs.php'); }, 1250);
	  });
	  return false;
     });
  
});
</script> 

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="firewall_alias">
	<input name="id" type="hidden" value="<?=$id;?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="name">Name</label>
                             <input id="name" type="text" name="name" value="<?=htmlspecialchars($pconfig['name']);?>" />
			</div>
                        <div>
                             <label for="descr">Description</label>
                             <input id="descr" type="text" size="50" name="descr" value="<?=htmlspecialchars($pconfig['descr']);?>" />
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="members">Members</label>
                             <select name="MEMBERS" style="width: 160px; height: 100px" id="MEMBERS" multiple>
        <?php for ($i = 0; $i<sizeof($pconfig['memberlist']); $i++): ?>
                <option value="<?=$pconfig['memberlist']["member$i"];?>">
                <?=$pconfig['memberlist']["member$i"];?>
                </option>
                <?php endfor; ?>
        </select>
                <input type=button id='remove' value='Remove Selected'><br><br>
                  <label for="members">Type</label>
                    <select name="srctype" class="formfld" id="srctype">
                      <option value="srchostdiv" selected>Host</option>
                      <option value="srcnetdiv" >Network</option>
                      <option value="srctablediv" >Alias</option>
        			  <option value="srcuserdiv" >User</option>
		            </select>
                </div>
                <div id='srchostdiv' style="display:block;">
                 <label for="srchost">Address</label>
                  <input name="srchost" type="text" class="formfld" id="srchost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                <input type=button id='hostaddbutton' value='Add'>
                </div>
                <div id='srcnetdiv' style="display:none;">
                 <label for="srcnet">Address</label>
                  <input name="srcnet" type="text" class="formfld" id="srcnet" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                   <strong>/</strong>
                    <select name="srcmask" class="formfld" id="srcmask">
                      <?php for ($i = 30; $i >= 1; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
                <input type=button id='netaddbutton' value='Add'>
                </div>
                <div id='srctablediv' style="display:none;">
                 <label for="srctable">Alias</label>
                    <select name="srctable" class="formfld" id="srctable">
                      <?php foreach($config['tablees']['table'] as $i): ?>
                      <option value="<?='$' . $i['name'];?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                <input type=button value='Add'>
                </div>
               	<div id='srcuser' style="display:none;">
                <strong>User</strong>
                    <select name="srcuser" class="formfld" id="srcuser">
                      <?php foreach($config['system']['accounts']['user'] as $i): ?>
                      <option value="<?=$i['name'];?>">
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                <input type=button value='Add'>
                </div> 
				</div>      
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
