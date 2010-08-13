#!/bin/php
<?php
require("guiconfig.inc");

$stat = $_GET['stat'];
$rdomain = $_GET['rdomain'];

function xml_highlight($s)
{       
    $s = htmlspecialchars($s);
    $s = preg_replace("#&lt;([/]*?)(.*)([\s]*?)&gt;#sU",
        "<font color=\"#0000FF\">&lt;\\1\\2\\3&gt;</font>",$s);
    $s = preg_replace("#&lt;([\?])(.*)([\?])&gt;#sU",
        "<font color=\"#800000\">&lt;\\1\\2\\3&gt;</font>",$s);
    $s = preg_replace("#&lt;([^\s\?/=])(.*)([\[\s/]|&gt;)#iU",
        "&lt;<font color=\"#808000\">\\1\\2</font>\\3",$s);
    $s = preg_replace("#&lt;([/])([^\s]*?)([\s\]]*?)&gt;#iU",
        "&lt;\\1<font color=\"#808000\">\\2</font>\\3&gt;",$s);
    $s = preg_replace("#([^\s]*?)\=(&quot;|')(.*)(&quot;|')#isU",
        "<font color=\"#800080\">\\1</font>=<font color=\"#FF00FF\">\\2\\3\\4</font>",$s);
    $s = preg_replace("#&lt;(.*)(\[)(.*)(\])&gt;#isU",
        "&lt;\\1<font color=\"#800080\">\\2\\3\\4</font>&gt;",$s);
    return $s;
}

function dump_pfconf() {
    global $g, $config;
    printf("<pre>");
    require_once("filter.inc");
    $pfconf = filter_print_pfconf();
    $pfconf = htmlentities($pfconf);
    printf("$pfconf");
    printf("</pre>");
}

function dump_pfoptions() {
    global $g, $config;
    printf("<pre>");
    $pfrules = "Timeouts:\n";
    $pfrules .= `/sbin/pfctl -s timeouts`;
    $pfrules .= "\nLimits:\n";
    $pfrules .= `/sbin/pfctl -s memory`;
    $pfrules = preg_replace("/@/", "\n<b>", $pfrules);
    $pfrules = preg_replace("/\n\s+\[/", "</b>\n [", $pfrules);
    printf("$pfrules");
}

function dump_ipsec_flows() {
    global $g, $config;
    printf("<pre>");
    $pfrules .= `/sbin/ipsecctl -vvs flow`;
    printf("$pfrules");
    printf("</pre>");
}

function dump_ipsec_sas() {
    global $g, $config;
    printf("<pre>");
    $pfrules .= `/sbin/ipsecctl -vvs sa`;
    printf("$pfrules");
    printf("</pre>");
}

function dump_ipsecconf() {
    global $g, $config;
    printf("<pre>");
    require_once("vpn.inc");
    $pfconf = vpn_print_ipsecconf();
    $pfconf = htmlentities($pfconf);
    printf("$pfconf");
    printf("</pre>");
}



function dump_isakmpdconf() {
    global $g, $config;
    printf("<pre>");
    require_once("vpn.inc");
    $pfconf = vpn_print_isakmpdconf();
    $pfconf = htmlentities($pfconf);
    printf("$pfconf");
    printf("</pre>");
}

function dump_df() {
        global $g, $config;
        printf("<pre>");
        $df = `/bin/df -h`;
        echo $df;
}

function dump_arp() {
        global $g, $config;
        printf("<pre>");
        $arp = `/usr/sbin/arp -a`;
        echo $arp;
}

function dump_interfaces() {
        global $g, $config;
        printf("<pre>");
        $ifconfig = `/sbin/ifconfig -A`;
        echo $ifconfig;
}

function dump_routes($rdomain) {
        if(!$rdomain)
            $rdomain = 'DEFAULT';
        global $g, $config;
        $routes = '<p align="right">Route Table: <select name="rtable" class="formfld">';
        $routes .= '<option value="DEFAULT" ';
        if ($rdomain == 'DEFAULT') 
            $routes .= 'selected ';
        $routes .= '>DEFAULT</option>';
        foreach ($config['system']['routetables']['routetable'] as $rtable) {
                $routes .= '<option value="' . $rtable['name'] . '"';
                if ($rdomain == $rtable['name']) 
                    $routes .= 'selected ';
                $routes .= '>' . $rtable['name'] . '</option>';
            }        
        $routes .= '</select></p>';
        $routes .= "\n<pre>\n";
        $addflags = '';
        if ($rdomain != 'DEFAULT')
            $addflags = $addflags .= "-T " . get_route_table_id_by_name($rdomain);  
        $routes .= $rdomain . ' ';
        $routes .= `/sbin/route -n $addflags show`;
        $routes .= "\n</pre>\n<script id=\"source\" language=\"javascript\" type=\"text/javascript\">";
        $routes .= '$("select").change( function() {
              clearInterval(refreshId);
              $("#livediag").load("stats.php?stat=routes_content&rdomain=" + $(this).val());
          });';
          $routes .= '</script>';
        echo $routes;
}

function dump_xmlconf() {
        global $g, $config;
        $xml = `cat /conf/config.xml`;
        $xml = xml_highlight($xml);
        printf("<pre>");
        echo $xml;
}

function dump_dmesg() {
        global $g, $config;
        printf("<pre>\n");
        $dmesg = `cat /var/log/dmesg.boot`;
        $dmesg = htmlentities($dmesg);
        echo $dmesg;
        printf("</pre>\n");
}

function dump_dhcp() {
        global $g, $config;
        printf("<pre>");
        $leases = `cat /var/db/dhcpd.leases`;
        echo $leases;
}

function get_cgi_stat($stat, $tabname) {
    $divstring = '#' . $tabname . 'tabs';
    $output = <<<EOD


<script type="text/javascript">
$('#livediag').show(); 

function updateLivediag() {
    $.get('stats.cgi?$stat' + "&random=" + Math.random(), function(response){
    if("$stat" == "logs"){
        count = $(response).length, countMax = 1000;
        if ( count < countMax ) {
            $('#livediag').append(scrub_raw_log(response)).find('tr').slice(0, count - countMax).remove();
            $('#livediag').attr({ scrollTop: $('#livediag').attr("scrollHeight") });
        } else {
            $('#livediag').html(scrub_raw_log(response));
        }
    } else {
        $('#livediag').html(response);
    }
    });
}
 
function scrub_raw_log(log) {
    log = log.replace(/block/g, '<font color="red">block</font>');
    log = log.replace(/pass/g, '<font color="chartreuse">pass</font>');
    log = log.replace(/nswall pf: rule \d+\./g, "");
    log = log.replace(/\.\d\/\(match\) \[uid \d\, pid \d+\]/g, "");
    return log;
}

$(function() {
    $("$divstring").resizable({
        alsoResize: '#livediag'
    });
});

clearInterval(refreshId);
updateLivediag();
var refreshId = setInterval(updateLivediag, 5000);

</script>
EOD;
    printf("$output");
}

function get_php_stat($stat, $tabname) {
    $divstring = '#' . $tabname . 'tabs';
    $output = <<<EOD
    <script type="text/javascript">
        $(function() {
         $("$divstring").resizable({
             alsoResize: '#livediag'
         });
        });
        clearInterval(refreshId);
        $.get('stats.php?stat=$stat' + "&random=" + Math.random(), function(response){
            $('#livediag').html(response);
        });
    </script>
EOD;
    printf("$output");
}


switch ($stat) {
    case 'pfconf':
        get_php_stat('pfconf_content', 'pf');
        break;
    case 'pfconf_content':
        dump_pfconf();
        break;
    case 'pfoptions':
        get_php_stat('pfoptions_content', 'pf');
        break;
    case 'pfoptions_content':
        dump_pfoptions();
        break;
    case 'pfrules':
        get_cgi_stat('rules', 'pf');
        break;
    case 'pfblockedsites':
        get_cgi_stat('blockedsites', 'pf');
        break;
    case 'ipsecconf':
        get_php_stat('ipsecconf_content', 'vpn');
        break;
    case 'isakmpdconf':
        get_php_stat('isakmpdconf_content', 'vpn');
        break;
    case 'ipsecconf_content':
        dump_ipsecconf();
        break;
    case 'isakmpdconf_content':
        dump_isakmpdconf();
        break;
    case 'ipsec_flows':
        get_php_stat('ipsecflows_content', 'vpn');
        break;
    case 'ipsec_sas':
        get_php_stat('ipsecsas_content', 'vpn');
        break;
    case 'ipsecflows_content':
        dump_ipsec_flows();
        break;
    case 'ipsecsas_content':
        dump_ipsec_sas();
        break;  
    case 'logs':
        get_cgi_stat('logs', 'log');
        break;
    case 'pfstates':
        get_cgi_stat('states', 'pf');
        break;
    case 'pfnat':
        get_cgi_stat('nat', 'pf');
        break;
    case 'pfqueues':
        get_cgi_stat('queues', 'pf');
        break;
    case 'top':
        get_cgi_stat('top', 'sys');
        break;
    case 'df':
        get_php_stat('df_content', 'sys');
        break;
    case 'df_content':
        dump_df();
        break;  
    case 'arp':
        get_php_stat('arp_content', 'sys');
        break;
    case 'arp_content':
        dump_arp();
        break;
    case 'interfaces':
        get_php_stat('interfaces_content', 'sys');
        break;
    case 'interfaces_content':
        dump_interfaces();
        break; 
    case 'routes':
        get_php_stat('routes_content', 'sys', $rdomain);
        break;
    case 'routes_content':
        dump_routes($rdomain);
        break;
    case 'dhcp':
        get_php_stat('dhcp_content', 'sys');
        break;
    case 'dhcp_content':
        dump_dhcp();
        break;  
    case 'xmlconf':
        get_php_stat('xmlconf_content', 'sys');
        break;
    case 'xmlconf_content':
        dump_xmlconf();
        break;
    case 'dmesg':
        get_php_stat('dmesg_content', 'sys');
        break;
    case 'dmesg_content':
        dump_dmesg();
        break;
}
?>

