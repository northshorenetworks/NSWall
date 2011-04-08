<?php
	if ($argv[1] == 'status') {

		if ($argv[2] == 'interfaces') {	
        		exec('ifconfig', $out);
			foreach ($out as $row) echo $row."\n";
		}	
		if ($argv[2] == 'rules') {
			exec('pfctl -sr', $out);
                        foreach ($out as $row) echo $row."\n";	
		}
		if ($argv[2] == 'states') {
                        exec('pfctl -s states', $out);
                        foreach ($out as $row) echo $row."\n";
                }
		if ($argv[2] == 'disk') {
                        exec('df -h', $out);
                        foreach ($out as $row) echo $row."\n";
                }
	} else {
		echo "I don't know what to do with command $argv[1]\n";
	}
?>
