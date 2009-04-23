#!/bin/php
<?php
/*
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

$pgtitle = array("Firewall", "Clustering Configuration");
require("guiconfig.inc");

/* get list without VLAN interfaces */
$portlist = get_interface_list();

if (!is_array($config['cluster'])) {
        $config['cluster'] = array();
}

$pconfig['nonodes'] = $config['cluster']['nonodes'];
$pconfig['clusterid'] = $config['cluster']['clusterid'];

foreach ($config['cluster']['clusterif'] as $clusterif) {
           foreach($config['interfaces'] as $ifname => $iface) {
                  if ($clusterif[$ifname]['carpnode'])
                  $pconfig[$ifname . '-carpnode'] = $clusterif[$ifname]['carpnode'];
                  if ($clusterif[$ifname]['clusterpw'])
                  $pconfig[$ifname . '-clusterpw'] = $clusterif[$ifname]['clusterpw'];    
                  if ($clusterif['lan']['clusterip'])
                  $pconfig['clusterip'] = $clusterif['lan']['clusterip'];
           }
      }	       


if ($_POST) {
        $pconfig = $_POST;
	$config['cluster']['nonodes'] = $_POST['nonodes'];
        $config['cluster']['pfsync'] = $_POST['pfsync'];
 	$config['cluster']['clusterid'] = $_POST['clusterid'];
        $config['cluster']['clusterif'] = array();
        foreach($config['interfaces'] as $ifname => $iface) {
                $config['cluster']['clusterif'][$ifname][$ifname] = array();
		if($_POST[$ifname . '-carpnode']) {
			$config['cluster']['clusterif'][$ifname][$ifname]['carpnode'] = $_POST[$ifname . '-carpnode'];
                }
		else {
			for($i=0;$i<100;$i++) {
				foreach($config['interfaces'] as $ifname => $iface) {
					if($config['cluster']['clusterif'][$ifname][$ifname]['carpnode'] == 'carp' . $i) {
						continue 2;
					} 	
				}
				$config['cluster']['clusterif'][$ifname][$ifname]['carpnode'] = 'carp' . $i;
				break;
			}
		}
                if($config['interfaces'][$ifname]['ipaddr'])
                     $config['cluster']['clusterif'][$ifname][$ifname]['sharedip'] = $config['interfaces'][$ifname]['ipaddr'];
                if($config['interfaces'][$ifname]['subnet'])
                     $config['cluster']['clusterif'][$ifname][$ifname]['subnet'] = $config['interfaces'][$ifname]['subnet'];
	        $config['cluster']['clusterif'][$ifname][$ifname]['if'] = $config['interfaces'][$ifname]['if']; 
        	if($ifname == 'lan')
			$config['cluster']['clusterif'][$ifname][$ifname]['clusterip'] = $_POST['clusterip'];;
	}	
	write_config();
		/*touch($d_sysrebootreqd_path);*/
}

?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>

<?php if (file_exists($d_sysrebootreqd_path)) print_info_box(get_std_save_message(0)); ?>
<form action="firewall_clustering.php" method="post" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
                  <td colspan="2" valign="top" class="listtopic">Clustering configuration</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">LAN Virtual IP start</td>
                  <td class="vtable"> <input name="clusterip" type="text" class="formfld" id="clusterip" size="16" value="<?=htmlspecialchars($pconfig['clusterip']);?>">
                    <br>
                    If you enter a value in this field, then MSS clamping for 
                    TCP connections to the value entered above minus 40 (TCP/IP 
                    header size) will be in effect. If you leave this field blank, 
                    an MTU of 1492 bytes for PPPoE and 1500 bytes for all other 
                    connection types will be assumed.</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">Cluster Nodes</td>
                  <td class="vtable"> <input name="nonodes" type="text" class="formfld" id="nonodes" size="4" value="<?=htmlspecialchars($pconfig['nonodes']);?>">
                    <br>    
                    The Virtual Host ID. This is a unique number that is used to  
                    identify the redundancy group to other nodes on the network. 
                    Acceptable values are from 1 to 255.</td>
                </tr>
		 <tr> 
                  <td valign="top" class="vncell">Cluster ID</td>
                  <td class="vtable"> <input name="clusterid" type="text" class="formfld" id="cluterid" size="4" value="<?=htmlspecialchars($pconfig['clusterid']);?>">
                    <br>    
                    The Cluster ID, this is the pool member number for the cluster. 
                    The master is set to 1, all other members configs will be auto-generated
                    This id should not be changed unless you know what you are doing.</td>
                </tr>
		<tr> 
                  <td valign="top" class="vncell">PFSync Interface</td>
		 <td> 
                  <select name="pfsync" class="formfld" id="pfsync">  
                  <?php foreach ($config['interfaces'] as $ifname => $iface):?>
		  <option value="<?=strtoupper($ifname);?>" <?php if ($portname == $iface['if']) echo "selected";?>> 
                  <?php echo strtoupper($ifname); ?>  
		  </option>
                  <?php endforeach; ?>
		 </select> 
                    <br>PFsync Interface    
                  </td>
                </tr>
                </table>
  <tr> 
    <td class="vncel">
                    <table border="0" cellpadding="0" cellspacing="0">
                      <tr> 
	<td class="listhdrr">Interface</td>
	<td class="list">&nbsp;</td>

  </tr>
  <?php foreach ($config['interfaces'] as $ifname => $iface):?>
  <tr> 
	<td class="listlr" align="center" valign="middle"><strong><?=strtoupper($ifname);?></strong></td>
  	<input name="<?=$ifname . "-carpnode";?>" type="hidden" value="<?=$pconfig[$ifname . '-carpnode'];?>">
  </tr>
  <?php endforeach; ?></table><table>


<br><input name="Submit" type="submit" class="formbtn" value="Save"><br><br>
<p><span class="vexpl"><strong><span class="red">Warning:</span><br>
</strong>After you click &quot;Save&quot;, you must reboot the firewall to make the changes take effect. You may also have to do one or more of the following steps before you can access your firewall again: </span></p>

<ul>
  <li><span class="vexpl">change the IP address of your computer</span></li>
  <li><span class="vexpl">renew its DHCP lease</span></li>
  <li><span class="vexpl">access the webGUI with the new IP address</span></li>
</ul></td>
	</tr>
</table>
</form>
<?php include("fend.inc"); ?>
