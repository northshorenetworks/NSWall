#!/bin/php
<?php

$pgtitle = array("Interfaces", "LAN");
require("guiconfig.inc");
include("ns-begin.inc");

$lancfg = &$config['interfaces']['lan'];

$pconfig['ipaddr']    = $lancfg['ipaddr'];
$pconfig['subnet']    = $lancfg['subnet'];
$pconfig['aliaslist'] = $lancfg['aliaslist'];
$pconfig['mtu']       = $lancfg['mtu'];
$pconfig['spoofmac']  = $lancfg['spoofmac'];
/* Wireless interface? */
if (isset($optcfg['wireless'])) {
        require("interfaces_wlan.inc");
        wireless_config_init();
}
$pconfig['gateway'] = $lancfg['dhcpd']['gateway'];
$pconfig['range_from'] = $lancfg['dhcpd']['range']['from'];
$pconfig['range_to'] = $lancfg['dhcpd']['range']['to'];
$pconfig['deftime'] = $lancfg['dhcpd']['defaultleasetime'];
$pconfig['maxtime'] = $lancfg['dhcpd']['maxleasetime'];
list($pconfig['wins1'],$lancfg['wins2']) = $lancfg['dhcpd']['winsserver'];
list($pconfig['dns1'],$lancfg['dns2']) = $lancfg['dhcpd']['dnsserver'];
$pconfig['dhcpdenable'] = isset($lancfg['dhcpd']['enable']);
$pconfig['denyunknown'] = isset($lancfg['dhcpd']['denyunknown']);

?> 

<script type="text/javascript">
// when a user changes the type of memeber, change the related div to sytle = display: block and hide all others

// wait for the DOM to be loaded
$(document).ready(function() {

     // When a user clicks on the host add button, validate and add the host.
     $("#hostaddbutton").click(function () {
          var ip = $("#srchost");
      $('#MEMBERS').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          ip.val("");
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

    <form action="forms/interfaces_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="interface_lan">
    <input name="id" type="hidden" value="<?=$id;?>">
    <fieldset>
        <legend><?=join(": ", $pgtitle);?></legend>
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
                             <label for="members">Alias IP's</label>
                             <select name="MEMBERS" style="width: 160px; height: 100px" id="MEMBERS" multiple>
        <?php for ($i = 0; $i<sizeof($pconfig['aliaslist']); $i++): ?>
                <option value="<?=$pconfig['aliaslist']["alias$i"];?>">
                <?=$pconfig['aliaslist']["alias$i"];?>
                </option>
                <?php endfor; ?>
        </select>
                <input type=button id='remove' value='Remove Selected'><br><br>
                 <label for="srchost">Address</label>
                  <input name="srchost" type="text" class="formfld" id="srchost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
                <input type=button id='hostaddbutton' value='Add'>
                </div>
                <div>
                             <label for="spoofmac">MAC Address Override</label>
                             <input name="spoofmac" type="text" class="formfld" id="spoofmac" size="30" value="<?=htmlspecialchars($pconfig['spoofmac']);?>">
                             <p class="note">This field can be used to modify ("spoof") the MAC
                    address of the WAN interface<br>
                    (may be required with some cable connections)<br>
                    Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx
                    or leave blank</p>
                        </div>
                        <div>
                             <label for="mtu">MTU</label>
                              <input name="mtu" type="text" class="formfld" id="mtu" size="8" value="<?=htmlspecialchars($pconfig['mtu']);?>">
                             <p class="note">If you enter a value in this field, then MSS clamping for
                    TCP connections to the value entered above minus 40 (TCP/IP
                    header size) will be in effect. If you leave this field blank,
                    an MTU of 1492 bytes for PPPoE and 1500 bytes for all other
                    connection types will be assumed.</p>
                        </div>
                        <div>
                             <label for="dhcpdenable">Enable DHCP Server</label>
                             <input id="dhcpdenable" type="checkbox" name="dhcpdenable" value="Yes" <?php if ($pconfig['dhcpdenable']) echo "checked"; ?> />
                             <p class="note">Enable DHCP server on <?=htmlspecialchars($iflist[$if]);?> interface.</p>
                             <input name="if" type="hidden" value="<?=htmlspecialchars($iflist[$if]);?>">
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
                             <?=long2ip(ip2long($pconfig['ipaddr']) & gen_subnet_mask_long($pconfig['subnet']));?>
                          -
                          <?=long2ip(ip2long($pconfig['ipaddr']) | (~gen_subnet_mask_long($pconfig['subnet']))); ?>
                                         
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
    </fieldset>
    
    <div class="buttonrow">
        <input type="submit" id="submitbutton" value="Save" class="button" />
    </div>

    </form>
    
    </div><!-- /form-container -->
    
</div><!-- /wrapper -->
