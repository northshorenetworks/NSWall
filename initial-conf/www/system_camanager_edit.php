#!/bin/php
<?php
$pgtitle = array("Interfaces", "Assign network ports", "Edit VLAN");
require("guiconfig.inc");

$id = $_GET['id'];  
if (isset($_POST['id']))  
    $id = $_POST['id']; 

$act = $_GET['act'];
if ($_POST['act'])
    $act = $_POST['act'];

if (!is_array($config['system']['certmgr']['ca']))
    $config['system']['certmgr']['ca'] = array();

$a_ca =& $config['system']['certmgr']['ca'];

system_ca_sort();

if ($act == "exp") {

    if (!$a_ca[$id]) {
        header("system_camanager.php");
        exit;
    }

    $exp_name = urlencode("{$a_ca[$id]['name']}.crt");
    $exp_data = base64_decode($a_ca[$id]['crt']);
    $exp_size = strlen($exp_data);

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename={$exp_name}");
    header("Content-Length: $exp_size");
    echo $exp_data;
    exit;
} 

$ca_methods = array(
    "existing" => "Import an existing Certificate Authority",
    "internal" => "Create an internal Certificate Authority");

$ca_keylens = array( "512", "1024", "2048", "4096");

$pgtitle = array("System", "Certificate Authority Manager");

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/system_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        		setTimeout(function(){ $('#content').load('system_cacert_tabs.php'); }, 1250);
		});
    return false;
    });
    $("#method").change(function() {
          var val = $(this).val();
          switch(val){
          case 'existing':
              $("#importdiv").show();
              $("#internaldiv").hide();
              break;
          case 'internal':
              $("#internaldiv").show();
              $("#importdiv").hide();
              break;
    }
     
     var method = $("#method");
     switch(method.val()){
          case 'existing':
              $("#importdiv").show();
              $("#internaldiv").hide();
              break;
          case 'internal':
              $("#internaldiv").show();
              $("#importdiv").hide();
              break;
          }
     });
    
    // When a user clicks on the src alias add button, add the selected value.
    $("#aliasaddbutton").click(function () {
         var alias = $("#aliashost");
     $('#ALIASADDR').append("<option value='" + alias.val() + "'>" + alias.val() + '</option>');
         return false;
    });

});
</script>

<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-all">

	<form action="forms/system_form_submit.php" method="post" name="iform" id="iform">
              <input name="formname" type="hidden" value="system_camanager">
			  <input name="id" type="hidden" value="<?=$id;?>">
	<fieldset>
		<legend><?=join(": ", $pgtitle);?></legend>
		<div>
			     <label for="name">Descriptive Name</label>
                 <input name="name" type="text" class="formfld" id="name" size="30" value="<?=htmlspecialchars($pconfig['name']);?>">
                 <p class="note">The name of the CA (not parsed)</p>
        </div>
        <div>
                 <label for "method">Method</label>
				 <select name='method' id='method' class="formselect" onchange='method_change()'>
                                <?php
                                    foreach($ca_methods as $method => $desc):
                                    $selected = "";
                                    if ($pconfig['method'] == $method)
                                        $selected = "selected";
                                    ?>
                                <option value="<?=$method;?>"<?=$selected;?>><?=$desc;?></option>
                                    <?php endforeach; ?>
                               </select>        		
		</div>         
                <div id='importdiv'>
                    <div>
                        <label for="cert">Certificate Data</label>
            		<textarea name="cert" id="cert" cols="65" rows="7" class="formfld_cert"><?=$pconfig['cert'];?></textarea>	
			<p class="note">Paste a certificate in X.509 PEM format here.</p>
		    </div>
		</div>	
			<div id='internaldiv'>
				 <div>
                                     <label for="keylen">Key Length</label>
                                     <select name='keylen' id='keylen'>
                                     <?php
                                         foreach( $ca_keylens as $len):
                                             $selected = "";
                                             if ($pconfig['keylen'] == $len)
                                                 $selected = "selected";
                                     ?>
                                         <option value="<?=$len;?>"<?=$selected;?>><?=$len;?></option>
                                     <?php endforeach; ?>
                                     </select> bits
			             <p class="note"> </p>
            	                 </div>	
				 <div>
					 <label for="lifetime">Lifetime</label>
					 <input name="lifetime" type="text" id="lifetime" size="5" value="<?=htmlspecialchars($pconfig['lifetime']);?>"/> days
				 	 <p class="note"> </p>
				 </div>
				 <div>
					 <label for="dn_country">Country Code</label>
					 <input name="dn_country" type="text"  size="2" value="<?=htmlspecialchars($pconfig['dn_country']);?>"/>
					 <p class="note">Example: US (two letters)</p>
				 </div>
				  <div>
                     <label for="dn_state">State/Province</label>
                     <input name="dn_state" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['dn_state']);?>"/>
		     <p class="note">Example: Texas</p>
                 </div>
				 <div>
                     <label for="dn_city">City</label>
                     <input name="dn_city" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['dn_city']);?>"/>
		     <p class="note">Example: Austin</p>
                 </div>
				 <div>
                     <label for="dn_organization">Organization</label>
                     <input name="dn_organization" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['dn_organization']);?>"/>
		     <p class="note">Example: My Company Inc.</p>
                 </div>
				 <div>
                     <label for="dn_email">Email Address</label>
                     <input name="dn_email" type="text" class="formfld unknown" size="25" value="<?=htmlspecialchars($pconfig['dn_email']);?>"/>
		     <p class="note">Example: admin@mycompany.com</p>
                 </div>
				 <div>
                     <label for="dn_commonname">Common Name</label>
                     <input name="dn_commonname" type="text" class="formfld unknown" size="25" value="<?=htmlspecialchars($pconfig['dn_commonname']);?>"/>
		     <p class="note">Example: internal-ca</p>
                 </div>
			</div>
	</fieldset>
	
	<div class="buttonrow">
		<input type="submit" id="submitbutton" value="Save" class="button" />
	</div>

	</form>
	
	</div><!-- /form-container -->
</div><!-- /wrapper -->
