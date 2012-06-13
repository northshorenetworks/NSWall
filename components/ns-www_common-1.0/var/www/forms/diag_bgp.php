#!/bin/php
<?php 
$pgtitle = array("Diagnostics", "BGPd");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">    
     $(document).ready(function() {  
        clearInterval(refreshId);  
        $("#bgptabs").tabs({ cache: false });  
     });  
    
</script>    

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
	<div id="bgptabs">  
    	<ul>
		<li><a href="stats.php?stat=bgpconf">bgpd.conf</a></li>  
       	        <li><a href="stats.php?stat=isakmpdconf">ISAKMPD.conf</a></li>  
		<li><a href="stats.php?stat=ipsec_flows">IPSec Flows</a></li>
		<li><a href="stats.php?stat=ipsec_sas">IPSec SADs</a></li>
	</ul>  
		<div id="livediag" class="livediagdiv"></div>
	</div>
</div>
