#!/bin/php
<?php
/*
  $Id: system_advanced.php,v 1.22 2009/04/20 06:59:37 jrecords Exp $
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
 
$pgtitle = array("System", "Advanced setup");
require("guiconfig.inc");
 
if($config['system']['networking']['maxinputque']) {
  $pconfig['maxinputque'] = $config['system']['networking']['maxinputque'];
} else {
        $pconfig['maxinputque'] = "512";
}
if($config['system']['networking']['maxicmperror']) {
  $pconfig['maxicmperror'] = $config['system']['networking']['maxicmperror'];
} else {
        $pconfig['maxicmperror'] = "100";
}
 
$pconfig['ackonpush'] = isset($config['system']['networking']['ackonpush']);
$pconfig['ecn'] = isset($config['system']['networking']['ecn']);
$pconfig['tcpscaling'] = isset($config['system']['networking']['tcpscaling']);
 
if($config['system']['networking']['tcprcv']) {
        $pconfig['tcprcv'] = $config['system']['networking']['tcprcv'];
} else {
        $pconfig['tcprcv'] = "16384";
}
if($config['system']['networking']['tcpsnd']) {
        $pconfig['tcpsnd'] = $config['system']['networking']['tcpsnd'];
} else {
        $pconfig['tcpsnd'] = "16384";
}
 
$pconfig['sack'] = isset($config['system']['networking']['sack']);
 
if($config['system']['networking']['udprcv']) {
        $pconfig['udprcv'] = $config['system']['networking']['udprcv'];
} else {
        $pconfig['udprcv'] = "41600";
}
if($config['system']['networking']['udpsnd']) {
        $pconfig['udpsnd'] = $config['system']['networking']['udpsnd'];
} else {
        $pconfig['udpsnd'] = "9216";
}
 
if ($_POST) {
 
  unset($input_errors);
  $pconfig = $_POST;
 
  /* input validation */
  if (!$input_errors) {
    $config['system']['networking']['maxinputque'] = $_POST['maxinputque'];
    $config['system']['networking']['maxicmperror'] = $_POST['maxicmperror'];
    $config['system']['networking']['ackonpush'] = $_POST['ackonpush'] ? true : false;
    $config['system']['networking']['ecn'] = $_POST['system']['ecn'] ? true : false;
    $config['system']['networking']['tcpscaling'] = $_POST['tcpscaling'] ? true : false;    
    $config['system']['networking']['tcprcv'] = $_POST['tcprcv'];
                $config['system']['networking']['tcpsnd'] = $_POST['tcpsnd'];
    $config['system']['networking']['sack'] = $_POST['sack'] ? true : false;
    $config['system']['networking']['udprcv'] = $_POST['udprcv'];
    $config['system']['networking']['udpsnd'] = $_POST['udpsnd'];
 
    write_config();
     push_config('networking');  
      
      touch($d_sysrebootreqd_path);
    }
    
    $retval = 0;
    if (!file_exists($d_sysrebootreqd_path)) {
      config_lock();
      $retval = filter_configure();
      $retval |= interfaces_optional_configure();
      $retval |= system_polling_configure();
      $retval |= system_set_termcap();
      $retval |= system_advancednetwork_configure();
      config_unlock();
    }
    $savemsg = get_std_save_message($retval);
}
?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
// -->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<p><span class="vexpl"><span class="red"><strong>Note: </strong></span>the
options on this page are intended for use by advanced users only.</span></p>
<form action="system_networking.php" method="post" name="iform" id="iform">
<table width="100%" border="0" cellpadding="6" cellspacing="0">
<tr>
<td colspan="2" class="list" height="12"></td>
</tr>
<tr>
<td colspan="2" valign="top" class="listtopic">Advanced Network Settings</td>
</tr>
    <tr>
<td width="22%" valign="top" class="vncellreq">Max input queue length</td>
<td width="78%" class="vtable">
<input name="maxinputque" type="text" class="formfld" id="maxinputque" size="5" value="<?=htmlspecialchars($pconfig['maxinputque']);?>">
<br> <span class="vexpl">Maximum allowed input queue length (256*number of interfaces)
</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">Max ICMP errors</td>
<td width="78%" class="vtable">
<input name="maxicmperror" type="text" class="formfld" id="maxicmperror" size="5" value="<?=htmlspecialchars($pconfig['maxicmperror']);?>">
<br> <span class="vexpl">Maximum number of outgoing ICMP error messages per second.</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">Ack on push</td>
<td width="78%" class="vtable">
<input name="ackonpush" type="checkbox" id="ackonpush" value="yes" <?php if ($pconfig['ackonpush']) echo "checked"; ?>>
<br> <span class="vexpl">ACKs for packets with teh push bit set should not be delayed</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">Enable ECN</td>
<td width="78%" class="vtable">
     <input name="ecn" type="checkbox" id="ecn" value="yes" <?php if ($pconfig['ecn']) echo "checked"; ?>>
<br> <span class="vexpl">Enable Explicit Congestion Notification</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">TCP Window Scaling</td>
<td width="78%" class="vtable">
<input name="tcpscaling" type="checkbox" id="tcpscaling" value="yes" <?php if ($pconfig['tcpscaling']) echo "checked"; ?>>
     <br> <span class="vexpl">RFC1323 TCP window scaling.</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">TCP receive window</td>
<td width="78%" class="vtable">
<input name="tcprcv" type="text" class="formfld" id="tcprcv" size="5" value="<?=htmlspecialchars($pconfig['tcprcv']);?>">
<br> <span class="vexpl">TCP receive window size</span></td>
</tr>
    <tr>
<td width="22%" valign="top" class="vncellreq">TCP send window</td>
<td width="78%" class="vtable">
<input name="tcpsnd" type="text" class="formfld" id="tcpsnd" size="5" value="<?=htmlspecialchars($pconfig['tcpsnd']);?>">
<br> <span class="vexpl">TCP send window size</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">TCP Selective ACK</td>
<td width="78%" class="vtable">
<input name="sack" type="checkbox" id="sack" value="yes" <?php if ($pconfig['sack']) echo "checked"; ?>>
     <br> <span class="vexpl">Enable TCP selective ACK (SACK) packet recovery.</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">UDP receive window</td>
<td width="78%" class="vtable">
<input name="udprcv" type="text" class="formfld" id="udprcv" size="5" value="<?=htmlspecialchars($pconfig['udprcv']);?>">
<br> <span class="vexpl">UDP receive window size.</span></td>
</tr>
<tr>
<td width="22%" valign="top" class="vncellreq">UDP send window</td>
<td width="78%" class="vtable">
<input name="udpsnd" type="text" class="formfld" id="udpsnd" size="5" value="<?=htmlspecialchars($pconfig['udpsnd']);?>">
<br> <span class="vexpl">UDP send window size</span></td>
</tr>
    <tr>
<td width="22%" valign="top">&nbsp;</td>
<td width="78%">
<input name="Submit" type="submit" class="formbtn" value="Save" onclick="enable_change(true)">
</td>
</tr>
</table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
