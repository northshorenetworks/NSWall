#!/bin/php
<?php

require("guiconfig.inc");
$pgtitle = array("Diagnostics", "Support");
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
                             <label for="caseid">Case Number</label>
                             <input id="caseid" type="text" name="name" value="" />
                             <p class="note">If you have a case number enter it here, leave blank to create a new case</p>
                </div>
                <div>
                             <label for="notes">Case Notes</label>
                             <textarea name="notes" cols="60" rows="7" id="notes" class="notes"></textarea> 
                             <p class="note">Enter any relevant information here to be read by a support technician</p>   
               </div>
			   <div>
                             <label for="notes">Support Access</label>
                             <input id="all" type="checkbox" name="all" value="" />
							 <p class="note">Create rule to allow support to connect to this appliance</p><br>
               </div>
               <div>
                             <label for="all">All (Reccomended)</label>
                             <input id="all" type="checkbox" name="all" value="" />
                             <p class="note">Attach all debug information to support case</p><br>

                             <label for="xmlconfig">ifconfig -A</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">Output of ifconfig -A command</p><br>

                             <label for="xmlconfig">netstat -rn</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">Output of netstat -rn command</p><br>

                             <label for="xmlconfig">pf.conf</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">The pf.conf file generate by NSWall</p><br>

                             <label for="xmlconfig">Processes</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">Ouput of ps -ax command</p><br>

                             <label for="xmlconfig">Disk Usage</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">Output of df -h command</p><br>

                             <label for="xmlconfig">Log Buffer</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">The current log buffer</p><br>

                             <label for="xmlconfig">ls /conf</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">Output of ls /conf command</p><br>

                             <label for="xmlconfig">ls /var/run</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">Output of ls /var/run command</p><br>

                             <label for="xmlconfig">XML Config File</label>
                             <input id="xmlconfig" type="checkbox" name="xmlconfig" value="" />
                             <p class="note">XML Configuration file, (passwords are not sent)</p><br>
               </div>
            </fieldset>
                <div class="buttonrow">
        <input type="submit" id="submitbutton" value="Submit" class="button" />
    </div>

</form>
</div>
</div>
<div>
