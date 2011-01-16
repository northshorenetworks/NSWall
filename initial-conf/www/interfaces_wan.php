#!/bin/php
<?php

$pgtitle = array("Interfaces", "WAN");
require("guiconfig.inc");
include("ns-begin.inc");
$wancfg = &$config['interfaces']['wan'];

if ($wancfg['ipaddr'] == "dhcp") {
	$pconfig['type'] = "DHCP";
} else {
	$pconfig['type'] = "Static";
	$pconfig['ipaddr'] = $wancfg['ipaddr'];
	$pconfig['subnet'] = $wancfg['subnet'];
	$pconfig['gateway'] = $wancfg['gateway'];
	$pconfig['pointtopoint'] = $wancfg['pointtopoint'];
}

$pconfig['blockpriv'] = isset($wancfg['blockpriv']);
$pconfig['spoofmac'] = $wancfg['spoofmac'];
$pconfig['mtu'] = $wancfg['mtu'];
$pconfig['aliaslist'] = $wancfg['aliaslist'];
$pconfig['altqenable'] = isset($wancfg['altqenable']);
$pconfig['bandwidth'] = $wancfg['bandwidth'];

/* Find next free carp interface */
for($i=0;$i<100; $i++) {
	if($config['interfaces']['wan']['carp']['carpif'] == 'carp' . "$i")
	continue;
	if($config['interfaces']['lan']['carp']['carpif'] == 'carp' . "$i")
	continue;
	$pconfig['carpif'] = 'carp' . "$i";
	break;
}

/* Wireless interface? */
if (isset($optcfg['wireless'])) {
	require("interfaces_wlan.inc");
	wireless_config_init();
}

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
     
     var wantype = $("#type");
     switch(wantype.val()){
        case 'Static':
            $("#dhcpdiv").hide();
            $("#staticdiv").show();
            break;
        case 'DHCP':
            $("#staticdiv").hide();
            $("#dhcpdiv").show();
            break;
     }

     // when a user changes the type of connection, change the related div to style = display: block and hide all others
     $("#type").change(function() {
          var val = $(this).val();
          switch(val){
        case 'Static':
            $("#dhcpdiv").hide();
            $("#staticdiv").show();
            break;
        case 'DHCP':
            $("#staticdiv").hide();
            $("#dhcpdiv").show();
            break;
        }
     });

     // When a user clicks on the host add button, validate and add the host.
     $("#hostaddbutton").click(function () {
          var ip = $("#srchost");
	  $('#MEMBERS').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          ip.val("");
          return false;
     });

     // When a user highlights an item and clicks remove, remove it
          $('#remove').click(function() {  
          return !$('#MEMBERS option:selected').remove();  
     });

     // When a user clicks on the submit button, post the form.
     $("#submitbutton").click(function () {
	  $("#save_config").html('<center>Saving Configuration File<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
      $(".ui-dialog-titlebar").css('display','block');
      $('#save_config').dialog('open'); 
	  var Options = $.map($('#MEMBERS option'), function(e) { return $(e).val(); } );
	  var str = Options.join(' ');
	  var QueryString = $("#iform").serialize()+'&memberslist='+str;
	  $.post("forms/interfaces_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);	  
	  		if(output.match(/SUBMITSUCCESS/))
			    setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
	  });
	  return false;
     });
  
});
</script>

<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">

<form action="forms/interfaces_form_submit.php" method="post"
	name="iform" id="iform"><input name="formname" type="hidden"
	value="interface_wan"> <input name="id" type="hidden" value="<?=$id;?>">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="type">Connection Type</label> <select name="type"
	class="formfld" id="type">
	<option value="Static">Static IP</option>
	<option value="DHCP"
	<?php if ('DHCP' == $pconfig['type']) echo "selected"; ?>>DHCP</option>
</select></div>
<div id='staticdiv'>
<div><label for="ipaddr">IP address</label> <input name="ipaddr"
	type="text" class="formfld" id="ipaddr" size="20"
	value="<?=htmlspecialchars($pconfig['ipaddr']);?>"> / <select
	name="subnet" class="formfld" id="subnet">
	<?php for ($i = 31; $i > 0; $i--): ?>
	<option value="<?=$i;?>"
	<?php if ($i == $pconfig['subnet']) echo "selected"; ?>><?=$i;?></option>
	<?php endfor; ?>
</select></div>
<div><label for="gateway">Gateway</label> <input name="gateway"
	type="text" class="formfld" id="gateway" size="20"
	value="<?=htmlspecialchars($pconfig['gateway']);?>"></div>
<div><label for="members">Alias IP's</label> <select name="MEMBERS"
	style="width: 160px; height: 100px" id="MEMBERS" multiple>
	<?php for ($i = 0; $i<sizeof($pconfig['aliaslist']); $i++): ?>
	<option value="<?=$pconfig['aliaslist']["alias$i"];?>"><?=$pconfig['aliaslist']["alias$i"];?>
	</option>
	<?php endfor; ?>
</select> <input type=button id='remove' value='Remove Selected'><br>
<br>
<label for="srchost">Address</label> <input name="srchost" type="text"
	class="formfld" id="srchost" size="16"
	value="<?=htmlspecialchars($pconfig['address']);?>"> <input type=button
	id='hostaddbutton' value='Add'></div>
</div>
<div style='display: none;' id='dhcpdiv'>
<div><label for="ipaddr">Client Identifier</label> <input
	name="dhcphostname" type="text" class="formfld" id="dhcphostname"
	size="40" value="<?=htmlspecialchars($pconfig['dhcphostname']);?>">
<p class="note">The value in this field is sent as the DHCP client
identifier and hostname when requesting a DHCP lease. Some ISPs may
require this (for client identification).</p>

</div>
</div>
<div><label for="spoofmac">MAC Address Override</label> <input
	name="spoofmac" type="text" class="formfld" id="spoofmac" size="30"
	value="<?=htmlspecialchars($pconfig['spoofmac']);?>">
<p class="note">This field can be used to modify (&quot;spoof&quot;) the
MAC address of the WAN interface<br>
(may be required with some cable connections)<br>
Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx or leave
blank</p>
</div>
<div><label for="mtu">MTU</label> <input name="mtu" type="text"
	class="formfld" id="mtu" size="8"
	value="<?=htmlspecialchars($pconfig['mtu']);?>">
<p class="note">If you enter a value in this field, then MSS clamping
for TCP connections to the value entered above minus 40 (TCP/IP header
size) will be in effect. If you leave this field blank, an MTU of 1492
bytes for PPPoE and 1500 bytes for all other connection types will be
assumed.</p>
</div>
<div><label for="altqenable">Enable ALTQ</label> <input id="altqenable"
	type="checkbox" name="altqenable" value="Yes"
	<?php if ($pconfig['altqenable']) echo "checked"; ?> /></div>
<div><label for="bandwidth">Uplink Speed</label> <input id="bandwidth"
	type="text" name="bandwidth"
	value="<?=htmlspecialchars($pconfig['bandwidth']);?>" />
<p class="note">Uplink speed of interface in Kilobits/s.</p>
</div>

</div>
</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container -->

</div>
<!-- /wrapper -->
