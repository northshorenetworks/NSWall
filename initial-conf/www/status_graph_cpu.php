#!/bin/php
<?php 

$pgtitle = array("Status", "CPU load");
require("guiconfig.inc");
include("ns-begin.inc");
?>
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>

<div id="container" style="height:450px;">
    <div id="placeholder" style="height:450px;"></div>
<div>

<script id="source" language="javascript" type="text/javascript"> 

$(function () {
    d1 = [];
    var iteration = 0;
    var options = {
        lines: { show: true },
        // points: { show: true },
        legend: { backgroundOpacity: .1 },
		xaxis: { mode: "time", timeformat: "%H:%M:%S", tickDecimals: 0 },
        yaxis: { min: 0, max: 100, tickDecimals: 0 }
    };
    function fetchData() {
            function onDataReceived(series) {
                series = series.replace("\n", '');
                d1.push([(new Date()).getTime(), series]); 
                if (d1.length > 120)  
                    d1.shift(); 
                $.plot($("#placeholder"), [ { data: d1, label: "CPU: "+Math.round(series)+"%" } ] , options);
                i++;
            }
        
            $.ajax({
                url: "stats.cgi?cpu",
                method: 'GET',
                dataType: 'text',
                success: onDataReceived
            });
            
            setTimeout(fetchData, 1500);
            iteration++;
      }
      
      setTimeout(fetchData, 1500);

});

$(function() {
    $("#container").resizable({
        alsoResize: '#placeholder'
    });
});

</script> 
