#!/bin/php
<?php

$pgtitle = array("Services", "DHCP Server");
require("guiconfig.inc");
include("ns-begin.inc");
$iflist = array("lan" => "LAN");

for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
	if ($config['interfaces']['opt' . $i]['wireless']['ifmode'] != 'lanbridge' && $config['interfaces']['wireless']['ifmode'] != 'dmzbridge')
	$oc = $config['interfaces']['opt' . $i];

	if (isset($oc['enable']) && $oc['if'] && (!$oc['bridge'])) {
		$iflist['opt' . $i] = $oc['descr'];
	}
}

for ($i = 0; isset($config['vlans']['vlan'][$i]); $i++) {
	$iflist[$config['vlans']['vlan'][$i]['descr']] = "{$config['vlans']['vlan'][$i]['descr']}";
}

?>

<script type="text/javascript">
     $(document).ready(function() {
        clearInterval(refreshId);
        $("#dhcptabs").tabs({ cache: false });
     });

</script>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div class="demo">
<div id="dhcptabs">
<ul>
<?php $i = 0; foreach ($iflist as $ifent => $ifname): ?>
<?php if(get_rtable_name_by_ifdescr($ifname) == 'DEFAULT' || $ifname == 'LAN'): ?>
	<li><a href="services_dhcp.php?if=<?=$ifent;?>"><?=htmlspecialchars($ifname);?></a></li>
	<?php endif; ?>
	<?php $i++; endforeach; ?>
</ul>
</div>
</div>

