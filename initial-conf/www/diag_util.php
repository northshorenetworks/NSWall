#!/bin/php
<?php 
$pgtitle = array("Diagnostics", "Utilities");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">  
     $(function() {  
         $("#utiltabs").tabs();  
     });  
</script>  
   
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
	<div id="utiltabs">  
    	<ul>
		<li><a href="diag_ping.php">Ping</a></li>  
       	<li><a href="diag_traceroute.php">Traceroute</a></li>  
       	<li><a href="diag_tcpdump.php">Tcp Dump</a></li>
       	<li><a href="diag_backup.php">Backup/Restore</a></li>
		<li><a href="diag_defaults.php">Factory Default</a></li>
		<li><a href="reboot.php">Reboot</a></li>
		</ul>  
	</div>
</div>
