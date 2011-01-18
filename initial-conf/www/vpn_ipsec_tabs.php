#!/bin/php
<?php

$pgtitle = array("VPN", "IPSec", "Gateways");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#gatewaytabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
<div id="gatewaytabs">
<ul>
	<li><a href="vpn_ipsec.php">IPSec Gateways</a></li>
</ul>
</div>
</div>
<div id="currentorder"></div>
