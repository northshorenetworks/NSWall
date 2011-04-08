#!/bin/php
<?php

require("guiconfig.inc");

if ($_POST) {
	if ($_POST['Submit'] != " No ") {
		reset_factory_defaults();
		system_reboot();
		$rebootmsg = "The system has been reset to factory defaults and is now rebooting. This may take one minute.";
	} else {
		header("Location: index.php");
		exit;
	}
}
?>

<table width="100%" border="0" cellpadding="6" cellspacing="0">
	<tr>
		<form action="diag_defaults.php" method="post">
		<p><strong>If you click &quot;Yes&quot;, the firewall will be reset to
		factory defaults and will reboot immediately. The entire system
		configuration will be overwritten. The LAN IP address will be reset to
		192.168.1.1, the system will be configured as a DHCP server, and the
		password will be set to 'mono'.<br>
		<br>
		Are you sure you want to proceed?</strong></p>
		<p><input name="Submit" type="submit" class="formbtn" value=" Yes "> <input
			name="Submit" type="submit" class="formbtn" value=" No "></p>

		</form>
	</tr>
</table>
