#!/bin/php
<?php

$pgtitle = array("System", "Routes");
require("guiconfig.inc");
include("ns-begin.inc");

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        var index = 0;
        if (window.location.hash) {
             var hash = window.location.hash.substr(1);
             if (hash.match(/_tab_rdomain$/)) {
                 index = 0;
             }
             else if (hash.match(/_tab_static$/)) {
                 index = 1;
             }
        }
        $('#routestabs').tabs({ selected: index });
        $('#routestabs').tabs("select", index);

        $('#routestabs').bind( "tabsshow", function(event, ui) {
            var selected = $('#routestabs').tabs( "option", "selected" );
            switch (selected) {
                case 0:
                    document.location = '#system_routes_tabs_tab_rdomain';
                    break;
                case 1:
                    document.location = '#system_routes_tabs_tab_static';
                    break;
                default:
                    document.location = '#system_routes_tabs_tab_rdomain';
                    break;
            }
        });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
<div id="routestabs">
<ul>
	<li><a href="system_route_tables.php">Route Tables</a></li>
	<li><a href="system_routes.php">Static Routes</a></li>
</ul>
</div>
</div>
<div id="currentorder"></div>
