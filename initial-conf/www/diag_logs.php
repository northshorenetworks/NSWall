#!/bin/php
<?php 
/*
	$Id: diag_logs.php,v 1.1.1.1 2008/08/01 07:56:19 root Exp $
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

$pgtitle = array("Diagnostics", "Logs");
require("guiconfig.inc");

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_POST['clear']) {
	exec("/usr/sbin/syslogc -C system");
	/* redirect to avoid reposting form data on refresh */
	header("Location: diag_logs.php");
	exit;
}

function resolve_logs_red($arr) {
        return ' <font color="red">' . gethostbyaddr($arr[0]) . '</font>';
}

function resolve_logs_green($arr) {
        return ' <font color="chartreuse">' . gethostbyaddr($arr[0]) . '</font>';
}

function scrub_raw_log($log) {
	/* scrub filter logs */ 
	if(preg_match('/pf:/', $log)) {
		/* strings we get scrub out of raw logs */
		$scrubstrings = '/\&lt\;.+?\&gt\;|\[tcp sum ok\]|\/\(match\)|\[uid \d+, pid \d+\]/';
		$log = preg_replace($scrubstrings, '', $log);
		$log = preg_replace('/rule \d+\./', 'rule: ', $log);
		$log = preg_replace('/\s\&gt\;\s/', ' to ', $log);
		$log = preg_replace('/\.(\d+)\s/', ' $1 ', $log);
		$log = preg_replace('/\.(\d+):/', ' $1 ', $log);
		/* colorize block logs */
		if(preg_match('/block in/', $log)) {
			echo $log;
			$log = preg_replace('/block/', '', $log);
                	$ipaddr = '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';
                        $log = preg_replace_callback($ipaddr, resolve_logs_red, $log);
		}	
		/* colorize pass logs */
		if(preg_match('/pass in/', $log)) {
			$ipaddr = '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';
			$log = preg_replace_callback($ipaddr, resolve_logs_green, $log);	
		}
	}
	return $log;
}

function dump_clog($logfile, $tail, $withorig = true) {
	global $g, $config;

	$sor = isset($config['syslog']['reverse']) ? "-r" : "";

	exec("/usr/sbin/syslogc " . $logfile . " | tail {$sor} -n " . $tail, $logarr);
	
	foreach ($logarr as $logent) {
		$logent = preg_split("/\s+/", $logent, 6);
		echo "<tr valign=\"top\">\n";
	
		if ($withorig) {
                        echo scrub_raw_log("<td class=\"listlogr\" nowrap>" . htmlspecialchars(join(" ", array_slice($logent, 0, 3)) . " " . $logent[4] . " " . $logent[5]) . "</td>\n");
                } else {
                        echo "<td class=\"listlr\" nowrap colspan=\"2\">" . htmlspecialchars($logent[5]) . "</td>\n";
                }
		echo "</tr>\n";
	}
}

?>
<script src="js/jquery-1.3.2.min.js"></script>

<script>
var refreshId = setInterval(function()
{
     $('#rtlogs').load('stats.cgi?logs');
}, 5000);
</script>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('System' => 'diag_logs.php',
           		  'Settings' => 'diag_logs_settings.php');
	dynamic_tab_menu($tabs);
?> 
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
	<div id="rtlogs" style="display:block; overflow:auto; max-height:500px; min-height:500px; max-width:800px; min-width:800px; background-color:black;"></div>
   </td>
  </tr>
</table>
