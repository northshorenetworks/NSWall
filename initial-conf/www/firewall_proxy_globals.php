#!/bin/php
<?php
/*
 $Id: firewall_nat_1to1.php,v 1.1.1.1 2008/08/01 07:56:19 root Exp $
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

$pgtitle = array("Firewall", "Relayd", "Global Settings");
require("guiconfig.inc");

$pconfig['interval'] = $config['relays']['interval'];
$pconfig['log'] = $config['relays']['log'];
$pconfig['prefork'] = $config['relays']['prefork'];
$pconfig['timeout'] = $config['relays']['timeout'];

$a_relay = &$config['relays'];

if ($_POST) {

	$pconfig = $_POST;
	if (!$input_errors) {
		$config['relays']['interval'] = $_POST['interval'];
		$config['relays']['log'] = $_POST['log'];
		$config['relays']['prefork'] = $_POST['prefork'];
		$config['relays']['timeout'] = $_POST['timeout'];
	}
	write_config();
	$retval = 0;
	$savemsg = get_std_save_message($retval);
}

?>
<?php include("fbegin.inc"); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="firewall_proxy_globals.php" method="post">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
		<ul id="tabnav">
		<?php
		$tabs = array('Tables' => 'firewall_relays.php',
                        'Redirections' => 'firewall_relays_redirections.php',
                        'Relays' => 'firewall_relays_relays.php',
                        'Protocols' => 'firewall_relays_protocols.php',
                        'Global Settings' => 'firewall_relays_globals.php');
		dynamic_tab_menu($tabs);
		?>
		</ul>
		</td>
	</tr>
</table>
<table width="100%" border="0" cellpadding="6" cellspacing="0">
	<tr>
		<tr>
			<td valign="top" class="vncell">Interval</td>
			<td class="vtable"><input name="interval" type="text" class="formfld"
				id="interval" size="5" value="<?=$pconfig['interval'];?>"> <br>
			<span class="vexpl">Set the interval in seconds at which the hosts
			will be checked. The default interval is 10 seconds. </span></td>
		</tr>
		<tr>
			<td width="22%" valign="top" class="vncell">Log</td>
			<td width="78%" class="vtable"><br>
			<select name="log" class="formfld" id="log">
				<option value="all"
				<?php if ($pconfig['log'] == 'all') echo "selected"; ?>>all</option>
				<option value="updates"
				<?php if ($pconfig['log'] == 'updates') echo "selected"; ?>>updates</option>
			</select><br>
			<br>
			<span class="vexpl">Log state notifications after completed host
			checks. Either only log the updates to new states or log all state
			notifications, even if the state didn't change. The host state can be
			up (the health check completed successfully), down (the host is down
			or didn't match the check criteria), or unknown (the host is dis-
			abled or has not been checked yet). </span></td>
		</tr>
		<tr>
			<td width="22%" valign="top" class="vncell">Prefork</td>
			<td width="78%" class="vtable"><input name="prefork" type="text"
				class="formfld" id="prefork" size="5"
				value="<?=$pconfig['prefork'];?>"> <br>
			<span class="vexpl">When using relays, run the specified number of
			processes to han- dle relayed connections. This increases the
			performance and pre- vents delays when connecting to a relay.
			relays(8) runs 5 relay processes by default and every process will
			handle all configured relays. &nbsp;&nbsp;&nbsp; </span></td>
		</tr>
		<tr>
			<td width="22%" valign="top" class="vncell">Timeout</td>
			<td width="78%" class="vtable"><input name="timeout" type="text"
				class="formfld" id="timeout" size="5"
				value="<?=$pconfig['timeout'];?>"> <br>
			<span class="vexpl"> Set the global timeout in milliseconds for
			checks. This can be overriden by the timeout value in the table
			definitions. The de- fault interval is 200 milliseconds and it must
			not exceed the global interval. Please note that the default value is
			optimized for checks within the same collision domain - use a higher
			time- out, such as 1000 milliseconds, for checks of hosts in other
			sub- nets. &nbsp;&nbsp;&nbsp; </span></td>
		</tr>
		<tr>
			<td width="22%" valign="top">&nbsp;</td>
			<td width="78%"><input name="Submit" type="submit" class="formbtn"
				value="Save"></td>
		</tr>

</table>
</form>
				<?php include("fend.inc"); ?>
