#!/bin/php
<?php 

$pgtitle = array("Status", "PF PPS graph");
require("guiconfig.inc");
$currule = $config['filter']['rule']['0']['name'];
if ($_GET['rulename'])
        $currule = $_GET['rulename'];
?>

<script type="text/javascript">

$("select").change( function() {
  //alert($(this).val());
  $('#content').load('status_graph_pps.php?rulename=' + $(this).val());
});

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>
Rule:
<select name="rulename" class="formfld">
<?php
foreach ($config['filter']['rule'] as $rule) {
	echo "<option value=\"{$rule['name']}\"";
	if ($rule['name'] == $currule) echo " selected";
	echo ">" . htmlspecialchars($rule['name']) . "</option>\n";
}
?>
</select>
<div align="center">
<embed src="graph_pps.php?rulenum=<?=filter_get_anchornumber($currule);?>&rulename=<?=rawurlencode("$currule");?>" type="image/svg+xml"
		width="550" height="275" pluginspage="http://www.adobe.com/svg/viewer/install/auto" />
</div>
<br><center><span class="red"><strong>Note:</strong></span> if you can't see the graph, you may have to install the <a href="http://www.adobe.com/svg/viewer/install/" target="_blank">Adobe SVG viewer</a>.</center>
