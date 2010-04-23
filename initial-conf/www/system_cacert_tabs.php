#!/bin/php
<?php 

$pgtitle = array("System", "Certificate Manager");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#cacerttabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
    <div id="cacerttabs">
        <ul>
             <li><a href="system_camanager.php">CA's</a></li>
        	 <li><a href="system_certmanager.php">Certificates</a></li>
		</ul>
    </div>
</div>
<div id="currentorder"></div>
