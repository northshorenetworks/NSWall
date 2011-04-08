#!/bin/php
<?php

require("guiconfig.inc");

if (!is_array($config['filter']['rule'])) {
	$config['filter']['rule'] = array();
}

filter_rules_sort();

$a_filter = &$config['filter']['rule'];
$a_filter_new = array();

echo '<pre>';
/* copy all rules that are not on the desired sort interface */
for ($i = 0; $i < count($a_filter); $i++) {
	$filterent = $a_filter[$i];
	if ($filterent['interface'] != $_GET['sortif'])
	$a_filter_new[] = $a_filter[$i];
}

/* copy sortif rules */
foreach ($_GET['listItem'] as $position => $item) :
$a_filter_new[] = $a_filter[$item];
endforeach;

$a_filter = $a_filter_new;
write_config();

?>
