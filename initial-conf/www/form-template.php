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

<style type="text/css">

/* Form styles */
div.form-container { margin: 10px; padding: 5px; background-color: #FFF; border: #EEE 1px solid; }

p.legend { margin-bottom: 1em; }
p.legend em { color: #C00; font-style: normal; }

div.errors { margin: 0 0 10px 0; padding: 5px 10px; border: #FC6 1px solid; background-color: #FFC; }
div.errors p { margin: 0; }
div.errors p em { color: #C00; font-style: normal; font-weight: bold; }

div.form-container form p { margin: 0; }
div.form-container form p.note { margin-left: 170px; font-size: 70%; font-style: italic; color: #333; }
div.form-container form fieldset { margin: 10px 0; padding: 10px; border: #DDD 1px solid; }
div.form-container form legend { font-weight: bold; color: #666; }
div.form-container form fieldset div { padding: 0.5em 0; margin: 0.5em; }
div.form-container label, 
div.form-container span.label { margin-right: 10px; padding-right: 10px; width: 150px; display: block; float: left; text-align: right; position: relative; font-size: .9em; font-style: bold;}
div.form-container label.error, 
div.form-container span.error { color: #C00; }
div.form-container label em, 
div.form-container span.label em { position: absolute; right: 0; font-size: 120%; font-style: normal; color: #C00; }
div.form-container input.error { border-color: #C00; background-color: #FEF; }
div.form-container input:focus,
div.form-container input.error:focus, 
div.form-container textarea:focus {	background-color: #FFC; border-color: #FC6; }
div.form-container div.controlset label, 
div.form-container div.controlset input { display: inline; float: none; }
div.form-container div.controlset div { margin-left: 170px; }
div.form-container div.buttonrow { margin-left: 180px; }

</style>



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
        <div class="form-container ui-tabs ui-widget ui-widget-content ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="system_general">

	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="host">Hostname</label>
                             <input id="host" type="text" name="host" value="<?=htmlspecialchars($pconfig['hostname']);?>" />
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
	
	<p id="copyright">Created by <a href="http://nidahas.com/">Prabhath Sirisena</a>. This stuff is in public domain.</p>
	
</div><!-- /wrapper -->
