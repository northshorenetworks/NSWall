#!/bin/php
<?php 

$pgtitle = array("Firewall", "Options", "Edit");
require("guiconfig.inc");
include("ns-begin.inc");

if (!is_array($config['filter']['options'])) {
    $config['filter']['options'] = array();
}
$a_filter = &$config['filter']['options'];

if($a_filter['timeouts']['tcpfirst']) {
        $pconfig['timeouts']['tcpfirst'] = $a_filter['timeouts']['tcpfirst'];
} else {
        $pconfig['timeouts']['tcpfirst'] = "120";
}
if($a_filter['timeouts']['tcpopening']) {
    $pconfig['timeouts']['tcpopening'] = $a_filter['timeouts']['tcpopening'];
} else {
    $pconfig['timeouts']['tcpopening'] = "30";
}
if($a_filter['timeouts']['tcpextablished']) {
        $pconfig['timeouts']['tcpestablished'] = $a_filter['timeouts']['tcpestablished'];
} else {
        $pconfig['timeouts']['tcpestablished'] = "86400";
}
if($a_filter['timeouts']['tcpclosing']) {
        $pconfig['timeouts']['tcpclosing'] = $a_filter['timeouts']['tcpclosing'];
} else {
        $pconfig['timeouts']['tcpclosing'] = "900";
}
if($a_filter['timeouts']['tcpfinwait']) {
        $pconfig['timeouts']['tcpfinwait'] = $a_filter['timeouts']['tcpfinwait'];
} else {
        $pconfig['timeouts']['tcpfinwait'] = "45";
}
if($a_filter['timeouts']['tcpclosed']) {
        $pconfig['timeouts']['tcpclosed'] = $a_filter['timeouts']['tcpclosed'];
} else {
        $pconfig['timeouts']['tcpclosed'] = "90";
}
if($a_filter['timeouts']['udpfirst']) {
        $pconfig['timeouts']['udpfirst'] = $a_filter['timeouts']['udpfirst'];
} else {
        $pconfig['timeouts']['udpfirst'] = "60";
}
if($a_filter['timeouts']['udpsingle']) {
        $pconfig['timeouts']['udpsingle'] = $a_filter['timeouts']['udpsingle'];
} else {
        $pconfig['timeouts']['udpsingle'] = "30";
}
if($a_filter['timeouts']['udpmultiple']) {
        $pconfig['timeouts']['udpmultiple'] = $a_filter['timeouts']['udpmultiple'];
} else {
        $pconfig['timeouts']['udpmultiple'] = "60";
}
if($a_filter['timeouts']['icmpfirst']) {
        $pconfig['timeouts']['icmpfirst'] = $a_filter['timeouts']['icmpfirst'];
} else {
        $pconfig['timeouts']['icmpfirst'] = "20";
}
if($a_filter['timeouts']['icmperror']) {
        $pconfig['timeouts']['icmperror'] = $a_filter['timeouts']['icmperror'];
} else {
        $pconfig['timeouts']['icmperror'] = "10";
}
if($a_filter['timeouts']['otherfirst']) {
        $pconfig['timeouts']['otherfirst'] = $a_filter['timeouts']['otherfirst'];
} else {
        $pconfig['timeouts']['otherfirst'] = "60";
}
if($a_filter['timeouts']['othersingle']) {
        $pconfig['timeouts']['othersingle'] = $a_filter['timeouts']['othersingle'];
} else {
        $pconfig['timeouts']['othersingle'] = "30";
}
if($a_filter['timeouts']['othermultiple']) {
        $pconfig['timeouts']['othermultiple'] = $a_filter['timeouts']['othermultiple'];
} else {
        $pconfig['timeouts']['othermultiple'] = "60";
}
if($a_filter['timeouts']['adaptivestart']) {
        $pconfig['timeouts']['adaptivestart'] = $a_filter['timeouts']['adaptivestart'];
} else {
        $pconfig['timeouts']['adaptivestart'] = "6000";
}
if($a_filter['timeouts']['adaptiveend']) {
        $pconfig['timeouts']['adaptiveend'] = $a_filter['timeouts']['adaptiveend'];
} else {
        $pconfig['timeouts']['adaptiveend'] = "12000";
}        
if($a_filter['limits']['maxstates']) {
        $pconfig['limits']['maxstates'] = $a_filter['limits']['maxstates'];
} else {
        $pconfig['limits']['maxstates'] = "10000";
}
if($a_filter['limits']['maxfrags']) {
        $pconfig['limits']['maxfrags'] = $a_filter['limits']['maxfrags'];
} else {
        $pconfig['limits']['maxfrags'] = "5000";
}
if($a_filter['limits']['srcnodes']) {
        $pconfig['limits']['srcnodes'] = $a_filter['limits']['srcnodes'];
} else {
        $pconfig['limits']['srcnodes'] = "10000";
}
if($a_filter['opt']['rulesetopt']) {
        $pconfig['opt']['rulesetopt'] = $a_filter['opt']['rulesetopt'];
} else {
        $pconfig['opt']['rulesetopt'] = "basic";
}
if($a_filter['opt']['stateopt']) {
        $pconfig['opt']['stateopt'] = $a_filter['opt']['stateopt'];
} else {
    $pconfig['opt']['stateopt'] = "normal";
}
if($a_filter['opt']['blockpol']) {
        $pconfig['opt']['blockpol'] = $a_filter['opt']['blockpol'];
} else {
        $pconfig['opt']['blockpol'] = "drop";
}
if($a_filter['opt']['statepol']) {
        $pconfig['opt']['statepol'] = $a_filter['opt']['statepol'];
} else {
        $pconfig['opt']['statepol'] = "floating";
}
$pconfig['scrub']['dfbit'] = isset($a_filter['scrub']['dfbit']);
if($a_filter['scrub']['minttl']) {
        $pconfig['scrub']['minttl'] = $a_filter['scrub']['minttl'];
}
if($a_filter['scrub']['maxmss']) {
        $pconfig['scrub']['maxmss'] = $a_filter['scrub']['maxmss'];
}
$pconfig['scrub']['randid'] = isset($a_filter['scrub']['randid']);
if($a_filter['scrub']['fraghandle']) {
    $pconfig['scrub']['fraghandle'] = $a_filter['scrub']['fraghandle'];
}
$pconfig['scrub']['reassembletcp'] = isset($a_filter['scrub']['reassembletcp']);
$pconfig['logging']['default'] = isset($a_filter['logging']['default']);

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    
    var index = 0;
        if (window.location.hash) {
             var hash = window.location.hash.substr(1);
             if (hash.match(/_tab_timeouts$/)) {
                 index = 0;
             }
             else if (hash.match(/_tab_limits$/)) {
                 index = 1;
             }
             else if (hash.match(/_tab_options$/)) {
                 index = 2;
             }
             else if (hash.match(/_tab_normalization$/)) {
                 index = 3;
             }
             else if (hash.match(/_tab_logging$/)) {
                 index = 4;
             }
        }
        $('#firewalloptionstabs').tabs({ selected: index });
        $('#firewalloptionstabs').tabs("select", index);

        $('#firewalloptionstabs').bind( "tabsshow", function(event, ui) {
            var selected = $('#firewalloptionstabs').tabs( "option", "selected" );
            switch (selected) {
                case 0:
                    document.location = '#firewall_options_edit_tab_timeouts';
                    break;
                case 1:
                    document.location = '#firewall_options_edit_tab_limits';
                    break;
                case 2:
                    document.location = '#firewall_options_edit_tab_options';
                    break;
                case 3:
                    document.location = '#firewall_options_edit_tab_normalization';
                    break;
                case 4:
                    document.location = '#firewall_options_edit_tab_logging';
                    break;
                default:
                    document.location = '#firewall_options_edit_tab_timetouts';
                    break;
            }
        });

    $("#submitbutton, #submitbutton2, #submitbutton3, #submitbutton4, #submitbutton5").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/firewall_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        });
    return false;
    });
});
</script>

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">
 
<div id="firewalloptionstabs">
    <ul>
        <li><a href="#tabTimeouts">Timeouts</a></li>
        <li><a href="#tabLimits">Limits</a></li>
        <li><a href="#tabOptions">Options</a></li>
        <li><a href="#tabNormalization">Normalization</a></li>
        <li><a href="#tabLogging">Logging</a></li>
    </ul>
         <form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
         <input name="formname" type="hidden" value="firewall_options">
             <div id="tabTimeouts">
             <fieldset>
         <legend><?=join(": ", $pgtitle);?></legend>
              <div>
                       <label for="tcpfirst">TCP First</label>
                       <input name="tcpfirst" type="text" class="formfld" id="tcpfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpfirst']);?>" />
                        <p class="note">The state after the first packet.</p>
          </div>
                  <div>
                       <label for="tcpopening">TCP Opening</label>
                       <input name="tcpopening" type="text" class="formfld" id="tcpopening" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpopening']);?>" />
                        <p class="note">The state before the destination host ever sends a packet.</p>
          </div>
                  <div>
                       <label for="tcpestablished">TCP Established</label>
                       <input name="tcpestablished" type="text" class="formfld" id="tcpestablished" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpestablished']);?>" />
                        <p class="note">The fully established state.</p>
          </div>
                  <div>
                       <label for="tcpclosing">TCP Closing</label>
                       <input name="tcpclosing" type="text" class="formfld" id="tcpclosing" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpclosing']);?>" />
                        <p class="note">The state after the first FIN has been sent.</p>
          </div>
                  <div>
                       <label for="tcpfinwait">TCP FIN Wait</label>
                       <input name="tcpfinwait" type="text" class="formfld" id="tcpfinwait" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpfinwait']);?>" />
                        <p class="note">The state after both FINs have been exchanged and the connec-
                 tion is closed.  Some hosts (notably web servers on Solaris)
                 send TCP packets even after closing the connection.  Increas-
                 ing tcp.finwait (and possibly tcp.closing) can prevent block-
                 ing of such packets.</p>
          </div>
                  <div>
                       <label for="tcpclosing">TCP Closed</label>
                       <input name="tcpclosed" type="text" class="formfld" id="tcpclosed" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpclosed']);?>" />
                        <p class="note">The state after one endpoint sends a RST.</p>
          </div>
                  <div>
                       <label for="udpfirst">UDP First</label>
                       <input name="udpfirst" type="text" class="formfld" id="udpfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['udpfirst']);?>" />
                        <p class="note">The state after the first packet.</p>
          </div>
                  <div>
                       <label for="udpsingle">UDP Single</label>
                       <input name="udpsingle" type="text" class="formfld" id="udpsingle" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['udpfirst']);?>" />
                        <p class="note">The state if the source host sends more than one packet but
                 the destination host has never sent one back.</p>
          </div>
                  <div>
                       <label for="udpmultiple">UDP Multiple</label>
                       <input name="udpmultiple" type="text" class="formfld" id="udpmultiple" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['udpmultiple']);?>" />
                        <p class="note">The state if both hosts have sent packets.</p>
          </div>
                  <div>
                       <label for="icmpfirst">ICMP First</label>
                       <input name="icmpfirst" type="text" class="formfld" id="icmpfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['icmpfirst']);?>" />
                        <p class="note">The state after the first packet.</p>
          </div>
                  <div>
                       <label for="icmperror">ICMP Error</label>
                       <input name="icmperror" type="text" class="formfld" id="icmperror" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['icmperror']);?>" />
                        <p class="note">The state after an ICMP error came back in response to an ICMP packet.</p>
          </div>
                  <div>
                       <label for="otherfirst">Other First</label>
                       <input name="otherfirst" type="text" class="formfld" id="otherfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['otherfirst']);?>" />
                        <p class="note">The state after the first packet.</p>
          </div>
                  <div>
                       <label for="othersingle">Other Single</label>
                       <input name="othersingle" type="text" class="formfld" id="othersingle" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['othersingle']);?>" />
                        <p class="note">The state after the first packet.</p>
          </div>
                  <div>
                       <label for="othermultiple">Other Multiple</label>
                       <input name="othermultiple" type="text" class="formfld" id="othermultiple" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['othermultiple']);?>" />
                        <p class="note">The state if both hosts have sent packets.</p>
          </div>
                  <div>
                       <label for="othermultiple">Other Multiple</label>
                       <input name="othermultiple" type="text" class="formfld" id="othermultiple" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['othermultiple']);?>" />
                        <p class="note">The state if both hosts have sent packets.</p>
          </div>
                  <div>
                <input type="submit" id="submitbutton" value="Save" class="button" />
              </div>
         </fieldset>
    </div>
        <div id="tabLimits">
             <fieldset>
                 <div>
                       <label for="adaptivestart">Adaptive Start</label>
                       <input name="adaptivestart" type="text" class="formfld" id="adaptivestart" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['adaptivestart']);?>" />
                        <p class="note">When the number of state entries exceeds this value, adaptive
                 scaling begins.  All timeout values are scaled linearly with
                 factor (adaptive.end - number of states) / (adaptive.end -
                 adaptive.start).</p>
          </div>
                  <div>
                       <label for="adaptiveend">Adaptive End</label>
                       <input name="adaptiveend" type="text" class="formfld" id="adaptiveend" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['adaptiveend']);?>" />
                        <p class="note">When reaching this number of state entries, all timeout values 
         become zero, effectively purging all state entries immediately.  
         This value is used to define the scale factor, it
                 should not actually be reached (set a lower state limit, see
                 below).</p>
          </div>
                  <div>
                       <label for="maxstates">Max States</label>
                       <input name="maxstates" type="text" class="formfld" id="maxstates" size="5" value="<?=htmlspecialchars($pconfig['limits']['maxstates']);?>" />
                        <p class="note">Sets the maximum number of entries in the memory pool used by state
           table entries (generated by pass rules which do not specify no
           state).</p>
          </div>
                   <div>
                       <label for="maxfrags">Max Frags</label>
                       <input name="maxfrags" type="text" class="formfld" id="maxfrags" size="5" value="<?=htmlspecialchars($pconfig['limits']['maxfrags']);?>" />
                        <p class="note">Sets the maximum number of entries in the memory pool used for
           fragment reassembly (generated by scrub rules).</p>
          </div>
                  <div>
                       <label for="srcnodes">Max Source Nodes</label>
                       <input name="srcnodes" type="text" class="formfld" id="srcnodes" size="5" value="<?=htmlspecialchars($pconfig['limits']['srcnodes']);?>" />
                        <p class="note">Sets the maximum number of entries in the memory pool used for
           tracking source IP addresses (generated by the sticky-address and
           src.track options).</p>
          </div> 
       </fieldset>
        <div>
            <input type="submit" id="submitbutton2" value="Save" class="button" />
        </div>  
    </div>
    <div id="tabOptions">
               <fieldset>
                <div>
                       <label for="rulesetopt">Ruleset Optimization</label>
                       <select name="rulesetopt" class="formfld">
                  <?php $types = explode(" ", "None Basic Profile"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['rulesetopt'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?>
                  </select>
                        <p class="note">
none   Disable the ruleset optimizer.
basic  Enable basic ruleset optimization.  This is the default
       behaviour.  Basic ruleset optimization does four things
       to improve the performance of ruleset evaluations:

       1.   remove duplicate rules
       2.   remove rules that are a subset of another rule
       3.   combine multiple rules into a table when advanta-
            geous
       4.   re-order the rules to improve evaluation performance

profile   Uses the currently loaded ruleset as a feedback profile
       to tailor the ordering of quick rules to actual network
       traffic.

It is important to note that the ruleset optimizer will modify the
ruleset to improve performance.  A side effect of the ruleset modi-
fication is that per-rule accounting statistics will have different
meanings than before.  If per-rule accounting is important for
billing purposes or whatnot, either the ruleset optimizer should
not be used or a label field should be added to all of the account-
ing rules to act as optimization barriers.
        </p>
                </div>
                <div>
                       <label for="stateopt">State Optimizations</label>
                       <select name="stateopt" class="formfld">
                  <?php $types = explode(" ", "Normal High-Latency Aggressive Conservative"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['stateopt'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?>
                  </select>
                        <p class="note">
Optimize state timeouts for one of the following network environments:

normal
       A normal network environment.  Suitable for almost all net-
       works.
high-latency
       A high-latency environment (such as a satellite connection).
satellite
       Alias for high-latency.
aggressive
       Aggressively expire connections.  This can greatly reduce the
       memory usage of the firewall at the cost of dropping idle
       connections early.
conservative
       Extremely conservative settings.  Avoid dropping legitimate
       connections at the expense of greater memory utilization
       (possibly much greater on a busy network) and slightly in-
       creased processor utilization.
        </p>
                </div>
                 <div>
                       <label for="blockpolpol">Block Policy</label>
                       <select name="blockpol" class="formfld">
                <?php $types = explode(" ", "Drop Return"); foreach ($types as $type): ?>
                <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['blockpol'])) echo "selected"; ?>>
                <?=htmlspecialchars($type);?>
                </option>
                <?php endforeach; ?>
                  </select>
                        <p class="note">
The block-policy option sets the default behaviour for the packet
block action:

    drop      Packet is silently dropped.
    return    A TCP RST is returned for blocked TCP packets, an ICMP
              UNREACHABLE is returned for blocked UDP packets, and all
              other packets are silently dropped.
rulesetopt</p>
                </div>
                <div>
                  <label for="statepol">State Policy</label>
                       <select name="statepol" class="formfld">
                  <?php $types = explode(" ", "if-bound floating"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['statepol'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?>
                  </select>
                  <p class="note">
The state-policy option sets the default behaviour for states:

   if-bound     States are bound to interface.
   floating     States can match packets on any interfaces (the de-
                fault).
        </p>
                </div>
                </fieldset>       
            <div>
                <input type="submit" id="submitbutton3" value="Save" class="button" />
            </div>
        </div>  
    <div id="tabNormalization">
                <fieldset>
            <legend><?=join(": ", $pgtitle);?></legend>
            <div>
                     <label for="tcpfirst">No DF</label>
                     <input name="dfbit" type="checkbox" id="dfbit" value="Yes" <?php if ($pconfig['scrub']['dfbit']) echo "checked"; ?>>
                     <p class="note">Clears the dont-fragment bit from a matching IP packet.  Some oper-
ating systems are known to generate fragmented packets with the
dont-fragment bit set.  This is particularly true with NFS.  Scrub
will drop such fragmented dont-fragment packets unless no-df is
specified.  Unfortunately some operating systems also generate their dont-
fragment packets with a zero IP identification field.  Clearing the
dont-fragment bit on packets with a zero IP ID may cause deleteri-
ous results if an upstream router later fragments the packet.  Us-
ing the random-id modifier (see below) is recommended in combina-
tion with the no-df modifier to ensure unique IP identifiers.</p>
          </div>
                  <div>
                     <label for="tcpfirst">Min TTL</label>
                     <input name="minttl" type="text" class="formfld" id="minttl" size="5" value="<?=htmlspecialchars($pconfig['scrub']['minttl']);?>" />
                     <p class="note">Enforces a minimum TTL for matching IP packets.</p>
          </div>
                  <div>
                     <label for="tcpfirst">Min MSS</label>
                     <input name="maxmss" type="text" class="formfld" id="maxmss" size="5" value="<?=htmlspecialchars($pconfig['scrub']['maxmss']);?>" />
                     <p class="note">Enforces a maximum MSS for matching TCP packets.</p>
          </div>
                  <div>
                     <label for="tcpfirst">Randomize ID</label>
                     <input name="randid" type="checkbox" id="randid" value="yes" <?php if ($pconfig['scrub']['randid']) echo "checked"; ?> />
                     <p class="note">Replaces the IP identification field with random values to compen-
sate for predictable values generated by many hosts.  This option
only applies to packets that are not fragmented after the optional
fragment reassembly.</p>
          </div>
                  <div>
                  <label for="statepol">Fragment Handling</label>
                  <select name="fraghandle" class="formfld">
                  <?php $types = explode(",", "Reassemble,Crop,Drop-Ovl"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['scrub']['fraghandle'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?>
                  </select>
                  <p class="note">
Fragment Reassemble:
Using scrub rules, fragments can be reassembled by normalization.
In this case, fragments are buffered until they form a complete
packet, and only the completed packet is passed on to the filter.
The advantage is that filter rules have to deal only with complete
packets, and can ignore fragments.  The drawback of caching frag-
ments is the additional memory cost.  But the full reassembly
method is the only method that currently works with NAT.  This is
the default behavior of a scrub rule if no fragmentation modifier
is supplied.                

Fragment Crop:
The default fragment reassembly method is expensive, hence the op-
tion to crop is provided.  In this case, pf will track the frag-
ments and cache a small range descriptor.  Duplicate fragments are
dropped and overlaps are cropped.  Thus data will only occur once
on the wire with ambiguities resolving to the first occurrence.
Unlike the fragment reassemble modifier, fragments are not
buffered, they are passed as soon as they are received.  The
fragment crop reassembly mechanism does not yet work with NAT. 

Fragment Drop Overlap:
This option is similar to the fragment crop modifier except that
all overlapping or duplicate fragments will be dropped, and all
further corresponding fragments will be dropped as well.
        </p>
                </div>
                <div>
                     <label for="tcpfirst">Reassemble TCP</label>
                     <input name="reassembletcp" type="checkbox" id="fragdropol" value="yes" <?php if ($pconfig['scrub']['reassembletcp']) echo "checked"; ?> />
                     <p class="note">Statefully normalizes TCP connections.  scrub reassemble tcp rules
         may not have the direction (in/out) specified.  reassemble
     tcp performs the following normalizations:

ttl      Neither side of the connection is allowed to reduce their
         IP TTL.  An attacker may send a packet such that it reach-
         es the firewall, affects the firewall state, and expires
         before reaching the destination host.  reassemble tcp will
         raise the TTL of all packets back up to the highest value
         seen on the connection.
timestamp modulation
         Modern TCP stacks will send a timestamp on every TCP pack-
         et and echo the other endpoint's timestamp back to them.
         Many operating systems will merely start the timestamp at
         zero when first booted, and increment it several times a
         second.  The uptime of the host can be deduced by reading
         the timestamp and multiplying by a constant.  Also observ-
         ing several different timestamps can be used to count
         hosts behind a NAT device.  And spoofing TCP packets into
         a connection requires knowing or guessing valid times-
         tamps.  Timestamps merely need to be monotonically in-
         creasing and not derived off a guessable base time.
         reassemble tcp will cause scrub to modulate the TCP times-
         tamps with a random number.
extended PAWS checks
         There is a problem with TCP on long fat pipes, in that a
         packet might get delayed for longer than it takes the con-
         nection to wrap its 32-bit sequence space.  In such an oc-
         currence, the old packet would be indistinguishable from a
         new packet and would be accepted as such.  The solution to
         this is called PAWS: Protection Against Wrapped Sequence
         numbers.  It protects against it by making sure the times-
         tamp on each packet does not go backwards.  reassemble tcp
         also makes sure the timestamp on the packet does not go
         forward more than the RFC allows.  By doing this, pf(4)
         artificially extends the security of TCP sequence numbers
         by 10 to 18 bits when the host uses appropriately random-
         ized timestamps, since a blind attacker would have to
         guess the timestamp as well.</p>
             </div>
             </fieldset>
        <div>
            <input type="submit" id="#submitbutton4" value="Save" class="button" />
        </div>
    </div>
    <div id="tabLogging">
         <fieldset>
         <legend><?=join(": ", $pgtitle);?></legend>
              <div>
                       <label for="tcpfirst">Default Packet Logging</label>
                       <input name="logdefault" type="checkbox" id="logdefault" value="yes" <?php if ($pconfig['logging']['default']) echo "checked"; ?>>
                        <p class="note"> Log packets that match the default rule.</p>
          </div>
         </fieldset>
         <div>
            <input type="submit" id="submitbutton5" value="Save" class="button" />
        </div>
        </div>
</form>
</div>
</div>
</div>
</div>
