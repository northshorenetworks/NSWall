#!/bin/php -q


<?php
require("guiconfig.inc");

if ($argv[1] == 'firewall') {

	filter_rules_sort();
	$a_filter = &$config['filter']['rule'];

	if ($argv[2] == 'show') {
		if ($argv[3] == 'rules') {
			$i=0;
			foreach($a_filter as $rule) {
				echo $i.' '.$rule['name'].' '.strtoupper($rule['type']).' '.strtoupper($rule['interface']).
            ' '.join(",", $rule['srclist']).' '.join(",", $rule['dstlist'])."\n";
				$i++;
			}
		}
		if ($argv[3] == 'rule') {
			$id = $argv[4];
         $i=0;
         foreach($a_filter as $rule) {
     			if ($i == $id) {
             	echo $i.' '.$rule['name'].' '.strtoupper($rule['type']).' '.strtoupper($rule['interface']).
              	' '.join(",", $rule['srclist']).' '.join(",", $rule['dstlist']).' '.join(",", $rule['options'])."\n";
            }
				$i++;
         }
      }
	}
	if ($argv[2] == 'modify') {
      if ($argv[3] == 'rule') {
      	$id = $argv[4];
			if ($argv[5] == 'action') {
				$action = $argv[6];
				echo "We are changing rule $id to action $action\n";
				$i=0;
           	foreach($a_filter as $rule) {
         	  	if ($i == $id) { 
						$rule['type'] = $action;
						$a_filter[$id] = $rule;
					}	
					echo $i.' '.$rule['name'].' '.strtoupper($rule['type']).' '.strtoupper($rule['interface']).
               ' '.join(",", $rule['srclist']).' '.join(",", $rule['dstlist'])."\n";
					$i++;
				}
			}
			if ($argv[5] == 'interface') {
         	$interface = $argv[6];
            echo "We are changing rule $id to interface $interface\n";
            $i=0;
            foreach($a_filter as $rule) {
            	if ($i == $id) {
               	$rule['interface'] = $interface;
                  $a_filter[$id] = $rule;
               }
               echo $i.' '.$rule['name'].' '.strtoupper($rule['type']).' '.strtoupper($rule['interface']).
					' '.join(",", $rule['srclist']).' '.join(",", $rule['dstlist']).' '.join(",", $rule['options'])."\n";
					echo join(",", $rule['srclist'])."\n";
               $i++;
            }
         }
			if ($argv[5] == 'srclist') {
         	$srclist = $argv[6];
         	echo "We are changing rule $id to interface $interface\n";
         	$i=0;
         	foreach($a_filter as $rule) {
            	if ($i == $id) {
						$array = split(',', $srclist);
						$srclist = array();
						for($i=0; $i<sizeof($array); $i++) {
							$src = 'src'."$i";
                					$srclist[$src] = $array[$i];
						}
                  $rule['srclist'] = $srclist;
						$a_filter[$id] = $rule;
               }
               echo $i.' '.$rule['name'].' '.strtoupper($rule['type']).' '.strtoupper($rule['interface']).
               ' '.join(",", $rule['srclist']).' '.join(",", $rule['dstlist']).' '.join(",", $rule['options'])."\n";
               $i++;
            }
         }
      }
		write_config();
		config_lock();
      $retval = filter_configure();
      config_unlock();
   }
}

#print_r($a_filter);
?>
