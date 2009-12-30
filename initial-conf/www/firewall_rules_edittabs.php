#!/bin/php
<?php 

$pgtitle = array("Firewall", "Options", "Edit");
require("guiconfig.inc");

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
 
if ($_POST) {
 
unset($input_errors);
$pconfig = $_POST;
 
/* input validation */
$reqdfields = explode(" ", "type interface srclist dstlist");
$reqdfieldsn = explode(",", "Type,Interface,Source,Destination");
 
do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
 
$filterent = array();
$filterent['name'] = $_POST['name'];
$filterent['descr'] = $_POST['descr'];
  $filterent['type'] = $_POST['type'];
$filterent['interface'] = $_POST['interface'];
if ($_POST['rdrlist']) {
$filterent['rdrlist'] = $_POST['rdrlist'];
}
$srclist = array_reverse(explode(',', $_POST['srclist']));
for($i=0;$i<sizeof($srclist); $i++) {
$member = 'src'."$i";
$source = preg_replace("/ /", "", $srclist[$i]);
$filterent['srclist'][$member] = $source;
}
$dstlist = array_reverse(explode(',', $_POST['dstlist']));
                for($i=0;$i<sizeof($dstlist); $i++) {
                        $member = 'dst'."$i";
                        $dest = preg_replace("/ /", "", $dstlist[$i]);
                        $filterent['dstlist'][$member] = $dest;
                }
if ($_POST['tcpports']) {
                 $tcplist = array_reverse(explode(',', $_POST['tcpports']));
for($i=0;$i<sizeof($tcplist); $i++) {
                         $member = 'tcp'."$i";
                         $tcp = preg_replace("/ /", "", $tcplist[$i]);
                         $filterent['tcplist'][$member] = $tcp;
                 }
}
if ($_POST['udpports']) {
                        $udplist = array_reverse(explode(',', $_POST['udpports']));
for($i=0;$i<sizeof($udplist); $i++) {
                                $member = 'udp'."$i";
                                $udp = preg_replace("/ /", "", $udplist[$i]);
                                $filterent['udplist'][$member] = $udp;
                        }
                }
                if ($_POST['ipprotos']) {
                        $ipprotolist = array_reverse(explode(',', $_POST['ipprotos']));
for($i=0;$i<sizeof($ipprotolist); $i++) {
                                $member = 'ip'."$i";
                                $ip = preg_replace("/ /", "", $ipprotolist[$i]);
                                $filterent['ipprotolist'][$member] = $ip;
                        }
                }
$filterent['disabled'] = $_POST['disabled'] ? true : false;
if ($_POST['portforward']) {
$filterent['portforward'] = $_POST['portforward'] ? true : false;
$filterent['dstrelay'] = $_POST['dstrelay'];
}
$filterent['log'] = $_POST['log'] ? true : false;
/* options stuff */
                if ($_POST['altqbucket']) {
                        $filterent['options']['altqbucket'] = $_POST['altqbucket'];
                }
                if ($_POST['altqlowdelay']) {
                        $filterent['options']['altqlowdelay'] = $_POST['altqlowdelay'] ? true : false;
                }
                if ($_POST['state']) {
                        $filterent['options']['state'] = $_POST['state'];
                }
                if ($_POST['maxstates']) {
                        $filterent['options']['maxstates'] = $_POST['maxstates'];
                }
                if ($_POST['srctrack']) {
                        $filterent['options']['srctrack'] = $_POST['srctrack'];
                }
                if ($_POST['maxsrcnodes']) {
                        $filterent['options']['maxsrcnodes'] = $_POST['maxsrcnodes'];
                }
                if ($_POST['maxsrcstates']) {
                        $filterent['options']['maxsrcstates'] = $_POST['maxsrcstates'];
                }
                if ($_POST['maxsrcconns']) {
                        $filterent['options']['maxsrcconns'] = $_POST['maxsrcconns'];
                }
                if ($_POST['maxsrcconnrate']) {
                        $filterent['options']['maxsrcconnrate'] = $_POST['maxsrcconnrate'];
                }
                if ($_POST['overload']) {
                        $filterent['options']['overload'] = $_POST['overload'] ? true : false;
                }
                if ($_POST['flush']) {
                        $filterent['options']['flush'] = $_POST['flush'] ? true : false;
                }
                if (isset($id) && $a_filter[$id])
                        $a_filter[$id] = $filterent;
                else {
                        if (is_numeric($after))
                                array_splice($a_filter, $after+1, 0, array($filterent));
                        else
                                $a_filter[] = $filterent;
                }
 
$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);
 
        if (filter_parse_config($config)) {
                $input_errors[] = "Could not parse the generated config file";
                $input_errors[] = "See log file for details";
                $input_errors[] = "XML Config file not modified";
        }
 
if (!$input_errors) {
write_config();
 
if (isset($rulesetid)) {
                 header("Location: firewall_rules.php?if=" . $_POST['interface'] . "&rulesetid=$rulesetid");
 
} else {
                 touch($d_filterconfdirty_path);
                 header("Location: firewall_rules.php?if=" . $_POST['interface']);
}
Exit;
}
}
 
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
        $pconfig['altqbucket'] = $a_filter[$id]['options']['altqbucket'];
        $pconfig['altqlowdelay'] = isset($a_filter[$id]['options']['altqlowdelay']);
        $pconfig['state'] = $a_filter[$id]['options']['state'];
        $pconfig['maxstates'] = $a_filter[$id]['options']['maxstates'];
        $pconfig['srctrack'] = $a_filter[$id]['options']['srctrack'];
        $pconfig['maxsrcnodes'] = $a_filter[$id]['options']['maxsrcnodes'];
        $pconfig['maxsrcstates'] = $a_filter[$id]['options']['maxsrcstates'];
        $pconfig['maxsrcconns'] = $a_filter[$id]['options']['maxsrcconns'];
        $pconfig['maxsrcconnrate'] = $a_filter[$id]['options']['maxsrcconnrate'];
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

// wait for the DOM to be loaded
$(document).ready(function() {
	 $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');

     // When a user clicks on the src host add button, validate and add the host.
     $("#srchostaddbutton").click(function () {
          var ip = $("#srchost");
	  $('#SRCADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          ip.val("");
          return false;
     });

     // When a user clicks on the src net add button, validate and add the host.
     $("#srcnetaddbutton").click(function () {
          var ip = $("#srcnet");
          var netmask = $("#srcmask");
	  $('#SRCADDR').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
          ip.val("");
          return false;
     });
     
      // When a user clicks on the dst host add button, validate and add the host.
     $("#dsthostaddbutton").click(function () {
          var ip = $("#dsthost");
	  $('#DSTADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          ip.val("");
          return false;
     });

     // When a user clicks on the dst net add button, validate and add the host.
     $("#dstnetaddbutton").click(function () {
          var ip = $("#dstnet");
          var netmask = $("#dstmask");
	  $('#DSTADDR').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
          ip.val("");
          return false;
     });


     // When a user highlights an item and clicks remove, remove it
          $('#srcremove').click(function() {  
          return !$('#SRCADDR option:selected').remove();  
     });
          $('#dstremove').click(function() {  
          return !$('#DSTADDR option:selected').remove();  
     });

     // When a user clicks on the submit button, post the form.
     $(".buttonrow").click(function () {
	 $("#save_config").html('<center>Saving Configuration File<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
     $(".ui-dialog-titlebar").css('display','block'); 
	 $('#save_config').dialog('open');
	 var Sources = $.map($('#SRCADDR option'), function(e) { return $(e).val(); } );
     var Destinations = $.map($('#DSTADDR option'), function(e) { return $(e).val(); } );
	 var TcpPorts = $.map($('#PROTOLIST option'), function(e) { if ($(e).val().match(/^tcp/)) { return $(e).val().replace(/tcp\//g, ''); } } );
     var UdpPorts = $.map($('#PROTOLIST option'), function(e) { if ($(e).val().match(/^udp/)) { return $(e).val().replace(/tcp\//g, ''); } } );
     var IpProtos = $.map($('#PROTOLIST option'), function(e) { if ($(e).val().match(/^ip/)) { return $(e).val().replace(/tcp\//g, ''); } } );
     var tcp = TcpPorts.join(' ');
     var udp = UdpPorts.join(' ');
     var ip = IpProtos.join(' ');
     var src = Sources.join(' ');
     var dst = Destinations.join(' ');
	 var QueryString = $("#iform").serialize()+'&srclist='+src+'&dstlist='+dst+'&tcpports='+tcp+'&udpports='+udp+'&ipprotos='+ip;
          $.post("forms/firewall_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);
               if(output.match(/SUBMITSUCCESS/))
                   setTimeout(function(){ $('#save_config').dialog('close'); }, 2000);
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
             <form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="firewall_rule">
             <input name="id" type="hidden" value="<?=$id;?>">
             <div id="tabAddress">
	        <fieldset>
                        <legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="name">Name</label>
                             <input id="name" type="text" name="name" value="<?=htmlspecialchars($pconfig['name']);?>" />
			</div>
                        <div>
                             <label for="descr">Description</label>
                             <input id="descr" type="text" size="50" name="descr" value="<?=htmlspecialchars($pconfig['descr']);?>" />
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="interface">Interface</label>
                              <select name="interface" class="formfld">
<?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP');
                                          for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                                                $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                                          }
                                          foreach ($interfaces as $iface => $ifacename): ?>
<option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
<?=htmlspecialchars($ifacename);?>
</option>
<?php endforeach; ?>
</select>
			     <p class="note">Choose on which interface packets must come in to match this rule.</p>
			</div>
                        <div>
                             <label for="action">Action</label>
                               <select name="type" class="formfld">
<?php $types = explode(" ", "Pass Block Reject"); foreach ($types as $type): ?>
<option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['type'])) echo "selected"; ?>>
<?=htmlspecialchars($type);?>
</option>
<?php endforeach; ?>
</select>
			     <p class="note"></p>
			</div>
                        <div>
                             <label for="SRCADDR">Source Addresses</label>
                              <select name="SRCADDR" style="width: 300px; height: 100px" id="SRCADDR" multiple>
<?php for ($i = 0; $i<sizeof($pconfig['srclist']); $i++): ?>
<option value="<?=$pconfig['srclist']["src$i"];?>">
<?php $display = preg_replace('/user:|:user/', '', $pconfig['srclist']["src$i"]);?>
<?=$display;?>
</option>
<?php endfor; ?>
</select>
<input type=button id='srcremove' value='Remove Selected'><br><br>
<label for="srctype">Type</label>
<select name="srctype" class="formfld" id="srctype">
<option value="srchostdiv" selected>Host</option>
<option value="srcnetdiv" >Network</option>
<option value="srcaliasdiv" >Alias</option>
<option value="srcuserdiv" >User</option>
</select>
                </div>
                 <div id='srchostdiv' style="display:block;">
<label for="srchost">Address</label>
<?=$mandfldhtml;?><input name="srchost" type="text" class="formfld" id="srchost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<input type=button id='srchostaddbutton' value='Add'>
</div>
<div id='srcnetdiv' style="display:none;">
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
<div id='srcaliasdiv' style="display:none;">
<label for="srcalias">Alias</label>
<select name="srcalias" class="formfld" id="srcalias">
<?php
                       $defaults = filter_system_aliases_names_generate();
                       $defaults = split(' ', $defaults);
                       foreach( $defaults as $i): ?>
<option value="<?='$' . $i;?>"><?=$i;?>
</option>
<?php endforeach; ?>
<?php foreach($config['aliases']['alias'] as $i): ?>
<option value="<?='$' . $i['name'];?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
<?=$i['name'];?>
</option>
<?php endforeach; ?>
</select>
<input type=button onClick="addOption('SRCADDR',document.iform.srcalias.value + '/32','alias' + ':' + document.iform.srcalias.value + '/32')"; value='Add'>
</div>
<div id='srcuserdiv' style="display:none;">
<label for="srcuser">User</label>
<select name="srcuser" class="formfld" id="srcuser">
<?php foreach($config['system']['accounts']['user'] as $i): ?>
<option value="<?=$i['name'];?>">
<?=$i['name'];?>
</option>
<?php endforeach; ?>
</select>
<input type=button onClick="addOption('SRCADDR',document.iform.srcuser.value + '/32','user:' + document.iform.srcuser.value + ':user')"; value='Add'>
</div>  

<div>
                             <label for="DSTADDR">Destination Addresses</label>
                               <select name="DSTADDR" style="width: 300px; height: 100px" id="DSTADDR" multiple>
<?php for ($i = 0; $i<sizeof($pconfig['dstlist']); $i++): ?>
<option value="<?=$pconfig['dstlist']["dst$i"];?>">
<?=$pconfig['dstlist']["dst$i"];?>
</option>
<?php endfor; ?>
</select>
<input type=button id='dstremove' value='Remove Selected'><br><br>
<label for="dsttype">Type</label>
<select name="dsttype" class="formfld" id="dsttype">
<option value="dsthostdiv" selected>Host</option>
<option value="dstnetdiv" >Network</option>
<option value="dstaliasdiv" >Alias</option>
<option value="dstsnatdiv" >SNAT</option>
</select>
</div>
<div id='dsthostdiv' style="display:block;">
<label for="dsthost">Address</label>
<input name="dsthost" type="text" class="formfld" id="dsthost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<input type=button id='dsthostaddbutton' value='Add'>
</div>
<div id='dstnetdiv' style="display:none;">
<label for="dstnet">Network Address</label>
<input name="dstnet" type="text" class="formfld" id="dstnet" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<strong>/</strong>
<select name="dstmask" class="formfld" id="dstmask"><?php for ($i = 30; $i >= 1; $i--): ?>
<option value="<?=$i;?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
<?=$i;?>
</option>
<?php endfor; ?>
</select>
<input type=button id='dstnetaddbutton' value='Add'>
</div>
<div id='dstaliasdiv' style="display:none;">
<label for="dstalias">Alias</label>
<select name="dstalias" class="formfld" id="dstalias">
<?php
$defaults = filter_system_aliases_names_generate();
$defaults = split(' ', $defaults);
                       foreach( $defaults as $i): ?>
<option value="<?='$' . $i;?>"><?=$i;?>
</option>
<?php endforeach; ?>
<?php foreach($config['aliases']['alias'] as $i): ?>
<option value="<?='$' . $i['name'];?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
<?=$i['name'];?>
</option>
<?php endforeach; ?>
</select>
<input type=button value='Add'>
</div>
<div id='dstsnatdiv' style="display:none;">
<label for="snat">SNAT</label>
<strong>External</strong>
<?=$mandfldhtml;?><input name="snatext" type="text" class="formfld" id="snatext" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<strong>Internal</strong>
<?=$mandfldhtml;?><input name="snatint" type="text" class="formfld" id="snatint" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<input type=button value='Add'>
</div>          
        <div class="buttonrow">
		<input type="submit" value="Save" class="button" />
	</div>  
	</fieldset>
	</div>
        <div id="tabProtocol">
             <fieldset> 
          <legend><?=join(": ", $pgtitle);?></legend>
	        <div>
                <label for="PROTOLIST">Protocol List</label>
                <select name="PROTOLIST" style="width: 150px; height: 100px" id="PROTOLIST" multiple>
                <?php for ($i = 0; $i<sizeof($pconfig['tcplist']); $i++): ?>
                <option value="tcp/<?=$pconfig['tcplist']["tcp$i"];?>">
                tcp/<?=$pconfig['tcplist']["tcp$i"];?>
                </option>
                <?php endfor; ?>
        </select>
        <?php for ($i = 0; $i<sizeof($pconfig['udplist']); $i++): ?>
                <option value="udp/<?=$pconfig['udplist']["udp$i"];?>">
                udp/<?=$pconfig['udplist']["udp$i"];?>
                </option>
                <?php endfor; ?>
        <input type=button onClick="removeOptions(PROTOLIST)"; value='Remove Selected'><br><br>
                    </div>
                    <div>
                    <label for="proto">Add Protocol</label>
                    <select name="proto" class="formfld" id="proto">
                      <option value="tcp" selected>TCP</option>
                      <option value="udp">UDP</option>
                      <option value="ip">IP</option>
                    </select>
                   <input name="fromport" type="text" class="formfld" id="fromport" size="5" value="">
                   <strong> To </strong>
                    <input name="toport" type="text" class="formfld" id="toport" size="5" value="">
                    <input type=button onClick="addOption('PROTOLIST',document.iform.proto.value + '/' + document.iform.fromport.value + ':' + document.iform.toport.value,document.iform.proto.value + '/' + document.iform.fromport.value + ':' + document.iform.toport.value)"; value='Add'>
                </div>
                <div id='srctablediv' style="display:none;">
                 <label for="srctable">Alias</label>
                    <select name="srctable" class="formfld" id="srctable">
                      <?php foreach($config['tablees']['table'] as $i): ?>
                      <option value="<?='$' . $i['name'];?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                <input type=button value='Add'> 
                </div>
               	<div id='srcuser' style="display:none;">
                <strong>User</strong>
                    <select name="srcuser" class="formfld" id="srcuser">
                      <?php foreach($config['system']['accounts']['user'] as $i): ?>
                      <option value="<?=$i['name'];?>">
                        <?=$i['name'];?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                <input type=button value='Add'>
                </div>  
	</fieldset>
        <div class="buttonrow">
		<input type="submit" value="Save" class="button" />
	</div>

	</div>
	<div id="tabOptions">
               <fieldset>
               <legend><?=join(": ", $pgtitle);?></legend>
                <div>
                  <label for="log">Log</label>
                  <input name="log" type="checkbox" id="log" value="yes" <?php if ($pconfig['log']) echo "checked"; ?>>
                  <p class="note">Log packets that are handled by this rule</p>
                </div>
                <div>
                      <label for="altqbucket">ALTQ</label>
                      <select name="altqbucket" class="formfld" id="altqbucket">
                      <?php foreach ($altqbuckets as $bucket): ?>
                      <option value="<?=$bucket;?>" <?php if ($bucket == $pconfig['altqbucket']) echo "selected"; ?>>
                      <?=htmlspecialchars($bucket);?>
                      </option>
                      <?php endforeach; ?>
                      </select>
                      <p class="note">Add packetes handled by this rule to ALTQ Bucket<p class="note"></p>
                </div>
                <div>
                  <label for="log">ALTQ Lowdelay</label>
                  <input name="altqlowdelay" type="checkbox" id="altqlowdelay" value="yes" <?php if ($pconfig['altqlowdelay']) echo "checked"; ?>>
                </div>
                <div>
                      <label for="state">State</label>
                      <select name="state" class="formfld" id="state">
            <?php foreach ($statetypes as $statetype): ?>
                      <option value="<?=$statetype;?>" <?php if ($statetype == $pconfig['state']) echo "selected"; ?>>
                      <?=htmlspecialchars($statetype);?>
                      </option>
                      <?php endforeach; ?>
                      </select>    
                </div>
                <div>
                  <label for="maxstates">Max States</label>
                  <input name="maxstates" type="text" class="formfld" id="maxstates" size="5" value="<?=htmlspecialchars($pconfig['maxstates']);?>">
                </div>
                <div>
                  <label for="srctrack">Source Tracking</label>
                  <select name="srctrack" class="formfld" id="srctrack">
                    <?php foreach ($srctracktypes as $srctracktype): ?>
                      <option value="<?=$srctracktype;?>" <?php if ($srctracktype == $pconfig['srctrack']) echo "selected"; ?>>
                      <?=htmlspecialchars($srctracktype);?>
                      </option>
                      <?php endforeach; ?>
                      </select>
                </div>
                <div>
                  <label for="maxsrcnodes">Max Src Nodes</label>
                  <input name="maxsrcnodes" type="text" class="formfld" id="maxsrcnodes" size="5" value="<?=htmlspecialchars($pconfig['maxsrcnodes']);?>">
                </div>
                <div>
                  <label for="maxsrcstates">Max Src States</label>
                  <input name="maxsrcstates" type="text" class="formfld" id="maxsrcstates" size="5" value="<?=htmlspecialchars($pconfig['maxsrcstates']);?>">
                </div>
                <div>
                  <label for="maxsrcconns">Max Src Conns</label>
                  <input name="maxsrcconns" type="text" class="formfld" id="maxsrcconns" size="5" value="<?=htmlspecialchars($pconfig['maxsrcconns']);?>">
                </div>
                <div>
                  <label for="maxsrcconrate">Max Src Conn Rate</label>
                  <input name="maxsrcconrate" type="text" class="formfld" id="maxsrcconrate" size="5" value="<?=htmlspecialchars($pconfig['maxsrcconrate']);?>">
                </div>
                <div>
		        <input type="submit" value="Save" class="button" />
	        </div>
                </fieldset>
                <div class="buttonrow">
		<input type="submit" value="Save" class="button" />
	</div>
       
        </div>
</form>
</div>
</div>
</div>
</div>
