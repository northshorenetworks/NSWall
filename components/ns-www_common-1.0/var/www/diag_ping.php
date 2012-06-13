#!/bin/php
<?php

$pgtitle = array("Diagnostics", "Ping");
require("guiconfig.inc");
include("ns-begin.inc");

define('MAX_COUNT', 10);
define('DEFAULT_COUNT', 4);

if (!isset($do_ping)) {
	$do_ping = false;
	$host = '';
	$count = DEFAULT_COUNT;
}

?>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">

<form action="forms/firewall_form_submit.php" method="post" name="iform"
	id="iform"><input name="formname" type="hidden" value="firewall_rule">
<input name="id" type="hidden" value="<?=$id;?>">
<div id="tabAddress">
<fieldset><legend><?=join(": ", $pgtitle);?></legend>
<div><label for="name">Host</label> <input id="name" type="text"
	name="name" value="" /></div>
<div><label for="interface">Interface</label> <select name="interface"
	class="formfld">
	<?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP');
	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
	}
	foreach ($interfaces as $iface => $ifacename): ?>
	<option value="<?=$iface;?>"><?=htmlspecialchars($ifacename);?></option>
	<?php endforeach; ?>
</select>
<p class="note">Choose on which interface to send the ping packets from</p>
</div>
<div><label for="interface">Count</label> <select name="count"
	class="formfld" id="count">
	<?php for ($i = 1; $i <= MAX_COUNT; $i++): ?>
	<option value="<?=$i;?>" <?php if ($i == $count) echo "selected"; ?>><?=$i;?></option>
	<?php endfor; ?>
</select>
<p class="note">Choose on which interface to send the ping packets from</p>
</div>
</fieldset>
<div class="buttonrow"><input type="submit" id="submitbutton"
	value="Submit" class="button" /></div>

</div>
</form>
</div>
</div>
</div>
