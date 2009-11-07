#!/bin/php
<?php 
/*
	$Id: diag_xml_config.php,v 1.3 2009/04/20 06:52:09 jrecords Exp $
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

$pgtitle = array("Diagnostics", "XML Config");
require("guiconfig.inc");

function dump_xml() {
        global $g, $config;
        printf("<strong><pre>\n");
        $xml = `cat /conf/config.xml`;
	$xml = htmlentities($xml);
	echo $xml;
        printf("</pre></strong>\n");
}


?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php
        $tabs = array(    'TOP' => 'javascript:loadContent(\'diag_top.php\');',
                          'Disk Usage' => 'javascript:loadContent(\'diag_df.php\');',
                          'Routes' => 'javascript:loadContent(\'diag_routes.php\');',
                          'DHCP Leases' => 'javascript:loadContent(\'diag_dhcp_leases.php\');',
                          'ARP Table' => 'javascript:loadContent(\'diag_arp.php\');',
                          'Interfaces' => 'javascript:loadContent(\'diag_interfaces.php\');',
                          'XML Config' => 'javascript:loadContent(\'diag_xmlconf.php\');',
                          'dmesg' => 'javascript:loadContent(\'diag_dmesg.php\');' );
        dynamic_tab_menu($tabs);
?> 
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
		  <tr> 
			<td colspan="2" class="listxmlic"> 
		  </tr>
		  <?php dump_xml(); ?>
		</table>
		<br><form action="diag_xml_conf.php" method="post">
</form>
	</td>
  </tr>
</table>
