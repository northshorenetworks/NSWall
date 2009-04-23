#!/bin/php
<?php 
/*
	$Id: firewall_aliases_edit.php,v 1.2 2008/08/26 02:05:07 jrecords Exp $
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
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
	OR CONowing conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or othWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

$pgtitle = array("Interfaces", "Trunks", "Edit trunks");
require("guiconfig.inc");

if (!is_array($config['trunks']['trunk']))
	$config['trunks']['trunk'] = array();

trunks_sort();
$a_trunks = &$config['trunks']['trunk'];

$id = $_GET['id'];

if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_trunks[$id]) {
	$pconfig['name'] = $a_trunks[$id]['name'];
	$pconfig['childiflist'] = $a_trunks[$id]['childiflist'];
	$pconfig['type'] = $a_trunks[$id]['type'];
	$pconfig['trunkport'] = $a_trunks[$id]['trunkport'];
} else{
	/* find the next availible trunk interface and use it */
	for($i=0;$i<100; $i++) {
        	foreach($a_trunks as $trunk) {
		     if($trunk['trunkport'] == 'trunk' . "$i") {
		          continue 2;
		     }
	        }	
                $pconfig['trunkport'] = 'trunk' . "$i";
                break;
        }
}

/* get list without VLAN interfaces */
$portlist = get_interface_list();

/* Find an unused port for this interface */
foreach ($portlist as $portname => $portinfo) {
	$portused = false;
        foreach ($config['interfaces'] as $ifname => $ifdata) {
        	if ($ifdata['if'] == $portname) {
                	$portused = true;
                        break;
                }
         }
}


if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation 
	$reqdfields = explode(" ", "name type children");
	$reqdfieldsn = explode(",", "Name,Type,Children");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);*/

	if (($_POST['name'] && !is_validaliasname($_POST['name']))) {
		$input_errors[] = "The trunk name may only consist of the characters a-z, A-Z, 0-9.";
	}
	
	/* check for name conflicts */
	foreach ($a_trunks as $trunk) {
		if (isset($id) && ($a_trunks[$id]) && ($a_trunks[$id] == $trunk))
			continue;

		if ($trunk['name'] == $_POST['name']) {
			$input_errors[] = "A trunk with this name already exists.";
			break;
		}
	}

	$trunk = array();
        $trunk['name'] = $_POST['name'];
	$trunk['type'] = $_POST['type'];
	$trunk['trunkport'] = $_POST['trunkport'];
        $childiflist = explode(',', $_POST['children']);
        for($i=0;$i<sizeof($childiflist); $i++) {
                $childif = 'childif'."$i";
                $prop = preg_replace("/ /", "", $childiflist[$i]);
                $trunk['childiflist'][$childif] = $prop;
        }
        if (isset($id) && $a_trunks[$id])
                $a_trunks[$id] = $trunk;
        else
                $a_trunks[] = $trunk;

	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);
        
	if (!$input_errors) {
		
		write_config();
		
		header("Location: interfaces_trunk.php");
		exit;
	}
}
?>

<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--

function addOption(selectbox,text,value)
{
var optn = document.createElement("OPTION");
   if(isDupe(selectbox,text,value) == 0) {
      document.getElementById(selectbox).options.add(optn);
      optn.text = text;
      optn.value = value;
   }
   else {
      alert("Child Interface Already Exists: " + text);
   }
}

function removeOptions(selectbox)
{
var i;
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
selectbox.remove(i);
}
}

function selectAllOptions(selectbox)
{
var i; 
for(i=selectbox.options.length-1;i>=0;i--)
{
selectbox.options[i].selected = true;
}
}

function isDupe(selectbox,text,value)
{
var i; 
if(document.getElementById(selectbox).options) {
  for(i=document.getElementById(selectbox).options.length-1;i>=0;i--)
  {
    if(document.getElementById(selectbox).options[i].text == text)
	return 1;
    }
  }
  return 0;
}

function createProp(selectbox)
{
var i;
var prop = '';
var rdrprop ='';
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
prop += selectbox.options[i].value + ', ';   
}
}
prop = prop.replace(/, $/,"");
if (selectbox.name=="CHILDREN") {
   document.iform.children.value=prop
   }
}

function prepareSubmit()
{
selectAllOptions(CHILDREN);
createProp(CHILDREN);
}

-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>

             <form action="trunks_edit.php" onSubmit="return prepareSubmit()" method="post" name="iform" id="iform">
  	     <table width="100%" border="0" cellpadding="6" cellspacing="0">
             <tr>
                  <td width="22%" valign="top" class="vncellreq">Name</td>
                  <td width="78%" class="vtable">
                    <input name="name" type="text" class="formfld" id="name" size="16" value="<?=htmlspecialchars($pconfig['name']);?>">
		   </td>
                    <input name="children" type="hidden" value="">
		    <input name="trunkport" type="hidden"value="<?=htmlspecialchars($pconfig['trunkport']);?>">	
                </tr>
             <tr>
                  <td width="22%" valign="top" class="vncellreq">Trunk Protocol</td>
                  <td width="78%" class="vtable">
                                        <select name="type" class="formfld">
                      <?php $types = explode(" ", "roundrobin failover loadbalance broadcast none"); foreach ($types as $type): ?>
                      <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['type'])) echo "selected"; ?>>
                      <?=htmlspecialchars($type);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                </tr>
	     <tr>
                <td width="22%" valign="top" class="vncellreq">Child Interfaces</td>
                <td width="78%" class="vtable">
                <SELECT style="width: 150px; height: 100px" id="CHILDREN" NAME="CHILDREN" MULTIPLE size=6 width=30>
                <?php for ($i = 0; $i<sizeof($pconfig['childiflist']); $i++): ?>
                <option value="<?=$pconfig['childiflist']["childif$i"];?>">
                <?=$pconfig['childiflist']["childif$i"];?>
                </option>
                <?php endfor; ?>
                <input type=button onClick="removeOptions(CHILDREN)"; value='Remove Selected'><br><br>
                 <strong>Interfaces</strong>
                    <select name="childifs" class="formfld" id="childifs">
<?php foreach ($portlist as $portname => $portinfo): ?>
                  <option value="<?=$portname;?>" <?php if ($portname == $iface['if']) echo "selected";?>>
                  <?php if ($portinfo['isvlan']) {
                                        $descr = "VLAN {$portinfo['tag']} on {$portinfo['if']}";
                                        if ($portinfo['descr'])
                                                $descr .= " (" . $portinfo['descr'] . ")";
                                        echo htmlspecialchars($descr);
                                  } else
                                        echo htmlspecialchars($portname);
                  ?>
                  </option>

                  <?php endforeach; ?>
                    </select>
                <input type=button onClick="addOption('CHILDREN',document.iform.childifs.value ,document.iform.childifs.value)"; value='Add'>
</table>
<table>
<tr><td>
<input name="Submit" type="submit" class="formbtn" value="Save"><br><br>
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
