#!/bin/php
<?php

$pgtitle = array("Wizard", "Initial Setup");

require("guiconfig.inc");

$lancfg = &$config['interfaces']['lan'];
$wancfg = &$config['interfaces']['wan'];
$wlchannels = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14);
$pconfig['lanipaddr'] = $lancfg['ipaddr'];
$pconfig['lansubnet'] = $lancfg['subnet'];

if ($wancfg['ipaddr'] == "dhcp") {
        $pconfig['wantype'] = "DHCP";
} else {
        $pconfig['wantype'] = "Static";
        $pconfig['wanipaddr'] = $wancfg['ipaddr'];
        $pconfig['wansubnet'] = $wancfg['subnet'];
        $pconfig['wangateway'] = $wancfg['gateway'];
        $pconfig['wanpointtopoint'] = $wancfg['pointtopoint'];
}

$pconfig['hostname'] = $config['system']['hostname'];
$pconfig['domain'] = $config['system']['general']['domain'];
list($pconfig['dns1'],$pconfig['dns2'],$pconfig['dns3']) = $config['system']['general']['dnsserver'];
$pconfig['username'] = $config['system']['username'];
if (!$pconfig['username'])
    $pconfig['username'] = "admin";
$pconfig['timezone'] = $config['system']['general']['timezone'];

if (!isset($pconfig['timeupdateinterval']))
    $pconfig['timeupdateinterval'] = 300;
if (!$pconfig['timezone'])
    $pconfig['timezone'] = "Etc/UTC";
if (!$pconfig['timeservers'])
    $pconfig['timeservers'] = "pool.ntp.org";

function is_timezone($elt) {
    return !preg_match("/\/$/", $elt);
}

/* Find Wireless interface(s), It is possible that a platform 
may have more than 1 wireless interface, however we will use the
first wireless interace we find here for the wizard to configure */
for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
	$optcfg = $config['interfaces']['opt' . $i];
 	if (preg_match($g['wireless_regex'], $optcfg['if'])) {	
		$wireless_index = $i;
		break;
	}	
}

exec('/bin/tar -tzf /usr/share/zoneinfo.tgz', $timezonelist);
$timezonelist = array_filter($timezonelist, 'is_timezone');
sort($timezonelist);

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>NSWall Initial Setup Wizard</title>

<link href="gui.css" rel="stylesheet" type="text/css">
<link type="text/css" href="style/jquery-ui-1.7.2.custom.css" rel="stylesheet" />

</head>

<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="js/ui.core.js"></script>
<script type="text/javascript" src="js/ui.dialog.js"></script>
<script type="text/javascript" src="js/jquery.form.js"></script>
<script language="javascript">

     $(document).ready(function() {
      $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
     
      // Display the Welcome Screen
      $("#loading").hide();
      $("#welcome").show();
      $(".form-container").css({height : '450', 'width' : '650'});
      $(".buttonrow").css({'bottom' : '15px', 'right' : '25px', 'position' : 'absolute' } );

        $("#login_nswall").dialog({
                    width: 'auto',
                    height: 'auto',
                    hide: 'scale',
                    show: 'scale',
                    resizable: false,
                    draggable: false,
                    closeOnEscape: false,
                    modal: true,
                    open: function(event, ui) {
                        $(".ui-dialog-titlebar-close").hide();
                        $(".ui-dialog-titlebar").css('display','block');
                    }
                });
         // Save Configuration dialog, displayed whenever a config change is written
        $("#save_config").dialog({
            autoOpen: false,
            width: 400,
            height: 200,
            hide: 'scale',
            show: 'scale',
            resizable: false,
            draggable: false,
            closeOnEscape: false,
            open: function(event, ui) {
                $(".ui-dialog-titlebar-close").hide();
                $(".ui-dialog-titlebar").css('display','block');
            }
        });

     
     //WAN page stuff:
     var wantype = $("#wantype");
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
     $("#wantype").change(function() {
          var val = $(this).val();
          switch(val){
        case 'Static':
            $("#staticdiv").show();
            break;
        case 'DHCP':
            $("#staticdiv").hide();
            break;
        }
     });

        // WIFI page stuff:
     var wifimode = $("#wifiifmode");
     switch(wifimode.val()){
          case 'nobridge':
              $("#ipdiv").show();
              break;
          default:
              $("#ipdiv").hide();
              break;
          }

     $("#wifiifmode").change(function() {
          var val = $(this).val();
          switch(val){
          case 'nobridge':
              $("#ipdiv").show();
              break;
          default:
              $("#ipdiv").hide();
              break;
          }
     });

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


        // When a user clicks on a button, navigate them
          $("#welcome_next_btn").click(function () {
               $("#welcome").hide();
               $("#general").show();
           return false;
          });
          $("#general_back_btn").click(function () {
               $("#welcome").show();
               $("#general").hide();
           return false;
          });
          $("#general_next_btn").click(function () {
               $("#general").hide();
               $("#wan").show();
           return false;
          });
          $("#wan_back_btn").click(function () {
               $("#general").show();
               $("#wan").hide();
           return false;
          });
          $("#wan_next_btn").click(function () {
               $("#wan").hide();
               $("#lan").show();
           return false;
          });
          $("#lan_back_btn").click(function () {
               $("#wan").show();
               $("#lan").hide();
           return false;
          });
          $("#lan_next_btn").click(function () {
               $("#lan").hide();
   <?php if (isset($wireless_index)): ?>
    	   	   $("#wifi").show();
   <?php else:?>
               $("#summary").show();
   <?php endif; ?>
		   return false;
          });
          $("#wifi_back_btn").click(function () {
               $("#lan").show();
               $("#wifi").hide();
           return false;
          });
          $("#wifi_next_btn").click(function () {
               $("#wifi").hide();
               $("#summary").show();
           return false;
          });
		  $("#summary_back_btn").click(function () {
    <?php if (isset($wireless_index)): ?> 
		   	   $("#wifi").show();
    <?php else:?>
			   $("#lan").show();
	<?php endif; ?>
               $("#summary").hide();
           return false;
          });
          $("#wizard_submit_btn").click(function () {
              $("#save_config").html('<center>Applying System Configuration<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
              $(".ui-dialog-titlebar").css('display','block');
              $('#save_config').dialog('open');
              //configure system settings
              var hostname = $("#hostname").val();
              var domain = $("#domain").val();
              var dns1 = $("#dns1").val();
              var dns2 = $("#dns2").val();
              var dns3 = $("#dns3").val();
              var username = $("#username").val();
              var password = $("#password").val();
              var password2 = $("#password2").val();
              var timezone = $("#timezone").val();
              var wantype = $("#wantype").val();
              var lanip = $("#lanipaddr").val();
              var lansubnet = $("#lansubnet").val();
              // wifi settings
              var wifienable = $("#wifienable").val();
              var wifiifmode = $("#wifiifmode").val();
              var wifiipaddr = $("#wifiipaddr").val();
              var wifisubnet = $("#wifisubnet").val();
              var ssid = $("#ssid").val();
              var channel = $("#channel").val();
              var encmode = $("#encmode").val();
              var wpacipher = $("#wpacipher").val();
              var wpapsk = $("#wpapsk").val();


              var SystemQueryString = 'hostname='+hostname+'&domain='+domain+'&dns1='+dns1+'&dns2='+dns2+'&username='+username+'&password='+password+'&password2='+password2+'&timezone='+timezone+'&formname=system_general';
              var WANQueryString = 'type='+wantype+'&formname=interface_wan';
              var LANQueryString = 'ipaddr='+lanip+'&subnet='+lansubnet+'&formname=interface_lan';
              var WIFIQueryString = 'enable='+wifienable+'&ifmode='+wifiifmode+'&ipaddr='+wifiipaddr+'&subnet='+wifisubnet+'&ssid='+ssid+'&channel='+channel+'&encmode='+encmode+'&wpacipher=Auto&wpamode=Auto&wpapsk='+wpapsk+'&formname=interface_opt&descr=WIFI&index=2';        

			  <?php if (isset($wireless_index)): ?>
		
              $.post("forms/system_form_submit.php", SystemQueryString, function(output) {
                  $("#save_config").html(output); 
                  setTimeout(function(){$("#save_config").html('<center>Applying WAN Configuration<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                  $.post("forms/interfaces_form_submit.php", WANQueryString, function(output) {
                      $("#save_config").html(output);
                      setTimeout(function(){$("#save_config").html('<center>Applying WIFI Configuration<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                      $.post("forms/interfaces_form_submit.php", WIFIQueryString, function(output) {
                          $("#save_config").html(output);
                          setTimeout(function(){$("#save_config").html('<center>Applying LAN Configuration<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                          $.post("forms/interfaces_form_submit.php", LANQueryString, function(output) {
                              $("#save_config").html(output);
                              setTimeout(function(){$("#save_config").html('<center>Initial Setup Wizard Completed<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                              setTimeout(function(){ 
                                  $("#save_config").dialog("close");
                                  window.location = "..#index";
                              }, 2500);
                          });
                      });
                  });
              });

			  <?php else:?>
             
			  $.post("forms/system_form_submit.php", SystemQueryString, function(output) {
                  $("#save_config").html(output); 
                  setTimeout(function(){$("#save_config").html('<center>Applying WAN Configuration<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                  $.post("forms/interfaces_form_submit.php", WANQueryString, function(output) {
                      $("#save_config").html(output);
                      setTimeout(function(){$("#save_config").html('<center>Applying WIFI Configuration<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                          $("#save_config").html(output);
                        setTimeout(function(){$("#save_config").html('<center>Applying LAN Configuration<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                          $.post("forms/interfaces_form_submit.php", LANQueryString, function(output) {
                              $("#save_config").html(output);
                              setTimeout(function(){$("#save_config").html('<center>Initial Setup Wizard Completed<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">'); }, 1000);
                              setTimeout(function(){ 
                                  $("#save_config").dialog("close");
                                  window.location = "..#index";
                              }, 2500);
                        });
                  });
              }); 

			  <?php endif; ?>

              return false;
			  $("#register").show();
          });
     });
</script>

<body>

<div id="login_nswall" title="NSWall Initial Setup Wizard">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">
<div id="save_config" title="Saving Configuration"></div>
<form id="iform" name="iform">
<span id="loading">
    <center>
        Loading NSWall Setup Utility<br><br>
        <img src="images/ajax-loader.gif" height="25" width="25" name="spinner">
    </center>
</span>
<span id="welcome" style="display:none";>
<fieldset>
        <legend><?=join(": ", $pgtitle);?>: Welcome To NSWall</legend>
            <div>
                <center><img alt="NSS Logo" class="png" width="50" height="50" src="/images/logo.jpg"/></center><br>
                <center>Welcome to NorthShore Software's NSWall</center><br>
                <center>The following wizard will guide you through the initial setup of the appliance</center><br>
            </div>
    </fieldset>
<div class="buttonrow">
        <input type="submit" id="welcome_next_btn" value="Next" class="button" />
</div>
</span>
<span id="general" style="display:none";>
<fieldset>
        <legend><?=join(": ", $pgtitle);?>: General Settings</legend>
            <div>
                             <label for="hostname">Hostname</label>
                             <input id="hostname" type="text" name="hostname" value="<?=htmlspecialchars($pconfig['hostname']);?>" />
                             <p class="note">Name of the firewall host without the domain part</p>
                        </div>
            <div>
                             <label for="domain">Domain</label>
                             <input id="domain" type="text" name="domain" value="<?=htmlspecialchars($pconfig['domain']);?>" />
                 <p class="note">e.g. <em>mycorp.com</em> </p>
            </div>
            <div>
                             <label for="dns1 dns2">DNS Servers</label>
                             <input id="dns1" type="text" name="dns1" value="<?=htmlspecialchars($pconfig['dns1']);?>" size="20" />
                             <input id="dns2" type="text" name="dns2" value="<?=htmlspecialchars($pconfig['dns2']);?>" size="20" />
                        </div>
            <div>
                             <label for="username">Username</label>
                             <input id="username" type="text" name="username" value="<?=$pconfig['username'];?>" />
                 <p class="note">If you want to change the username for accessing the WebUI do it here.</p>
            </div>
                        <div>
                             <label for="password">Password</label>
                             <input id="password" type="password" name="password" value="" />
                             <input id="password2" type="password" name="password2" value="" />
                             &nbsp;(confirmation) <br> <p class="note">If you want to change the password for accessing the webGUI, enter it here twice.</p>
            </div>
            <div>
                             <label for="timezone">Timezone</label>
                             <select name="timezone" id="timezone">
                             <?php foreach ($timezonelist as $value): ?>
                                  <option value="<?=htmlspecialchars($value);?>"
                                  <?php if ($value == $pconfig['timezone']) echo "selected"; ?>>
                                  <?=htmlspecialchars($value);?>
                                  </option>
                                  <?php endforeach; ?>
                             </select>
            </div>
    </fieldset>
<div class="buttonrow">
        <input type="submit" id="general_back_btn" value="Back" class="button" />
        <input type="submit" id="general_next_btn" value="Next" class="button" />
</div>
</span>
<span id="wan" style="display:none";>
<fieldset>
        <legend><?=join(": ", $pgtitle);?>: WAN Configuration</legend>
            <div>
                             <label for="wantype">Connection Type</label>
                             <select name="wantype" class="formfld" id="wantype">
                                 <option value="DHCP" selected>DHCP</option>
                                 <option value="Static" >Static IP</option>
                             </select>
                        </div>
                        <div id='staticdiv'>
            <div>
                             <label for="wanipaddr">IP address</label>
                             <input name="wanipaddr" type="text" class="formfld" id="wanipaddr" size="20" value="<?=htmlspecialchars($pconfig['wanipaddr']);?>">
                    /
                    <select name="wansubnet" class="formfld" id="subnet">
                      <?php for ($i = 31; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['wansubnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
            </div>
        </div>
    </fieldset>
<div class="buttonrow">
        <input type="submit" id="wan_back_btn" value="Back" class="button" />
        <input type="submit" id="wan_next_btn" value="Next" class="button" />
</div>
</span>
<span id="lan" style="display:none";>
<fieldset>
        <legend><?=join(": ", $pgtitle);?>: LAN Configuration</legend>
            <div>
                    <label for="lanipaddr">IP address</label>
                    <input name="lanipaddr" type="text" class="formfld" id="lanipaddr" size="20" value="<?=htmlspecialchars($pconfig['lanipaddr']);?>">
                    /
                    <select name="lansubnet" class="formfld" id="lansubnet">
                      <?php for ($i = 31; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['lansubnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
            </div>
    </fieldset>
<div class="buttonrow">
        <input type="submit" id="lan_back_btn" value="Back" class="button" />
        <input type="submit" id="lan_next_btn" value="Next" class="button" />
</div>
</span>
<span id="wifi" style="display:none";>
<fieldset>
        <legend><?=join(": ", $pgtitle);?>: WIFI Configuration</legend>
                             <div>
                             <label for="wifienable">Enable Wireless IF</label>
                             <input id="wifienable" type="checkbox" name="wifienable" value="Yes" <?php if ($pconfig['enable']) echo "checked"; ?> />
                        </div>

                             <div>
                                 <label for="wifiifmode">Wireless Mode</label>
                                 <select name="wifiifmode" class="formfld" id="wifiifmode">
                                 <?php $modes = array('lanbridge' => 'Bridge to LAN', 'nobridge' => 'Independant Network');
                                 foreach ($modes as $mode => $modename): ?>
                                     <option value="<?=$mode;?>" <?php if ($mode == $pconfig['ifmode']) echo "selected"; ?>>
                                         <?=htmlspecialchars($modename);?>
                                     </option>
                                 <?php endforeach; ?>
                                 </select>
                             </div>
                        <div id="ipdiv">
                             <label for="wifiipaddr">IP address</label>
                             <input name="wifiipaddr" type="text" class="formfld" id="wifiipaddr" size="20" value="<?=htmlspecialchars($pconfig['wifiipaddr']);?>">
                    /
                    <select name="wifisubnet" class="formfld" id="wifisubnet">
                      <?php for ($i = 31; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['wifisubnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
            </div>
                <div>
                    <label for="ssid">SSID</label>
                    <input name="ssid" type="text" class="formfld" id="ssid" size="20" value="<?=htmlspecialchars($pconfig['ssid']);?>">
                </div>
                <div>
                    <label for="channel">Channel</label>
                    <select name="channel" class="formfld" id="channel">
                      <?php
                      foreach ($wlchannels as $channel): ?>
                              <option <?php if ($channel == $pconfig['channel']) echo "selected";?> value="<?=$channel;?>">
                                  <?=$channel;?>
                              </option>
                          <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="encmode">Encryption</label>
                    <select name="encmode" class="formfld" id="encmode">
                      <?php $modes = array('wpa' => 'WPA', 'open' => 'Open System', 'wep' => 'WEP');
                                foreach ($modes as $mode => $modename): ?>
                                        <option value="<?=$mode;?>" <?php if ($mode == $pconfig['encmode']) echo "selected"; ?>>
                                        <?=htmlspecialchars($modename);?>
                                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="wpadiv">
                    <label for="wpacipher">Preshared Key</label>
                        <input name="wpapsk" type="password" class="formfld" id="wpapsk" size="30" value="<?=htmlspecialchars($pconfig['wpapsk']);?>">
                </div>
    </fieldset>
<div class="buttonrow">
        <input type="submit" id="wifi_back_btn" value="Back" class="button" />
        <input type="submit" id="wifi_next_btn" value="Next" class="button" />
</div>
</span>
<span id="summary" style="display:none";>
<fieldset>
        <legend><?=join(": ", $pgtitle);?>: Welcome To NSWall</legend>
            <div>
                <center><img alt="NSS Logo" class="png" width="50" height="50" src="/images/logo.jpg"/></center><br>
                <center>Your Device is now ready to be configured</center><br>
                <center>The following settings will be applied to your appliance after clicking "Submit"</center><br>
            </div>
        <center><input type="submit" id="wizard_submit_btn" value="Submit" class="button" /></center>
    </fieldset>
<div class="buttonrow">
        <input type="submit" id="summary_back_btn" value="Back" class="button" />
</div>
</span>
</form>
</div>
</div>
</div>

</body>
</html>
