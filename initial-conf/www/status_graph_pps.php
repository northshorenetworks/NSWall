#!/bin/php
<?php 

$pgtitle = array("Status", "PF PPS graph");
require("guiconfig.inc");
include("ns-begin.inc");
$currule = $config['filter']['rule']['0']['name'];
if ($_GET['rulename'])
        $currule = $_GET['rulename'];

?>

<p class="pgtitle"><?=join(": ", $pgtitle);?></p>
Rule:
<select name="rulename" class="formfld">
<?php
foreach ($config['filter']['rule'] as $rule) {
	echo "<option value=\"{$rule['name']}\"";
	if ($rule['name'] == $currule) echo " selected";
	echo ">" . htmlspecialchars($rule['name']) . "</option>\n";
}
?>
</select>

<div id="container" style="height:450px;">
    <div id="placeholder" style="height:450px;"></div>
<div>

<script id="source" language="javascript" type="text/javascript">

$(function () {
    d1 = [];
    var oldbits = 0;
    var oldtime = 0;
    var iteration = 0;
    var options = {
        lines: { show: true },
        legend: { backgroundOpacity: .1 },
		xaxis: { mode: "time", timeformat: "%H:%M:%S", tickDecimals: 0 },
        yaxis: { min: 0, tickDecimals: 0 }
    };
    function fetchData() {
            function onDataReceived(series) { 
                var Response = series.split("|");
                var time = Response[0];
                var pps = Response[1];

                if(iteration > 1) {
                   var graphval = eval(eval(pps - oldpps) / (time - oldtime));
                   d1.push([(new Date()).getTime(), graphval]);
                   if (d1.length > 120)
                      d1.shift();
                   $.plot($("#placeholder"), [ { data: d1, label: "PPS: "+Math.round(graphval) } ], options);
                }
                oldpps = pps;
                oldtime = time;
            }

            $.ajax({
                url: "statuspps.cgi?<?=filter_get_anchornumber($currule);?>",
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
          $('#content').load('status_graph_pps.php?rulename=' + $(this).val());
});
</script>
