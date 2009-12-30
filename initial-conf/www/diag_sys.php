#!/bin/php
<?php 

$pgtitle = array("Diagnostics", "System");
require("guiconfig.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#systabs").tabs({ cache: false });
     });

</script>
   
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
	<div id="systabs">  
    	<ul>
		<li><a href="stats.php?stat=top">TOP</a></li>  
       	<li><a href="stats.php?stat=df">Disk Usage</a></li>  
       	<li><a href="stats.php?stat=routes">Route Table</a></li>  
       	<li><a href="stats.php?stat=dhcp">DHCP Leases</a></li>
		<li><a href="stats.php?stat=arp">ARP Table</a></li>
		<li><a href="stats.php?stat=interfaces">Interfaces</a></li>  
     	<li><a href="stats.php?stat=xmlconf">XML Config</a></li>
		<li><a href="stats.php?stat=dmesg">dmesg</a></li>
		</ul>  
		<div id="livediag" class="livediagdiv"></div>
	</div>
</div>
