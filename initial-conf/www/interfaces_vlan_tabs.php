#!/bin/php
<?php 

$pgtitle = array("Interfaces", "VLANs");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#vlantabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
    <div id="vlantabs">
        <ul>
             <li><a href="interfaces_vlan.php">VLANS</a></li>
        </ul>
    </div>
</div>
<div id="currentorder"></div>
