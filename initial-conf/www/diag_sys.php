#!/bin/php
<?php 
$pgtitle = array("Diagnostics", "System");
require("guiconfig.inc");
include("ns-begin.inc");
?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        var index = 0;
        if (window.location.hash) {
             var hash = window.location.hash.substr(1);
             if (hash.match(/_tab_top$/)) {
                 index = 0;   
             } 
             else if (hash.match(/_tab_du$/)) {
                 index = 1;   
             } 
             else if (hash.match(/_tab_route$/)) {
                 index = 2;   
             }
             else if (hash.match(/_tab_dhcp$/)) {
                 index = 3;   
             }
             else if (hash.match(/_tab_arp$/)) {
                 index = 4;   
             }
             else if (hash.match(/_tab_ifconfig$/)) {
                 index = 5;   
             }
             else if (hash.match(/_tab_xml$/)) {
                 index = 6;   
             }
             else if (hash.match(/_tab_dmesg$/)) {
                 index = 7;   
             }
             else if (hash.match(/_tab_tcpdump$/)) {
                 index = 8;   
             }
        }
        $('#systabs').tabs({ selected: index });
        $('#systabs').tabs("select", index); 

        $('#systabs').bind( "tabsshow", function(event, ui) {
            var selected = $('#systabs').tabs( "option", "selected" );
            switch (selected) {
                case 0:
                    document.location = '#diag_sys_tab_top';
                    break;
                case 1:
                    document.location = '#diag_sys_tab_du';
                    break;
                case 2:
                    document.location = '#diag_sys_tab_route';
                    break;
                case 3:
                    document.location = '#diag_sys_tab_dhcp';
                    break;
                case 4:
                    document.location = '#diag_sys_tab_arp';
                    break;
                case 5:
                    document.location = '#diag_sys_tab_ifconfig';
                    break;
                case 6:
                    document.location = '#diag_sys_tab_xml';
                    break;
                case 7:
                    document.location = '#diag_sys_tab_dmesg';
                    break;
                case 8:
                    document.location = '#diag_sys_tab_tcpdump';
                    break;
                default:
                    document.location = '#diag_sys_tab_top';
                    break;
            }
        });
     });

</script>
   
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>  
   
<div class="demo">  
    <div id="systabs">  
        <ul>
        <li><a href="stats.php?stat=top">TOP</a></li>  
        <li><a href="stats.php?stat=df">Disk Usage</a></li>  
        <li><a href="stats.php?stat=routes">Route Table</a></li>  
        <li><a href="stats.php?stat=dhcp">DHCP Leases</a></li>
        <li><a href="stats.php?stat=arp">ARP Table</a></li>
        <li><a href="stats.php?stat=interfaces">Interfaces</a></li>  
        <li><a href="stats.php?stat=xmlconf">XML Config</a></li>
        <li><a href="stats.php?stat=dmesg">dmesg</a></li>
        <li><a href="stats.php?stat=tcpdump">tcpdump</a></li>
        </ul>  
        <div id="livediag" class="livediagdiv"></div>
    </div>
</div>
