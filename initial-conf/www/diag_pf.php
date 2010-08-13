#!/bin/php
<?php 
$pgtitle = array("Diagnostics", "Packet Filter");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">    
     $(document).ready(function() {  
        clearInterval(refreshId);  
        $("#pftabs").tabs({ cache: false });  
     });  
    
</script>    

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
	<div id="pftabs">  
    	<ul>
			<li><a href="stats.php?stat=pfconf">Configuration</a></li>  
       		<li><a href="stats.php?stat=pfrules">Rules</a></li>  
       		<li><a href="stats.php?stat=pfstates">States</a></li>
			<li><a href="stats.php?stat=pfoptions">Options</a></li>
			<li><a href="stats.php?stat=pfqueues">Queues</a></li>  
     		<!--<li><a href="stats.php?stat=pfblockedsites">Blocked Sites</a></li>-->	
		</ul>  
		<div id="livediag" class="livediagdiv"></div>
	</div>
</div>
