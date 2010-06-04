#!/bin/php
<?php

require("guiconfig.inc");
include("ns-begin.inc");

unset($index);
if ($_GET['index'])
    $index = $_GET['index'];
else if ($_POST['index'])
    $index = $_POST['index'];

if (!$index)
    exit;

$optcfg = &$config['interfaces']['opt' . $index];

/* Find Wireless interface(s), It is possible that a platform
may have more than 1 wireless interface, however we will use the
first wireless interace we find here for the wizard to configure */
system_determine_hwplatform();
$optcfg['if'] = $g['hwplatformconfig'][INTERFACES]['OPT' . $index]['IF'];

/* Wireless interface? */

if (preg_match($g['wireless_regex'], $optcfg['if'])) {
    require("interfaces_wlan.inc");
    wireless_config_init();
}

$pconfig['descr']      = $optcfg['descr'];
$pconfig['ipaddr']     = $optcfg['ipaddr'];
$pconfig['subnet']     = $optcfg['subnet'];
$pconfig['aliaslist']  = $optcfg['aliaslist'];
$pconfig['enable']     = isset($optcfg['enable']);
$pconfig['gateway']    = $optcfg['gateway'];
$pconfig['iftype']     = $optcfg['iftype'];
$pconfig['wantype']    = $optcfg['wantype'];
$pconfig['mtu'] 	   = $optcfg['mtu'];
$pconfig['spoofmac']   = $optcfg['spoofmac'];
$pconfig['altqenable'] = isset($optcfg['altqenable']);
$pconfig['bandwidth']  = $optcfg['bandwidth'];


$pgtitle = array("Interfaces", htmlspecialchars($optcfg['descr']));

?> 

<script type="text/javascript">
// when a user changes the type of member, change the related div to sytle = display: block and hide all others

// wait for the DOM to be loaded
$(document).ready(function() {
     $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');

     <?php if (preg_match($g['wireless_regex'], $optcfg['if'])): ?>
     var wifimode = $("#ifmode");
     switch(wifimode.val()){
          case 'nobridge':
              $("#ipdiv").show();
              $("#aliasdiv").show();
              break;
          default:
              $("#ipdiv").hide();
              $("#aliasdiv").hide();
              break;
          }

     $("#ifmode").change(function() {
          var val = $(this).val();
          switch(val){
          case 'nobridge':
              $("#ipdiv").show();
              $("#aliasdiv").show();
              break;
          default:
              $("#ipdiv").hide();
              $("#aliasdiv").hide();
              break;
          }
     });
     <?php endif; ?>

     var encmode = $("#encmode");
     switch(encmode.val()){
     case 'open':
         $("#wpadiv").hide();
         $("#wepdiv").hide();
         break;
     case 'wep':
         $("#wpadiv").hide();
         $("#wepdiv").show();
         break;
     case 'wpa':
         $("#wpadiv").show();
         $("#wepdiv").show();
         break;
     }
  
     $("#encmode").change(function() {
          var val = $(this).val();
          switch(val){
          case 'open':
              $("#wpadiv").hide();
              $("#wepdiv").hide();
              break;
          case 'wep':
              $("#wpadiv").hide();
              $("#wepdiv").show();
              break;
          case 'wpa':
              $("#wpadiv").show();
              $("#wepdiv").show();
              break;
          }
     });

     var iftype = $("#iftype");
     switch(iftype.val()){
          case 'wan':
              $("#waniftype").show();
              $("#laniftype").hide();
              $("#gatewaydiv").show();
              $("#connectiontype").show();
              $("#altqdiv").show();
              break;
          case 'lan':
              $("#waniftype").show();
              $("#gatewaydiv").hide();
              $("#connectiontype").hide();
               $("#altqdiv").hide();
			  break;
          }

     $("#iftype").change(function() {
          var val = $(this).val();
          switch(val){
          case 'wan':
              $("#waniftype").show();
              $("#laniftype").hide();
              $("#gatewaydiv").show();
              $("#connectiontype").show();
			  $("#altqdiv").show();
              break;
          case 'lan':
              $("#waniftype").show();
              $("#gatewaydiv").hide();
              $("#connectiontype").hide();
			  $("#altqdiv").hide();
              break;
          }
     });

      var wantype = $("#wantype");
      switch(wantype.val()){
          case 'Static':
              $("#staticdiv").show();
              $("#dhcpdiv").hide();
              break;
          case 'DHCP':
              $("#staticdiv").hide();
              $("#dhcpdiv").show();
              break;
          }

     $("#wantype").change(function() {
          var val = $(this).val();
          switch(val){
          case 'Static':
              $("#staticdiv").show();
              $("#dhcpdiv").hide();
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
	<form action="forms/interfaces_form_submit.php" method="post" name="iform" id="iform">
    <input name="formname" type="hidden" value="interface_opt">
	<input name="index" type="hidden" value="<?=$index;?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="enable">
                                  Enable <?=$pconfig['descr']; ?> IF
                             </label>
                             <input id="enable" type="checkbox" name="enable" value="Yes" <?php if ($pconfig['enable']) echo "checked"; ?> />
                        </div>
                        <div>
			     <label for="descr">Interface Name</label>
                             <input name="descr" type="text" class="formfld" id="descr" size="30" value="<?=htmlspecialchars($pconfig['descr']);?>">
                             <p class="note">The name of the interface (not parsed)</p>
                        </div>
				<?php if (isset($config['system']['advanced']['multiwansupport']) && sg_get_const("ENTERPRISE_ROUTING") == 'ENABLED'): ?>
   				 <div>
                 		    <label for=="iftype">Interface Type</label>
                 		    <select name="iftype" class="formfld" id="iftype">
                                       <?php $modes = array('lan' => 'LAN Interface', 'wan' => 'WAN Interface');
                                         foreach ($modes as $mode => $modename): ?>
                                            <option value="<?=$mode;?>" <?php if ($mode == $pconfig['iftype']) echo "selected"; ?>>
                                               <?=htmlspecialchars($modename);?>
                                            </option>
                                         <?php endforeach; ?>
                                    </select>	
                                    <p class="note">Select WAN to configure this interface as an additional WAN connection</p>
            			</div>
                             <div id='waniftype'>
			      <div id ='connectiontype'>
                             <label for="wantype">Connection Type</label>
                             <select name="wantype" class="formfld" id="wantype">
                                 <option value="Static" >Static IP</option>
                                 <option value="DHCP" <?php if ('DHCP' == $pconfig['wantype']) echo "selected"; ?>>DHCP</option>
                             </select>
                        </div>
                        <?php endif; ?>
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
            <?php if (isset($config['system']['advanced']['multiwansupport']) && sg_get_const("ENTERPRISE_ROUTING") == 'ENABLED'): ?>
            <div id='gatewaydiv'>
                             <label for="gateway">Gateway</label>
                             <input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>">
            </div>
            <?php endif; ?>
            <div id='aliasdiv'>
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
              </div>
                <div style='display: none;' id='dhcpdiv'></div>
			<?php if (preg_match($g['wireless_regex'], $optcfg['if'])): ?>
                             <div>
                                 <label for="ifmode">Wireless Mode</label>
                                 <select name="ifmode" class="formfld" id="ifmode" onChange="switchwifibridge(document.iform.ifmode.value)">
                                 <?php $modes = array('nobridge' => 'Independant Network', 'lanbridge' => 'Bridge to LAN', 'dmzbridge' => 'Bridge to DMZ');
                                 foreach ($modes as $mode => $modename): ?>
                                     <option value="<?=$mode;?>" <?php if ($mode == $pconfig['ifmode']) echo "selected"; ?>>
                                         <?=htmlspecialchars($modename);?>
                                     </option>
                                 <?php endforeach; ?>
                                 </select>
                             </div>
                        <?php endif; ?>
	 	<div>
                    <label for="spoofmac">MAC Address Override</label>
                    <input name="spoofmac" type="text" class="formfld" id="spoofmac" size="30" value="<?=htmlspecialchars($pconfig['spoofmac']);?>">
                    <p class="note">This field can be used to modify (&quot;spoof&quot;) the MAC address of the interface<br>
                    Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx or leave blank</p>
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
				<?php if (isset($config['system']['advanced']['multiwansupport']) && sg_get_const("ENTERPRISE_ROUTING") == 'ENABLED'): ?>
                <div id='altqdiv'>
			    <div>
                    <label for="altqenable">Enable ALTQ</label>
                    <input id="altqenable" type="checkbox" name="altqenable" value="Yes" <?php if ($pconfig['altqenable']) echo "checked"; ?> />
                </div>
                <div>
                    <label for="bandwidth">Uplink Speed</label>
                    <input id="bandwidth" type="text" name="bandwidth" value="<?=htmlspecialchars($pconfig['bandwidth']);?>" />
                    <p class="note">Uplink speed of interface in Kilobits/s.</p>
                </div>
				</div>
				<?php endif; ?>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
</div><!-- /wrapper -->
