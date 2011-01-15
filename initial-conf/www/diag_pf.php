#!/bin/php
<?php 
$pgtitle = array("Diagnostics", "Packet Filter");
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
             else if (hash.match(/_tab_rules$/)) {
                 index = 1;   
             } 
             else if (hash.match(/_tab_states$/)) {
                 index = 2;   
             }
             else if (hash.match(/_tab_opts$/)) {
                 index = 3;   
             }
             else if (hash.match(/_tab_queues$/)) {
                 index = 4;   
             }
        }
        $('#pftabs').tabs({ selected: index });
        $('#pftabs').tabs("select", index); 

        $('#pftabs').bind( "tabsshow", function(event, ui) {
            var selected = $('#pftabs').tabs( "option", "selected" );
            switch (selected) {
                case 0:
                    document.location = '#diag_pf_tab_config';
                    break;
                case 1:
                    document.location = '#diag_pf_tab_rules';
                    break;
                case 2:
                    document.location = '#diag_pf_tab_states';
                    break;
                case 3:
                    document.location = '#diag_pf_tab_opts';
                    break;
                case 4:
                    document.location = '#diag_pf_tab_queues';
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
    <div id="pftabs">  
        <ul>
        <li><a href="stats.php?stat=pfconf">Configuration</a></li>  
            <li><a href="stats.php?stat=pfrules">Rules</a></li>  
            <li><a href="stats.php?stat=pfstates">States</a></li>
        <li><a href="stats.php?stat=pfoptions">Options</a></li>
        <li><a href="stats.php?stat=pfqueues">Queues</a></li>  
            <!--<li><a href="stats.php?stat=pfblockedsites">Blocked Sites</a></li>-->   
        </ul>  
        <div id="livediag" class="livediagdiv"></div>
    </div>
</div>
