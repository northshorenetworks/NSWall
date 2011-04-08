#!/bin/php
<?php 
/*
    Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

    $form = $_POST['formname'];

    switch($form) {
    case "service_dyndns":
    unset($input_errors);
    $pconfig = $_POST;

        $if = $pconfig['if'];

    /* input validation */
    $reqdfields = array();
    $reqdfieldsn = array();
    if ($_POST['enable']) {
        $reqdfields = array_merge($reqdfields, explode(" ", "host username password type"));
        $reqdfieldsn = array_merge($reqdfieldsn, explode(",", "Hostname,Username,Password,Service type"));
    }
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

    if (($_POST['host'] && !is_domain($_POST['host']))) {
        $input_errors[] = "The host name contains invalid characters.";
    }
    if (($_POST['server'] && !is_domain($_POST['server']) && !is_ipaddr($_POST['server']))) {
        $input_errors[] = "The server name contains invalid characters.";
    }
    if (($_POST['port'] && !is_port($_POST['port']))) {
        $input_errors[] = "The server port must be an integer between 1 and 65535.";
    }
    if (($_POST['mx'] && !is_domain($_POST['mx']))) {
        $input_errors[] = "The MX contains invalid characters.";
    }
    if (($_POST['username'] && !is_dyndns_username($_POST['username']))) {
        $input_errors[] = "The username contains invalid characters.";
    }

    if (!$input_errors) {
        $config['dyndns']['type'] = $_POST['type']; 
        $config['dyndns']['username'] = $_POST['username'];
        $config['dyndns']['password'] = $_POST['password'];
        $config['dyndns']['host'] = $_POST['host'];
        $config['dyndns']['server'] = $_POST['server'];
        $config['dyndns']['port'] = $_POST['port'];
        $config['dyndns']['mx'] = $_POST['mx'];
        $config['dyndns']['wildcard'] = $_POST['wildcard'] ? true : false;
        $config['dyndns']['enable'] = $_POST['enable'] ? true : false;

        write_config();

        $retval = 0;
        if (!file_exists($d_sysrebootreqd_path)) {
            /* nuke the cache file */
            config_lock();
            services_dyndns_reset();
            $retval = services_dyndns_configure();
            config_unlock();
        }
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
             case "services_bgpd":
                unset($input_errors);
                $pconfig = $_POST;
                if (!$input_errors) {   
                    $config['bgpd']['as'] = $_POST['as'];
                    $config['bgpd']['routerid'] = $_POST['routerid'];
		    $config['bgpd']['connectretry'] = $_POST['connectretry'];
		    $config['bgpd']['holdtime'] = $_POST['holdtime'];
		    $config['bgpd']['minholdtime'] = $_POST['minholdtime'];
		    $config['bgpd']['listenon'] = $_POST['listenon'];
                    $config['bgpd']['couplefib'] = $_POST['couplefib'] ? true : false;
                    $config['bgpd']['logenable'] = $_POST['logenable'] ? true : false;
		    $config['bgpd']['qualifyviabgp'] = $_POST['qualifyviabgp'] ? true : false; 
                    $config['bgpd']['routecollector'] = $_POST['routecollector'] ? true : false;
		    $config['bgpd']['transparentas'] = $_POST['transparentas'] ? true : false;
	            $srclist = array_reverse(explode(' ', $_POST['srclist']));
                    for($i=0;$i<sizeof($srclist); $i++) {
                        $member = 'route'."$i";
                        $source = preg_replace("/ /", "", $srclist[$i]);
                        $config['bgpd']['routelist'][$member] = $source;
                    }

                    write_config();

                    $retval = 0;
                    if (!file_exists($d_sysrebootreqd_path)) {
                        /* nuke the cache file */
                        config_lock();
                        services_bgpd_configure();
			config_unlock();
                    }
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
	     
	    case "services_bgpd_neighbor":
                unset($input_errors);
                $pconfig = $_POST;
	
		if (!is_array($config['bgpd']['neighbor']))
                $config['bgpd']['neighbor'] = array();

            	#services_bgp_neighbors_sort();
            	$a_neighbor = &$config['bgpd']['neighbor'];

		$id = $_GET['id'];
            	if (is_numeric($_POST['id']))
            		$id = $_POST['id'];
		
                if (!$input_errors) {
		    $neighborent = array();
		    $neighborent['name'] = $_POST['name'];
		    $neighborent['descr'] = $_POST['descr'];
	    	    if (isset($_POST['remoteas']) && $_POST['remoteas'] != '')	
                	$neighborent['remoteas'] = $_POST['remoteas'];
		    $neighborent['announce4byte'] = $_POST['announce4byte'] ? true : false;
 		    $neighborent['announcecapabilities'] = $_POST['announcecapabilities'] ? true : false;
		    $neighborent['announcerefresh'] = $_POST['announcerefresh'] ? true : false;
		    $neighborent['announcerestart'] = $_POST['announcerestart'] ? true : false;
		    if (isset($_POST['localaddress']) && $_POST['localaddress'] != '')
			$neighborent['localaddress'] = $_POST['localaddress'];
		    if (isset($_POST['holdtime']) && $_POST['holdtime'] != '')
			$neighborent['holdtime'] = $_POST['holdtime'];
		    if (isset($_POST['minholdtime']) && $_POST['minholdtime'] != '')
		        $neighborent['minholdtime'] = $_POST['minholdtime'];
		    if (isset($_POST['multihop']) && $_POST['multihop'] != '')
		    	$neighborent['multihop'] = $_POST['multihop'];
		    $neighborent['transparentas'] = $_POST['transparentas'] ? true : false;
          	    $neighborent['ttlsecurity'] = $_POST['ttlsecurity'] ? true : false;
		    $neighborent['groupname'] = $_POST['group']; 
		    if (isset($id) && $a_neighbor[$id]) {
 	               $a_neighbor[$id] = $neighborent;
        	    } else {
                	if (is_numeric($after)) {
                    	array_splice($a_neighbor, $after+1, 0, array($neighborent));
                	} else
                    	$a_neighbor[] = $neighborent;
            	    }

		    write_config();

                    $retval = 0;
                    if (!file_exists($d_sysrebootreqd_path)) {
                        /* nuke the cache file */
                        config_lock();
                        services_bgpd_configure();
			config_unlock();
                    }
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
	    case "services_bgpd_group":
                unset($input_errors);
                $pconfig = $_POST;

                if (!is_array($config['bgpd']['group']))
                $config['bgpd']['group'] = array();

                #services_bgp_groups_sort();
                $a_group = &$config['bgpd']['group'];

                $id = $_GET['id'];
                if (is_numeric($_POST['id']))
                        $id = $_POST['id'];

                if (!$input_errors) {
                    $groupent = array();
		    $groupent['name'] = $_POST['name'];
		    if (isset($_POST['descr']) && $_POST['descr'] != '')
                        $groupent['descr'] = $_POST['descr'];
                    if (isset($_POST['remoteas']) && $_POST['remoteas'] != '')
                        $groupent['remoteas'] = $_POST['remoteas'];
                    $groupent['announce4byte'] = $_POST['announce4byte'] ? true : false;
                    $groupent['announcecapabilities'] = $_POST['announcecapabilities'] ? true : false;
                    $groupent['announcerefresh'] = $_POST['announcerefresh'] ? true : false;
                    $groupent['announcerestart'] = $_POST['announcerestart'] ? true : false;
                    if (isset($_POST['localaddress']) && $_POST['localaddress'] != '')
                        $groupent['localaddress'] = $_POST['localaddress'];
                    if (isset($_POST['holdtime']) && $_POST['holdtime'] != '')
                        $groupent['holdtime'] = $_POST['holdtime'];
                    if (isset($_POST['minholdtime']) && $_POST['minholdtime'] != '')
                        $groupent['minholdtime'] = $_POST['minholdtime'];
                    if (isset($_POST['multihop']) && $_POST['multihop'] != '')
                        $groupent['multihop'] = $_POST['multihop'];
                    $groupent['transparentas'] = $_POST['transparentas'] ? true : false;
                    $groupent['ttlsecurity'] = $_POST['ttlsecurity'] ? true : false;

                    if (isset($id) && $a_group[$id]) {
                       $a_group[$id] = $groupent;
                    } else {
                        if (is_numeric($after)) {
                        array_splice($a_group, $after+1, 0, array($groupent));
                        } else
                        $a_group[] = $groupent;
                    }

                    write_config();

                    $retval = 0;
                    if (!file_exists($d_sysrebootreqd_path)) {
                        /* nuke the cache file */
                        config_lock();
                        services_bgpd_configure();
			config_unlock();
                    }
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
            case "services_bgpd_rule":
            unset($input_errors);
                
            if (!is_array($config['bgpd']['filter']['rule']))
                $config['bgpd']['filter']['rule'] = array();

	    bgp_filter_rules_sort();
            $a_filter = &$config['bgpd']['filter']['rule'];

            $id = $_GET['id'];
            if (is_numeric($_POST['id']))
                $id = $_POST['id'];

            $after = $_GET['after'];

            if (isset($_POST['after']))
                $after = $_POST['after'];

            if (isset($_GET['dup'])) {
                $id = $_GET['dup'];
                $after = $_GET['dup'];
            }

            $pconfig = $_POST;
 
            /* input validation */
            $reqdfields = explode(" ", "type direction srclist");
            $reqdfieldsn = explode(",", "Type,direction,Srclist");
 
            do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
 
            $filterent = array();
            $filterent['name'] = $_POST['name'];
            $filterent['descr'] = $_POST['descr'];
            $filterent['type'] = $_POST['type'];
            $filterent['direction'] = $_POST['direction'];
            if ($_POST['rdrlist']) {
                $filterent['rdrlist'] = $_POST['rdrlist'];
            }
            $srclist = array_reverse(explode(',', $_POST['srclist']));
            for($i=0;$i<sizeof($srclist); $i++) {
		$source = preg_replace("/ /", " ", $srclist[$i]);
                $member = 'src'."$i";
                $filterent['srclist'][$member] = $source;
            }
	    $filterent['prefix'] = $_POST['prefix'];
	    $filterent['prefixlen'] = $_POST['prefixlen'];
	    $filterent['prefixlenop'] = $_POST['prefixlenop'];

            if (isset($id) && $a_filter[$id]) {
                $a_filter[$id] = $filterent;
            } else {
                if (is_numeric($after)) {
                    array_splice($a_filter, $after+1, 0, array($filterent));
                } else  
                    $a_filter[] = $filterent;
            }
  
            if (!$input_errors) {
                write_config();
                config_lock();
		services_bgpd_configure();
                config_unlock();
                push_config('bgprules');
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
	    case "service_bgp_filter_options":
                unset($input_errors);
                $pconfig = $_POST;
                if (!$input_errors) {
                    $config['bgpd']['filter']['options']['defaultroute'] = $_POST['defaultroute'] ? true : false;
                    $config['bgpd']['filter']['options']['rfc5735'] = $_POST['rfc5735'] ? true : false;
                    $config['bgpd']['filter']['options']['prefixfilter'] = $_POST['prefixfilter'] ? true : false;

                    write_config();

                    $retval = 0;
                    if (!file_exists($d_sysrebootreqd_path)) {
                        /* nuke the cache file */
                        config_lock();
                        services_bgpd_configure();
			config_unlock();
                    }
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

            case "service_dnsforwarder":
                unset($input_errors);
                $pconfig = $_POST;
                if (!$input_errors) {   
                    $config['dnsmasq']['enable'] = $_POST['enable'] ? true : false;

                    write_config();

                    $retval = 0;
                    if (!file_exists($d_sysrebootreqd_path)) {
                        /* nuke the cache file */
                        config_lock();
                        $retval = services_dnsmasq_configure();
                        config_unlock();
                    }
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
            case "services_dhcpd":
                unset($input_errors);
                $pconfig = $_POST;
               
                $if = strtolower($pconfig['if']);

                /* input validation */
                if ($_POST['enable']) {
                    $reqdfields = explode(" ", "range_from range_to");
                    $reqdfieldsn = explode(",", "range begin,range end");

                    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

                    if (($_POST['gateway'] && !is_ipaddr($_POST['gateway']))) {
                                    $input_errors[] = "a valid gateway must be specified.";
                            }
                    if (($_POST['range_from'] && !is_ipaddr($_POST['range_from']))) {
                        $input_errors[] = "a valid range must be specified.";
                    }
                    if (($_POST['range_to'] && !is_ipaddr($_POST['range_to']))) {
                        $input_errors[] = "a valid range must be specified.";
                    }
                    if (($_POST['wins1'] && !is_ipaddr($_POST['wins1'])) || ($_POST['wins2'] && !is_ipaddr($_POST['wins2']))) {
                        $input_errors[] = "a valid ip address must be specified for the primary/secondary wins server.";
                    }
                    if (($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($$_POST['dns2'] && !is_ipaddr($_POST['dns2']))) {
                                    $input_errors[] = "a valid ip address must be specified for the primary/secondary dns server.";
                            }
                    if ($_POST['deftime'] && (!is_numericint($_POST['deftime']))) {
                        $input_errors[] = "the default lease time must be an integer.";
                    }
                    if ($_POST['maxtime'] && (!is_numericint($_POST['maxtime']) || ($_POST['maxtime'] <= $_POST['deftime']))) {
                        $input_errors[] = "the maximum lease time must be higher than the default lease time.";
                    }

                    if (!$input_errors) {
                        /* make sure the range lies within the current subnet */
                        $subnet_start = (ip2long($ifcfg['ipaddr']) & gen_subnet_mask_long($ifcfg['subnet']));
                        $subnet_end = (ip2long($ifcfg['ipaddr']) | (~gen_subnet_mask_long($ifcfg['subnet'])));

                        /*if ((ip2long($_POST['range_from']) < $subnet_start) || (ip2long($_POST['range_from']) > $subnet_end) ||
                            (ip2long($_POST['range_to']) < $subnet_start) || (ip2long($_POST['range_to']) > $subnet_end)) {
                            $input_errors[] = "the specified range lies outside of the current subnet.";    
                        }*/

                        if (ip2long($_POST['range_from']) > ip2long($_POST['range_to']))
                            $input_errors[] = "the range is invalid (first element higher than second element).";

                        /* make sure that the dhcp relay isn't enabled on this interface */
                        if (isset($config['dhcrelay'][$if]['enable']))
                            $input_errors[] = "you must disable the dhcp relay on the {$iflist[$if]} interface before enabling the dhcp server.";
                    }
                }

                if (!$input_errors) {
                    unset($config['dhcpd'][$if]);
                    $config['dhcpd'][$if]['gateway'] = $_POST['gateway'];
                    $config['dhcpd'][$if]['range']['from'] = $_POST['range_from'];
                    $config['dhcpd'][$if]['range']['to'] = $_POST['range_to'];
                    $config['dhcpd'][$if]['defaultleasetime'] = $_POST['deftime'];
                    $config['dhcpd'][$if]['maxleasetime'] = $_POST['maxtime'];
                    $config['dhcpd'][$if]['enable'] = $_POST['enable'] ? true : false;
                    $config['dhcpd'][$if]['denyunknown'] = $_POST['denyunknown'] ? true : false;

                    unset($config['dhcpd'][$if]['winsserver']);
                    if ($_POST['wins1'])
                        $config['dhcpd'][$if]['winsserver'][] = $_POST['wins1'];
                    if ($_POST['wins2'])
                        $config['dhcpd'][$if]['winsserver'][] = $_POST['wins2'];

                    unset($config['dhcpd'][$if]['dnsserver']);
                            if ($_POST['dns1'])
                                    $config['dhcpd'][$if]['dnsserver'][] = $_POST['dns1'];
                            if ($_POST['dns2'])
                                    $config['dhcpd'][$if]['dnserver'][] = $_POST['dns2'];       
                } 

                $retval = 0;
                if (!file_exists($d_sysrebootreqd_path)) {
                    write_config();
                    /* nuke the cache file */
                    config_lock();
                    $retval = services_dhcpd_configure();
                    config_unlock();
                }
                $savemsg = get_std_save_message($retval);
                if ($retval == 0 && !$input_errors) {
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
            default;
                  echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="$(\'#save_config\').dialog(\'close\')"></center>';
            return 0;
    }
}

if ($_GET) {
     $id = $_GET['id'];
     $action = $_GET['action'];
     $type = $_GET['type'];

     if ($type == 'neighbor') {
          if (!is_array($config['bgpd']['neighbor']))
               $config['bgpd']['neighbor'] = array();

          bgpneighbors_sort();
          $a_neighbor = &$config['bgpd']['neighbor'];
          if ($action == 'delete') {
               unset($a_neighbor[$id]);
               write_config();            
          }
     }
     if ($type == 'group') {
          if (!is_array($config['bgpd']['group']))
               $config['bgpd']['group'] = array();

          bgpgroups_sort();
          $a_group = &$config['bgpd']['group'];
          if ($action == 'delete') {
               unset($a_group[$id]);
               write_config();
          }
     }
     if ($type == 'filter') {
          if (!is_array($config['bgpd']['filter']['rule']))
               $config['bgpd']['filter']['rule'] = array();

          bgp_filter_rules_sort();
          $a_filter = &$config['bgpd']['filter']['rule'];
          if ($action == 'delete') {
               unset($a_filter[$id]);
               write_config();
          }
     }
}

?>
