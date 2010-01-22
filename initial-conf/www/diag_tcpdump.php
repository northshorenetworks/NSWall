#!/bin/php
<?php 

$pgtitle = array("Diagnostics", "TCPDump");
require("guiconfig.inc");
include("ns-begin.inc");

define('MAX_COUNT', 10);
define('DEFAULT_COUNT', 4);

if (!isset($do_ping)) {
    $do_ping = false;
    $host = '';
    $count = DEFAULT_COUNT;
}

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
	 $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');

});

</script> 

<div class="demo">
<div id="wrapper">
<div class="form-container ui-tabs ui-widget ui-corner-content">
 
             <form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
	     <input name="formname" type="hidden" value="firewall_rule">
             <input name="id" type="hidden" value="<?=$id;?>">
             <div id="tabAddress">
	        <fieldset>
                        <legend><?=join(": ", $pgtitle);?></legend>
                        <div>
                             <label for="protocol">Protocol</label>
                              <select name="port" class="formfld" id="proto">
                                        <option selected value="http">HTTP</option>
                                        <option value="smtp">SMTP</option>
                                        <option value="ftp or ftp-data">FTP</option>
                    <option value="telnet">Telnet</option>
                    <option value="ssh">SSH</option>
                    <option value="icmp">ICMP</option>
                    <option value="any">Any</option>
                                        </select>
			     <p class="note">Choose on which interface to send the ping packets from</p>
			</div>
                        <div>
                             <label for="interface">Interface</label>
                              <select name="interface" class="formfld">
                              <?php $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP');
                                          for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                                                $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                                          }
                                          foreach ($interfaces as $iface => $ifacename): ?>
<option value="<?=$iface;?>">
<?=htmlspecialchars($ifacename);?>
</option>
<?php endforeach; ?>
</select>
			     <p class="note">Choose on which interface to tcpdump from</p>
			</div>
                        <div>
                             <label for="interface">Count</label>
                              <select name="count" class="formfld" id="count">
                    <option selected value="10">10</option>
                    <option value="100">100</option>
                    <option value="1000">1000</option>
                    </select>
			     <p class="note">Choose how many packets to capture</p>
			</div>
                </fieldset>
                <div class="buttonrow">
		<input type="submit" id="submitbutton" value="Submit" class="button" />
	</div>
       
        </div>
</form>
</div>
</div>
</div>
