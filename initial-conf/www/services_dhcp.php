#!/bin/php
<?php
 
$pgtitle = array("Services", "DHCP server");
require("guiconfig.inc");

$if = get_interface_name_by_descr($_GET['if']);
if ($_POST['if'])
    $if = get_interface_name_by_descr($_POST['if']);

$iflist = array("lan" => "LAN");

for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
    if ($config['interfaces']['opt' . $i]['wireless']['ifmode'] != 'lanbridge' && $config['interfaces']['wireless']['ifmode'] != 'dmzbridge')
        $oc = $config['interfaces']['opt' . $i];

    if (isset($oc['enable']) && $oc['if'] && (!$oc['bridge'])) {
        $iflist['opt' . $i] = $oc['descr'];
    }
}

for ($i = 0; isset($config['vlans']['vlan'][$i]); $i++) {
    $iflist['vlan' . $config['vlans']['vlan'][$i]['tag']] = "VLAN{$config['vlans']['vlan'][$i]['tag']}";
}

if (!$if || !isset($iflist[$if]))
    $if = "lan";

$pconfig['gateway'] = $config['dhcpd'][$if]['gateway'];
$pconfig['range_from'] = $config['dhcpd'][$if]['range']['from'];
$pconfig['range_to'] = $config['dhcpd'][$if]['range']['to'];
$pconfig['deftime'] = $config['dhcpd'][$if]['defaultleasetime'];
$pconfig['maxtime'] = $config['dhcpd'][$if]['maxleasetime'];
list($pconfig['wins1'],$pconfig['wins2']) = $config['dhcpd'][$if]['winsserver'];
list($pconfig['dns1'],$pconfig['dns2']) = $config['dhcpd'][$if]['dnsserver'];
$pconfig['enable'] = isset($config['dhcpd'][$if]['enable']);
$pconfig['denyunknown'] = isset($config['dhcpd'][$if]['denyunknown']);

if (strstr($if,'vlan')) {
    for ($i = 0; isset($config['vlans']['vlan'][$i]); $i++) {
        if ('vlan' . $config['vlans']['vlan'][$i]['tag'] == $if) {
            $ifcfg = $config['vlans']['vlan'][$i];
            break;
        }
    }
} else {
    $ifcfg = $config['interfaces'][$if];
}

if (!is_array($config['dhcpd'][$if]['staticmap'])) {
    $config['dhcpd'][$if]['staticmap'] = array();
}
staticmaps_sort($if);
$a_maps = &$config['dhcpd'][$if]['staticmap'];

$ifsubmit = $if . 'dhcp';
$formname = $if . 'form';

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#<?=$ifsubmit?>").click(function () {
        displayProcessingDiv();
        var QueryString = $("#<?=$formname?>").serialize();
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
        <div class="form-container ui-tabs ui-widget ui-corner-content">

    <form action="forms/services_form_submit.php" method="post" name="<?=$formname?>" id="<?=$formname?>">
        <input name="formname" type="hidden" value="services_dhcpd">

    <fieldset>
        <legend><?=join(": ", $pgtitle);?></legend>
            <div>
                             <label for="enable">Enable DHCP Server</label>
                             <input id="enable" type="checkbox" name="enable" value="Yes" <?php if ($pconfig['enable']) echo "checked"; ?> />
                             <p class="note">Enable DHCP server on <?=htmlspecialchars($iflist[$if]);?> interface.</p>
                             <input name="if" type="hidden" value="<?=htmlspecialchars($iflist[$if]);?>">
            </div>
                        <div>
                             <label for="subnet">Subnet</label>
                             <?=gen_subnet($ifcfg['ipaddr'], $ifcfg['subnet']);?>
                             <p class="note"></p>
                             <label for="subnet">Subnet mask</label>
                             <?=gen_subnet_mask($ifcfg['subnet']);?>
                             <p class="note"></p>
                             <label for="subnet">Availible Range</label>
                             <?=long2ip(ip2long($ifcfg['ipaddr']) & gen_subnet_mask_long($ifcfg['subnet']));?>
                          -
                          <?=long2ip(ip2long($ifcfg['ipaddr']) | (~gen_subnet_mask_long($ifcfg['subnet']))); ?>
                             <p class="note"></p>            
            </div>
                        <div>
                             <label for="range_from">Range</label>
                             <input name="range_from" type="text" class="formfld" id="range_from" size="20" value="<?=htmlspecialchars($pconfig['range_from']);?>">
                             &nbsp;to&nbsp;
                             <input name="range_to" type="text" class="formfld" id="range_to" size="20" value="<?=htmlspecialchars($pconfig['range_to']);?>">
                             <p class="note">Define the address range for the DHCP server.</p>
            </div>
                        <div>
                             <label for="gateway">Gateway</label>
                             <input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>">
                             <p class="note">Define the Gateway IP for the DHCP server.  Hint: leaving this blank will use the interface IP address</p>
            </div>
                        <div>
                             <label for="dns1">DNS Servers</label>
                             <input name="dns1" type="text" class="formfld" id="dns1" size="20" value="<?=htmlspecialchars($pconfig['dns1']);?>">
                             &nbsp;
                             <input name="dns2" type="text" class="formfld" id="dns2" size="20" value="<?=htmlspecialchars($pconfig['dns2']);?>">
                             <p class="note">Define the DNS servers for the DHCP server.</p>
            </div>
                        <div>
                             <label for="wins1">Wins Servers</label>
                              <input name="wins1" type="text" class="formfld" id="wins1" size="20" value="<?=htmlspecialchars($pconfig['wins1']);?>">
                             &nbsp;
                              <input name="wins2" type="text" class="formfld" id="wins2" size="20" value="<?=htmlspecialchars($pconfig['wins2']);?>">
                             <p class="note">Define the DNS servers for the DHCP server.</p>
            </div>
                        <div>
                             <label for="deftime">Default Lease Time</label>
                             <input name="deftime" type="text" class="formfld" id="deftime" size="10" value="<?=htmlspecialchars($pconfig['deftime']);?>">seconds
                             <p class="note">This is used for clients that do not ask for a specific expiration time.  The default is 7200 seconds.</p>
            </div>
                        <div>
                             <label for="maxtime">Maximum Lease Time</label>
                             <input name="maxtime" type="text" class="formfld" id="maxtime" size="10" value="<?=htmlspecialchars($pconfig['maxtime']);?>">seconds
                             <p class="note">This is the maximum lease time for clients that ask for a specific expiration time.  The default is 86400 seconds.</p>
            </div>
    </fieldset>
    
    <div class="buttonrow">
        <input type="submit" id="<?=$ifsubmit;?>" value="Save" class="button" />
    </div>

    </form>
    
    </div><!-- /form-container -->
    
</div><!-- /wrapper -->
