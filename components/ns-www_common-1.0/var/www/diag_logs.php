#!/bin/php
<?php
$pgtitle = array("Diagnostics", "Logs");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">    
     $(document).ready(function() {  
        clearInterval(refreshId);  
        $("#logtabs").tabs({ cache: false });  
     });  
</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
<div id="logtabs">
<ul>
	<li><a href="stats.php?stat=logs">System Logs</a></li>
	<li><a href="diag_logs_settings.php">Remote Logging Setup</a></li>
</ul>
<div id="livediag" class="livediagdiv"></div>
</div>
</div>
