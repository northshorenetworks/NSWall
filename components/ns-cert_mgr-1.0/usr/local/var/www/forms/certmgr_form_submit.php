#!/bin/php
<?php 
/*
    Northshore Software Header
*/

define("OPEN_SSL_CONF_PATH", "/etc/ssl/openssl.cnf");

require("guiconfig.inc");

if ($_POST) {

    $form = $_POST['formname'];

    switch($form) {
            case "system_camgr":

            $id = $_GET['id'];  
            if (isset($_POST['id']))  
                $id = $_POST['id']; 

            if (!is_array($config['system']['certmgr']['ca']))
                $config['system']['certmgr']['ca'] = array();

            $a_ca =& $config['system']['certmgr']['ca'];

            system_ca_sort();
 
            unset($input_errors);
        		$pconfig = $_POST;

        		/* input validation */

				unset($input_errors);
				$pconfig = $_POST;

				/* input validation */
				if ($pconfig['method'] == "existing") {
					 $reqdfields = explode(" ", "name cert");
					 $reqdfieldsn = explode(",", "Desriptive name,Certificate data");
				}
				if ($pconfig['method'] == "internal") {
					 $reqdfields = explode(" ",
								"name keylen lifetime dn_country dn_state dn_city ".
								"dn_organization dn_email dn_commonname");
					 $reqdfieldsn = explode(",",
								"Desriptive name,Key length,Lifetime,".
								"Distinguished name Country Code,".
								"Distinguished name State or Province,".
								"Distinguished name City,".
								"Distinguished name Organization,".
								"Distinguished name Email Address,".
								"Distinguished name Common Name");
				}

				do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

				/* save modifications */
				if (!$input_errors) {

					 $ca = array();
					 $ca['refid'] = uniqid('');
					 if (isset($id) && $a_ca[$id])
						  $ca = $a_ca[$id];

					 $ca['name'] = $pconfig['name'];

					 if ($pconfig['method'] == "existing")
						  ca_import($ca, $pconfig['cert']);

					 if ($pconfig['method'] == "internal")
					 {
						  $dn = array(
								'countryName' => $pconfig['dn_country'],
								'stateOrProvinceName' => $pconfig['dn_state'],
								'localityName' => $pconfig['dn_city'],
								'organizationName' => $pconfig['dn_organization'],
								'emailAddress' => $pconfig['dn_email'],
								'commonName' => $pconfig['dn_commonname']);

						  ca_create(& $ca, $pconfig['keylen'], $pconfig['lifetime'], $dn);
					 }
					 
					 if (isset($id) && $a_ca[$id])
						  $a_ca[$id] = $ca;
					 else
						  $a_ca[] = $ca;

					 write_config();
				} else {
					 print_input_errors($input_errors);
					 echo '<script type="text/javascript">
								 $("#okbtn").click(function () {
									  $("#save_config").dialog("close");
								 });
							 </script>';
					 echo '<INPUT TYPE="button" value="OK" id="okbtn"></center>';
				}
				return $retval;
                case "system_ca_delete":
                        $id = $_POST['id'];
                        if (!is_array($config['system']['certmgr']['ca']))
                        $config['system']['certmgr']['ca'] = array();

                        $a_ca = &$config['system']['certmgr']['ca'];

                        if ($retval == 0 && !$input_errors) {
                                unset($a_ca[$id]);
                                write_config();
                                sleep(2);
                                echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
                        } else {
                                print_input_errors($input_errors);
                                echo '<script type="text/javascript">
                        $("#okbtn").click(function () {
                            $("#save_config").dialog("close");
                        });
                        </script>';
                                echo '<center><INPUT TYPE="button" value="OK" id="okbtn"></center>';
                        }
                        return $retval;
		case "system_cert_delete":
                        $id = $_POST['id'];
                        if (!is_array($config['system']['certmgr']['cert']))
                        $config['system']['certmgr']['cert'] = array();

                        $a_cert = &$config['system']['certmgr']['cert'];

                        // go through all the firewall rules and make sure none use this if
                        if ($config['system']['general']['webgui']['certificate'] == $a_cert[$id]['name']) {
                                $input_errors[] = "This certificate is in use by the WebUI.";
                                $input_errors[] = "You must change the WebUI certificate before deleting this certificate";
                        }

                        if ($retval == 0 && !$input_errors) {
                                unset($a_cert[$id]);
                                write_config();
                                sleep(2);
                                echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
                        } else {
                                print_input_errors($input_errors);
                                echo '<script type="text/javascript">
                        $("#okbtn").click(function () {
                            $("#save_config").dialog("close");
                        });
                        </script>';
                                echo '<center><INPUT TYPE="button" value="OK" id="okbtn"></center>';
                        }
                        return $retval;
			default:
			  echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="$(\'#save_config\').dialog(\'close\')"></center>';
			  return 0;
			}
	}	
?>
