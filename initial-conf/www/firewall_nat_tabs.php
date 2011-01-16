#!/bin/php
<?php

$pgtitle = array("Firewall", "NAT");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        var index = 0;
        if (window.location.hash) {
             var hash = window.location.hash.substr(1);
             if (hash.match(/_tab_dynamic$/)) {
                 index = 0;
             }
             else if (hash.match(/_tab_1to1$/)) {
                 index = 1;
             }
        }
        $('#nattabs').tabs({ selected: index });
        $('#nattabs').tabs("select", index);

        $('#nattabs').bind( "tabsshow", function(event, ui) {
            var selected = $('#nattabs').tabs( "option", "selected" );
            switch (selected) {
                case 0:
                    document.location = '#firewall_nat_tabs_tab_dynamic';
                    break;
                case 1:
                    document.location = '#firewall_nat_tabs_tab_1to1';
                    break;
                default:
                    document.location = '#firewall_nat_tabs_tab_dynamic';
                    break;
            }
        });
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
