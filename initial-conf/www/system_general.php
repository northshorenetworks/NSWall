#!/bin/php
<?php 

$pgtitle = array("System", "General setup");
require("guiconfig.inc");

$pconfig['cert'] = $config['system']['general']['webgui']['certificate'];
$pconfig['hostname'] = $config['system']['hostname'];
$pconfig['domain'] = $config['system']['general']['domain'];
list($pconfig['dns1'],$pconfig['dns2'],$pconfig['dns3']) = $config['system']['general']['dnsserver'];
$pconfig['dnsallowoverride'] = isset($config['system']['general']['dnsallowoverride']);
$pconfig['username'] = $config['system']['username'];
if (!$pconfig['username'])
	$pconfig['username'] = "admin";
$pconfig['webguiproto'] = $config['system']['general']['webgui']['protocol'];
if (!$pconfig['webguiproto'])
	$pconfig['webguiproto'] = "http";
$pconfig['webguiport'] = $config['system']['general']['webgui']['port'];
$pconfig['timezone'] = $config['system']['general']['timezone'];
$pconfig['timeupdateinterval'] = $config['system']['general']['time-update-interval'];
$pconfig['timeservers'] = $config['system']['general']['timeservers'];
$pconfig['sshdenabled'] = isset($config['system']['general']['sshd']['enabled']);
$pconfig['symonenabled'] = isset($config['system']['general']['symon']['enabled']);
$pconfig['muxip'] = $config['system']['general']['symon']['muxip'];

if (!isset($pconfig['timeupdateinterval']))
	$pconfig['timeupdateinterval'] = 300;
if (!$pconfig['timezone'])
	$pconfig['timezone'] = "Etc/UTC";
if (!$pconfig['timeservers'])
	$pconfig['timeservers'] = "pool.ntp.org";
	
function is_timezone($elt) {
	return !preg_match("/\/$/", $elt);
}

exec('/bin/tar -tzf /usr/share/zoneinfo.tgz', $timezonelist);
$timezonelist = array_filter($timezonelist, 'is_timezone');
sort($timezonelist);

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

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_general">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="hostname">Hostname</label>
                             <input id="hostname" type="text" name="hostname" value="<?=htmlspecialchars($pconfig['hostname']);?>" />
                             <p class="note">Name of the firewall host without the domain part</p>
                        </div>
			<div>
                             <label for="email">Domain</label>
                             <input id="domain" type="text" name="domain" value="<?=htmlspecialchars($pconfig['domain']);?>" />
			     <p class="note">e.g. <em>mycorp.com</em> </p>
			</div>
			<div>
                             <label for="dns1 dns2 dns3">DNS Servers</label>
                             <input id="dns1" type="text" name="dns1" value="<?=htmlspecialchars($pconfig['dns1']);?>" size="20" />
                             <input id="dns2" type="text" name="dns2" value="<?=htmlspecialchars($pconfig['dns2']);?>" size="20" />
                             <input id="dns3" type="text" name="dns3" value="<?=htmlspecialchars($pconfig['dns3']);?>" size="20" />
                        </div>
			<div>
                             <label for="username">Username</label>
                             <input id="username" type="text" name="username" value="<?=$pconfig['username'];?>" />
			     <p class="note">If you want to change the username for accessing the WebUI do it here.</p>
			</div>
                        <div>
                             <label for="password">Password</label>
                             <input id="password" type="password" name="password" value="" />
                             <input id="password2" type="password" name="password2" value="" />
                             &nbsp;(confirmation) <br> <p class="note">If you want to change the password for accessing the webGUI, enter it here twice.</p>
			</div>
                       	 <div>
                             <label for="webguiport">WebGUI Port</label>
            				 <input id="webguiport" type="text" name="webguiport" value="<?=htmlspecialchars($pconfig['webguiport']);?>" />
			</div> 
						 <div>
                             <label for="cert">WebGUI Cert</label>
                             <select name="cert" class="formfld" id="cert">
                                  <?php foreach($config['system']['certmgr']['cert'] as $i): ?>
                                  <option value="<?=$i['name'];?>" 
                                  <?php if ($i == $pconfig['cert']) echo "selected"; ?>>
                                  <?=$i['name'];?>
                                  </option>
                                  <?php endforeach; ?>
                             </select>
			</div>
                        <div>
                             <label for="timezone">Timezone</label>
                             <select name="timezone" id="timezone">
                             <?php foreach ($timezonelist as $value): ?>
                                  <option value="<?=htmlspecialchars($value);?>" 
                                  <?php if ($value == $pconfig['timezone']) echo "selected"; ?>>
                                  <?=htmlspecialchars($value);?>
                                  </option>
                                  <?php endforeach; ?>
                             </select>
			</div>
                        <div>
                             <label for="sshenabled">Enable SSH Server</label>
                             <input id="sshenabled" type="checkbox" name="sshenabled" value="<?php if ($pconfig['sshdenabled']) echo "checked"; ?>" />
			     <p class="note">We will never sell or disclose your email address to anyone.</p>
			</div>
                        <div>
                             <label for="symuxenabled">Enable symux logging</label>
                             <input id="symuxenabled" type="checkbox" name="symuxenabled" value="<?php if ($pconfig['symonenabled']) echo "checked"; ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Symux Server:
                             <input id="symuxserver" type="text" name="symuxserver" value="<?=htmlspecialchars($pconfig['muxip']);?>" size="20" />
			     <p class="note">We will never sell or disclose your email address to anyone. </p>
			</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" value="Save" class="button" />

		<input type="button" value="Discard" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
	<p id="copyright">NorthShore Software Footer</p>
	
</div><!-- /wrapper -->
