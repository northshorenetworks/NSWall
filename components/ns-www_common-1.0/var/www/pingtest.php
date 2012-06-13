#!/bin/php
<?php
$host = $_GET['host'];

echo '<div style=""><label>PPTP Status</label>';
exec("/sbin/ping -q -w 2 -c 1 $host", $output);
preg_match("/\s(\d+.\d+)/", $output[3], $matches);
if (preg_match("/1 packets received/", $output[2])) {
	echo "<img alt=\"greendot\" title=\"Host: $host $matches[0]ms\" class=\"png\" width=\"16\" height=\"16\" src=\"/images
/greendot1.jpg\" ></a>";
} else {
	echo "<img alt=\"reddot\" title=\"Host: $host request timed out\" class=\"png\" width=\"16\" height=\"16\" src=\"/im
ages/reddot1.jpg\" ></a>";
}
echo '</div>';
?>
