#!/bin/php
<?php
 
$pgtitle = array("System", "Static routes", "Edit");
require("guiconfig.inc");

if (!is_array($config['staticroutes']['route']))
    $config['staticroutes']['route'] = array();

staticroutes_sort();
$a_routes = &$config['staticroutes']['route'];

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

if (isset($id) && $a_routes[$id]) {
    $pconfig['interface'] = $a_routes[$id]['interface'];
    list($pconfig['network'],$pconfig['network_subnet']) =
        explode('/', $a_routes[$id]['network']);
    $pconfig['gateway'] = $a_routes[$id]['gateway'];
    $pconfig['descr'] = $a_routes[$id]['descr'];
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

	<form action="forms/firewall_form_submit.php" method="post" name="iform" id="iform">
              <input name="formname" type="hidden" value="system_routes">
			  <input name="id" type="hidden" value="<?=$id;?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
			<div>
                             <label for="interface">Interface</label>
                             <select name="interface" class="formfld">
                        <?php
                        $interfaces = array('wan' => 'WAN', 'pptp' => 'PPTP');
                        for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
                            $interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
                        }
                        foreach ($interfaces as $iface => $ifacename): ?>
                        <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
                        <?=htmlspecialchars($ifacename);?>
                        </option>
                        <?php endforeach; ?>
                    </select>
			     <p class="note">Choose which interface this rule applies to.  Hint: in most cases, you'll want to use WAN here..</p>
			</div>
                        <div>
                             <label for="descr">Description</label>
                             <input id="descr" type="text" size="50" name="descr" value="<?=htmlspecialchars($pconfig['descr']);?>" />
			     <p class="note">You may enter a description here for your reference (not parsed).</p>
			</div>
                        <div>
                             <label for="network">Destination</label>
                             <input name="network" type="text" class="formfld" id="network" size="20" value="<?=htmlspecialchars($pconfig['network']);?>">
                  /
                    <select name="network_subnet" class="formfld" id="network_subnet">
                      <?php for ($i = 32; $i >= 1; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['network_subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
			     <p class="note">Destination network for this static route.</p>
			</div>
                        <div>
                             <label for="gateway">Gateway</label>
                             <input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>">
                             <p class="note">Gateway to be used to reach the destination network.</p>
                        </div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
	
	<p id="copyright">Created by <a href="http://nidahas.com/">Prabhath Sirisena</a>. This stuff is in public domain.</p>
	
</div><!-- /wrapper -->
