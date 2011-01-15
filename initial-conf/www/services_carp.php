#!/bin/php
<?php 

$pgtitle = array("Services", "CARP");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        var index = 0;
        if (window.location.hash) {
             var hash = window.location.hash.substr(1);
             if (hash.match(/_tab_config$/)) {
                 index = 0;
             }
             else if (hash.match(/_tab_vid$/)) {
                 index = 1;
             }
             else if (hash.match(/_tab_pfsync$/)) {
                 index = 2;
             }
             else if (hash.match(/_tab_configsync$/)) {
                 index = 3;
             }
        }
        $('#carptabs').tabs({ selected: index });
        $('#carptabs').tabs("select", index);

        $('#carptabs').bind( "tabsshow", function(event, ui) {
            var selected = $('#carptabs').tabs( "option", "selected" );
            switch (selected) {
                case 0:
                    document.location = '#services_carp_tab_config';
                    break;
                case 1:
                    document.location = '#services_carp_tab_vid';
                    break;
                case 2:
                    document.location = '#services_carp_tab_pfsync';
                    break;
                case 3:
                    document.location = '#services_carp_tab_configsync';
                    break;
                default:
                    document.location = '#diag_pf_tab_config';
                    break;
            }
        });
     });

</script>


<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
    <div id="carptabs">  
        <ul>
    <li><a href="firewall_carp.php">Configuration</a></li>  
        <li><a href="firewall_carp_vid.php">Virtual Host ID's</a></li>  
        <li><a href="firewall_pfsync.php">PFSync</a></li>  
        <li><a href="firewall_carp_sync_hosts.php">Config Sync Hosts</a></li>
        </ul>  
    </div>
</div>
