#!/bin/php
<?php

$pgtitle = array("Diagnostics", "TCPDump");
require("guiconfig.inc");
include("ns-begin.inc");

define('MAX_COUNT', 10);
define('DEFAULT_COUNT', 4);

if (!isset($do_ping)) {
	$do_ping = false;
	$host = '';
	$count = DEFAULT_COUNT;
}

exec ("ls /tmp/debug/*.cap" . " 2>&1", $execOutput, $execStatus);

if (!preg_match('/No such file or directory/', $execOutput[0])) {

	for ($i = 0; isset($execOutput[$i]); $i++) {
		if ($i > 0) {
			$output .= "\n";
		}
		// Get rid of leading /tmp/debug on filenames display
		$filename = preg_replace('/\/tmp\/debug\//', '', htmlspecialchars($execOutput[$i],ENT_NOQUOTES));
		$output .= '<li id=' . ($i + 1) . '"><span class="col1">' . $filename . '</span>';
		$output .= '<span class="col2"><a href="/debug/' . $filename . '"><span title="Download capture file" class="ui-icon ui-icon-circle-plus"></span></a></span>';
		$output .= '<span class="col3"><a href="forms/diagnostics_form_submit.php?filename=' . $filename . '&action=delete"><span title="Delete capture file" class="ui-icon ui-icon-circle-close"></span></a></span>';
		$output .= '<span class="col4"><a href="support_case.php?capturefile=' . $filename . '"><span title="Send packet capture to tech support" class="ui-icon ui-icon-circle-plus"></span></a></span>';
		$output .= '</li>';
	}
}

?>

<style type="text/css">
#capturesortable {
	list-style-type: none;
	margin: auto auto 1em;
	padding: 0;
	width: 95%;
}

#capturesortable li {
	padding: 0.1em;
	margin-left: 0;
	padding-left: 1;
	font-size: 1.4em;
	height: 18px;
	border: 1px solid #E4E4E4;
	font-size: 1em;
}

#capturesortable li span.col1 {
	position: relative;
	float: left;
	width: 10em;
}

#capturesortable li span.col2 {
	position: relative;
	float: left;
	width: 7em;
}

#capturesortable li span.col3 {
	position: relative;
	float: left;
	width: 7em;
}

#capturesortable li span.col4 {
	position: relative;
	float: left;
	width: 10em;
}
</style>


<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {

        // Make the list of rules for this interface sortable
        $("#capturesortable").sortable({
           axis: 'y',
           containment: 'parent',
           items: 'li:not(.ui-state-disabled)',
        });

        $(".col4 a").click(function () {
            $('#load_content').dialog('open');
            var toLoad = $(this).attr('href');
            window.location.hash = $(this).attr('href').substr(0,$(this).attr('href').length-4);
            clearInterval(refreshId);
            $("#content").load(toLoad);
            return false;
        }); 

        $(".col3 a").click(function () {
        if (confirm('Are you sure you want to delete this packet capture file?')){
             $("#save_config").html('<center>Deleting Packet Capture<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
             $(".ui-dialog-titlebar").css('display','block');
             $('#save_config').dialog('open');
             var id = $(this).attr('href');
             $("#currentorder").load(id);
             $("<?= $ifsortable; ?>").sortable('refresh');
             setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
             setTimeout(function(){ $('#content').load('diag_util.php'); }, 1250);
        }
        return false;
        });
     // When a user clicks on the submit button, post the form.
     $("#submitbutton, #submitbutton2, #submitbutton3").click(function () {

         $("#save_config").html('<center>Running Packet Capture Utility<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
         $(".ui-dialog-titlebar").css('display','block');
         $('#save_config').dialog('open');
         var QueryString = $("#iform").serialize();
            $.post("forms/diagnostics_form_submit.php", QueryString, function(output) {
               $("#save_config").html(output);
            });
              return false;
         });
});

</script>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">

<form action="forms/diagnostics_form_submit.php" method="post"
	name="iform" id="iform"><input name="formname" type="hidden"
	value="diagnostic_tcpdump"> <input name="id" type="hidden"
	value="<?=$id;?>">
<div id="tabAddress">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="protocol">Protocol</label> <select name="port"
	class="formfld" id="proto">
	<option selected value="port http">HTTP</option>
	<option value="port smtp">SMTP</option>
	<option value="port ftp or ftp-data">FTP</option>
	<option value="port telnet">Telnet</option>
	<option value="proto icmp">ICMP</option>
	<option value="">Any</option>
</select>
<p class="note">Choose what protocol to filter for</p>
</div>
<div><label for="interface">Interface</label> <select name="interface"
	class="formfld">
	<?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP');
	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
	}
	foreach ($config['vlans']['vlan'] as $vlan) {
		$interfaces[$vlan['descr']] = "VLAN{$vlan['tag']}";
	}
	foreach ($interfaces as $iface => $ifacename): ?>
	<option value="<?=$iface;?>"><?=htmlspecialchars($ifacename);?></option>
	<?php endforeach; ?>
</select>
<p class="note">Choose on which interface to tcpdump from</p>
</div>
<div><label for="interface">Count</label> <select name="count"
	class="formfld" id="count">
	<option selected value="10">10</option>
	<option value="100">100</option>
	<option value="1000">1000</option>
</select>
<p class="note">Choose how many packets to capture</p>
</div>

</div>
</fieldset>
<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Submit" class="button" /></div>
<div><br>
<br>
<center>Capture Files</center>
<br>
<br>
<ul id="capturesortable">
	<li id="list" class="connectedSortable ui-state-disabled">
		<span class="col1">Filename</span>
		<span class="col2">Download</span>
		<span class="col3">Delete</span>
		<span class="col4">Support</span>
	</li>
	<?php echo $output ?>       
</ul>

</div>
</form>
</div>
</div>
</div>
