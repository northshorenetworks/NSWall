#!/bin/php
<?php

$pgtitle = array("Services", "BGPd", "Global Settings");
require("guiconfig.inc");
include("ns-begin.inc"); 

$pconfig['as'] = $config['bgpd']['as'];
$pconfig['routerid'] = $config['bgpd']['routerid'];
$pconfig['connectretry'] = $config['bgpd']['connectretry'];
$pconfig['holdtime'] = $config['bgpd']['holdtime'];
$pconfig['minholdtime'] = $config['bgpd']['minholdtime'];
$pconfig['listenon'] = $config['bgpd']['listenon'];
$pconfig['couplefib'] = isset($config['bgpd']['couplefib']);
$pconfig['logenable'] = isset($config['bgpd']['logenable']);
$pconfig['qualifyviabgp'] = isset($config['bgpd']['qualifyviabgp']);
$pconfig['routecollector'] = isset($config['bgpd']['routecollector']);
$pconfig['transparentas'] = isset($config['bgpd']['transparentas']);
$pconfig['routelist'] = $config['bgpd']['routelist']; 
?> 

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    // When a user clicks on the src net add button, validate and add the host.
    $("#srcnetaddbutton").click(function () {
         var ip = $("#srcnet");
         var netmask = $("#srcmask");
         if(verifyIP(ip.val()) == 0) {
                       var firstitem = $("#SRCADDR option:first").text();
                       if(firstitem == "any") {
                           $("#SRCADDR option:first").remove();
                       } 
           $('#SRCADDR').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
           ip.val("");
           return false;
         } 
    });

    // When a user highlights an item and clicks remove, remove it
    $('#srcremove').click(function() {  
        $('#SRCADDR option:selected').remove();  
        return false;
    });

    $("#submitbutton").click(function () {
        var Sources = $.map($('#SRCADDR option'), function(e) { return $(e).val(); } );
        var src = Sources.join(' ');
        displayProcessingDiv();
        var QueryString = $("#iform").serialize()+'&srclist='+src;
        $.post("forms/services_form_submit.php", QueryString, function(output) {
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

	<form action="forms/services_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="services_bgpd">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="name">AS</label>
                             <input id="as" size="16" type="text" name="as" value="<?=htmlspecialchars($pconfig['as']);?>" />
                             <p class="note">The Autonomous System this box advertises (digits only)</p>
                        </div>
                        <div>
                             <label for="name">Router ID</label>
                             <input id="routerid" size="16" type="text" name="routerid" value="<?=htmlspecialchars($pconfig['routerid']);?>" />
                             <p class="note">A unique name for this router (IP address, for instance)</p>
                        </div>
			<div>
                             <label for="name">Connect Retry</label>
                             <input id="connectretry" size="3" type="text" name="connectretry" value="<?=htmlspecialchars($pconfig['connectretry']);?>" />
                             <p class="note">A unique name for this router (IP address, for instance)</p>
                        </div>
			<div>
                             <label for="name">Holdtime</label>
                             <input id="holdtime" size="3" type="text" name="holdtime" value="<?=htmlspecialchars($pconfig['holdtime']);?>" />
                             <p class="note">A unique name for this router (IP address, for instance)</p>
                        </div>
			<div>
                             <label for="name">Minimum Holdtime</label>
                             <input id="minholdtime" size="3" type="text" name="minholdtime" value="<?=htmlspecialchars($pconfig['minholdtime']);?>" />
                             <p class="note">A unique name for this router (IP address, for instance)</p>
                        </div>
			<div>
                             <label for="name">Listen on</label>
                             <input id="listenon" type="text" size="16" name="listenon" value="<?=htmlspecialchars($pconfig['listenon']);?>" />
                             <p class="note">A unique name for this router (IP address, for instance)</p>
                        </div>
                        <div>
                             <label for="couplefib">FIB updates</label>
                             <input id="couplefib" type="checkbox" name="couplefib" value="Yes" <?php if ($pconfig['couplefib']) echo "checked"; ?> />
			     <p class="note">Insert learned routes into kernel routing table.</p>
			</div>
			<div>
                             <label for="logenable">Update Logging</label>
                             <input id="logenable" type="checkbox" name="logenable" value="Yes" <?php if ($pconfig['logenable']) echo "checked"; ?> />
                             <p class="note">turn off when not debugging</p>
                        </div>
			<div>
                             <label for="qualifyviabgp">Qualify via BGP</label>
                             <input id="qualifyviabgp" type="checkbox" name="qualifyviabgp" value="Yes" <?php if ($pconfig['qualifyviabgp']) echo "checked"; ?> />
			     <p class="note">BGP can use BGP routes to verify nexthops (off for normal configurations)</p>
			</div> 
			 <div>
                             <label for="logenable">Route Collector</label>
                             <input id="routecollector" type="checkbox" name="routecollector" value="Yes" <?php if ($pconfig['routecollector']) echo "checked"; ?> />
                             <p class="note">turn off when not debugging</p>
                        </div>
			 <div>
                             <label for="logenable">Transparent AS</label>
                             <input id="transparentas" type="checkbox" name="transparentas" value="Yes" <?php if ($pconfig['transparentas']) echo "checked"; ?> />
                             <p class="note">turn off when not debugging</p>
                        </div>


                        <div>
                             <label for="SRCADDR">Advertised Routes</label>
                              <select name="SRCADDR" style="width: 150px; height: 100px" id="SRCADDR" multiple>
<?php for ($i = 0; $i<sizeof($pconfig['routelist']); $i++): ?>
<option value="<?=$pconfig['routelist']["route$i"];?>"><?php $display = preg_replace('/user:|:user/', '', $pconfig['routelist']["route$i"]);?><?=$display;?></option>
<?php endfor; ?>
</select>
<input type=button id='srcremove' value='Remove Selected'><br><br>
</div>
<div id='srcnetdiv' style="display:block;">
<label for="srcnet">Network Address</label>
<?=$mandfldhtml;?><input name="srcnet" type="text" class="formfld" id="srcnet" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<strong>/</strong>
<select name="srcmask" class="formfld" id="srcmask">
<?php for ($i = 30; $i >= 1; $i--): ?>
<option value="<?=$i;?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
<?=$i;?>
</option>
<?php endfor; ?>
</select>
<input type=button id='srcnetaddbutton' value='Add'>
</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
