#!/bin/php
<?php 

$pgtitle = array("Diagnostics", "Traceroute");
require("guiconfig.inc");
include("ns-begin.inc");

define('MAX_COUNT', 64);
define('DEFAULT_COUNT', 18);

if (!isset($do_ping)) {
    $do_ping = false;
    $host = '';
    $count = DEFAULT_COUNT;
}

?>

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
                             <label for="name">Host</label>
                             <input id="name" type="text" name="name" value="" />
			</div>
                        <div>
                             <label for="hops">Max Hops</label>
                              <select name="count" class="formfld" id="count">
                    <?php for ($i = 1; $i <= MAX_COUNT; $i++): ?>
                    <option value="<?=$i;?>" <?php if ($i == $count) echo "selected"; ?>><?=$i;?></option>
                    <?php endfor; ?>
                    </select>
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
