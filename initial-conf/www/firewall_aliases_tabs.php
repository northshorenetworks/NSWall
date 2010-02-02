#!/bin/php
<?php 

$pgtitle = array("Firewall", "NAT", "Dynamic");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#aliastabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
    <div id="aliastabs">
        <ul>
             <li><a href="firewall_aliases.php">Aliases</a></li>
        </ul>
    </div>
</div>
<div id="currentorder"></div>
