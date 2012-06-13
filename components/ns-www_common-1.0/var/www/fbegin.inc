<?php
/*
 $Id: fbegin.inc,v 1.16 2009/04/20 06:52:10 jrecords Exp $
 part of m0n0wall (http://m0n0.ch/wall)
 Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
 All rights reserved.

 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:

 1. Redistributions of source code must retain the above copyright notice,
 this list of conditions and the following disclaimer.

 2. Redistributions in binary form must reproduce the above copyright
 notice, this list of conditions and the following disclaimer in the
 documentation and/or other materials provided with the distribution.

 THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 POSSIBILITY OF SUCH DAMAGE.
 */
/* make sure the user is an admin */
require_once("adminOnly.php");

$navlevelsep = ": "; /* navigation level separator string */
$mandfldhtml = ""; /* display this before mandatory input fields */
$mandfldhtmlspc = ""; /* same as above, but with spacing */

function gentitle($title) {
	global $navlevelsep;
	return join($navlevelsep, $title);
}

function genhtmltitle($title) {
	global $allowed, $config;
	return $config['system']['hostname'] . "." . $config['system']['general']['domain'] . " - " . gentitle($title);
}

function dynamic_menu(&$menu, $menu_title) {
	global $config;

	// Make sure the admin can see everything
	unset($allowed);
	foreach ($menu as $item) {
		$allowed[] = $item[0];
	}

	foreach ($menu as $desc => $links) {
		if ($intersect = array_intersect($menu[$desc],$allowed)) {
			if (!isset($header_displayed)) {
				echo "\n <strong>".$menu_title.'</strong><br>';
				$header_displayed = true;
			}
			else {
				if (!isset($header_displayed)) {
				}
			}
			// $intersect may contain one or more links, but we only need the first one
			// BUT, we are doing a foreach here because it might not be element [0]
			foreach ($intersect as $link) {
				// Below lines are spaced out oddly to help improve the look of resulting HTML code.
				?>

<li><a href="<?=$link?>" class="navlnk"><?=$desc?></a></li>
				<?
				// Only need the first one, so break out of foreach
				break;
			}
		}
	}
}

$menu['System']['General setup'] = array('#system_general');
$menu['System']['Routing'] = array('#system_routes_tabs');
$menu['System']['License'] = array('#system_license');
$menu['System']['Firmware'] = array('#system_firmware');
$menu['System']['Components'] = array('#system_components');
$menu['System']['Advanced'] = array('#system_advanced');
$menu['System']['Networking'] = array('#system_networking');
$menu['System']['Manage Certificates'] = array('#system_cacert_tabs');
$menu['System']['User manager'] = array('#system_accounts_tabs');

$menu['Firewall']['Abusive Sites'] = array('#firewall_abusive_sites');
$menu['Firewall']['Aliases'] = array('#firewall_aliases_tabs');
$menu['Firewall']['Global Options'] = array('#firewall_options_edit');
$menu['Firewall']['NAT'] = array('#firewall_nat_tabs');
$menu['Firewall']['Rules'] = array('#firewall_rules_tabs');

if ($g['module'] == 'FIREWALL') {
	$menu['Services']['DNS forwarder'] = array('#services_dnsmasq');
	$menu['Services']['Dynamic DNS'] = array('#services_dyndns');
}
if (file_exists('/usr/local/var/www/services_snmp.php')) {
	$menu['Services']['SNMP Daemon'] = array('#services_snmp');
}
$menu['Services']['CARP'] = array('#services_carp');
$menu['Services']['OpenBGPd'] = array('#services_bgpd');
$menu['Services']['Config Server'] = array('#services_config_server');
// $menu['Services']['Relayd'] = array('#firewall_relayd_relays');

if ($g['module'] == 'FIREWALL') {
	$menu['VPN']['IPSec'] = array('#vpn_ipsec_tabs');
	$menu['VPN']['PPTP Client'] = array('#vpn_pptp_client');
	$menu['VPN']['PPTP Server'] = array('#vpn_pptp_server');
}

$menu['Status']['System'] = array('#status_system');
$menu['Status']['Traffic graph'] = array('#status_graph');
$menu['Status']['CPU graph'] = array('#status_graph_cpu');
$menu['Status']['PF Traffic graph'] = array('#status_graph_rule');
$menu['Status']['PF PPS graph'] = array('#status_graph_pps');
$menu['Status']['PF States graph'] = array('3status_graph_states');

$menu['Diagnostics']['PF'] = array('#diag_pf');
$menu['Diagnostics']['System'] = array ('#diag_sys');
$menu['Diagnostics']['Logs'] = array ('#diag_logs');
$menu['Diagnostics']['VPN'] = array('#diag_vpn');
$menu['Diagnostics']['BGPd'] = array('#diag_bgp');
$menu['Diagnostics']['Utilities'] = array('#diag_util');

$menu['Support']['Submit Debug'] = array ('#support_case');

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title><?=genhtmltitle($pgtitle);?></title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/images/logo.jpg" width="100px" height="50px"
	type="image/x-icon" rel="shortcut icon" />
<link href="gui.css" rel="stylesheet" type="text/css">
<link type="text/css" href="style/jquery-ui-1.7.2.custom.css"
	rel="stylesheet" />
<link type="text/css" href="style/jquery.contextmenu.css"
	rel="stylesheet" />

</head>
<script src="js/jquery-1.3.2.min.js"></script>
<script src="js/nss.js"></script>
<script type="text/javascript" src="js/jquery.form.js"></script>
<script type="text/javascript" src="js/ui.core.js"></script>
<script type="text/javascript" src="js/ui.tabs.js"></script>
<script type="text/javascript" src="js/ui.resizable.js"></script>
<script type="text/javascript" src="js/ui.dialog.js"></script>
<script type="text/javascript" src="js/ui.draggable.js"></script>
<script type="text/javascript" src="js/ui.droppable.js"></script>
<script type="text/javascript" src="js/ui.progressbar.js"></script>
<script type="text/javascript" src="js/ui.sortable.js"></script>
<script type="text/javascript" src="js/swfobject.js"></script>
<script type="text/javascript" src="js/jquery.uploadify.v2.1.0.min.js"></script>
<script type="text/javascript" src="js/jquery.validate.js"></script>
<script type="text/javascript" src="js/jquery.contextmenu.js"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="../excanvas.min.js"></script><![endif]-->
<script type="text/javascript" src="js/jquery.flot.js"></script>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<div id="webui">

<div id="support_diag" title="NSWall Support"></div>
<div id="support_login" title="NSWall Support">
<body>

<div id="login_nswall" title="NSWall Login">
<p>Welcome to Northshore software's NSWall Firewall/VPN appliance.<br>
</p>
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-all">
<form id="supportlogin" method="post" name="login">
<fieldset><legend>Support : Login</legend>
<div><label for="username">Username1</label> <input type="text"
	size="10" name="username"></div>
<div><label for="password">Password</label> <input type="password"
	size="10" name="password"></div>
</fieldset>
<div class="buttonrow">
<center><input type="submit" id="support_login_button" value="Login"
	class="button" /></center>
</div>

</form>

</div>
</div>
</div>

</body>

</html>
</center>
</div>

<div id="save_config"
	title="Saving Configuration"></div>

<div id="upload_firmware" title="Uploading Firmware Image">
<center>Uploading Firmware Image<br>
This may take a moment...<br>
<br>
<img src="images/ajax-loader.gif" height="25" width="25" name="spinner">
</center>
</div>

<div id="reboot_nswall" title="Rebooting NSWall">
<center>Rebooting NSWall Device...<br>
You will be redirected to the login page when the device is back up...<br>
<br>
<img src="images/ajax-loader.gif" height="25" width="25 name="spinner">
</center>
</div>

<div id="load_content">
<center>Loading Content...<br>
<br>
<br>
<img src="images/ajax-loader.gif" height="25" width="25" name="spinner">
</center>
</div>

<div class="ui-widget-content" id="header">
<div id="logout"><a href="logout.php">Log out</a></div>
<div id="deviceid">
<div style=""><label>Hostname:</label><a target="contentframe"
	title="<?php readfile("/etc/version"); ?>" href="system.php"><?php echo $config['system']['hostname'] . "." . $config['system']['general']['domain']; ?></a></div>
<div style=""><label>IP Address:</label> <?php if (!$pgtitle_omit): ?> <?php echo $config['interfaces']['lan']['ipaddr']; ?>
<?php else: ?> <?php echo get_current_wan_address(); ?> <?php endif; ?>

</div>
</div>

<div id="uptimeload"></div>

<div id="userinfo">
<div style=""><label>User:</label><a target="contentframe"
	href="/tmui/Control/jspmap/tmui/system/user/properties.jsp?name=admin">admin</a></div>
<div style=""><label>Last Config Change:</label><?=htmlspecialchars(date("D M j G:i:s T Y", $config['lastchange']));?></a></div>
</div>
</div>

<div class="ui-widget-header" id="banner">
<div id="logo"><a
	onclick="return loadContent(path_startPage ? path_startPage : $('#mainpanel div.module ul a:first').attr('href'));"
	href="http://www.northshoresoftware.com"><img alt="NSS Logo"
	title="Click to return to the start page" class="png" width="50"
	height="50" src="/images/logo.jpg" /></a></div>
<div id="alert"></div>
</div>

<div class="ui-widget-header ui-corner-bottom left"
	style="list-style: none;" id="panels"><?php
	echo '<div id="systempanel" class="ui-widget-header panel"><img src="/images/system.png" id="system_img" border="0"></a><strong>System</strong><br>
<span id="system" style="display: none">';
	dynamic_menu($menu['System'], '');
	echo '</span></div>';

	echo '<div id="ifpanel" class="ui-widget-header panel"><img src="/images/interface.png" id="if_img" border="0"></a><strong>Interfaces</strong><br>
<span id="if" style="display: none">';

	// The Interfaces menu is different than the rest because of the Assign link and
	// The dynamic nature of the optional interfaces.
	$if_menu = array('(assign)' => '3interfaces_assign',
     'LAN' => 'interfaces_lan.php',
'WAN' => 'interfaces_wan.php',
'OPTx' => 'interfaces_opt.php');

	$allowed = $if_menu;
	if (array_intersect($if_menu,$allowed)) {
		/*if ((!isset($config['system']['webgui']['noassigninterfaces'])) &&
		 (in_array('interfaces_assign.php',$allowed))) {
		 echo '<a href="/interfaces_assign.php" class="navlnks"> (assign)</a>';
		 }*/
		echo '<br>';
		if (in_array('interfaces_wan.php',$allowed)) {
			echo '<li><a href="#interfaces_wan" class="navlnk">WAN</a></li>';
		}
		if ($g['module'] == 'FIREWALL') {
			if (in_array('interfaces_lan.php',$allowed)) {
				echo '<li><a href="#interfaces_lan" class="navlnk">LAN</a></li>';
			}
			if (file_exists("{$g['etc_path']}/hwplatform")) {
				$g['hwplatform'] = chop(file_get_contents("{$g['etc_path']}/hwplatform"));
				$pla = explode("_", $g['hwplatform']);
				$g['hwplatform'] = $pla[0];
			}
			$hwplatformconfig = parse_nonconfig_xml("{$g['etc_path']}/hwplatformconfig.xml");
			$ifs = $hwplatformconfig[strtoupper($g['hwplatform'])][INTERFACES];
			$wanif = array_shift($ifs);
			$lanif = array_shift($ifs);
			for ($i = 1; isset($ifs['OPT' . $i]); $i++) {
				echo '<li><a href="#interfaces_opt_index_' .$i.
                '" class="navlnk">'.htmlspecialchars($ifs['OPT' . $i]['DESCR'])
				.'</a></li>';
			}
		}
		echo '<li><a href="#interfaces_vlan_tabs" class="navlnk">VLANs</a></li>';
		echo '<li><a href="#interfaces_trunks_tabs" class="navlnk">Trunks</a></li>';
	}
	echo '</span></div>';

	echo '<div id="firewallpanel" class="ui-widget-header panel"><img src="/images/firewall.png" id="firewall_img" border="0"></a><strong>Firewall</strong><br>
<span id="firewall" style="display: none">';
	dynamic_menu($menu['Firewall'], '');
	echo '</span></div>';


	echo '<div id="servicespanel" class="ui-widget-header panel"><img src="/images/service.png" id="services_img" border="0"></a><strong>Services</strong><br>
<span id="services" style="display: none">';
	dynamic_menu($menu['Services'], '');
	echo '</span></div>';

	if ($g['module'] == 'FIREWALL') {
		echo '<div id="vpnpanel" class="ui-widget-header panel"><img src="/images/vpn.png" id="vpn_img" border="0"></a><strong>VPN</strong><br>
<span id="vpn" style="display: none">';
		dynamic_menu($menu['VPN'], '');
		echo '</span></div>';
	}

	echo '<div id="statuspanel" class="ui-widget-header panel"><img src="/images/status.png" id="status_img" border="0"></a><strong>Status</strong><br>
<span id="statusmenu" style="display: none">';
	dynamic_menu($menu['Status'], '');
	echo '</span></div>';

	echo '<div id="diagpanel" class="ui-widget-header panel"><img src="/images/diagnostics.png" id="diagnostics_img" border="0"></a><strong>Diagnostics</strong><br>
<span id="diagnostics" style="display: none">';
	dynamic_menu($menu['Diagnostics'], '');
	echo '</span></div>';

	echo '<div id="supportpanel" class="ui-widget-header panel"><img src="/images/support.png" id="support_img" border="0"></a><strong>Support</strong><br>
<span id="support" style="display: none">';
	dynamic_menu($menu['Support'], '');
	echo '<a class="navlnk" href="http://www.onlinechatcenters.com/chat/?id=13692&amp;dep=17438" target="_blank">Live Chat</a>';
	echo '</span></div>';

	?></div>
<script language="javascript">
<!--

// Check if the box has been registered, if it hasn't dislay an alert
<?php if (file_exists("/conf/unregistered")): ?>
$("#alert").html('This device has not been registerd, please register it by clicking <a href="support_registration.php">here</a>');
$("#alert").show();
<?php endif; ?>

var refreshId = "";

function updateUptimeLoad () {
          $('#uptimeload').load('uptimeload.php?top' + "&random=" + Math.random());
}

$('#panels li a').click(function(){  
     $('#load_content').dialog('open'); 
     var toLoad = $(this).attr('href').substr(1)+'.php'; 
     // special case to handle opt interfaces
     if( toLoad.match(/\_index\_/) ) {
         index = toLoad.replace(/interfaces\_opt\_index\_/,'');
         index = index.replace(/.php/,'');
         hash = toLoad.replace(/\_index\_.+/,'');
         var toLoad = hash+'.php?index='+index;
     }    
     window.location.hash = $(this).attr('href');  
     clearInterval(refreshId);
     $("#content").load(toLoad);
     return false;  
});  

$('#alert a').click(function(){
     $('#load_content').dialog('open');
     var toLoad = $(this).attr('href');
     window.location.hash = $(this).attr('href').substr(0,$(this).attr('href').length-4);
     clearInterval(refreshId);
     $("#content").load(toLoad);
     return false;
});

$(document).ready(function() {         
      // Check for hash value in URL  
      if (window.location.hash) {
           var hash = window.location.hash.substr(1);   
           if (hash == 'index') {
                var toLoad = 'status_system.php';
           } else { 
                if( hash.match(/\_tab\_/) ) {
                    hash = hash.replace(/\_tab\_.+/,'');
                    var toLoad = hash+'.php';
                } 
                // handle optional interfaces
                if( hash.match(/\_index\_/) ) {
                    index = hash.replace(/interfaces\_opt\_index\_/,'');
                    hash = hash.replace(/\_index\_.+/,'');
                    var toLoad = hash+'.php?index='+index;
                }   
           }
           clearInterval(refreshId);
           $('#content').load(toLoad);
           updateUptimeLoad()
           var displayLoadUptime = setInterval("updateUptimeLoad()", 60000);
      } else {
           var toLoad = 'status_system.php';
           clearInterval(refreshId);
           $('#content').load(toLoad);
           updateUptimeLoad();
           var displayLoadUptime = setInterval("updateUptimeLoad()", 60000);
      }  
      //Initialize all of our dialogs
    $.ui.dialog.defaults.bgiframe = true;

        // Support Login dialog, displayed whenever a config change is written
        $("#support_login").dialog({ 
            autoOpen: false, 
            width: 640, 
            height: 405, 
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

        // Support dialog, displayed whenever a config change is written
        $("#support_diag").dialog({ 
            autoOpen: false, 
            width: 650, 
            height: 405, 
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
    
        // Upload Firmware dialog, displayed when uploading a new firmware image to the device
        $("#upload_firmware").dialog({ 
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
    
        // Reboot Nswall dialog, displayed when rebooting device
        $("#reboot_nswall").dialog({ 
            autoOpen: false, 
            width: 650, 
            height: 405, 
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
        
        // Load Content dialog, this is displayed when moving between pages
        $("#load_content").dialog({  
            //width: 150, 
            //height: 15, 
            resizable: false, 
            draggable: false, 
            closeOnEscape: false,
            open: function(event, ui) {
                $(".ui-dialog-titlebar").hide();
            }
        });

        // Make all nav panels clickable

         $("#systempanel").click(function () {
             showhide('system','system_img');
         });
         $("#ifpanel").click(function () {
             showhide('if','if_img');
         });
         $("#firewallpanel").click(function () {
             showhide('firewall','firewall_img');
         });
         $("#servicespanel").click(function () {
             showhide('services','sercices_img');
         });
         $("#vpnpanel").click(function () {
             showhide('vpn','vpn_img');
         });
         $("#statuspanel").click(function () {
             showhide('statusmenu','status_img');
         });
         $("#diagpanel").click(function () {
             showhide('diagnostics','diagnostics_img');
         });  
         $("#supportpanel").click(function () {
             showhide('support','support_img');
         });

});

function showhide(tspan, tri) {
    tspanel = document.getElementById(tspan);
    if (tspanel.style.display == 'none') {
        tspanel.style.display = '';
    } else {
        tspanel.style.display = 'none';
    }
}

-->
</script>
<div
	id="content"
	style="margin-top: .5em; margin-left: 18.5em; margin-right: 2.5em"></div>

