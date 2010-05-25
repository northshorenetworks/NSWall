#!/bin/php
<?php 

$pgtitle = array("Status", "Traffic graph");
require("guiconfig.inc");
include("ns-begin.inc");

system_determine_hwplatform();

$curif = "wan";
if ($_GET['if'])
	$curif = $_GET['if'];
	
if ($curif == "wan")
	$ifnum = get_real_wan_interface();
else
	$ifnum = get_interface_name_by_descr($curif);
?>

<?php

$ifdescrs = array('wan' => 'WAN', 'lan' => 'LAN');
	
for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
	$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
}
?>

<script type="text/javascript">

$("select").change( function() {
  //alert($(this).val());
  $('#content').load('status_graph.php?if=' + $(this).val());
});

</script>


<p class="pgtitle"><?=join(": ", $pgtitle);?></p>
Interface: 

<select name="if" class="formfld">
<?php
foreach ($ifdescrs as $ifn => $ifd) {
	echo "<option value=\"$ifd\"";
	if ($ifd == $curif) echo " selected";
	echo ">" . htmlspecialchars($ifd) . "</option>\n";
}
?>
</select>

<div align="center">
<embed src="graph.php?ifnum=<?=$ifnum;?>&ifname=<?=rawurlencode($ifdescrs[$curif]);?>" type="image/svg+xml"
		width="550" height="275" pluginspage="http://www.adobe.com/svg/viewer/install/auto" />
</div>
<br><center><span class="red"><strong>Note:</strong></span> if you can't see the graph, you may have to install the <a href="http://www.adobe.com/svg/viewer/install/" target="_blank">Adobe SVG viewer</a>.</center>
