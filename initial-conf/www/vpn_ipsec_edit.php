#!/bin/php
<?php 

$pgtitle = array("VPN", "IPSec", "Gateway", "Edit");
require("guiconfig.inc");
include("ns-begin.inc");

$specialsrcdst = explode(" ", "any wanip lan pptp");
 
if (!is_array($config['ipsec']['gateway']))
$config['ipsec']['gateway'] = array();
 
vpn_ipsec_gateway_sort();
$a_gateway = &$config['ipsec']['gateway'];
 
$id = $_GET['id'];
if (is_numeric($_POST['id']))
$id = $_POST['id'];
 

function is_specialnet($net) {
global $specialsrcdst;
 
if (in_array($net, $specialsrcdst) || (strstr($net, "opt") && !is_alias($net)))
return true;
else
return false;
}
 
if (isset($id) && $a_gateway[$id]) {
	$pconfig['disabled'] = isset($a_gateway[$id]['disabled']);
	//$pconfig['auto'] = isset($a_gateway[$id]['auto']); 

	if (!isset($a_gateway[$id]['local-subnet']))
		$pconfig['localnet'] = "lan";
	else
		address_to_pconfig($a_gateway[$id]['local-subnet'], $pconfig['localnet'], $pconfig['localnetmask']);
 
	if ($a_gateway[$id]['interface'])
		$pconfig['interface'] = $a_gateway[$id]['interface'];
	else
		$pconfig['interface'] = "wan";
 
	$pconfig['remotegw'] = $a_gateway[$id]['remote-gateway'];
	$pconfig['srclist'] = $a_gateway[$id]['srclist'];
    $pconfig['dstlist'] = $a_gateway[$id]['dstlist'];
 
	if (isset($a_gateway[$id]['p1']['myident']['myaddress']))
		$pconfig['p1myidentt'] = 'myaddress';
	else if (isset($a_gateway[$id]['p1']['myident']['address'])) {
		$pconfig['p1myidentt'] = 'address';
		$pconfig['p1myident'] = $a_gateway[$id]['p1']['myident']['address'];
	} else if (isset($a_gateway[$id]['p1']['myident']['fqdn'])) {
		$pconfig['p1myidentt'] = 'fqdn';
		$pconfig['p1myident'] = $a_gateway[$id]['p1']['myident']['fqdn'];
	} else if (isset($a_gateway[$id]['p1']['myident']['ufqdn'])) {
		$pconfig['p1myidentt'] = 'user_fqdn';
		$pconfig['p1myident'] = $a_gateway[$id]['p1']['myident']['ufqdn'];
	}

	$pconfig['p1myident'] = $a_gateway[$id]['p1']['myident']['myaddress'];
	$pconfig['p1ealgo'] = $a_gateway[$id]['p1']['encryption-algorithm'];
	$pconfig['p1halgo'] = $a_gateway[$id]['p1']['hash-algorithm'];
	$pconfig['p1dhgroup'] = $a_gateway[$id]['p1']['dhgroup'];
	$pconfig['p1lifetime'] = $a_gateway[$id]['p1']['lifetime'];
	$pconfig['p1authentication_method'] = $a_gateway[$id]['p1']['authentication_method'];
	$pconfig['p1pskey'] = base64_decode($a_gateway[$id]['p1']['pre-shared-key']);
	$pconfig['p2proto'] = $a_gateway[$id]['p2']['protocol'];
	$pconfig['p2ealgos'] = $a_gateway[$id]['p2']['encryption-algorithm'];
	$pconfig['p2halgos'] = $a_gateway[$id]['p2']['hash-algorithm'];
	$pconfig['p2pfsgroup'] = $a_gateway[$id]['p2']['pfsgroup'];
	$pconfig['p2lifetime'] = $a_gateway[$id]['p2']['lifetime'];
	$pconfig['descr'] = $a_gateway[$id]['descr'];
	$pconfig['name'] = $a_gateway[$id]['name'];
	$pconfig['addresspolicies'] = $a_gateway[$id]['addresspolicies'];
 
} else {
	/* defaults */
	$pconfig['interface'] = "wan";
	$pconfig['localnet'] = "lan";
	$pconfig['p1mode'] = "aggressive";
	$pconfig['p1myidentt'] = "myaddress";
	$pconfig['p1authentication_method'] = "pre_shared_key";
	$pconfig['p1ealgo'] = "3des";
	$pconfig['p1halgo'] = "sha1";
	$pconfig['p1dhgroup'] = "2";
	$pconfig['p2proto'] = "esp";
	$pconfig['p2ealgos'] = explode(",", "des,3des,aes,aes-128,aes-192,aes-256,aesctr,blowfish,cast,skipjack,none");
	$pconfig['p2halgos'] = explode(",", "hmac-md5,hmac_sha1,hmac-sha2-256,hmac-sha2-384,hmac-sha2-512");
	$pconfig['p2pfsgroup'] = "0";
	$pconfig['remotebits'] = 32;
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
		}  
     });

     // When a user clicks on the submit button, post the form.
     $("#submitbutton, #submitbutton2, #submitbutton3").click(function () {

         $("#save_config").html('<center>Saving Configuration File<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
         $(".ui-dialog-titlebar").css('display','block');
         $('#save_config').dialog('open');
         var Sources = $.map($('#SRCADDR option'), function(e) { return $(e).val(); } );
         var Destinations = $.map($('#DSTADDR option'), function(e) { return $(e).val(); } );
         var src = Sources.join(' ');
         var dst = Destinations.join(' ');
         var QueryString = $("#iform").serialize()+'&srclist='+src+'&dstlist='+dst;
         $.post("forms/vpn_form_submit.php", QueryString, function(output) {
             $("#save_config").html(output);
                 if(output.match(/SUBMITSUCCESS/))
                     setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
              });
              return false;
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
	$('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
	
	   // When a user clicks on the src host add button, validate and add the host.
     $("#srchostaddbutton").click(function () {
          var ip = $("#srchost");
		  if(verifyIP(ip.val()) == 0) {
		  	$('#SRCADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          	        ip.val("");
          	         return false;
     	          }
	 });

     // When a user clicks on the src net add button, validate and add the host.
     $("#srcnetaddbutton").click(function () {
          var ip = $("#srcnet");
          var netmask = $("#srcmask");
	  	  if(verifyIP(ip.val()) == 0) {
		  	$('#SRCADDR').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
          	ip.val("");
          	return false;
		  }	
     });

      // When a user clicks on the dst host add button, validate and add the host.
     $("#dsthostaddbutton").click(function () {
          var ip = $("#dsthost");
		  if(verifyIP(ip.val()) == 0) { 
	  		$('#DSTADDR').append("<option value='" + ip.val() + "'>"+ip.val() + '</option>');
          	ip.val("");
          	return false;
		  }	
     });

     // When a user clicks on the dst net add button, validate and add the host.
     $("#dstnetaddbutton").click(function () {
          var ip = $("#dstnet");
          var netmask = $("#dstmask");
	  	  if(verifyIP(ip.val()) == 0) {	
			$('#DSTADDR').append("<option value='" + ip.val() + "/" + netmask.val() + "'>"+ip.val() + "/" + netmask.val() + '</option>');
          	ip.val("");
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
});

    $(function() {
        $("#firewalloptionstabs").tabs();
    });
</script>
<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">
 
<div id="firewalloptionstabs">
    <ul>
        <li><a href="#tabGateway">Gateway</a></li>
        <li><a href="#tabPhase1">Phase 1</a></li>
        <li><a href="#tabPhase2">Phase 2</a></li>
    </ul>
             <form action="forms/vpn_form_submit.php" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="vpn_ipsec_gateway">
             <input name="id" type="hidden" value="<?=$id;?>">
             <div id="tabGateway">
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
                     <select name="interface" id="interface" class="formfld">
                     <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
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
				<label for="remotegw">Remote Gateway</label>
				<input name="remotegw" type="text" class="formfld" id="remotegw" size="16" value="<?=htmlspecialchars($pconfig['remotegw']);?>">
				<p class="note">Enter the public IP address of the remote gateway.</p>
			</div>	
		</fieldset>
        <div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div> 
	</div>
    <div id="tabPhase1">
        <fieldset> 
        <legend><?=join(": ", $pgtitle);?></legend>
		<div>
                  <label for="maxstates">Negotiation Mode</label>
                   <select name="p1mode" class="formfld">
                        <?php $modes = explode(" ", "main aggressive"); foreach ($modes as $mode): ?>
                        <option value="<?=$mode;?>" <?php if ($mode == $pconfig['p1mode']) echo "selected"; ?>>
                            <?=htmlspecialchars($mode);?>
                        </option>
                        <?php endforeach; ?>
                   </select>
                   <p class="note">TODO: Come up with something good for this text</p>
        </div>
		<div>
                  <label for="myaddress">My Identifier</label>
				  <input name="myaddress" type="text" class="formfld" id="myaddress" size="30" value="<?=$pconfig['p1myident'];?>">
				  <p class="note">TODO: Come up with something good for this text</p>
        </div>
		 <div>
                        <label for="p1dhgroup">DH key group</label>
                        <select name="p1dhgroup" class="formfld">
                        <?php foreach ($p2_pfskeygroups as $keygroup => $keygroupname): ?>
                        <option value="<?=$keygroup;?>" <?php if ($keygroup == $pconfig['p1dhgroup']) echo "selected"; ?>>
                            <?=htmlspecialchars($keygroupname);?>
                        </option>
                        <?php endforeach; ?>
                        </select>
                        <p class="note">1 = 768 bit, 2 = 1024 bit, 5 = 1536 bit<p class="note"></p>
                </div>
		<div>
                  <label for="p1ealgo">Encryption</label>
                  <select name="p1ealgo" class="formfld">
					  <?php foreach ($p1_ealgos as $algo => $algoname): ?>
					  <option value="<?=$algo;?>" <?php if ($algo == $pconfig['p1ealgo']) echo "selected"; ?>>
						  <?=htmlspecialchars($algoname);?>
					  </option>
					  <?php endforeach; ?>
				  </select> 
				  <p class="note">Must match the setting chosen on the remote side.</p>
        </div>
		<div>
                  <label for="p1halgo">Hash</label>
                   <select name="p1halgo" class="formfld">
				   		<?php foreach ($p1_halgos as $algo => $algoname): ?>
						<option value="<?=$algo;?>" <?php if ($algo == $pconfig['p1halgo']) echo "selected"; ?>>
							<?=htmlspecialchars($algoname);?>
						</option>
						<?php endforeach; ?>
					</select>
				  	<p class="note">Must match the setting chosen on the remote side.</p>
        </div>
		<div>
                <label for="p1lifetime">Lifetime</label>
                <input name="p1lifetime" type="text" class="formfld" id="p1lifetime" size="20" value="<?=$pconfig['p1lifetime'];?>">seconds
				<p class="note">Set the lifetime of Phase 1 Negotiation timeout in seconds</p>
        </div>
		<div id='psk' style="display:block;">
						<label for="p1pskey">Pre Shared Key</label>
						<input name="p1pskey" type="text" class="formfld" id="p1pskey" size="16" value="<?=htmlspecialchars($pconfig['p1pskey']);?>">
		</div>
		</fieldset>
    <div class="buttonrow">
		<input type="submit" id="submitbutton2" value="Save" class="button" />
	</div>
	</div>
	<div id="tabPhase2">
               <fieldset>
               <legend><?=join(": ", $pgtitle);?></legend>
                <div>
                  <label for="p2proto">Protocol</label>
                   <select name="p2proto" class="formfld">
					<?php foreach ($p2_protos as $proto => $protoname): ?>
					<option value="<?=$proto;?>" <?php if ($proto == $pconfig['p2proto']) echo "selected"; ?>>
						<?=htmlspecialchars($protoname);?>
					</option>
					<?php endforeach; ?>
					</select> 
				  <p class="note">ESP is encryption, AH is authentication only</p>
                </div>
				<div>
                      <label for="p2ealgos">Encryption</label>
                       <select name="p2ealgos" class="formfld">
						<?php foreach ($p2_ealgos as $algo => $algoname): ?>
						<option value="<?=$algo;?>" <?php if (in_array($algo, $pconfig['p2ealgos'])) echo "checked"; ?>>
							<?=htmlspecialchars($algoname);?>
						</option>
						<?php endforeach; ?>
					   </select>
                      <p class="note"> Hint: use 3DES for best compatibility or if you have a hardware crypto accelerator card. Blowfish is usually the fastest in software encryption.<p class="note"></p>
                </div>
				<div>
                      <label for="p2halgos">Hash</label>
                       <select name="p2halgos" class="formfld">
						<?php foreach ($p2_halgos as $algo => $algoname): ?>
						<option value="<?=$algo;?>" <?php if (in_array($algo, $pconfig['p2halgos'])) echo "checked"; ?>>
							<?=htmlspecialchars($algoname);?>
						</option>
						<?php endforeach; ?> 
					    </select>
                        <p class="note">Say something smart here.<p class="note"></p>
                </div>
                <div>
                        <label for="pspfsgroup">PFS key group</label>
                        <select name="p2pfsgroup" class="formfld">
						<?php foreach ($p2_pfskeygroups as $keygroup => $keygroupname): ?>
						<option value="<?=$keygroup;?>" <?php if ($keygroup == $pconfig['p2pfsgroup']) echo "selected"; ?>>
							<?=htmlspecialchars($keygroupname);?>
						</option>
						<?php endforeach; ?>
						</select>
						<p class="note">1 = 768 bit, 2 = 1024 bit, 5 = 1536 bit<p class="note"></p>
                </div>
				<div>
                		<label for="p2lifetime">Lifetime</label>
                		<input name="p2lifetime" type="text" class="formfld" id="p2lifetime" size="20" value="<?=$pconfig['p2lifetime'];?>">seconds
                		<p class="note">Set the lifetime of Phase 2 Negotiation timeout in seconds</p>
        	</div>
	        <div>
                             <label for="SRCADDR">Source Addresses</label>
                              <select name="SRCADDR" style="width: 150px; height: 100px" id="SRCADDR" multiple>
<?php for ($i = 0; $i<sizeof($pconfig['srclist']); $i++): ?>
<option value="<?=$pconfig['srclist']["src$i"];?>"><?php $display = preg_replace('/user:|:user/', '', $pconfig['srclist']["src$i"]);?><?=$display;?></option>
<?php endfor; ?>
</select>
<input type=button id='srcremove' value='Remove Selected'><br><br>
<label for="srctype">Type</label>
<select name="srctype" class="formfld" id="srctype">
<option value="srchostdiv" selected>Host</option>
<option value="srcnetdiv" >Network</option>
</select>
<br>        
<span id='srchostdiv' style="display:block;">
<label for="srchost">Address</label>
<?=$mandfldhtml;?><input name="srchost" type="text" class="formfld" id="srchost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<input type=button id='srchostaddbutton' value='Add'>
</span>
<span id='srcnetdiv' style="display:none;">
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
</span>
</div>
        <div>
                              <label for="DSTADDR">Destination Addresses</label>
                              <select name="DSTADDR" style="width: 150px; height: 100px" id="DSTADDR" multiple>
<?php for ($i = 0; $i<sizeof($pconfig['dstlist']); $i++): ?>
<option value="<?=$pconfig['dstlist']["dst$i"];?>"><?php $display = preg_replace('/user:|:user/', '', $pconfig['dstlist']["dst$i"]);?><?=$display;?></option>
<?php endfor; ?>
</select>
<input type=button id='dstremove' value='Remove Selected'><br><br>
<label for="dsttype">Type</label>
<select name="dsttype" class="formfld" id="dsttype">
<option value="dsthostdiv" selected>Host</option>
<option value="dstnetdiv" >Network</option>
</select>
<br>        
<span id='dsthostdiv' style="display:block;">
<label for="dsthost">Address</label>
<?=$mandfldhtml;?><input name="dsthost" type="text" class="formfld" id="dsthost" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<input type=button id='dsthostaddbutton' value='Add'>
</span>
<span id='dstnetdiv' style="display:none;">
<label for="dstnet">Network Address</label>
<?=$mandfldhtml;?><input name="dstnet" type="text" class="formfld" id="dstnet" size="16" value="<?=htmlspecialchars($pconfig['address']);?>">
<strong>/</strong>
<select name="dstmask" class="formfld" id="dstmask">
<?php for ($i = 30; $i >= 1; $i--): ?>
<option value="<?=$i;?>" <?php if ($i == $pconfig['address_subnet']) echo "selected"; ?>>
<?=$i;?>
</option>
<?php endfor; ?>
</select>
<input type=button id='dstnetaddbutton' value='Add'>
</span>
</div>
</fieldset>
        <div class="buttonrow">
		<input type="submit" id="submitbutton3" value="Save" class="button" />
	</div>
      
        </div>
</form>
</div>
</div>
</div>
</div>
