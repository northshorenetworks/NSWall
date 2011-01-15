#!/bin/php
<?php

$pgtitle = array("Status", "Traffic graph");
require("guiconfig.inc");
include("ns-begin.inc");

system_determine_hwplatform();

$curif = "wan";
if ($_GET['if'])
    $curif = $_GET['if'];

if ($curif == "wan")
    $ifnum = get_real_wan_interface();
else
    $ifnum = get_interface_name_by_descr($curif);
?>

<?php

$ifdescrs = array('wan' => 'WAN', 'lan' => 'LAN');

for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
    $ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
}

if (is_array($config['vlans']['vlan'])) {
    foreach ($config['vlans']['vlan'] as $vlan) {
        $ifdescrs[$vlan['descr']] = $vlan['descr'];
    }
} 
?>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>
Interface:

<select name="if" class="formfld">
<?php
foreach ($ifdescrs as $ifn => $ifd) {
    echo "<option value=\"$ifd\"";
    if ($ifd == $curif) echo " selected";
    echo ">" . htmlspecialchars($ifd) . "</option>\n";
}
?>
</select>

<div id="container" style="height:450px;">
    <div id="placeholder" style="height:450px;"></div>
<div>

<script id="source" language="javascript" type="text/javascript">

// Convert Bytes to bits
function bytes2bits(val) {
    if (val < 125000)
        return (Math.round(val / 125)) + " Kb/s";
    if (val < 125000000)
        return (Math.round(val / 1250)/100) + " Mb/s";
    //else 
        return (Math.round(speed / 1250000)/100) + " Gb/s";
}

$(function () {
    d1 = [];
    d2 = [];
    var oldtxbytes = 0;
    var oldrxbytes = 0;
    var oldtime = 0;
    var iteration = 0;
    var options = {
        lines: { show: true },
        legend: { backgroundOpacity: .1 },
        xaxis: { mode: "time", timeformat: "%H:%M:%S", tickDecimals: 0 },
        yaxis: { min: 0, 
             tickDecimals: 1, 
             tickFormatter: function suffixFormatter(val, axis) {
                 if (val < 125000)
                     return (Math.round(val / 125)).toFixed(axis.tickDecimals) + " Kb/s";
                 if (val < 125000000)
                     return (Math.round(val / 1250)/100).toFixed(axis.tickDecimals) + " Mb/s";
                 ///else 
                return (Math.round(speed / 1250000)/100).toFixed(axis.tickDecimals) + " Gb/s";
             }
        }
    };
    function fetchData() {
            function onDataReceived(series) { 
                var Response = series.split("|");
                var time = Response[0];
                var rxbytes = Response[1];
                var txbytes = Response[2];
              
                if(iteration > 1) {
                   var txval = eval(eval(txbytes - oldtxbytes) / (time - oldtime));
                   var rxval = eval(eval(rxbytes - oldrxbytes) / (time - oldtime));
                   //alert('graphval: '+graphval+' series: '+series+' oldbits: '+oldbits+' time: '+time+' oldtime: '+oldtime);
                   d1.push([(new Date()).getTime(), txval]);
                   d2.push([(new Date()).getTime(), rxval]);
                   if (d1.length > 120) {
                      d1.shift();
                      d2.shift();
                   }
                   $.plot($("#placeholder"), [{ data: d1, label: "TX: "+bytes2bits(txval) }, { data: d2, label: "RX: "+bytes2bits(rxval) }], options);
                }
                oldtxbytes = txbytes;
                oldrxbytes = rxbytes;
                oldtime = time;
            }

            $.ajax({
                url: "stats.cgi?<?=$ifnum?>",
                method: 'GET',
                dataType: 'text',
                success: onDataReceived
            });
            iteration++;
      }
      refreshId = setInterval(fetchData, 1000);
});



$(function() {
    $("#container").resizable({
        alsoResize: '#placeholder'
    });
});

$("select").change( function() {
	  //alert($(this).val());
          clearInterval(refreshId);
          $('#content').load('status_graph.php?if=' + $(this).val());
});
</script>
