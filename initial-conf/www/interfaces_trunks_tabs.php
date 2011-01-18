#!/bin/php
<?php 

$pgtitle = array("Interfaces", "Trunks");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#trunktabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
    <div id="trunktabs">
        <ul>
             <li><a href="interfaces_trunk.php">Trunks</a></li>
        </ul>
    </div>
</div>
<div id="currentorder"></div>
