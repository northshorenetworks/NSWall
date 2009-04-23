#!/bin/php
<?php 
/*
	$Id: status_graph_states.php,v 1.1 2009/03/18 05:25:28 jrecords Exp $
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

$pgtitle = array("Status", "PF States graph");
require("guiconfig.inc");
include("fbegin.inc");
$currule = $config['filter']['rule']['0']['name'];
if ($_GET['rulename'])
        $currule = $_GET['rulename'];

?>
<form name="form1" action="" method="get" style="padding-bottom: 10px; margin-bottom: 14px; border-bottom: 1px solid #999999">
Rule:
<select name="rulename" class="formfld" onchange="document.form1.submit()">
<?php
foreach ($config['filter']['rule'] as $rule) {
	echo "<option value=\"{$rule['name']}\"";
	if ($rule['name'] == $currule) echo " selected";
	echo ">" . htmlspecialchars($rule['name']) . "</option>\n";
}
?>
</select>
</form>
<div align="center">
<embed src="graph_states.php?rulenum=<?=filter_get_anchornumber($currule);?>&rulename=<?=rawurlencode("$currule");?>" type="image/svg+xml"
		width="550" height="275" pluginspage="http://www.adobe.com/svg/viewer/install/auto" />
</div>
<br><span class="red"><strong>Note:</strong></span> if you can't see the graph, you may have to install the <a href="http://www.adobe.com/svg/viewer/install/" target="_blank">Adobe SVG viewer</a>.
<?php include("fend.inc"); ?>
