#!/bin/php
<?php 
/*
	$Id: firewall_options_edit.php,v 1.2 2009/04/20 06:56:53 jrecords Exp $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.
	
	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

$pgtitle = array("Firewall", "Options", "Edit");
require("guiconfig.inc");

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

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	if ($_POST['apply']) {
                $retval = 0;
                if (!file_exists($d_sysrebootreqd_path)) {
                        config_lock();
                        $retval = filter_configure();
			config_unlock();
        		push_config('pfoptions');
	        }
                $savemsg = get_std_save_message($retval);
                if ($retval == 0) {
                        if (file_exists($d_natconfdirty_path))
                                unlink($d_natconfdirty_path);
                        if (file_exists($d_filterconfdirty_path))
                                unlink($d_filterconfdirty_path);
                header("Location: firewall_options_edit.php");
		}
        } else {		

	/* input validation  
	$reqdfields = explode(" ", "type interface srclist dstlist");
	$reqdfieldsn = explode(",", "Type,Interface,Source,Destination");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	*/
	if (!$input_errors) {
		$filterent['timeouts'] = array();
		if($_POST['tcpfirst'] != '120') 
			$filterent['timeouts']['tcpfirst'] = $_POST['tcpfirst'];
		if($_POST['tcpopening'] != '30')
			$filterent['timeouts']['tcpopening'] = $_POST['tcpopening'];
		if($_POST['tcpestablished'] != '86400')	
			$filterent['timeouts']['tcpestablished'] = $_POST['tcpestablished'];
		if($_POST['tcpclosing'] != '900')
			$filterent['timeouts']['tcpclosing'] = $_POST['tcpclosing'];
		if($_POST['tcpfinwait'] != '45')
			$filterent['timeouts']['tcpfinwait'] = $_POST['tcpfinwait'];
		if($_POST['tcpclosed'] != '90')
			$filterent['timeouts']['tcpclosed'] = $_POST['tcpclosed'];
		if($_POST['udpfirst'] != '60')
			$filterent['timeouts']['udpfirst'] = $_POST['udpfirst'];
                if($_POST['udpsingle'] != '30')
			$filterent['timeouts']['udpsingle'] = $_POST['udpsingle'];
                if($_POST['udpmultiple'] != '60')
			$filterent['timeouts']['udpmultiple'] = $_POST['udpmultiple'];
                if($_POST['icmpfirst'] != '20')
			$filterent['timeouts']['icmpfirst'] = $_POST['icmpfirst'];
                if($_POST['icmperror'] != '10')
			$filterent['timeouts']['icmperror'] = $_POST['icmperror'];
                if($_POST['otherfirst'] != '60')
			$filterent['timeouts']['otherfirst'] = $_POST['otherfirst'];
		if($_POST['othersingle'] != '30')
			$filterent['timeouts']['othersingle'] = $_POST['othersingle'];
                if($_POST['othermultiple'] != '60')
			$filterent['timeouts']['othermultiple'] = $_POST['othermultiple'];
                if($_POST['adaptivestart'] != '6000')
			$filterent['timeouts']['adaptivestart'] = $_POST['adaptivestart'];
                if($_POST['adaptiveend'] != '12000')
			$filterent['timeouts']['adaptiveend'] = $_POST['adaptiveend'];
		if($_POST['maxstates'] != '10000')
                        $filterent['limits']['maxstates'] = $_POST['maxstates'];
		if($_POST['maxfrags'] != '5000')
                        $filterent['limits']['maxfrags'] = $_POST['maxfrags'];
		if($_POST['srcnodes'] != '10000') 
                        $filterent['limits']['srcnodes'] = $_POST['srcnodes'];
        	if($_POST['rulesetopt'] != 'basic')
                        $filterent['opt']['rulesetopt'] = $_POST['rulesetopt'];
		if($_POST['stateopt'] != 'normal')
                        $filterent['opt']['stateopt'] = $_POST['stateopt'];
		if($_POST['blockpol'] != 'drop')
                        $filterent['opt']['blockpol'] = $_POST['blockpol'];
		if($_POST['statepol'] != 'floating')
                        $filterent['opt']['statepol'] = $_POST['statepol'];
		if ($_POST['dfbit']) {
                        $filterent['scrub']['dfbit'] = $_POST['dfbit'] ? true : false;
                }
		if ($_POST['minttl'] != "") {
                        $filterent['scrub']['minttl'] = $_POST['minttl'];
                }
		if ($_POST['maxmss'] != "") {
                        $filterent['scrub']['maxmss'] = $_POST['maxmss'];
                }
		if ($_POST['randid']) {
                        $filterent['scrub']['randid'] = $_POST['randid'] ? true : false;
		}
		if ($_POST['fraghandle']) {
                        $filterent['scrub']['fraghandle'] = $_POST['fraghandle'];
                }
                if ($_POST['reassembletcp']) {
		        $filterent['scrub']['reassembletcp'] = $_POST['reassembletcp'];
                }
		if ($_POST['logdefault']) {
                        $filterent['logging']['default'] = $_POST['logdefault'] ? true : false;
                }
	}
		$a_filter = $filterent;
		
		write_config();
		touch($d_filterconfdirty_path);
		
		header("Location: firewall_options_edit.php");
		exit;
	}	
}
?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
var tabs=new Array('tabTimeouts', 'tabLimits', 'tabOptions', 'tabNormalization', 'tabLogging');

function switchtab(tab){       
        hidealltabs();
        showdiv(tab);
}

function hidealltabs(){
        //loop through the array and hide each element by id
        for (var i=0;i<tabs.length;i++){
                if(tabs[i].match( /^tab/ )) {
                        hidediv(tabs[i]);
                }        
	}                 
}

function hidediv(id) {
        //safe function to hide an element with a specified id
        if (document.getElementById) { // DOM3 = IE5, NS6
                document.getElementById(id).style.display = 'none';
        }
        else {
                if (document.layers) { // Netscape 4
                        document.id.display = 'none';
                }
                else { // IE 4
                        document.all.id.style.display = 'none';
                }
        }
}

function showdiv(id) {
        //safe function to show an element with a specified id
                  
        if (document.getElementById) { // DOM3 = IE5, NS6
                document.getElementById(id).style.display = 'block';
        }
        else {
                if (document.layers) { // Netscape 4
                        document.id.display = 'block';
                }
                else { // IE 4
                        document.all.id.style.display = 'block';
                }
        }
}

function activate(obj){
	links = document.getElementById('navigator').getElementsByTagName('li');
	for(i=0;i<links.length;i++){
		links[i].className = 'tabinact';
		if(links[i].id==obj){
			links[i].className='tabact';
		}
	}
}
-->
</script>

<table width="100%" id="navigator" border="0" cellpadding="0" cellspacing="0">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
<li class="tabact" id="timeouttab" onclick="activate('timeouttab'); switchtab('tabTimeouts')"><a>Timeouts</a></li>
<li class="tabinact" id="limittab" onclick="activate('limittab'); switchtab('tabLimits')"><a>Limits</a></li>
<li class="tabinact" id="optionstab" onclick="activate('optionstab'); javascript:switchtab('tabOptions')"><a>Options<a/></li>
<li class="tabinact" id="normalizationtab" onclick="activate('normalizationtab'); javascript:switchtab('tabNormalization')"><a>Normalization</a></li>
<li class="tabinact" id="loggingtab" onclick="activate('loggingtab'); javascript:switchtab('tabLogging')"><a>Logging</a></li>
  </ul>
  </td></tr>
  <tr>
    <td class="tabcont">
<center>
	<div id="tabTimeouts" style="display:block">
             <form action="firewall_options_edit.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
	     <?php if ($savemsg) print_info_box($savemsg); ?>
	     <?php if (file_exists($d_filterconfdirty_path)): ?><p>
	     <?php print_info_box_np("The firewall option configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
	     <input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
	     <?php endif; ?> 
	     <table width="100%" border="0" cellpadding="6" cellspacing="0">
        	<tr>
                  <td width="22%" valign="top" class="vncellreq">TCP First</td>
                  <td width="78%" class="vtable">
                    <input name="tcpfirst" type="text" class="formfld" id="tcpfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpfirst']);?>">
		    <br> <span class="vexpl">The state after the first packet.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">TCP Opening</td>
                  <td width="78%" class="vtable">
                    <input name="tcpopening" type="text" class="formfld" id="tcpopening" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpopening']);?>">
                    <br> <span class="vexpl">The state before the destination host ever sends a packet.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">TCP Established</td>
                  <td width="78%" class="vtable">
                    <input name="tcpestablished" type="text" class="formfld" id="tcpestablished" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpestablished']);?>">
                    <br> <span class="vexpl">The fully established state.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">TCP Closing</td>
                  <td width="78%" class="vtable">
                    <input name="tcpclosing" type="text" class="formfld" id="tcpclosing" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpclosing']);?>">
                    <br> <span class="vexpl">The state after the first FIN has been sent.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">TCP FIN Wait</td>
                  <td width="78%" class="vtable">
                    <input name="tcpfinwait" type="text" class="formfld" id="tcpfinwait" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpfinwait']);?>">
                    <br> <span class="vexpl"> The state after both FINs have been exchanged and the connec-
                 tion is closed.  Some hosts (notably web servers on Solaris)
                 send TCP packets even after closing the connection.  Increas-
                 ing tcp.finwait (and possibly tcp.closing) can prevent block-
                 ing of such packets..</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">TCP Closed</td>
                  <td width="78%" class="vtable">
                    <input name="tcpclosed" type="text" class="formfld" id="tcpclosed" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['tcpclosed']);?>">
                    <br> <span class="vexpl">The state after one endpoint sends a RST.</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">UDP First</td>
                  <td width="78%" class="vtable">
                    <input name="udpfirst" type="text" class="formfld" id="udpfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['udpfirst']);?>">
                    <br> <span class="vexpl">The state after the first packet.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">UDP Single</td>
                  <td width="78%" class="vtable">
                    <input name="udpsingle" type="text" class="formfld" id="udpsingle" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['udpsingle']);?>">
                    <br> <span class="vexpl">The state if the source host sends more than one packet but
                 the destination host has never sent one back..</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">UDP Multiple</td>
                  <td width="78%" class="vtable">
                    <input name="udpmultiple" type="text" class="formfld" id="udpmultiple" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['udpmultiple']);?>">
                    <br> <span class="vexpl">The state if both hosts have sent packets.</span></td>
                </tr> 
	 	<tr>
                  <td width="22%" valign="top" class="vncellreq">ICMP First</td>
                  <td width="78%" class="vtable">
                    <input name="icmpfirst" type="text" class="formfld" id="icmpfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['icmpfirst']);?>">
                    <br> <span class="vexpl">The state after the first packet.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">ICMP Error</td>
                  <td width="78%" class="vtable">
                    <input name="icmperror" type="text" class="formfld" id="icmperror" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['icmperror']);?>">
                    <br> <span class="vexpl">The state after an ICMP error came back in response to an ICMP packet..</span></td>
                </tr>	
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Other First</td>
                  <td width="78%" class="vtable">
                    <input name="otherfirst" type="text" class="formfld" id="otherfirst" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['otherfirst']);?>">
                    <br> <span class="vexpl">The state after the first packet.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Other Single</td>
                  <td width="78%" class="vtable">
                    <input name="othersingle" type="text" class="formfld" id="othersingle" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['othersingle']);?>">
                    <br> <span class="vexpl">The state if the source host sends more than one packet but
                 the destination host has never sent one back..</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Other Multiple</td>
                  <td width="78%" class="vtable">
                    <input name="othermultiple" type="text" class="formfld" id="othermultiple" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['othermultiple']);?>">
                    <br> <span class="vexpl">The state if both hosts have sent packets.</span></td>
                </tr>
		 <tr>
                  <td width="22%" valign="top" class="vncellreq">Adaptive Start</td>
                  <td width="78%" class="vtable">
                    <input name="adaptivestart" type="text" class="formfld" id="adaptivestart" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['adaptivestart']);?>">
                    <br> <span class="vexpl">When the number of state entries exceeds this value, adaptive
                 scaling begins.  All timeout values are scaled linearly with
                 factor (adaptive.end - number of states) / (adaptive.end -
                 adaptive.start).</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Adaptive End</td>
                  <td width="78%" class="vtable">
                    <input name="adaptiveend" type="text" class="formfld" id="adaptiveend" size="5" value="<?=htmlspecialchars($pconfig['timeouts']['adaptiveend']);?>">
                    <br> <span class="vexpl">When reaching this number of state entries, all timeout values 
		 become zero, effectively purging all state entries immediately.  
		 This value is used to define the scale factor, it
                 should not actually be reached (set a lower state limit, see
                 below).</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_filter['timeouts'][$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>
		</table>
	</div>
        <div id="tabLimits" style="display:none">
	<?php if ($savemsg) print_info_box($savemsg); ?>
        <?php if (file_exists($d_filterconfdirty_path)): ?><p>
        <?php print_info_box_np("The firewall option configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
        <input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
        <?php endif; ?>	
	<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Max States</td>
                  <td width="78%" class="vtable">
                    <input name="maxstates" type="text" class="formfld" id="maxstates" size="5" value="<?=htmlspecialchars($pconfig['limits']['maxstates']);?>">
                    <br> <span class="vexpl">sets the maximum number of entries in the memory pool used by state
           table entries (generated by pass rules which do not specify no
           state).</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Max Frags</td>
                  <td width="78%" class="vtable">
                    <input name="maxfrags" type="text" class="formfld" id="maxfrags" size="5" value="<?=htmlspecialchars($pconfig['limits']['maxfrags']);?>">
                    <br> <span class="vexpl">sets the maximum number of entries in the memory pool used for
           fragment reassembly (generated by scrub rules).</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Source Nodes</td>
                  <td width="78%" class="vtable">
                    <input name="srcnodes" type="text" class="formfld" id="srcnodes" size="5" value="<?=htmlspecialchars($pconfig['limits']['srcnodes']);?>">
                    <br> <span class="vexpl">sets the maximum number of entries in the memory pool used for
           tracking source IP addresses (generated by the sticky-address and
           src.track options)</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_filter['timeouts'][$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>	
		</table>
	</div>
	<div id="tabOptions" style="display:none">
                <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Ruleset Optimization</td>
                  <td width="78%" class="vtable">
                  <select name="rulesetopt" class="formfld">
                  <?php $types = explode(" ", "None Basic Profile"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['rulesetopt'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?>
                  </select> <br>
		  <br> <span class="vexpl"><pre>
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
		</pre></span></td>                
                </tr>
                <tr>
                  <td width="22%" valign="top" class="vncellreq">State Optimizations</td>
                  <td width="78%" class="vtable">
                  <select name="stateopt" class="formfld">
                  <?php $types = explode(" ", "Normal High-Latency Aggressive Conservative"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['stateopt'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?>
   
		  <br> <span class="vexpl"><pre>
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
		</pre></span></td>
                </tr>
                <tr>
                <td width="22%" valign="top" class="vncellreq">Block Policy</td>
                <td width="78%" class="vtable">
 		<select name="blockpol" class="formfld">
                <?php $types = explode(" ", "Drop Return"); foreach ($types as $type): ?>
                <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['blockpol'])) echo "selected"; ?>>
                <?=htmlspecialchars($type);?>
                </option>
                <?php endforeach; ?>                 
	        <span class="vexpl"><pre>
The block-policy option sets the default behaviour for the packet
block action:

    drop      Packet is silently dropped.
    return    A TCP RST is returned for blocked TCP packets, an ICMP
              UNREACHABLE is returned for blocked UDP packets, and all
              other packets are silently dropped.
</pre></span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">State Policy</td>
                  <td width="78%" class="vtable">
                  <select name="statepol" class="formfld">
                  <?php $types = explode(" ", "if-bound floating"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['opt']['statepol'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?> 
		  <br> <span class="vexpl"><pre>
The state-policy option sets the default behaviour for states:

   if-bound     States are bound to interface.
   floating     States can match packets on any interfaces (the de-
                fault).
</pre></span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_filter['timeouts'][$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>
		</table>
        </div>	
	<div id="tabNormalization" style="display:none">
		<?php if ($savemsg) print_info_box($savemsg); ?>
             	<?php if (file_exists($d_filterconfdirty_path)): ?><p>
             	<?php print_info_box_np("The firewall option configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
             	<input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
             	<?php endif; ?>
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
		<tr>    
                  <td width="22%" valign="top" class="vncellreq">No DF</td>
                  <td width="78%" class="vtable">
                    <input name="dfbit" type="checkbox" id="dfbit" value="Yes" <?php if ($pconfig['scrub']['dfbit']) echo "checked"; ?>>
                    <br><span class="vexpl"><pre>
Clears the dont-fragment bit from a matching IP packet.  Some oper-
ating systems are known to generate fragmented packets with the
dont-fragment bit set.  This is particularly true with NFS.  Scrub
will drop such fragmented dont-fragment packets unless no-df is
specified.

Unfortunately some operating systems also generate their dont-
fragment packets with a zero IP identification field.  Clearing the
dont-fragment bit on packets with a zero IP ID may cause deleteri-
ous results if an upstream router later fragments the packet.  Us-
ing the random-id modifier (see below) is recommended in combina-
tion with the no-df modifier to ensure unique IP identifiers.
		</pre></span></td>
                </tr>	
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Min TTL</td>
                  <td width="78%" class="vtable">
                    <input name="minttl" type="text" class="formfld" id="minttl" size="5" value="<?=htmlspecialchars($pconfig['scrub']['minttl']);?>">
                    <br> <span class="vexpl">Enforces a minimum TTL for matching IP packets</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Max MSS</td>
                  <td width="78%" class="vtable">
                    <input name="maxmss" type="text" class="formfld" id="maxmss" size="5" value="<?=htmlspecialchars($pconfig['scrub']['maxmss']);?>">
                    <br> <span class="vexpl">Enforces a maximum MSS for matching TCP packets.</span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Randomize ID</td>
                  <td width="78%" class="vtable">
                    <input name="randid" type="checkbox" id="randid" value="yes" <?php if ($pconfig['scrub']['randid']) echo "checked"; ?>>
                    <br><span class="vexpl"><pre>
Replaces the IP identification field with random values to compen-
sate for predictable values generated by many hosts.  This option
only applies to packets that are not fragmented after the optional
fragment reassembly.
		</pre></span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Fragment Handling</td>
                  <td width="78%" class="vtable">
                <select name="fraghandle" class="formfld">
                  <?php $types = explode(",", "Reassemble,Crop,Drop-Ovl"); foreach ($types as $type): ?>
                  <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['scrub']['fraghandle'])) echo "selected"; ?>>
                  <?=htmlspecialchars($type);?>
                  </option>
                  <?php endforeach; ?>     
		<br><span class="vexpl"><pre>
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
                </pre></span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top" class="vncellreq">Reassemble TCP</td>
                  <td width="78%" class="vtable">
		<input name="reassembletcp" type="checkbox" id="fragdropol" value="yes" <?php if ($pconfig['scrub']['reassembletcp']) echo "checked"; ?>>
		<br><span class="vexpl"><pre>
Statefully normalizes TCP connections.  scrub reassemble tcp rules
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
         guess the timestamp as well.
                </pre></span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_filter['timeouts'][$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>
		</table>
	</div>
	<div id="tabLogging" style="display:none">
        <?php if ($savemsg) print_info_box($savemsg); ?>
        <?php if (file_exists($d_filterconfdirty_path)): ?><p>
        <?php print_info_box_np("The firewall option configuration has been changed.<br>You must apply the changes in order for them to take effect.");?><br>
        <input name="apply" type="submit" class="formbtn" id="apply" value="Apply changes"></p>
        <?php endif; ?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr>
                  <td width="22%" valign="top" class="vncellreq">Log Default rule</td>
                  <td width="78%" class="vtable">
                    <input name="logdefault" type="checkbox" id="logdefault" value="yes" <?php if ($pconfig['logging']['default']) echo "checked"; ?>>
                    <br><span class="vexpl"><pre>
        Log packets that match the default rule.
                </pre></span></td>
                </tr>
		<tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%">
                    <input name="Submit" type="submit" class="formbtn" value="Save">
                    <?php if (isset($id) && $a_filter['timeouts'][$id]): ?>
                    <input name="id" type="hidden" value="<?=$id;?>">
                     <?php endif; ?>
                    <input name="after" type="hidden" value="<?=$after;?>">
                  </td>
                </tr>
                </table>
		</form>
        </div>
</center>
<script language="JavaScript">
<!--
//-->
</script>
<?php include("fend.inc"); ?>
