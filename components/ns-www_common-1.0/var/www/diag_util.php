#!/bin/php
<?php
$pgtitle = array("Diagnostics", "Utilities");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        var index = 0;
        if (window.location.hash) {
             var hash = window.location.hash.substr(1);
             if (hash.match(/_tab_tcpdump$/)) {
                 index = 0;   
             } 
             else if (hash.match(/_tab_backup$/)) {
                 index = 1;   
             } 
             else if (hash.match(/_tab_default$/)) {
                 index = 2;   
             }
             else if (hash.match(/_tab_reboot$/)) {
                 index = 3;   
             }
        }
        $('#utiltabs').tabs({ selected: index });
        $('#utiltabs').tabs("select", index); 

        $('#utiltabs').bind( "tabsshow", function(event, ui) {
            var selected = $('#utiltabs').tabs( "option", "selected" );
            switch (selected) {
                case 0:
                    document.location = '#diag_util_tab_tcpdump';
                    break;
                case 1:
                    document.location = '#diag_util_tab_backup';
                    break;
                case 2:
                    document.location = '#diag_util_tab_default';
                    break;
                case 3:
                    document.location = '#diag_util_tab_reboot';
                    break;
                default:
                    document.location = '#diag_util_tab_tcpdump';
                    break;
            }
        });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
<div id="utiltabs">
<ul>
	<!--<li><a href="diag_ping.php">Ping</a></li>  
        <li><a href="diag_traceroute.php">Traceroute</a></li>-->
	<li><a href="diag_tcpdump.php">Tcp Dump</a></li>
	<li><a href="diag_backup.php">Backup/Restore</a></li>
	<li><a href="diag_defaults.php">Factory Default</a></li>
	<li><a href="reboot.php">Reboot</a></li>
</ul>
</div>
</div>
