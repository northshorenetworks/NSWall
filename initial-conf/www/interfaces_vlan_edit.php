#!/bin/php
<?php
$pgtitle = array("Interfaces", "Assign network ports", "Edit VLAN");
require("guiconfig.inc");

if (!is_array($config['vlans']['vlan']))
    $config['vlans']['vlan'] = array();

vlans_sort();
$a_vlans = &$config['vlans']['vlan'];

$portlist = get_interface_list();

/* add Trunk interfaces */
if (is_array($config['trunks']['trunk']) && count($config['trunks']['trunk'])) {
        $i = 0;
        foreach ($config['trunks']['trunk'] as $trunk) {
                $trunkport = $trunk['trunkport'];
                $portlist[$trunkport] = $trunk;
                $portlist[$trunkport]['istrunk'] = true;
                $i++;
        }
}

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

if (isset($id) && $a_vlans[$id]) {
    $pconfig['if'] = $a_vlans[$id]['if'];
    $pconfig['tag'] = $a_vlans[$id]['tag'];
    $pconfig['descr'] = $a_vlans[$id]['descr'];
    $pconfig['ipaddr'] = $a_vlans[$id]['ipaddr'];
    $pconfig['subnet'] = $a_vlans[$id]['subnet'];
    $pconfig['rtable'] = $a_vlans[$id]['rtable'];
    $pconfig['gateway'] = $a_vlans[$id]['dhcpd']['gateway'];
    $pconfig['range_from'] = $a_vlans[$id]['dhcpd']['range']['from'];
    $pconfig['range_to'] = $a_vlans[$id]['dhcpd']['range']['to'];
    $pconfig['deftime'] = $a_vlans[$id]['dhcpd']['defaultleasetime'];
    $pconfig['maxtime'] = $a_vlans[$id]['dhcpd']['maxleasetime'];
    list($pconfig['wins1'],$a_vlans[$id]['wins2']) = $a_vlans[$id]['dhcpd']['winsserver'];
    list($pconfig['dns1'],$a_vlans[$id]['dns2']) = $a_vlans[$id]['dhcpd']['dnsserver'];
    $pconfig['dhcpdenable'] = isset($a_vlans[$id]['dhcpd']['enable']);
    $pconfig['denyunknown'] = isset($a_vlans[$id]['dhcpd']['denyunknown']);
} 
?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/interfaces_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/)) {
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                setTimeout(function(){ $('#content').load('interfaces_vlan_tabs.php'); }, 1250);
            }    
        });
        return false;
    });
     // When a user clicks on the src alias add button, add the selected value.
    $("#aliasaddbutton").click(function () {
         var alias = $("#aliashost");
     $('#ALIASADDR').append("<option value='" + alias.val() + "'>" + alias.val() + '</option>');
         return false;
    });

    var val = $("#dhcpdenable:checked").val();
    if (val != undefined) $('#dhcpddiv').show();
    else $('#dhcpddiv').hide(); 

    $("#dhcpdenable").click(function() {
         var val = $("#dhcpdenable:checked").val();
         if (val != undefined) $('#dhcpddiv').show();
         else $('#dhcpddiv').hide();
     });

     $("#rtable").change(function() {
         var val = $("#rtable").val();
         if (val != 'DEFAULT') {
             alert("Currently only the default routing table can have dhcp services enabled");
             $('#dhcpdenable').attr('checked', false);
             $('#dhcpdenable').attr('disabled', true);
             $('#dhcpddiv').hide();
         } else { 
             $('#dhcpdenable').attr('disabled', false); 
         }
     });

});
</script>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

    <form action="forms/interfaces_form_submit.php" method="post" name="iform" id="iform">
              <input name="formname" type="hidden" value="interface_vlan">
              <input name="id" type="hidden" value="<?=$id;?>">
              <input name="oldtag" type="hidden" value="<?=$pconfig['tag'];?>">
              <input name="oldif" type="hidden" value="<?=$pconfig['if'];?>">
    <fieldset>
        <legend><?=join(": ", $pgtitle);?></legend>
        <div>
                 <label for="descr">Interface Name</label>
                             <input name="descr" type="text" class="formfld" id="descr" size="30" value="<?=htmlspecialchars($pconfig['descr']);?>">
                             <p class="note">The name of the interface (not parsed)</p>
                </div>
                <div>
                   <label for "tag">Tag</label>
                   <input name="tag" type="text" class="formfld" id="tag" size="20" value="<?=htmlspecialchars($pconfig['tag']);?>">
                   <p class="note">802.1Q VLAN tag (between 1 and 4094)</p>
                </div>         
                <div id='staticdiv'>
            <div>
                             <label for="ipaddr">IP address</label>
                             <input name="ipaddr" type="text" class="formfld" id="ipaddr" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
                    /
                    <select name="subnet" class="formfld" id="subnet">
                      <?php for ($i = 31; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
            </div>
                <div>
                <label for="interface">Parent Interface</label>
                <select name="if" class="formfld">
                      <?php
                      foreach ($portlist as $ifn => $ifinfo): ?>
                      <option value="<?=$ifn;?>" <?php if ($ifn == $pconfig['if']) echo "selected"; ?>> 
                      <?=htmlspecialchars($ifn . " (" . $ifinfo['mac'] . ")");?>
                      </option>
                      <?php endforeach; ?>
                    </select>  
                <p class="note">Choose which interface this rule applies to.  Hint: in most cases, you'll want to use WAN here..</p>
            </div>
            <div>
                <label for="rtable">Route Table</label>
                <select id="rtable" name="rtable" class="formfld">
                      <option value="DEFAULT" <?php if ($rtable['name'] == "DEFAULT") echo "selected"; ?>>DEFAULT</option>
                      <?php
                      foreach ($config['system']['routetables']['routetable'] as $rtable): ?>
                      <option value="<?=$rtable['name'];?>" <?php if ($rtable['name'] == $pconfig['rtable']) echo "selected"; ?>><?=$rtable['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                <p class="note">Choose which route table this vlan will use</p>
            </div>
                  <div>
                             <label for="dhcpdenable">Enable DHCP Server</label>
                             <input id="dhcpdenable" type="checkbox" name="dhcpdenable" value="Yes" <?php if ($pconfig['dhcpdenable']) echo "checked"; ?> />
                             <p class="note">Enable DHCP server on <?=htmlspecialchars($pconfig['descr']);?> interface.</p>
                        </div>
                        <div id="dhcpddiv" name="dhcpddiv">
                         <div>
                             <label for="subnet">Subnet</label>
                             <?=gen_subnet($pconfig['ipaddr'], $pconfig['subnet']);?>
                        </div>
                        <div>    
                             <label for="subnet">Subnet mask</label>
                             <?=gen_subnet_mask($pconfig['subnet']);?>
                        </div>
                        <div>     
                             <label for="subnet">Availible Range</label>
                             <?=long2ip(ip2long($pconfig['ipaddr']) & gen_subnet_mask_long($pconfig['subnet']));?> - <?=long2ip(ip2long($pconfig['ipaddr']) | (~gen_subnet_mask_long($pconfig['subnet']))); ?>
                                         
                        </div>
                        <div>
                             <label for="range_from">Range</label>
                             <input name="range_from" type="text" class="formfld" id="range_from" size="20" value="<?=htmlspecialchars($pconfig['range_from']);?>"> to <input name="range_to" type="text" class="formfld" id="range_to" size="20" value="<?=htmlspecialchars($pconfig['range_to']);?>">
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
                             <input name="dns2" type="text" class="formfld" id="dns2" size="20" value="<?=htmlspecialchars($pconfig['dns2']);?>">
                             <p class="note">Define the DNS servers for the DHCP server.</p>
            </div>
                        <div>
                             <label for="wins1">Wins Servers</label>
                              <input name="wins1" type="text" class="formfld" id="wins1" size="20" value="<?=htmlspecialchars($pconfig['wins1']);?>">
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
    </div>
</div>
    </fieldset>
    
    <div class="buttonrow">
        <input type="submit" id="submitbutton" value="Save" class="button" />
    </div>

    </form>
    
    </div><!-- /form-container -->
    
</div><!-- /wrapper -->
