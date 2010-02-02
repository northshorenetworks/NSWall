#!/bin/php
<?php 
/*
	Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		case "system_advanced":
			unset($input_errors);

			/* input validation */
			if (!$input_errors) {
				$config['system']['disableconsolemenu'] = $_POST['disableconsolemenu'] ? true : false;
				$config['system']['disablefirmwarecheck'] = $_POST['disablefirmwarecheck'] ? true : false;
				$config['system']['webgui']['expanddiags'] = $_POST['expanddiags'] ? true : false;
				$config['system']['webgui']['noantilockout'] = $_POST['noantilockout'] ? true : false;
				$config['filter']['bypassstaticroutes'] = $_POST['bypassstaticroutes'] ? true : false;
				write_config();
	 			push_config('networking');	
			}
		
			$retval = 0;
			if (!file_exists($d_sysrebootreqd_path)) {
				config_lock();
				$retval = filter_configure();
				$retval |= system_polling_configure();
				$retval |= system_set_termcap();
				config_unlock();
			}
			$savemsg = get_std_save_message($retval);
			if ($retval == 0) {
				sleep(2);
				echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
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
		case "system_general":
			unset($input_errors);

			/* input validation */
			$reqdfields = split(" ", "hostname domain username");
			$reqdfieldsn = split(",", "Hostname,Domain,Username");
	
			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
			if ($_POST['hostname'] && !is_hostname($_POST['hostname']))
				$input_errors[] = "The hostname may only contain the characters a-z, 0-9 and '-'.";
			if ($_POST['domain'] && !is_domain($_POST['domain']))
				$input_errors[] = "The domain may only contain the characters a-z, 0-9, '-' and '.'.";
			if (($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr($_POST['dns2'])) || ($_POST['dns3'] && !is_ipaddr($_POST['dns3'])))
				$input_errors[] = "A valid IP address must be specified for the primary/secondary/tertiary DNS server.";
			if ($_POST['username'] && !preg_match("/^[a-zA-Z0-9]*$/", $_POST['username']))
				$input_errors[] = "The username may only contain the characters a-z, A-Z and 0-9.";
			if ($_POST['webguiport'] && (!is_numericint($_POST['webguiport']) || 
				($_POST['webguiport'] < 1) || ($_POST['webguiport'] > 65535)))
				$input_errors[] = "A valid TCP/IP port must be specified for the webGUI port.";
			if (($_POST['password']) && ($_POST['password'] != $_POST['password2']))
				$input_errors[] = "The passwords do not match.";
	
			$t = (int)$_POST['timeupdateinterval'];
			if (($t < 0) || (($t > 0) && ($t < 6)) || ($t > 1440))
				$input_errors[] = "The time update interval must be either 0 (disabled) or between 6 and 1440.";
			foreach (explode(' ', $_POST['timeservers']) as $ts) {
				if (!is_domain($ts))
					$input_errors[] = "A NTP Time Server name may only contain the characters a-z, 0-9, '-' and '.'.";
			}

			if (!$input_errors) {
				$config['system']['hostname'] = strtolower($_POST['hostname']);
				$config['system']['general']['domain'] = strtolower($_POST['domain']);
				$oldwebguiproto = $config['system']['general']['webgui']['protocol'];
				$config['system']['username'] = $_POST['username'];
				$config['system']['general']['webgui']['protocol'] = $_POST['webguiproto'];
				$config['system']['general']['webgui']['certificate'] = $_POST['cert'];
				$oldwebguiport = $config['system']['general']['webgui']['port'];
				$config['system']['general']['webgui']['port'] = $_POST['webguiport'];
				$config['system']['general']['timezone'] = $_POST['timezone'];
				$config['system']['general']['timeservers'] = strtolower($_POST['timeservers']);
				$config['system']['general']['time-update-interval'] = $_POST['timeupdateinterval'];
		
				unset($config['system']['general']['dnsserver']);
				if ($_POST['dns1'])
					$config['system']['general']['dnsserver'][] = $_POST['dns1'];
				if ($_POST['dns2'])
					$config['system']['general']['dnsserver'][] = $_POST['dns2'];
				if ($_POST['dns3'])
					$config['system']['general']['dnsserver'][] = $_POST['dns3'];
		
				$olddnsallowoverride = $config['system']['general']['dnsallowoverride'];
				$config['system']['general']['dnsallowoverride'] = $_POST['dnsallowoverride'] ? true : false;
				$oldsshd = isset($config['system']['general']['sshd']['enabled']);	
				$config['system']['general']['sshd']['enabled'] = $_POST['sshdenabled'] ? true : false;
				$config['system']['general']['symon']['enabled'] = $_POST['symonenabled'] ? true : false;
                		if ($_POST['muxip'])
					$config['system']['general']['symon']['muxip'] = $_POST['muxip'];
				if ($_POST['password'])
					$config['system']['password'] = base64_encode($_POST['password']);
		
				write_config();
		
				if (($oldwebguiproto != $config['system']['general']['webgui']['protocol']) ||
					($oldwebguiport != $config['system']['general']['webgui']['port']) ||
					($oldsshd != isset($config['system']['general']['sshd']['enabled'])))
					touch($d_sysrebootreqd_path);
		
				$retval = 0;
		
				if (!file_exists($d_sysrebootreqd_path)) {
					config_lock();
					$retval = system_hostname_configure();
					$retval |= system_hosts_generate();
					$retval |= system_resolvconf_generate();
					$retval |= system_password_configure();
					$retval |= services_dnsmasq_configure();
					$retval |= system_timezone_configure();
 					$retval |= system_ntp_configure();
 			
		 			if ($olddnsallowoverride != $config['system']['general']['dnsallowoverride'])
 						$retval |= interfaces_wan_configure();
 			
					config_unlock();
				}
				if ($retval == 0) {
                                	sleep(2);
                         	       echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
                        	}
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
		case "system_routes":
        		unset($input_errors);	
			if ($_POST['apply']) {
                		$retval = 0;
                		if (!file_exists($d_sysrebootreqd_path)) {
                        		$retval = system_routing_configure();
                        		$retval |= filter_configure();
                        		push_config('staticroutes');
                		}
                		$savemsg = get_std_save_message($retval);
                		if ($retval == 0) {
                        		if (file_exists($d_staticroutesdirty_path)) {
                                		config_lock();
                                		unlink($d_staticroutesdirty_path);
                                		config_unlock();
                        		}
                		}
        		}
			if ($retval == 0) {
                                sleep(2);
                                echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
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
		case "system_networking":
			unset($input_errors);

 			/* input validation */
  			if (!$input_errors) {
    				$config['system']['networking']['maxinputque'] = $_POST['maxinputque'];
    				$config['system']['networking']['maxicmperror'] = $_POST['maxicmperror'];
    				$config['system']['networking']['ackonpush'] = $_POST['ackonpush'] ? true : false;
    				$config['system']['networking']['ecn'] = $_POST['ecn'] ? true : false;
    				$config['system']['networking']['tcpscaling'] = $_POST['tcpscaling'] ? true : false;
    				$config['system']['networking']['tcprcv'] = $_POST['tcprcv'];
                	$config['system']['networking']['tcpsnd'] = $_POST['tcpsnd'];
    				$config['system']['networking']['sack'] = $_POST['sack'] ? true : false;
    				$config['system']['networking']['udprcv'] = $_POST['udprcv'];
    				$config['system']['networking']['udpsnd'] = $_POST['udpsnd'];

 	   				write_config();
     				push_config('networking');
    			}

    			$retval = 0;
    			if (!file_exists($d_sysrebootreqd_path)) {
      				config_lock();
      				$retval |= system_advancednetwork_configure();
      				config_unlock();
    			}
    			$savemsg = get_std_save_message($retval);
			if ($retval == 0) {
                   	sleep(2);
            	    echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
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
		case "system_submit_support_ticket":
                        /* Gather debug Data */
$debug_data = array();
$no_debug_data = array();

/* Execute a command */
 
function doCmdT($title, $command, $isstr) {
global $debug_data;

if ($command == "dumpconfigxml") {
    $fd = @fopen("/conf/config.xml", "r");
    if ($fd) {
        while (!feof($fd)) {
            $line = fgets($fd);
            /* remove password tag contents */
            $line = preg_replace("/<password>.*?<\\/password>/", "<password>xxxxx</password>", $line);
            $line = preg_replace("/<pre-shared-key>.*?<\\/pre-shared-key>/", "<pre-shared-key>xxxxx</pre-shared-key>", $line);
            $line = str_replace("\t", " ", $line);
            $output .= htmlspecialchars($line,ENT_NOQUOTES);
        }
    }
    fclose($fd);
} else {
    exec ($command . " 2>&1", $execOutput, $execStatus);
    for ($i = 0; isset($execOutput[$i]); $i++) {
        if ($i > 0) {
            $output .= "\n";
        }
        $output .= htmlspecialchars($execOutput[$i],ENT_NOQUOTES);
    }
}
$debug_data["$title"] = base64_encode($output);
}
 
/* Execute a command, giving it a title which is the same as the command. */
function doCmd($command) {
    doCmdT($command,$command);
}
 
/* Define a command, with a title, to be executed later. */
function defCmdT($title, $command) {
    global $commands;
    $title = htmlspecialchars($title,ENT_NOQUOTES);
    $commands[] = array($title, $command, false);
}
 
/* Define a command, with a title which is the same as the command,
* to be executed later.
*/
function defCmd($command) {
    defCmdT($command,$command);
}
 
/* Execute all of the commands which were defined by a call to defCmd. */
function execCmds() {
    global $commands;
    for ($i = 0; isset($commands[$i]); $i++ ) {
        doCmdT($commands[$i][0], $commands[$i][1], $commands[$i][2]);
    }
}

/* Set up all of the commands we want to execute. */
defCmdT("ifconfig","/sbin/ifconfig -a");
defCmdT("routes","netstat -nr -f inet");
defCmdT("pfctlall", "/sbin/pfctl -vvs all");
defCmdT("resolvconf","cat /etc/resolv.conf");
defCmdT("processes","ps xauww");
defCmdT("df","/bin/df");
defCmdT("logbuffer","/usr/sbin/syslogc all");
defCmdT("confdir", "ls /conf");
defCmdT("varrundir", "ls /var/run");
defCmdT("configxml","dumpconfigxml");
 
execCmds();

/* Gather php generated output */
$pfrules = filter_tables_generate();
$pfrules .= filter_aliasrules_generate();
$pfrules .= filter_options_generate();
$pfrules .= filter_normalization_generate();
$pfrules .= altq_conf_generate();
$pfrules .= filter_nat_rules_generate();
$pfrules .= filter_rdrrules_generate();
$pfrules .= filter_rules_generate();
$debug_data['pfconf'] = base64_encode($pfrules);
$debug_data['version'] = base64_encode(file_get_contents('/etc/version'));
$debug_data['platform'] = base64_encode($g['fullplatform']);
$no_debug_data['version'] = base64_encode(file_get_contents('/etc/version'));
$no_debug_data['platform'] = base64_encode($g['fullplatform']);

if(isset($_POST['debuginfo'])) {
    $postdata = $debug_data;
} else {
    $postdata = $no_debug_data;
}
                    /* add id and notes to formdata */
                        $postdata['caseid']     = $_POST['caseid'];
                        $postdata['notes']      = base64_encode($_POST['notes']);

                        $authdata['username'] = $_POST['username'];
                        $authdata['password'] = $_POST['password'];
                        
                        $url    = 'http://www.northshoresoftware.com/authenticate_user.php';
			$retval = http_request( '', '', $url, $authdata, 'POST' );
        	
                        if (preg_match('/\d{8}/',$retval)) {
                            $postdata['customerid'] = $retval;
			    $url    = 'http://www.northshoresoftware.com/submit_support_ticket.php';
			    $retval = http_request( '', '', $url, $postdata, 'POST' );
                            echo "$retval";
			    echo '<script type="text/javascript">
                                      $("#okbtn").click(function () {
                                          $("#support").dialog("close");
                                      });
                                  </script>';
                            echo '<center><INPUT TYPE="button" value="OK" id="okbtn"></center>';
                       } else {
                            echo $retval;
                       }
                       return $retval;
			
		case "system_users":

			if (isset($_POST['id']))
	   			$id = $_POST['id'];
       
    			if (!is_array($config['system']['accounts']['user']))
    				$config['system']['accounts']['user'] = array();
    			
			admin_users_sort();
 			
			$a_user = &$config['system']['accounts']['user'];

        		unset($input_errors);

		        /* input validation */
       			if (isset($id) && ($a_user[$id])) {
                		$reqdfields = explode(" ", "username");
                		$reqdfieldsn = explode(",", "Username");
        		} else {
                		$reqdfields = explode(" ", "username password");
                		$reqdfieldsn = explode(",", "Username,Password");
        		}

        		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

 		       	if (preg_match("/[^a-zA-Z0-9\.\-_]/", $_POST['username']))
                       		$input_errors[] = "The username contains invalid characters.";

      	                if($_POST['username']==$config['system']['username'])
        	                $input_errors[] = "username can not match the administrator username!";

        		if (($_POST['password']) && ($_POST['password'] != $_POST['password2']))
                		$input_errors[] = "The passwords do not match.";

 		       if (!$input_errors && !(isset($id) && $a_user[$id])) {
                		/* make sure there are no dupes */
                		foreach ($a_user as $userent) {
                        		if ($userent['name'] == $_POST['username']) {
                                		$input_errors[] = "Another entry with the same username already exists.";
                                		break;
                        		}
                		}
        		}

 		        if (!$input_errors) {

 		                if (isset($id) && $a_user[$id])
        	 	                $userent = $a_user[$id];

 	                        $userent['name'] = $_POST['username'];
        	        		$userent['fullname'] = $_POST['fullname'];

 		               	if ($_POST['password'])
 		                       	$userent['password'] = base64_encode($_POST['password']);

 		               	if (isset($id) && $a_user[$id])
                	        	$a_user[$id] = $userent;
                		else
                        		$a_user[] = $userent;

 		               	write_config();
                		push_config('accounts');
				$retval = 0;
                        	$retval = system_password_configure();
                        	$savemsg = get_std_save_message($retval);

			}
			if ($retval == 0) {
                                sleep(2);
                                echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
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
		case "system_groups":
			unset($input_errors);

		        /* input validation */
 		        $reqdfields = explode(" ", "groupname");
   		        $reqdfieldsn = explode(",", "Group Name");

     		        do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

 	                if (preg_match("/[^a-zA-Z0-9\.\-_ ]/", $_POST['groupname']))
                       		$input_errors[] = "The group name contains invalid characters.";

 		        if (!$input_errors && !(isset($id) && $a_group[$id])) {
                		/* make sure there are no dupes */
               		 	foreach ($a_group as $group) {
                        		if ($group['name'] == $_POST['groupname']) {
                                		$input_errors[] = "Another entry with the same group name already exists.";
                                		break;
                	        	}
                		}
        		}

  		        if (!$input_errors) {

 	                	if (isset($id) && $a_group[$id])
                        		$group = $a_group[$id];

 		               	$group['name'] = $_POST['groupname'];
                		$group['description'] = $_POST['description'];
                		unset($group['pages']);
                		foreach ($pages as $fname => $title) {
                        		$identifier = str_replace('.php','',$fname);
                        		if ($_POST[$identifier] == 'yes')
                                		$group['pages'][] = $fname;
                        	}
 	              	}

 	               if (isset($id) && $a_group[$id])
        	                $a_group[$id] = $group;
                	else
                        	$a_group[] = $group;

 	               write_config();
        	       push_config('accounts');
			if ($retval == 0) {
                                sleep(2);
                                echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
			} else {
					print_input_errors($input_errors);
                                        echo '<script type="text/javascript">
                                        $("#okbtn").click(function () {	
                                            $("#save_config").dialog("close");
                                        });
                                        </script>';
					echo '<INPUT TYPE="button" value="OK" id="okbtn"></center>';
			}
			return 0; 
			case "system_reboot":
                    $reboot = $_POST['reboot'];
			if($reboot == 'yes') {
			echo '<script type="text/javascript">				
								setTimeout(function(){ $("#save_config").dialog("close"); }, 2000);
                                setTimeout(function(){ $("#reboot_nswall").dialog("open"); }, 2250);
                                setTimeout(function(){ $("#reboot_nswall").dialog("close"); window.location = "/login.htm"; }, 75000);
                        </script>';	
                                echo "<center>The device will now reboot!</center>";
				system_reboot();						
			}
			return 0;	
			case "system_status":
            	$config['system']['notes'] = base64_encode($_POST['notes']);
				write_config();
				sleep(2);
                echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
			return 0;	
			default;
 				echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK"></center>';
				 // When a user clicks on the submit button, post the form.
				echo '<script type="text/javascript">
					$(".buttonrow").click(function () {
               			setTimeout(function(){ $("#save_config").fadeOut("slow"); }, 1000);
      				});
				</script>';
			return 0;
	}
}
if ($_GET) {
     $id = $_GET['id'];
     $action = $_GET['action'];
     $type = $_GET['type'];

     if ($type == 'routes') {
	 if (!is_array($config['staticroutes']['route']))  
     	 $config['staticroutes']['route'] = array();  
        
	 staticroutes_sort();  
	 $a_routes = &$config['staticroutes']['route'];  
		  if ($action == 'delete') {
               unset($a_routes[$id]);
               write_config();
          }
     }
}
?>
