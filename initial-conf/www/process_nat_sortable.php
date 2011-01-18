#!/bin/php
<?php

require("guiconfig.inc");

if (!is_array($config['nat']['advancedoutbound']['rule']))
    $config['nat']['advancedoutbound']['rule'] = array();

//nat_out_rules_sort();

$a_dnat = &$config['nat']['advancedoutbound']['rule'];
$a_dnat_new = array();

/* copy sortif rules */
foreach ($_GET['listItem'] as $position => $item) :
    $a_dnat_new[] = $a_dnat[$item];
endforeach;

$a_dnat = $a_dnat_new;
write_config();

?>
