#!/bin/php
<?php

$pgtitle = array("Services", "Configuration Server");
require("guiconfig.inc");
include("ns-begin.inc");
$pconfig['ip']         = $config['system']['configserver']['ip'];
$pconfig['identifier'] = $config['system']['configserver']['identifier'];
$pconfig['caname']     = $config['system']['configserver']['caname'];
$pconfig['certname']   = $config['system']['configserver']['certname'];
$pconfig['takey']      = base64_decode($config['system']['configserver']['takey']);

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/openvpn_form_submit.php", QueryString, function(output) {
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

<form action="forms/openvpn_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden"
	value="openvpn_config_server">

<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="ip">Server Address</label> 
<input name="ip" type="text" class="formfld" id="ip" size="40" value="<?=htmlspecialchars($pconfig['ip']);?>">	
</div>

<div><label for="identifier">Client Identifier</label>
<input name="identifier" type="text" class="formfld" id="identifier" size="40" value="<?=htmlspecialchars($pconfig['identifier']);?>">
</div>

<div>
                             <label for="cert">CA</label>
                             <select name="caname" class="formfld" id="caname">
                                  <?php foreach($config['system']['certmgr']['ca'] as $i): ?>
                                  <?php if ($i['crt']): ?>
                                      <option value="<?=$i['name'];?>"
                                      <?php if ($i == $pconfig['caname']) echo "selected"; ?>>
                                      <?=$i['name'];?>
                                      </option>
                                  <?php endif; ?>
                                  <?php endforeach; ?>
                             </select>
                        </div>

<div>
                             <label for="cert">Certificate</label>
                             <select name="certname" class="formfld" id="certname">
                                  <?php foreach($config['system']['certmgr']['cert'] as $i): ?>
                                  <?php if ($i['crt']): ?>
                                      <option value="<?=$i['name'];?>"
                                      <?php if ($i == $pconfig['certname']) echo "selected"; ?>>
                                      <?=$i['name'];?>
                                      </option>
                                  <?php endif; ?>
                                  <?php endforeach; ?>
                             </select>
                        </div>

 <div>
                        <label for="cert">Static Key File</label>
                        <textarea name="takey" id="cert" cols="65" rows="7" class="formfld_cert"><?=$pconfig['takey'];?></textarea>
                        <p class="note">Paste an OpenVPN static key file here.</p>
                    </div>

</fieldset>

<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>

</form>

</div>
<!-- /form-container --></div>
<!-- /wrapper -->
