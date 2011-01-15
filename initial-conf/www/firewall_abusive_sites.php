#!/bin/php
<?php
$pgtitle = array("Firewall", "Abusive Sites");
 
require("guiconfig.inc");
include("ns-begin.inc");

$a_blockedsites = &$config['filter']['blockedsites'];

$pconfig['memberlist'] = $a_blockedsites['memberlist'];
$pconfig['nonmemberlist'] = $a_blockedsites['nonmemberlist'];
$pconfig['dynamictimeout'] = $a_blockedsites['dynamictimeout'];
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
            $("#srcblockedsitesdiv").hide();
            $("#srcuserdiv").hide();
            break;
        case 'srcnetdiv':
            $("#srcnetdiv").show();
            $("#srchostdiv").hide();
            $("#srcblockedsitesdiv").hide();
            $("#srcuserdiv").hide();
            break;
        case 'srcblockedsitesdiv':
            $("#srcblockedsitesdiv").show();
            $("#srchostdiv").hide();
            $("#srcnetdiv").hide();
            $("#srcuserdiv").hide();
            break;
        case 'srcuserdiv':
            $("#srcuserdiv").show();
            $("#srcblockedsitesdiv").hide();
            $("#srchostdiv").hide();
            $("#srcnetdiv").hide();
            break;
        }  
     });
}); 

$(function(){
     $("#exceptionsrctype").change(function() {
          var val = $(this).val();
          switch(val){
        case 'exceptionsrchostdiv':
            $("#exceptionsrchostdiv").show();
            $("#exceptionsrcnetdiv").hide();
            $("#exceptionsrcblockedsitesdiv").hide();
            $("#exceptionsrcuserdiv").hide();
            break;
        case 'exceptionsrcnetdiv':
            $("#exceptionsrcnetdiv").show();
            $("#exceptionsrchostdiv").hide();
            $("#exceptionsrcblockedsitesdiv").hide();
            $("#exceptionsrcuserdiv").hide();
            break;
        case 'exceptionsrcblockedsitesdiv':
            $("#exceptionsrcblockedsitesdiv").show();
            $("#exceptionsrchostdiv").hide();
            $("#exceptionsrcnetdiv").hide();
            $("#exceptionsrcuserdiv").hide();
            break;
        case 'exceptionsrcuserdiv':
            $("#exceptionsrcuserdiv").show();
            $("#exceptionsrcblockedsitesdiv").hide();
            $("#exceptionsrchostdiv").hide();
            $("#exceptionsrcnetdiv").hide();
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

     // When a user clicks on the host add button, validate and add the host.
     $("#exceptionhostaddbutton").click(function () {
          var ip = $("#exceptionsrchost");
      $('#NONMEMBERS').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
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

     // When a user clicks on the net add button, validate and add the host.
     $("#exceptionnetaddbutton").click(function () {
          var ip = $("#exceptionsrcnet");
          var netmask = $("#exceptionsrcmask");
      $('#NONMEMBERS').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
          ip.val("");
          return false;
     });

     // When a user highlights an item and clicks remove, remove it
          $('#remove').click(function() {  
          return !$('#MEMBERS option:selected').remove();  
     });

     // When a user highlights an item and clicks remove, remove it
          $('#exceptionremove').click(function() {  
          return !$('#NONMEMBERS option:selected').remove();  
     });

     // When a user clicks on the submit button, post the form.
     $("#submitbutton").click(function () {
      displayProcessingDiv();
      var Options = $.map($('#MEMBERS option'), function(e) { return $(e).val(); } );
      var str = Options.join(' ');
          var ExceptionOptions = $.map($('#NONMEMBERS option'), function(e) { return $(e).val(); } );
      var Exceptionstr = ExceptionOptions.join(' ');
      var QueryString = $("#iform").serialize()+'&memberslist='+str+'&nonmemberslist='+Exceptionstr;
      $.post("forms/firewall_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);    
               setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
               setTimeout(function(){ $('#content').load('firewall_abusive_sites.php'); }, 1250);
      });
      return false;
     });
  
});
</script> 

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

    <form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="firewall_blockedsites">
    <input name="id" type="hidden" value="<?=$id;?>">
    <fieldset>
        <legend><?=join(": ", $pgtitle);?></legend>
                      <div>
                             <label for="members">Blocked Sites</label>
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
                      <option value="srcblockedsitesdiv" >Alias</option>
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
                <div id='srcblockedsitesdiv' style="display:none;">
                 <label for="srcblockedsites">Table</label>
                    <select name="srcblockedsites" class="formfld" id="srcblockedsites">
                      <?php foreach($config['blockedsiteses']['blockedsites'] as $i): ?>
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
    </fieldset>
    
    <div class="buttonrow">
        <input type="submit" id="submitbutton" value="Save" class="button" />
    </div>

    </form>
    
    </div><!-- /form-container -->
</div><!-- /wrapper -->
