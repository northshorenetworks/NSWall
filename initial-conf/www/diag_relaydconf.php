#!/bin/php
<?php
/*
 $Id: diag_relaydconf.php,v 1.2 2008/10/31 06:26:40 jrecords Exp $
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

$pgtitle = array("Diagnostics", "View relayd.conf");
require("guiconfig.inc");

function dump_relaydconf() {
	global $g, $config;
	printf("<pre>");
	require_once("filter.inc");
	$relaydconf = relay_print_relaydconf();
	$relaydconf = htmlentities($relaydconf);
	printf("$relaydconf");
	printf("</pre>");
}

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
		<ul id="tabnav">
		<?php
		$tabs = array('relayd.conf' => 'diag_relaydconf.php',
                      'Sessions' => 'diag_relaydsessions.php',
		      'Summary' => 'diag_relaydsummary.php');
		dynamic_tab_menu($tabs);
		?>

		</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td colspan="2" class="listtopic">
			
			</tr>
			<?php dump_relaydconf(); ?>
		</table>
		<br>
		<form action="diag_relaydconf.php" method="post"></form>
		</td>
	</tr>
</table>
