#!/bin/php
<?php 

$pgtitle = array("System", "Accounts");
require("guiconfig.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#accountstabs").tabs({ cache: false });
     });

</script>
   
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
	<div id="accountstabs">  
    	<ul>
		<li><a href="system_usermanager.php">Users</a></li>  
       	<li><a href="system_groupmanager.php">Groups</a></li>  
		</ul>  
	</div>
</div>
