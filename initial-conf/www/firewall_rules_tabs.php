#!/bin/php
<?php 

$pgtitle = array("Firewall", "Rules");
require("guiconfig.inc");
include("ns-begin.inc");

    $iflist = array("wan" => "WAN","lan" => "LAN");

    if ($config['pptpd']['mode'] == "server")
        $iflist['pptp'] = "PPTP VPN";

    for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
        if ($config['interfaces']['opt' . $i]['wireless']['ifmode'] != 'lanbridge' && $config['interfaces']['wireless']['ifmode'] != 'dmzbridge')
        $iflist['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
    }

    for ($i = 0; isset($config['vlans']['vlan'][$i]); $i++) {
        $iflist['vlan' . $config['vlans']['vlan'][$i]['tag']] = "VLAN{$config['vlans']['vlan'][$i]['tag']}";
    }
?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#iftabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
    <div id="iftabs">
        <ul>
             <li><a href="firewall_rules.php">Firewall Rules</a></li>
        </ul>
    </div>
</div>
<div id="currentorder"></div>
