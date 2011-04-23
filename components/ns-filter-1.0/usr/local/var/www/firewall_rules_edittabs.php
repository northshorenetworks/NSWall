#!/bin/php
<?php

$pgtitle = array("Firewall", "Options", "Edit");
require("guiconfig.inc");
include("ns-begin.inc");

$specialsrcdst = explode(" ", "any wanip lan pptp");

if (isset($_POST['rulesetid']) || isset($_GET['rulesetid'])) {

	if (!is_array($config['grouppolicies']['ruleset']))
	$config['grouppolicies']['ruleset'] = array();

	filter_rulesets_sort();

	$a_ruleset = &$config['grouppolicies']['ruleset'];

	$rulesetid = $_GET['rulesetid'];
	if (isset($_POST['rulesetid']))
	$rulesetid = $_POST['rulesetid'];

	if (isset($_POST['after']))
	$after = $_POST['after'];

	$id = $_GET['id'];
	if (is_numeric($_POST['id']))
	$id = $_POST['id'];

	$after = $_GET['after'];

	if (isset($_POST['after']))
	$after = $_POST['after'];

	if (isset($_GET['dup'])) {
		$id = $_GET['dup'];
		$after = $_GET['dup'];
	}

	$a_filter = &$config['grouppolicies']['ruleset'][$rulesetid]['rule'];

} else {
	if (!is_array($config['filter']['rule']))
	$config['filter']['rule'] = array();

	filter_rules_sort();
	$a_filter = &$config['filter']['rule'];

	$id = $_GET['id'];
	if (is_numeric($_POST['id']))
	$id = $_POST['id'];

	$after = $_GET['after'];

	if (isset($_POST['after']))
	$after = $_POST['after'];

	if (isset($_GET['dup'])) {
		$id = $_GET['dup'];
		$after = $_GET['dup'];
	}
}

function is_specialnet($net) {
	global $specialsrcdst;

	if (in_array($net, $specialsrcdst) || (strstr($net, "opt") && !is_alias($net)))
	return true;
	else
	return false;
}

if (isset($_GET['dup']))
unset($id);

if (isset($id) && $a_filter[$id]) {
	$pconfig['name'] = $a_filter[$id]['name'];
	$pconfig['descr'] = $a_filter[$id]['descr'];
	$pconfig['type'] = $a_filter[$id]['type'];
	$pconfig['proto'] = $a_filter[$id]['protocol'];
	$pconfig['interface'] = $a_filter[$id]['interface'];
	$pconfig['srclist'] = $a_filter[$id]['srclist'];
	$pconfig['dstlist'] = $a_filter[$id]['dstlist'];
	$pconfig['dstrelay'] = $a_filter[$id]['dstrelay'];
	$pconfig['portforward'] = isset($a_filter[$id]['portforward']);
	$pconfig['tcplist'] = $a_filter[$id]['tcplist'];
	$pconfig['udplist'] = $a_filter[$id]['udplist'];
	$pconfig['disabled'] = isset($a_filter[$id]['disabled']);
	$pconfig['log'] = isset($a_filter[$id]['log']);
	$pconfig['multiwan'] = $a_filter[$id]['options']['multiwan'];
	$pconfig['altqbucket'] = $a_filter[$id]['options']['altqbucket'];
	$pconfig['altqlowdelay'] = isset($a_filter[$id]['options']['altqlowdelay']);
	$pconfig['state'] = $a_filter[$id]['options']['state'];
	$pconfig['maxstates'] = $a_filter[$id]['options']['maxstates'];
	$pconfig['srctrack'] = $a_filter[$id]['options']['srctrack'];
	$pconfig['maxsrcnodes'] = $a_filter[$id]['options']['maxsrcnodes'];
	$pconfig['maxsrcstates'] = $a_filter[$id]['options']['maxsrcstates'];
	$pconfig['maxsrcconns'] = $a_filter[$id]['options']['maxsrcconns'];
	$pconfig['maxsrcconrateconns'] = $a_filter[$id]['options']['maxsrcconrateconns'];
	$pconfig['maxsrcconrateseconds'] = $a_filter[$id]['options']['maxsrcconrateseconds'];
	$pconfig['overload'] = isset($a_filter[$id]['options']['overload']);
	$pconfig['flush'] = isset($a_filter[$id]['options']['flush']);
} else {
	/* defaults */
	if ($_GET['if'])
	$pconfig['interface'] = $_GET['if'];
	$pconfig['type'] = "pass";
	$pconfig['srclist']['src0'] = "any";
	$pconfig['dstlist']['dst0'] = "any";
}

if (isset($_GET['logmessage'])) {
	$logmessage = base64_decode($_GET['logmessage']);

	preg_match('/in on (\w+): (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\.\d+.+?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\.(\d+):\s(.+)/', $logmessage, $matches);
	$log_if = $matches[1];
	$log_src = $matches[2];
	$log_dst = $matches[3];
	$log_port = $matches[4];
	$log_proto = $matches[5];

	$pconfig['srclist']['src0'] = $log_src;
	$pconfig['dstlist']['dst0'] = $log_dst;

	if(preg_match('/(udp|domain)/', $log_proto))
	$pconfig['udplist']['udp0'] = $log_port;
	if(preg_match('/S/', $log_proto))
	$pconfig['tcplist']['tcp0'] = $log_port;
}

?>

<script type="text/javascript">
// when a user changes the type of memeber, change the related div to sytle = display: block and hide all others
$(function(){
     $("#srctype,#dsttype").change(function() {
          var val = $(this).val();
          switch(val){
        case 'srchostdiv':
            $("#srchostdiv").show();
            $("#srcnetdiv").hide();
                        $("#srcaliasdiv").hide();
                        $("#srcuserdiv").hide();
            break;
        case 'srcnetdiv':
            $("#srcnetdiv").show();
            $("#srchostdiv").hide();
                        $("#srcaliasdiv").hide();
                        $("#srcuserdiv").hide();
            break;
                case 'srcaliasdiv':
            $("#srcaliasdiv").show();
            $("#srchostdiv").hide();
                        $("#srcnetdiv").hide();
                        $("#srcuserdiv").hide();
            break;
                case 'srcuserdiv':
                        $("#srcuserdiv").show();
            $("#srcaliasdiv").hide();
            $("#srchostdiv").hide();
                        $("#srcnetdiv").hide();
                case 'dsthostdiv':
            $("#dsthostdiv").show();
            $("#dstnetdiv").hide();
                        $("#dstaliasdiv").hide();
                        $("#dstsnatdiv").hide();
            break;
        case 'dstnetdiv':
            $("#dstnetdiv").show();
            $("#dsthostdiv").hide();
                        $("#dstaliasdiv").hide();
                        $("#dstsnatdiv").hide();
            break;
                case 'dstaliasdiv':
            $("#dstaliasdiv").show();
            $("#dsthostdiv").hide();
                        $("#dstnetdiv").hide();
                        $("#dstsnatdiv").hide();
            break;
                case 'dstsnatdiv':
                        $("#dstsnatdiv").show();
            $("#dstaliasdiv").hide();
            $("#dsthostdiv").hide();
                        $("#dstnetdiv").hide();
            break;
        }  
     });
}); 

// When a user changes the address family, clear all current addresses
$(function(){
     $("#addressfamily").change(function() {
          var val = $(this).val();
          var answer = confirm ("Are you sure you want to change the address family, doing so will remove all currently configured source and destination addresses?")
          if (answer){
              $("#SRCADDR option").remove();
              $("#DSTADDR option").remove();
              $('#SRCADDR').append('<option value="any">any</option>');
              $('#DSTADDR').append('<option value="any">any</option>');
          }
     });
}); 

// When a user changes the interface to something other than WAN, hide the SNAT destination option
$(function(){
     $("#interface").change(function() {
          var val = $(this).val();
          switch(val){
        case 'wan':
            $("#dsttype option[value=dstsnatdiv]").show();
                        break;
        default:
            $("#dsttype option[value=dstsnatdiv]").hide();
            break;
          }
     });
}); 

// wait for the DOM to be loaded
$(document).ready(function() {
     // When a user changes the interface to something other than WAN, hide the SNAT destination option
     var interface = $("#interface").val();
     switch(interface){
          case 'wan':
        $("#dsttype option[value=dstsnatdiv]").show();
                break;
      default:
        $("#dsttype option[value=dstsnatdiv]").hide();
        break;
     } 

     // When a user clicks on the src host add button, validate and add the host.
     $("#srchostaddbutton").click(function () {
          var ip = $("#srchost");
          if($("#addressfamily").val() == 'inet6') {
              if(test_ipv6(ip.val()) == true) {
                  var firstitem = $("#SRCADDR option:first").text();
                  if(firstitem == "any") {
                      $("#SRCADDR option:first").remove();
                  } 
                  $('#SRCADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
                  ip.val("");
                  return false;
              }
          } else {
              if(verifyIP(ip.val()) == 0) {
                  var firstitem = $("#SRCADDR option:first").text();
                  if(firstitem == "any") {
                      $("#SRCADDR option:first").remove();
                  } 
                  $('#SRCADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
                  ip.val("");
                  return false;
              }
          }
     });

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

     // When a user clicks on the src alias add button, add the selected value.
     $("#srcaliasaddbutton").click(function () {
          var alias = $("#srcalias");
          var firstitem = $("#SRCADDR option:first").text();
                        if(firstitem == "any") {
                            $("#SRCADDR option:first").remove();
                        } 
      $('#SRCADDR').append("<option value='" + alias.val() + "'>" + alias.val() + '</option>');
          return false; 
     });
     
      // When a user clicks on the dst host add button, validate and add the host.
     $("#dsthostaddbutton").click(function () {
          var ip = $("#dsthost");
          if($("#addressfamily").val() == 'inet6') {
              if(test_ipv6(ip.val()) == true) {
                  var firstitem = $("#DSTADDR option:first").text();
                  if(firstitem == "any") {
                      $("#DSTADDR option:first").remove();
                  } 
                  $('#DSTADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
                  ip.val("");
                  return false;
              }
          } else {
              if(verifyIP(ip.val()) == 0) {
                  var firstitem = $("#DSTADDR option:first").text();
                  if(firstitem == "any") {
                      $("#DSTADDR option:first").remove();
                  } 
                  $('#DSTADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
                  ip.val("");
                  return false;
              }
          }
     });

     // When a user clicks on the dst net add button, validate and add the host.
     $("#dstnetaddbutton").click(function () {
          var ip = $("#dstnet");
          var netmask = $("#dstmask");
          if(verifyIP(ip.val()) == 0) {
                        var firstitem = $("#DSTADDR option:first").text();
                        if(firstitem == "any") {
                            $("#DSTADDR option:first").remove();
                        }   
            $('#DSTADDR').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
            ip.val("");
            return false;
          }
     });

       // When a user clicks on the dst alias add button, add the selected value.
     $("#dstaliasaddbutton").click(function () {
          var alias = $("#dstalias");
          var firstitem = $("#DSTADDR option:first").text();
          if(firstitem == "any") {
              $("#DSTADDR option:first").remove();
          } 
      $('#DSTADDR').append("<option value='" + alias.val() + "'>" + alias.val() + '</option>');
          return false; 
     });

     // When a user clicks on the dst snat add button, validate and add the entry.
     $("#dstsnataddbutton").click(function () {
          var extip = $("#snatext");
          var intip = $("#snatint");
          if (extip.val() == 'WAN_IF') {
              if(verifyIP(intip.val()) == 0) {
                  var firstitem = $("#DSTADDR option:first").text();
                  if(firstitem == "any") {
                      $("#DSTADDR option:first").remove();
                  }     
              $('#DSTADDR').append("<option value='" + extip.val() + ":" + intip.val() + "'>"+extip.val() + "->" + intip.val() + '</option>');
                  intip.val("");
                  return false;
              }
          } else if (verifyIP(extip.val()) == 0 && verifyIP(intip.val()) == 0) {
              var firstitem = $("#DSTADDR option:first").text();
              if(firstitem == "any") {
                  $("#DSTADDR option:first").remove();
              }     
          $('#DSTADDR').append("<option value='" + extip.val() + ":" + intip.val() + "'>"+extip.val() + "->" + intip.val() + '</option>');
              extip.val("");
              intip.val("");
              return false;
      }
     });


     // When a user highlights an item and clicks remove, remove it
          $('#srcremove').click(function() {  
              $('#SRCADDR option:selected').remove();  
              var firstitem = $("#SRCADDR option:first").text();
              if(firstitem == "") {
                  $('#SRCADDR').append('<option value="any">any</option>');
              }
              return false;
          });
          $('#dstremove').click(function() {  
              $('#DSTADDR option:selected').remove();  
              var firstitem = $("#DSTADDR option:first").text();
              if(firstitem == "") {
                  $('#DSTADDR').append('<option value="any">any</option>');
              }
              return false;
          });
          $('#protoremove').click(function() {  
              $('#PROTOLIST option:selected').remove();  
              return false;
          });

     // When a user clicks on the proto add button, validate and add the proto.
     $("#protoaddbutton").click(function () {
          var fromport = $("#fromport");
          var toport = $("#toport");
          var rdrport = $("#rdrport");
          var proto = $("#proto");
          if(toport.val() == "") {
             if((validateRange(fromport.val(),0,65536) == 0) && (fromport.val() != '')) {
                 // Case for no port forward, single port rule
                 if ((isNaN(fromport.val()) == false) && (rdrport.val() != '') && (isNaN(rdrport.val()) == false)) { 
                     $('#PROTOLIST').append('<option value="'+proto.val()+'/'+fromport.val()+'->'+rdrport.val()+'">'+proto.val()+'/'+fromport.val()+'->'+rdrport.val()+'</option>');
                     fromport.val("");
                     rdrport.val("");
                 } else if (isNaN(fromport.val()) == false) { 
                     $('#PROTOLIST').append('<option value="'+proto.val()+'/'+fromport.val()+'">'+proto.val()+'/'+fromport.val()+'</option>');
                     fromport.val("");
                 } else {
                     alert('from port value must be a number');
                     return false;
                 }
             }
          } else {
              if (isNaN(fromport.val()) == false && isNaN(toport.val()) == false) {
                  if (fromport.val() > toport.val()) {
                      alert('from port value must be less than to port value');
                      return false;
                  } else {
                     if ((isNaN(fromport.val()) == false) && (isNaN(toport.val()) == false) && (rdrport.val() != '') && (isNaN(rdrport.val()) == false)) { 
                         $('#PROTOLIST').append('<option value="'+proto.val()+'/'+fromport.val()+':'+toport.val()+'->'+rdrport.val()+'">'+proto.val()+'/'+fromport.val()+':'+toport.val()+'->'+rdrport.val()+'</option>');
                         fromport.val("");
                         toport.val("");
                         rdrport.val("");
                     } else if (isNaN(fromport.val()) == false) { 
                         $('#PROTOLIST').append('<option value="'+proto.val()+'/'+fromport.val()+':'+toport.val()+'">'+proto.val()+'/'+fromport.val()+':'+toport.val()+'</option>');
                         fromport.val("");
                         toport.val("");
                         return false;
                      }
                  }
              } else {
                     alert('from port & to port value must be a number');
                     return false;
                 }
          }
     });

     $("#srchost, #srcnet, #dsthost, #dstnet").focus(function() {
        $(this).css({"background-color": "#FFFFCC"});
     });

      $("#srchost, #srcnet, #dsthost, #dstnet").blur(function() {
         value = $(this).val();
         if($("#addressfamily").val() == 'inet6') {
             if (test_ipv6(value) == true)
                 $(this).css({"background-color": "#FFFFFF"});
             else
                 $(this).css({"background-color": "#FFAEAE"});
         } else {
             if (verifyIP(value) == 0)
                 $(this).css({"background-color": "#FFFFFF"});
             else
                 $(this).css({"background-color": "#FFAEAE"});
         }
     });

      $("#srchost, #srcnet, #dsthost, #dstnet").keyup(function() {
         value = $(this).val();
         $(this).css({"background-color": "#FFAEAE"});
         if($("#addressfamily").val() == 'inet6') {
             if (test_ipv6(value) == true)
                $(this).css({"background-color": "#CDFECD"});
         } else {
             if (verifyIP(value) == 0)
                $(this).css({"background-color": "#CDFECD"});
         }

     });

     // When a user clicks on the submit button, post the form.
     $("#submitbutton, #submitbutton2, #submitbutton3").click(function () {
         // Validate all fields
         var maxstates = $("#maxstates");
         if (maxstates.val() != "") {
             if (isNaN(maxstates.val()) == false) {
                 if(validateRange(maxstates.val(),0,1000) != 0) {
                     return false;
                 }
             } else {
                 alert('Max States value must be a number');
                 return false;
             }
         }
         var maxsrcnodes = $("#maxsrcnodes");
         if (maxsrcnodes.val() != "") {
             if (isNaN(maxsrcnodes.val()) == false) {
                 if(validateRange(maxsrcnodes.val(),0,1000) != 0) {
                     return false;
                 }
             } else {
                 alert('Max Source Nodes value must be a number');
                 return false;
             }
         }
         var maxsrcstates = $("#maxsrcstates");
         if (maxsrcstates.val() != "") {
             if (isNaN(maxsrcstates.val()) == false) {
                 if(validateRange(maxsrcstates.val(),0,1000) != 0) {
                     return false;
                 }
             } else {
                 alert('Max Source States value must be a number');
                 return false;
             }
         }
         var maxsrcconns = $("#maxsrcconns");
         if (maxsrcconns.val() != "") {
             if (isNaN(maxsrcconns.val()) == false) {
                 if(validateRange(maxsrcconns.val(),0,1000) != 0) {
                     return false;
                 }
             } else {
                 alert('Max Source Connections value must be a number');
                 return false;
             }
         }
     $("#save_config").html('<center>Saving Configuration File<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
     $(".ui-dialog-titlebar").css('display','block'); 
     $('#save_config').dialog('open');
     var Sources = $.map($('#SRCADDR option'), function(e) { return $(e).val(); } );
     var Destinations = $.map($('#DSTADDR option'), function(e) { return $(e).val(); } );
     var TcpPorts = $.map($('#PROTOLIST option'), function(e) { if ($(e).val().match(/^tcp/)) { return $(e).val().replace(/tcp\//g, ''); } } );
     var UdpPorts = $.map($('#PROTOLIST option'), function(e) { if ($(e).val().match(/^udp/)) { return $(e).val().replace(/udp\//g, ''); } } );
     var IpProtos = $.map($('#PROTOLIST option'), function(e) { if ($(e).val().match(/^ip/)) { return $(e).val().replace(/ip\//g, ''); } } );
     var tcp = TcpPorts.join(' ');
     var udp = UdpPorts.join(' ');
     var ip = IpProtos.join(' ');
     var src = Sources.join(' ');
     var dst = Destinations.join(' ');
     var QueryString = $("#iform").serialize()+'&srclist='+src+'&dstlist='+dst+'&tcpports='+tcp+'&udpports='+udp+'&ipprotos='+ip;
          $.post("forms/firewall_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);
               if(output.match(/SUBMITSUCCESS/))
                   setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                   setTimeout(function(){ $('#content').load('firewall_rules_tabs.php'); }, 1250);
          });
      return false;
     });
  
});
</script>

<script type="text/javascript">
    $(function() {
        $("#firewalloptionstabs").tabs();
    });
</script>
<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">

<div id="firewalloptionstabs">
<ul>
	<li><a href="#tabAddress">Addresses</a></li>
	<li><a href="#tabProtocol">Protocol</a></li>
	<li><a href="#tabOptions">Options</a></li>
</ul>
<form action="forms/firewall_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="firewall_rule">
<input name="id" type="hidden" value="<?=$id;?>">
<div id="tabAddress">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="name">Name</label> <input id="name" type="text"
	name="name" value="<?=htmlspecialchars($pconfig['name']);?>" /></div>
<div><label for="descr">Description</label> <input id="descr"
	type="text" size="50" name="descr"
	value="<?=htmlspecialchars($pconfig['descr']);?>" />
<p class="note">You may enter a description here for your reference (not
parsed).</p>
</div>
<div><label for="interface">Interface</label> <select name="interface"
	id="interface" class="formfld">
	<?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP');
	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
	}
	foreach ($config['vlans']['vlan'] as $vlan) {
		$interfaces['vlan' . $vlan['tag']] = "VLAN{$vlan['tag']}";
	}
	foreach ($interfaces as $iface => $ifacename): ?>
	<option value="<?=$iface;?>"
	<?php if ($iface == $pconfig['interface']) echo "selected"; ?>><?=htmlspecialchars($ifacename);?>
	</option>
	<?php endforeach; ?>
</select>
<p class="note">Choose on which interface packets must come in to match
this rule.</p>
</div>
<div><label for="action">Action</label> <select name="type"
	class="formfld">
	<?php $types = explode(" ", "Pass Block Reject"); foreach ($types as $type): ?>
	<option value="<?=strtolower($type);?>"
	<?php if (strtolower($type) == strtolower($pconfig['type'])) echo "selected"; ?>>
		<?=htmlspecialchars($type);?></option>
		<?php endforeach; ?>
</select>
<p class="note"></p>
</div>
<div><label for="addressfamily">Address Family</label> <select name="addressfamily" id="addressfamily"
	class="formfld">
	<?php $types = explode(" ", "inet inet6"); foreach ($types as $type): ?>
	<option value="<?=strtolower($type);?>"
	<?php if (strtolower($type) == strtolower($pconfig['addressfamily'])) echo "selected"; ?>>
		<?=htmlspecialchars($type);?></option>
		<?php endforeach; ?>
</select>
<p class="note"></p>
</div>
<div><label for="SRCADDR">Source Addresses</label> <select
	name="SRCADDR" style="width: 300px; height: 100px" id="SRCADDR"
	multiple>
	<?php for ($i = 0; $i<sizeof($pconfig['srclist']); $i++): ?>
	<option value="<?=$pconfig['srclist']["src$i"];?>"><?php $display = preg_replace('/user:|:user/', '', $pconfig['srclist']["src$i"]);?><?=$display;?></option>
	<?php endfor; ?>
</select> <input type=button id='srcremove' value='Remove Selected'><br>
<br>
<label for="srctype">Type</label> <select name="srctype" class="formfld"
	id="srctype">
	<option value="srchostdiv" selected>Host</option>
	<option value="srcnetdiv">Network</option>
	<option value="srcaliasdiv">Alias</option>
	<option value="srcuserdiv">User</option>
</select></div>
<div id='srchostdiv' style="display: block;"><label for="srchost">Address</label>
	<?=$mandfldhtml;?><input name="srchost" type="text" class="formfld"
	id="srchost" size="16"
	value="<?=htmlspecialchars($pconfig['address']);?>"> <input type=button
	id='srchostaddbutton' value='Add'></div>
<div id='srcnetdiv' style="display: none;"><label for="srcnet">Network
Address</label> <?=$mandfldhtml;?><input name="srcnet" type="text"
	class="formfld" id="srcnet" size="16"
	value="<?=htmlspecialchars($pconfig['address']);?>"> <strong>/</strong>
<select name="srcmask" class="formfld" id="srcmask">
<?php for ($i = 30; $i >= 1; $i--): ?>
	<option value="<?=$i;?>"
	<?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>><?=$i;?>
	</option>
	<?php endfor; ?>
</select> <input type=button id='srcnetaddbutton' value='Add'></div>
<div id='srcaliasdiv' style="display: none;"><label for="srcalias">Alias</label>
<select name="srcalias" class="formfld" id="srcalias">
<?php
$defaults = filter_system_aliases_names_generate();
$defaults = split(' ', $defaults);
foreach( $defaults as $i): ?>
	<option value="<?='$' . $i;?>"><?=$i;?></option>
	<?php endforeach; ?>
	<?php foreach($config['aliases']['alias'] as $i): ?>
	<option value="<?='$' . $i['name'];?>"
	<?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>><?=$i['name'];?>
	</option>
	<?php endforeach; ?>
</select> <input type=button id='srcaliasaddbutton' value='Add'></div>
<div id='srcuserdiv' style="display: none;"><label for="srcuser">User</label>
<select name="srcuser" class="formfld" id="srcuser">
<?php foreach($config['system']['accounts']['user'] as $i): ?>
	<option value="<?=$i['name'];?>"><?=$i['name'];?></option>
	<?php endforeach; ?>
</select> <input type=button
	onClick="addOption('SRCADDR',document.iform.srcuser.value + '/32','user:' + document.iform.srcuser.value + ':user')"
	; value='Add'></div>

<div><label for="DSTADDR">Destination Addresses</label> <select
	name="DSTADDR" style="width: 300px; height: 100px" id="DSTADDR"
	multiple>
	<?php for ($i = 0; $i<sizeof($pconfig['dstlist']); $i++): ?>
	<option value="<?=$pconfig['dstlist']["dst$i"];?>"><?=preg_replace('/:/', '->', $pconfig['dstlist']["dst$i"]);?></option>
	<?php endfor; ?>
</select> <input type=button id='dstremove' value='Remove Selected'><br>
<br>
<label for="dsttype">Type</label> <select name="dsttype" class="formfld"
	id="dsttype">
	<option value="dsthostdiv" selected>Host</option>
	<option value="dstnetdiv">Network</option>
	<option value="dstaliasdiv">Alias</option>
	<option value="dstsnatdiv">SNAT</option>
</select></div>
<div id='dsthostdiv' style="display: block;"><label for="dsthost">Address</label>
<input name="dsthost" type="text" class="formfld" id="dsthost" size="16"
	value="<?=htmlspecialchars($pconfig['address']);?>"> <input type=button
	id='dsthostaddbutton' value='Add'></div>
<div id='dstnetdiv' style="display: none;"><label for="dstnet">Network
Address</label> <input name="dstnet" type="text" class="formfld"
	id="dstnet" size="16"
	value="<?=htmlspecialchars($pconfig['address']);?>"> <strong>/</strong>
<select name="dstmask" class="formfld" id="dstmask">
<?php for ($i = 30; $i >= 1; $i--): ?>
	<option value="<?=$i;?>"
	<?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>><?=$i;?>
	</option>
	<?php endfor; ?>
</select> <input type=button id='dstnetaddbutton' value='Add'></div>
<div id='dstaliasdiv' style="display: none;"><label for="dstalias">Alias</label>
<select name="dstalias" class="formfld" id="dstalias">
<?php
$defaults = filter_system_aliases_names_generate();
$defaults = split(' ', $defaults);
foreach( $defaults as $i): ?>
	<option value="<?='$' . $i;?>"><?=$i;?></option>
	<?php endforeach; ?>
	<?php foreach($config['aliases']['alias'] as $i): ?>
	<option value="<?='$' . $i['name'];?>"
	<?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>><?=$i['name'];?>
	</option>
	<?php endforeach; ?>
</select> <input type=button id='dstaliasaddbutton' value='Add'></div>

	<?php if ($config['interfaces']['wan']['ipaddr'] == 'dhcp'): ?>
<div id='dstsnatdiv' style="display: none;"><label for="snat">SNAT</label>
<strong>External</strong> <input name="snatext" disabled class="formfld"
	id="snatext" size="16" value="WAN_IF"> <strong>Internal</strong> <?=$mandfldhtml;?><input
	name="snatint" type="text" class="formfld" id="snatint" size="16"
	value=""> <input type=button id='dstsnataddbutton' value='Add'></div>
	<?php else: ?>
<div id='dstsnatdiv' style="display: none;"><label for="snat">SNAT</label>
<strong>External</strong> <?=$mandfldhtml;?><input name="snatext"
	class="formfld" id="snatext" size="16" value=""> <strong>Internal</strong>
	<?=$mandfldhtml;?><input name="snatint" type="text" class="formfld"
	id="snatint" size="16" value=""> <input type=button
	id='dstsnataddbutton' value='Add'> <?php endif; ?></div>
</fieldset>
<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Save" class="button" /></div>
</div>
<div id="tabProtocol">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="PROTOLIST">Protocol List</label> <select
	name="PROTOLIST" style="width: 150px; height: 100px" id="PROTOLIST"
	multiple>
	<?php for ($i = 0; $i<sizeof($pconfig['tcplist']); $i++): ?>
	<option value="tcp/<?=$pconfig['tcplist']["tcp$i"];?>">tcp/<?=$pconfig['tcplist']["tcp$i"];?>
	</option>
	<?php endfor; ?>
	<?php for ($i = 0; $i<sizeof($pconfig['udplist']); $i++): ?>
	<option value="udp/<?=$pconfig['udplist']["udp$i"];?>">udp/<?=$pconfig['udplist']["udp$i"];?>
	</option>
	<?php endfor; ?>
	<?php for ($i = 0; $i<sizeof($pconfig['ipprotolist']); $i++): ?>
	<option value="ip/<?=$pconfig['ipprotolist']["ip$i"];?>">ip/<?=$pconfig['ipprotolist']["ip$i"];?>
	</option>
	<?php endfor; ?>
</select> <input type=button id="protoremove" value='Remove Selected'><br>
<br>
</div>
<div><label for="proto">Add Protocol</label> <select name="proto"
	class="formfld" id="proto">
	<option value="tcp" selected>TCP</option>
	<option value="udp">UDP</option>
	<option value="ip">IP</option>
</select> <input name="fromport" type="text" class="formfld"
	id="fromport" size="5" value=""> <strong> To </strong> <input
	name="toport" type="text" class="formfld" id="toport" size="5" value="">
<input type=button id="protoaddbutton" value='Add'></div>
<div><label for="proto">Port Forward</label> <input name="rdrport"
	type="text" class="formfld" id="rdrport" size="5" value=""></div>
<div id='srctablediv' style="display: none;"><label for="srctable">Alias</label>
<select name="srctable" class="formfld" id="srctable">
<?php foreach($config['tablees']['table'] as $i): ?>
	<option value="<?='$' . $i['name'];?>"
	<?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>><?=$i['name'];?>
	</option>
	<?php endforeach; ?>
</select> <input type=button value='Add'></div>
<div id='srcuser' style="display: none;"><strong>User</strong> <select
	name="srcuser" class="formfld" id="srcuser">
	<?php foreach($config['system']['accounts']['user'] as $i): ?>
	<option value="<?=$i['name'];?>"><?=$i['name'];?></option>
	<?php endforeach; ?>
</select> <input type=button value='Add'></div>
</fieldset>
<div class="buttonrow"><input type="submit" id="submitbutton2"
	value="Save" class="button" /></div>
</div>
<div id="tabOptions">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="log">Log</label> <input name="log" type="checkbox"
	id="log" value="yes" <?php if ($pconfig['log']) echo "checked"; ?>>
<p class="note">Log packets that are handled by this rule</p>
</div>
	<?php $wanifs = filter_generate_multiwan_interfaces(); ?>
<div><label for="multiwan">Multiwan</label> <select name="multiwan"
	class="formfld" id="multiwan">
	<option value="roundrobin"
	<?php if ('roundrobin' == $pconfig['multiwan']) echo "selected"; ?>>Roundrobin
	(default)</option>
	<?php foreach ($wanifs as $multiwanif): ?>
	<option value="<?=get_interface_descr($multiwanif);?>"
	<?php if (strtoupper(get_interface_descr($multiwanif)) == strtoupper($pconfig['multiwan'])) echo "selected"; ?>>
		<?=htmlspecialchars(strtoupper(get_interface_descr($multiwanif)));?></option>
		<?php endforeach; ?>
</select>
<p class="note">Assign packets handled by this rule to a specific WAN
connection
<p class="note"></p>

</div>
<div><label for="altqbucket">ALTQ</label> <select name="altqbucket"
	class="formfld" id="altqbucket">
	<?php foreach ($altqbuckets as $bucket): ?>
	<option value="<?=$bucket;?>"
	<?php if ($bucket == $pconfig['altqbucket']) echo "selected"; ?>><?=htmlspecialchars($bucket);?>
	</option>
	<?php endforeach; ?>
</select>
<p class="note">Add packetes handled by this rule to ALTQ Bucket
<p class="note"></p>

</div>
<div><label for="log">ALTQ Lowdelay</label> <input name="altqlowdelay"
	type="checkbox" id="altqlowdelay" value="yes"
	<?php if ($pconfig['altqlowdelay']) echo "checked"; ?>></div>
<div><label for="state">State</label> <select name="state"
	class="formfld" id="state">
	<?php foreach ($statetypes as $statetype): ?>
	<option value="<?=$statetype;?>"
	<?php if ($statetype == $pconfig['state']) echo "selected"; ?>><?=htmlspecialchars($statetype);?>
	</option>
	<?php endforeach; ?>
</select></div>
<div><label for="maxstates">Max States</label> <input name="maxstates"
	type="text" class="formfld" id="maxstates" size="5"
	value="<?=htmlspecialchars($pconfig['maxstates']);?>">
<p class="note">Limits the number of concurrent states the rule may
create. When this limit is reached, further packets that would create
state will not match this rule until existing states time out.</p>
</div>
<div><label for="srctrack">Source Tracking</label> <select
	name="srctrack" class="formfld" id="srctrack">
	<?php foreach ($srctracktypes as $srctracktype): ?>
	<option value="<?=$srctracktype;?>"
	<?php if ($srctracktype == $pconfig['srctrack']) echo "selected"; ?>>
		<?=htmlspecialchars($srctracktype);?></option>
		<?php endforeach; ?>
</select></div>
<div><label for="maxsrcnodes">Max Src Nodes</label> <input
	name="maxsrcnodes" type="text" class="formfld" id="maxsrcnodes"
	size="5" value="<?=htmlspecialchars($pconfig['maxsrcnodes']);?>">
<p class="note">Limits the maximum number of source addresses which can
simultaneously have state table entries.</p>
</div>
<div><label for="maxsrcstates">Max Src States</label> <input
	name="maxsrcstates" type="text" class="formfld" id="maxsrcstates"
	size="5" value="<?=htmlspecialchars($pconfig['maxsrcstates']);?>">
<p class="note">Limits the maximum number of simultaneous state entries
that a single source address can create with this rule.</p>
</div>
<div><label for="maxsrcconns">Max Src Conns</label> <input
	name="maxsrcconns" type="text" class="formfld" id="maxsrcconns"
	size="5" value="<?=htmlspecialchars($pconfig['maxsrcconns']);?>">
<p class="note">Limits the maximum number of simultaneous TCP
connections which have completed the 3-way handshake that a single host
can make.</p>
</div>
<div><label for="maxsrcconrateconns">Max Src Conn Rate</label> <input
	name="maxsrcconrateconns" type="text" class="formfld"
	id="maxsrcconrateconns" size="5"
	value="<?=htmlspecialchars($pconfig['maxsrcconrateconns']);?>">
connections, every <input name="maxsrcconrateseconds" type="text"
	class="formfld" id="maxsrcconrateseconds" size="5"
	value="<?=htmlspecialchars($pconfig['maxsrcconrateseconds']);?>">
seconds
<p class="note">Limit the rate of new connections over a time interval.
The connection rate is an approximation calculated as a moving average.</p>
</div>
</fieldset>
<div class="buttonrow"><input type="submit" id="submitbutton3"
	value="Save" class="button" /></div>

</form>
</div>
</div>
</div>
</div>
