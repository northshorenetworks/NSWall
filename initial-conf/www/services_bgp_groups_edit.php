#!/bin/php
<?php 

$pgtitle = array("Services", "BGPd", "Edit Neighbor");
require("guiconfig.inc");
include("ns-begin.inc");

if (!is_array($config['bgpd']['group']))
        $config['bgpd']['group'] = array();
 
 
bgpgroups_sort();
$a_group = &$config['bgpd']['group'];
 
$id = $_GET['id'];
if (isset($_POST['id']))
        $id = $_POST['id'];
 
if (isset($_POST['after']))
        $after = $_POST['after'];
 
if (isset($_GET['dup'])) {
        $id = $_GET['dup'];
        $after = $_GET['dup'];
}
 
if (isset($id) && $a_group[$id]) {
    $pconfig['name'] = $a_group[$id]['name'];
    $pconfig['descr'] = $a_group[$id]['descr'];
    $pconfig['remoteas'] = $a_group[$id]['remoteas'];
    $pconfig['announce4byte'] = isset($a_group[$id]['announce4byte']);
    $pconfig['announcecababilities'] = isset($a_group[$id]['announcecababilities']);
    $pconfig['announcerefresh'] = isset($a_group[$id]['announcerefresh']);
    $pconfig['announcerestart'] = isset($a_group[$id]['announcerestart']);
    $pconfig['localaddress'] = $a_group[$id]['localaddress'];
    $pconfig['holdtime'] = $a_group[$id]['holdtime'];
    $pconfig['minholdtime'] = $a_group[$id]['minholdtime'];
    $pconfig['multihop'] = $a_group[$id]['multihop'];
    $pconfig['transparentas'] = isset($a_group[$id]['transparentas']);
    $pconfig['ttlsecurity'] = isset($a_group[$id]['ttlsecurity']);

}
 
if (isset($_GET['dup']))
        unset($id);

?>

<script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/services_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
                setTimeout(function(){  
                     document.location = '#services_bgp_tab_peers';
                     $('#content').load('services_bgpd.php');
                }, 1250);
        });
    return false;
    });
});
</script>

<div class="demo">
<div id="wrapper">
        <div class="form-container ui-tabs ui-widget ui-corner-content">

    <form action="forms/services_form_submit.php" method="post" name="iform" id="iform">
        <input name="formname" type="hidden" value="services_bgpd_group">
        <input name="id" type="hidden" value="<?=$id;?>">
    <fieldset>
        <legend><?=join(": ", $pgtitle);?></legend>
            <div>
                             <label for="name">Name</label>
                             <input id="name" type="text" name="name" value="<?=htmlspecialchars($pconfig['name']);?>" />
                             <p class="note">Name of the virtual host, for your reference</p>
            </div>
            <div>
                             <label for="email">Description</label>
                             <input id="descr" type="text" name="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>" />
                             <p class="note">You may enter a description here for your reference (not parsed).</p>
            </div>
            <div>
                             <label for="remoteas">Remote AS</label>
                             <input id="remoteas" type="text" size="8" name="remoteas" value="<?=htmlspecialchars($pconfig['remoteas']);?>" />
                             <p class="note">Autonomous System Number for the peer (digits only)</p>
            </div>
            <div>
                             <label for="announce4byte">Announce as 4byte</label>
                             <input id="announce4byte" type="checkbox" name="announce4byte" value="Yes" <?php if ($pconfig['announce4byte']) echo "checked"; ?> />
                             <p class="note">Insert learned routes into kernel routing table.</p>
            </div>
	    <div>
                             <label for="announcecapabilities">Announce Capabilities</label>
                             <input id="announcecapabilities" type="checkbox" name="announcecapabilities" value="Yes" <?php if ($pconfig['announcecapabilities']) echo "checked"; ?> />
                             <p class="note">Insert learned routes into kernel routing table.</p>
            </div>
  	    <div>
                             <label for="announcerefresh">Announce Refresh</label>
                             <input id="announcerefresh" type="checkbox" name="announcerefresh" value="Yes" <?php if ($pconfig['announcerefresh']) echo "checked"; ?> />
                             <p class="note">Insert learned routes into kernel routing table.</p>
            </div>
 	    <div>
                             <label for="announcerestart">Announce Restart</label>
                             <input id="announcerestart" type="checkbox" name="announcerestart" value="Yes" <?php if ($pconfig['announcerestart']) echo "checked"; ?> />
                             <p class="note">Insert learned routes into kernel routing table.</p>
            </div>
	    <div>
                             <label for="enforcegroupas">Enforce Neighbor AS</label>
                             <input id="enforcegroupas" type="checkbox" name="enforcegroupas" value="Yes" <?php if ($pconfig['enforcegroupas']) echo "checked"; ?> />
			     <p class="note">Maximum number of hops a group can be away.</p>
            </div>
	    <div>
                             <label for="holdtime">Holdtime</label>
                             <input id="holdtime" type="text" size="4" name="holdtime" value="<?=htmlspecialchars($pconfig['holdtime']);?>" />
                             <p class="note">Maximum number of hops a group can be away.</p>
            </div>
  	    <div>
                             <label for="minholdtime">Minimum Holdtime</label>
                             <input id="minholdtime" type="text" size="4" name="minholdtime" value="<?=htmlspecialchars($pconfig['minholdtime']);?>" />
                             <p class="note">Maximum number of hops a group can be away.</p>
            </div>
	    <div>
                             <label for="localaddress">Local Address</label>
                             <input id="localaddress" type="text" size="20" name="localaddress" value="<?=htmlspecialchars($pconfig['localaddress']);?>" />
                             <p class="note">Maximum number of hops a group can be away.</p>
            </div>
            <div>
                             <label for="multihop">Multihop</label>
                             <input id="multihop" type="text" size="4" name="multihop" value="<?=htmlspecialchars($pconfig['multihop']);?>" />
                             <p class="note">Maximum number of hops a group can be away.</p>
            </div>
	    <div>
                             <label for="transparentas">Transparent AS</label>
                             <input id="transparentas" type="checkbox" name="transparentas" value="Yes" <?php if ($pconfig['transparentas']) echo "checked"; ?> />
                             <p class="note">Insert learned routes into kernel routing table.</p>
            </div>
	    <div>
                             <label for="ttlsecurity">TTL Security</label>
                             <input id="ttlsecurity" type="checkbox" name="ttlsecurity" value="Yes" <?php if ($pconfig['ttlsecurity']) echo "checked"; ?> />
                             <p class="note">Insert learned routes into kernel routing table.</p>
            </div>
                      
    </fieldset>
    
    <div class="buttonrow">
        <input type="submit" id="submitbutton" value="Save" class="button" />
    </div>

    </form>
    
    </div><!-- /form-container -->
    
</div><!-- /wrapper -->

