#!/bin/php
<?php
$pgtitle = array("VPN", "PPTP Client");
 
require("guiconfig.inc");
include("ns-begin.inc");

if (!is_array($config['pptp']['client']))
    $config['pptp']['client'] = array();

$a_client = &$config['pptp']['client'];

$pconfig['enable'] = isset($a_client['enable']);
$pconfig['connectonboot'] = isset($a_client['connectonboot']);
$pconfig['server'] = $a_client['server'];
$pconfig['username'] = $a_client['username'];
$pconfig['password'] = $a_client['password'];
$pconfig['routelist'] = $a_client['routelist'];
$pconfig['autonat'] = isset($a_client['autonat']);
$pconfig['lcplog'] = isset($a_client['lcplog']);

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
     // When a user clicks the forceall button, grey out the route configuration area
     $("#forcetunnel").click(function () {
               if ($("#forcetunnel").is(":checked")) {    
                    $('#MEMBERS').attr("disabled", true);
                    $('#remove').attr("disabled", true);
                    $('#srchost').attr("disabled", true);
                    $('#hostaddbutton').attr("disabled", true);
                    $('#netaddbutton').attr("disabled", true);
                    $('#srcnet').attr("disabled", true);
                    $('#srctable').attr("disabled", true);
                    $('#srcuser').attr("disabled", true);
                    $('#srctype').attr("disabled", true);
                    $('#tunnelforceoverride').attr("disabled", false);
                    return true;
               } else {
                    $('#MEMBERS').attr("disabled", false);
                    $('#remove').attr("disabled", false);
                    $('#srchost').attr("disabled", false);
                    $('#hostaddbutton').attr("disabled", false);
                    $('#netaddbutton').attr("disabled", false);
                    $('#srcnet').attr("disabled", false);
                    $('#srctable').attr("disabled", false);
                    $('#srcuser').attr("disabled", false);
                    $('#srctype').attr("disabled", false);
                    $('#tunnelforceoverride').attr("disabled", true);
                    return true;
               }
     });

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

     // When a user clicks on the submit button, post the form.
     $("#submitbutton").click(function () {
	  displayProcessingDiv();
	  var Options = $.map($('#MEMBERS option'), function(e) { return $(e).val(); } );
	  var str = Options.join(' ');
	  var QueryString = $("#iform").serialize()+'&submit=Save&routelist='+str;
	  $.post("forms/vpn_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);	  
               //setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 1000);            
	  });
	  return false;
     });
  
});
</script> 

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/vpn_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="vpn_pptp_client">
	<input name="id" type="hidden" value="<?=$id;?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="enable">Enable PPTP Client</label>
                             <input name="enable" type="checkbox" id="enable" value="Yes" <?php if ($pconfig['enable']) echo "checked"; ?>>
                             <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="connectonboot">Attempt Connection on Bootup</label>
                             <input name="connectonboot" type="checkbox" id="connectonboot" value="Yes" <?php if ($pconfig['connectonboot']) echo "checked"; ?>>
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="server">Server Address</label>
                             <input name="server" type="text" class="formfld" id="server" size="25" value="<?=htmlspecialchars($pconfig['server']);?>">
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="username">Username</label>
                             <input name="username" type="text" class="formfld" id="username" size="10" value="<?=htmlspecialchars($pconfig['username']);?>">
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="password">Password</label>
                             <input name="password" type="password" class="formfld" id="password" size="10" value="<?=htmlspecialchars($pconfig['password']);?>">
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
			<div>
                    <label for="tunnelforce">PPTP Connection Force</label>
                    <input name="tunnelforce" type="checkbox" id="forcetunnel" value="Yes" <?php if ($pconfig['forcetunnel']) echo "checked"; ?>>
                    <p class="note">Check this box to force all traffic to be routed to the PPTP server instead of the WAN default gateway ip.</p>
        	</div>
			<div>
                    <label for="tunnelforceoverride">PPTP Connection Force Override</label>
                    <input name="tunnelforceoverride" type="checkbox" id="tunnelforceoverride" value="Yes" <?php if ($pconfig['forcetunneloverride']) echo "checked"; ?>>
                    <p class="note">In the event of a PPTP tunnel failure, connections will be sent to the WAN default gateway ip.</p>
            </div>
                        <div id="routes">
                             <label for="members">Remote Network(s)</label>
                             <select name="MEMBERS" style="width: 160px; height: 100px" id="MEMBERS" multiple>
        <?php for ($i = 0; $i<sizeof($pconfig['routelist']); $i++): ?>
                <option value="<?=$pconfig['routelist']["route$i"];?>">
                <?=$pconfig['routelist']["route$i"];?>
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
                <div>
                    <label for="autonat">Generate NAT rules for PPTP</label>
                    <input name="autonat" type="checkbox" id="autonat" value="Yes" <?php if ($pconfig['autonat']) echo "checked"; ?>>
                    <p class="note">You may enter a description here for your reference (not parsed).</p>
		</div>
                <div>
                    <label for="lcplog">LCP debug log</label>
                    <input name="lcplog" type="checkbox" id="lcplog" value="Yes" <?php if ($pconfig['lcplog']) echo "checked"; ?>>
                    <p class="note">You may enter a description here for your reference (not parsed).</p>
		</div> 
		</div>      
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
