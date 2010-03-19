#!/bin/php
<?php 

$pgtitle = array("Firewall", "NAT");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#nattabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
    <div id="nattabs">
        <ul>
             <li><a href="firewall_nat_dynamic.php">Dynamic NAT</a></li>
			 <li><a href="firewall_nat_1to1.php">1 to 1 NAT</a></li>
        </ul>
    </div>
</div>
<div id="currentorder"></div>
