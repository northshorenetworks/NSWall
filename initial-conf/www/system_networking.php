#!/bin/php
<?php
 
$pgtitle = array("System", "Advanced Networking");
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
?>

<script type="text/javascript">

// pre-submit callback 
function showRequest(formData, jqForm, options) { 
    displayProcessingDiv(); 
    return true; 
}

// post-submit callback 
function showResponse(responseText, statusText)  {
    if(responseText.match(/SUBMITSUCCESS/)) {  
           setTimeout(function(){ $('#save_config').fadeOut('slow'); }, 2000);
    }
} 

        // wait for the DOM to be loaded
    $(document).ready(function() {
            $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
            var options = {
                        target:        '#save_config',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback 
                        success:       showResponse  // post-submit callback
            };

           // bind form using 'ajaxForm'
           $('#iform').ajaxForm(options);
    });
</script> 

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_networking">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="maxinputque">Max input queue length</label>
                             <input id="maxinputque" type="text" name="maxinputque" value="<?=htmlspecialchars($pconfig['maxinputque']);?>" />
                             <p class="note">Maximum allowed input queue length (256*number of interfaces)</p>
                        </div>
			<div>
                             <label for="maxicmperror">Max ICMP errors</label>
                             <input id="maxicmperror" type="text" name="maxicmperror" value="<?=htmlspecialchars($pconfig['maxicmperror']);?>" />
			     <p class="note">Maximum number of outgoing ICMP error messages per second.</p>
			</div>
			<div>
                             <label for="ackonpush">ACK on push</label>
                             <input id="ackonpush" type="checkbox" name="ackonpush" value="Yes" <?php if ($pconfig['ackonpush']) echo "checked"; ?> />
			     <p class="note">ACKs for packets with the push bit set should not be delayed</p>
			</div>
                        <div>
                             <label for="ecn">ECN</label>
                             <input id="ecn" type="checkbox" name="ecn" value="Yes" <?php if ($pconfig['ecn']) echo "checked"; ?> />
			     <p class="note">Enable Explicit Congestion Notification.</p>
			</div>
                       	<div>
                             <label for="tcpscaling">TCP Window Scaling</label>
                             <input id="tcpscaling" type="checkbox" name="tcpscaling" value="Yes" <?php if ($pconfig['tcpscaling']) echo "checked"; ?> />
			     <p class="note">Enable RFC1323 TCP window scaling.</p>
			</div> 
			<div>
                             <label for="tcprcv">TCP receive window</label>
                             <input id="tcprcv" type="text" name="tcprcv" value="<?=htmlspecialchars($pconfig['tcprcv']);?>" />
			     <p class="note">TCP receive window size</p>
			</div>
                        <div>
                             <label for="tcpsnd">TCP send window</label>
                             <input id="tcpsnd" type="text" name="tcpsnd" value="<?=htmlspecialchars($pconfig['tcpsnd']);?>" />
			     <p class="note">TCP send window size</p>
			</div>
                        <div>
                             <label for="sack">TCP Selective ACK</label>
                             <input id="sack" type="checkbox" name="sack" value="Yes" <?php if ($pconfig['sack']) echo "checked"; ?> />
                             <p class="note">Enable TCP selective ACK (SACK) packet recovery.</p>
			</div>
                        <div>
                             <label for="udprcv">UDP receive window</label>
                             <input id="udprcv" type="text" name="udprcv" value="<?=htmlspecialchars($pconfig['udprcv']);?>" />
			     <p class="note">UDP receive window size</p>
			</div>
                        <div>
                             <label for="udpsnd">UDP send window</label>
                             <input id="udpsnd" type="text" name="udpsnd" value="<?=htmlspecialchars($pconfig['udpsnd']);?>" />
			     <p class="note">UDP send window size</p>
			</div>
                      
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" value="Save" class="button" />
		<input type="button" value="Discard" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
	<p id="copyright">Created by <a href="http://nidahas.com/">Prabhath Sirisena</a>. This stuff is in public domain.</p>
	
</div><!-- /wrapper -->
