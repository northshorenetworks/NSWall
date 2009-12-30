#!/bin/php
<?php
/*
	$Id: diag_tcpdump.php,v 1.1.1.1 2008/08/01 07:56:19 root Exp $
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2006 Bob Zoller (bob@kludgebox.com) and Manuel Kasper <mk@neon1.net>.
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

require("guiconfig.inc");

define('MAX_COUNT', 1000);
define('DEFAULT_COUNT', 10);

if ($_POST) {
	unset($input_errors);
	unset($do_tcpdump);

	/* input validation */
	$reqdfields = explode(" ", "count");
	$reqdfieldsn = explode(",", "Count");
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['count'] < 1) || ($_POST['count'] > MAX_COUNT)) {
		$input_errors[] = "Count must be between 1 and {MAX_COUNT}";
	}

	if (!$input_errors) {
		$do_tcpdump = true;
		$interface = $_POST['interface'];
		$count = $_POST['count'];
		$port = $_POST['port'];
	}
}
if (!isset($do_tcpdump)) {
	$do_tcpdump = false;
	$host = '';
	$count = DEFAULT_COUNT;
	$port = 'http';
}

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr> 
    <td class="tabcont">
<?php if ($input_errors) print_input_errors($input_errors); ?>
			<form action="diag_tcpdump.php" method="post" name="iform" id="iform">
			  <table width="100%" border="0" cellpadding="6" cellspacing="0">
				<tr>
				  <td dth="22%" valign="top" class="vncellreq">Protocol</td>
                                  <td width="78%" class="vtable">
                                        <select name="port" class="formfld" id="proto">
                                        <option selected value="http">HTTP</option>
                                        <option value="smtp">SMTP</option>
                                        <option value="ftp or ftp-data">FTP</option>
					<option value="telnet">Telnet</option>
					<option value="ssh">SSH</option>
					<option value="icmp">ICMP</option>
					<option value="any">Any</option>
                                        </select></td>
                                </tr>
                                <tr>
				  <td dth="22%" valign="top" class="vncellreq">Interface</td>
				  <td width="78%" class="vtable">
				  <select name="interface" class="formfld">
                      <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					    if (isset($config['interfaces']['opt' . $i]['enable']) &&
							!$config['interfaces']['opt' . $i]['bridge'])
					  		$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $interface) echo "selected"; ?>> 
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                      </select>
				  </td>
				</tr>
				<tr>
				  <td width="22%" valign="top" class="vncellreq">Packet Count</td>
				  <td width="78%" class="vtable">
					<select name="count" class="formfld" id="count">
					<option selected value="10">10</option>
					<option value="100">100</option>
					<option value="1000">1000</option>
					</select></td>
				</tr>
				<tr>
				  <td width="22%" valign="top">&nbsp;</td>
				  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="TCPDump">
				</td>
				</tr>
				<tr>
				<td valign="top" colspan="2">
				<? if ($do_tcpdump) {
                                        echo("<strong>TCPDump in Progress: Do not navigate away from this page</strong><br>");
                                        $ifname = get_interface_name($interface);
                                        if ($port != 'any') {
                                                $proto = "port $port";
                                        } elseif ($port == 'icmp') {
						$proto = 'icmp';
					}else {
						$proto = '';
					}
                                        mwexec("/usr/sbin/tcpdump -i $ifname -c $count -w /tmp/debug/nswall.cap $proto");
                                        echo("<strong><a href=\"/debug/nswall.cap\">nswall.cap</a></strong><br>");
                                }
                                ?>
				</td>
				</tr>
			</table>
</form>
</td></tr></table>
