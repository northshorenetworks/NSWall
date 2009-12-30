#!/bin/php
<?php 

$pgtitle = array("Diagnostics", "VPN");
require("guiconfig.inc");

?>

<script type="text/javascript">    
     $(document).ready(function() {  
        clearInterval(refreshId);  
        $("#vpntabs").tabs({ cache: false });  
     });  
    
</script>    

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
	<div id="vpntabs">  
    	<ul>
		<li><a href="stats.php?stat=ipsecconf">IPSEC.conf</a></li>  
       	<li><a href="stats.php?stat=isakmpdconf">ISAKMPD.conf</a></li>  
     	</ul>  
		<div id="livediag" class="livediagdiv"></div>
	</div>
</div>
