#!/bin/php
<?php 

$pgtitle = array("System", "Routes");
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
             <li><a href="system_routes.php">Static Routes</a></li>
        </ul>
    </div>
</div>
<div id="currentorder"></div>
