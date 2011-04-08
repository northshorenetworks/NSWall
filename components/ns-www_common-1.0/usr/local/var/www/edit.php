#!/bin/php
<?php
/* $Id: edit.php,v 1.35.2.10 2008/08/01 06:30:57 mgrooms Exp $ */
/*
 edit.php
 Copyright (C) 2004, 2005 Scott Ullrich
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

if (($_POST['submit'] == "Load") && file_exists($_POST['savetopath'])) {
	$fd = fopen($_POST['savetopath'], "r");
	if ((filesize($_POST['savetopath']) != "0")) {  $content = fread($fd, filesize($_POST['savetopath'])); } else { $content = ""; }
	fclose($fd);
	$edit_area="";
	$loadmsg = "Loaded text from " . $_POST['savetopath'];
	if(stristr($_POST['savetopath'], ".php") == true)
	$language = "php";
	else if(stristr($_POST['savetopath'], ".inc") == true)
	$language = "php";
	else if(stristr($_POST['savetopath'], ".sh") == true)
	$language = "core";
	else if(stristr($_POST['savetopath'], "rc.") == true)
	$language = "core";
	else if(stristr($_POST['savetopath'], ".xml") == true)
	$language = "xml";
	$savetopath = $_POST['savetopath'];
} else if (($_POST['submit'] == "Save")) {
	conf_mount_rw();
	$content = ereg_replace("\r","",$_POST['code']) ;
	$fd = fopen($_POST['savetopath'], "w");
	fwrite($fd, $content);
	fclose($fd);
	$edit_area="";
	$savemsg = "Saved text to " . $_POST['savetopath'];
	if($_POST['savetopath'] == "/cf/conf/config.xml")
	unlink_if_exists("/tmp/config.cache");
	conf_mount_ro();
	$savetopath = $_POST['savetopath'];
} else if (($_POST['submit'] == "Load") && !file_exists($_POST['savetopath'])) {
	$savemsg = "File not found " . $_POST['savetopath'];
	$content = "";
	$_POST['savetopath'] = "";
}

if($_POST['highlight'] <> "") {
	if($_POST['highlight'] == "yes" or
	$_POST['highlight'] == "enabled") {
		$highlight = "yes";
	} else {
		$highlight = "yes";
	}
} else {
	$highlight = "yes";
}
?>
<?php

// Function: is Blank
// Returns true or false depending on blankness of argument.

function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }

// Function: Puts
// Put string, Ruby-style.

function puts( $arg ) { echo "$arg\n"; }

// "Constants".

$Version    = '';
$ScriptName = $HTTP_SERVER_VARS['SCRIPT_NAME'];

// Get year.

$arrDT   = localtime();
$intYear = $arrDT[5] + 1900;

$pgtitle = array("Debug","Edit File");

?>


<script language="Javascript">
function sf() { document.forms[0].savetopath.focus(); }
</script>
<body onLoad="sf();">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if ($loadmsg) echo "<p><b><div style=\"background:#eeeeee\" id=\"shapeme\">&nbsp;&nbsp;&nbsp;{$loadmsg}</div><br>"; ?>
<form action="edit.php" method="POST">

<div id="shapeme">
<table width="100%" cellpadding='9' cellspacing='9' bgcolor='#eeeeee'>
	<tr>
		<td>
		<center>Save/Load from path: <input size="42" id="savetopath"
			class="formfld unknown" name="savetopath"
			value="<?php echo $savetopath; ?>"> <input name="submit"
			type="submit" class="button" id="Load" value="Load"> <input
			name="submit" type="submit" class="button" id="Save" value="Save">
		<hr noshade>
		
		</td>
	</tr>
</table>
</div>

<br>

<table width='100%'>
	<tr>
		<td valign="top" class="label">
		<div style="background: #eeeeee" id="textareaitem">&nbsp;<br>
		&nbsp; <textarea style="width: 98%" name="code"
			language="<?php echo $language; ?>" rows="30" cols="66"
			name="content"><?php echo htmlentities($content); ?></textarea><br>
		&nbsp;</div>
		<p>
		
		</td>
	</tr>
</table>
</form>
</body>
</html>

<script language="Javascript">
sf();
</script>

</div>
<link
	href="style/SyntaxHighlighter.css" rel="stylesheet" type="text/css">
<script language="javascript"
	src="js/shCore.js"></script>
<script
	language="javascript" src="js/shBrushPhp.js"></script>
<script
	language="javascript" src="js/shBrushJScript.js"></script>
<script
	language="javascript" src="js/shBrushXml.js"></script>

<?php
echo "<script language=\"javascript\">\n";
echo "dp.SyntaxHighlighter.HighlightAll('code', true, true);\n";
echo "</script>\n";
?>
