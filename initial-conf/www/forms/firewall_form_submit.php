#!/bin/php
<?php 
/*
	Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		case "firewall_rule":
			unset($input_errors);
                
            if (!is_array($config['filter']['rule']))
            	$config['filter']['rule'] = array();

            filter_rules_sort();
            $a_filter = &$config['filter']['rule'];

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
			$reqdfields = explode(" ", "type interface srclist dstlist");
			$reqdfieldsn = explode(",", "Type,Interface,Srclist,Dstlist");
 
			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
 
			$filterent = array();
			$filterent['name'] = $_POST['name'];
			$filterent['descr'] = $_POST['descr'];
  			$filterent['type'] = $_POST['type'];
			$filterent['interface'] = $_POST['interface'];
			if ($_POST['rdrlist']) {
				$filterent['rdrlist'] = $_POST['rdrlist'];
			}
			$srclist = array_reverse(explode(' ', $_POST['srclist']));
			for($i=0;$i<sizeof($srclist); $i++) {
				$member = 'src'."$i";
				$source = preg_replace("/ /", "", $srclist[$i]);
				$filterent['srclist'][$member] = $source;
			}
			$dstlist = array_reverse(explode(' ', $_POST['dstlist']));
            for($i=0;$i<sizeof($dstlist); $i++) {
            	$member = 'dst'."$i";
            	$dest = preg_replace("/ /", "", $dstlist[$i]);
            	$filterent['dstlist'][$member] = $dest;
            }

			if ($_POST['tcpports']) {
                $tcplist = array_reverse(explode(' ', $_POST['tcpports']));
				for($i=0;$i<sizeof($tcplist); $i++) {
                	$member = 'tcp'."$i";
                	$tcp = preg_replace("/ /", "", $tcplist[$i]);
                	$filterent['tcplist'][$member] = $tcp;
                }
			}
			if ($_POST['udpports']) {
            	$udplist = array_reverse(explode(' ', $_POST['udpports']));
				for($i=0;$i<sizeof($udplist); $i++) {
            	    $member = 'udp'."$i";
                	$udp = preg_replace("/ /", "", $udplist[$i]);
                	$filterent['udplist'][$member] = $udp;
                }
            }
            if ($_POST['ipprotos']) {
            	$ipprotolist = array_reverse(explode(' ', $_POST['ipprotos']));
				for($i=0;$i<sizeof($ipprotolist); $i++) {
                	$member = 'ip'."$i";
                	$ip = preg_replace("/ /", "", $ipprotolist[$i]);
                	$filterent['ipprotolist'][$member] = $ip;
                }
            }
			$filterent['disabled'] = $_POST['disabled'] ? true : false;
			if ($_POST['portforward']) {
				$filterent['portforward'] = $_POST['portforward'] ? true : false;
				$filterent['dstrelay'] = $_POST['dstrelay'];
			}
			$filterent['log'] = $_POST['log'] ? true : false;
			
			/* options stuff */
			if ($_POST['multiwan'])
				$filterent['options']['multiwan'] = $_POST['multiwan'];
            if ($_POST['altqbucket'])
            	$filterent['options']['altqbucket'] = $_POST['altqbucket'];
            if ($_POST['altqlowdelay'])
            	$filterent['options']['altqlowdelay'] = $_POST['altqlowdelay'] ? true : false;
            if ($_POST['state'])
            	$filterent['options']['state'] = $_POST['state'];
            if ($_POST['maxstates'])
            	$filterent['options']['maxstates'] = $_POST['maxstates'];
            if ($_POST['srctrack'])
            	$filterent['options']['srctrack'] = $_POST['srctrack'];
            if ($_POST['maxsrcnodes'])
            	$filterent['options']['maxsrcnodes'] = $_POST['maxsrcnodes'];
            if ($_POST['maxsrcstates'])
            	$filterent['options']['maxsrcstates'] = $_POST['maxsrcstates'];
            if ($_POST['maxsrcconns'])
            	$filterent['options']['maxsrcconns'] = $_POST['maxsrcconns'];
            if ($_POST['maxsrcconrateconns'])
            	$filterent['options']['maxsrcconrateconns'] = $_POST['maxsrcconrateconns'];
            if ($_POST['maxsrcconrateseconds'])
				$filterent['options']['maxsrcconrateseconds'] = $_POST['maxsrcconrateseconds'];
			if ($_POST['overload'])
            	$filterent['options']['overload'] = $_POST['overload'] ? true : false;
            if ($_POST['flush'])
            	$filterent['options']['flush'] = $_POST['flush'] ? true : false;
            if (isset($id) && $a_filter[$id]) {
            	$a_filter[$id] = $filterent;
            } else {
            	if (is_numeric($after)) {
            		array_splice($a_filter, $after+1, 0, array($filterent));
            	} else  
            		$a_filter[] = $filterent;
            }
 
			$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);
 
        	if (filter_parse_config($config)) {
                $input_errors[] = "Could not parse the generated config file";
                $input_errors[] = "See log file for details";
                $input_errors[] = "XML Config file not modified";
        	}
 
            if (!$input_errors) {
                write_config();
				config_lock();
                $retval = filter_configure();
                config_unlock();
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
		case "firewall_altq":
			unset($input_errors);
        	$config['altq']['enable'] = $_POST['enable'] ? true : false;
        	$config['altq']['bandwidth'] = $_POST['bandwidth'];

        	$retval = 0;
            	write_config();
				config_lock();
            	$retval = filter_configure();
            	config_unlock();
            	push_config('altq');
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
		case "firewall_rule_delete":
			if (!is_array($config['filter']['rule']))
				$config['filter']['rule'] = array();

			filter_rules_sort();
			$a_filter = &$config['filter']['rule'];
 
			if ($_POST['if'])
				$if = $_POST['if'];	
			
			if (isset($_POST['del_x'])) {
				/* delete selected rules */
				if (is_array($_POST['rule']) && count($_POST['rule'])) {
					foreach ($_POST['rule'] as $rulei) {
                    	unset($a_filter[$rulei]);
                    }
				}
			}
			$retval = 0;
            if (!file_exists($d_sysrebootreqd_path)) {
                write_config();
                config_lock();
                $retval = filter_configure();
                config_unlock();
                push_config('fwrules');
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
		case "firewall_options":
			unset($input_errors);
			$a_filter = &$config['filter']['options'];	
 
			/* input validation
			$reqdfields = explode(" ", "type interface srclist dstlist");
			$reqdfieldsn = explode(",", "Type,Interface,Source,Destination");
			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
			*/

				$filterent['timeouts'] = array();
				if($_POST['tcpfirst'] != '120')
					$filterent['timeouts']['tcpfirst'] = $_POST['tcpfirst'];
				if($_POST['tcpopening'] != '30')
					$filterent['timeouts']['tcpopening'] = $_POST['tcpopening'];
				if($_POST['tcpestablished'] != '86400')
					$filterent['timeouts']['tcpestablished'] = $_POST['tcpestablished'];
				if($_POST['tcpclosing'] != '900')
					$filterent['timeouts']['tcpclosing'] = $_POST['tcpclosing'];
				if($_POST['tcpfinwait'] != '45')
					$filterent['timeouts']['tcpfinwait'] = $_POST['tcpfinwait'];
				if($_POST['tcpclosed'] != '90')
					$filterent['timeouts']['tcpclosed'] = $_POST['tcpclosed'];
				if($_POST['udpfirst'] != '60')
					$filterent['timeouts']['udpfirst'] = $_POST['udpfirst'];
                if($_POST['udpsingle'] != '30')
					$filterent['timeouts']['udpsingle'] = $_POST['udpsingle'];
                if($_POST['udpmultiple'] != '60')
					$filterent['timeouts']['udpmultiple'] = $_POST['udpmultiple'];
                if($_POST['icmpfirst'] != '20')
					$filterent['timeouts']['icmpfirst'] = $_POST['icmpfirst'];
                if($_POST['icmperror'] != '10')
					$filterent['timeouts']['icmperror'] = $_POST['icmperror'];
                if($_POST['otherfirst'] != '60')
					$filterent['timeouts']['otherfirst'] = $_POST['otherfirst'];
				if($_POST['othersingle'] != '30')
					$filterent['timeouts']['othersingle'] = $_POST['othersingle'];
                if($_POST['othermultiple'] != '60')
					$filterent['timeouts']['othermultiple'] = $_POST['othermultiple'];
                if($_POST['adaptivestart'] != '6000')
					$filterent['timeouts']['adaptivestart'] = $_POST['adaptivestart'];
                if($_POST['adaptiveend'] != '12000')
					$filterent['timeouts']['adaptiveend'] = $_POST['adaptiveend'];
				if($_POST['maxstates'] != '10000')
                    $filterent['limits']['maxstates'] = $_POST['maxstates'];
				if($_POST['maxfrags'] != '5000')
                    $filterent['limits']['maxfrags'] = $_POST['maxfrags'];
				if($_POST['srcnodes'] != '10000')
                    $filterent['limits']['srcnodes'] = $_POST['srcnodes'];
         		if($_POST['rulesetopt'] != 'basic')
                    $filterent['opt']['rulesetopt'] = $_POST['rulesetopt'];
				if($_POST['stateopt'] != 'normal')
                    $filterent['opt']['stateopt'] = $_POST['stateopt'];
				if($_POST['blockpol'] != 'drop')
                    $filterent['opt']['blockpol'] = $_POST['blockpol'];
				if($_POST['statepol'] != 'floating')
                    $filterent['opt']['statepol'] = $_POST['statepol'];
				if ($_POST['dfbit']) 
                    $filterent['scrub']['dfbit'] = $_POST['dfbit'] ? true : false;
				if ($_POST['minttl'] != "") 
                    $filterent['scrub']['minttl'] = $_POST['minttl'];
				if ($_POST['maxmss'] != "") 
                    $filterent['scrub']['maxmss'] = $_POST['maxmss'];
				if ($_POST['randid'])
                    $filterent['scrub']['randid'] = $_POST['randid'] ? true : false;
				if ($_POST['fraghandle'])
                    $filterent['scrub']['fraghandle'] = $_POST['fraghandle'];
                if ($_POST['reassembletcp'])
					$filterent['scrub']['reassembletcp'] = $_POST['reassembletcp'];
				if ($_POST['logdefault'])
                        $filterent['logging']['default'] = $_POST['logdefault'] ? true : false;

				$a_filter = $filterent;

				$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);
 
				if (filter_parse_config($config)) {
    	            $input_errors[] = "Could not parse the generated config file";
        	        $input_errors[] = "See log file for details";
            	    $input_errors[] = "XML Config file not modified";
	            }

				$retval = 0;
	            if (!$input_errors) {
    	            write_config();
        	        config_lock();
            	    $retval = filter_configure();
                	config_unlock();
                	push_config('pfoptions');
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
		case "firewall_alias":
		 	if (!is_array($config['aliases']['alias']))
    			$config['aliases']['alias'] = array();

			aliases_sort();
			$a_aliases = &$config['aliases']['alias'];

			if (isset($_POST['id']))
    			$id = $_POST['id'];

			unset($input_errors);
			$pconfig = $_POST;

    		/* input validation */
    		$reqdfields = explode(" ", "name memberslist");
    		$reqdfieldsn = explode(",", "Name,Memberslist");

    		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

    		$alias = array();
        	$alias['name'] = $_POST['name'];
        	$alias['descr'] = $_POST['descr'];
    		$memberslist = explode(' ', $_POST['memberslist']);
        	for($i=0;$i<sizeof($memberslist); $i++) {
                $member = 'member'."$i";
                $prop = preg_replace("/ /", "", $memberslist[$i]);
                $alias['memberlist'][$member] = $prop;
        	}
        	if (isset($id) && $a_aliases[$id])
                $a_aliases[$id] = $alias;
        	else
                $a_aliases[] = $alias;

    		$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);

        	if (filter_parse_config($xmlconfig)) {
        		$input_errors[] = "Could not parse the generated config file";
        		$input_errors[] = "See log file for details";
        		$input_errors[] = "XML Config file not modified";
    		}

			$retval = 0;
                if (!$input_errors) {
                    write_config();
                    config_lock();
                    $retval = filter_configure();
                    config_unlock();
                    push_config('pfaliases');
                }
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
		case "firewall_nat":
				if (!is_array($config['nat']['advancedoutbound']['rule']))
    				$config['nat']['advancedoutbound']['rule'] = array();
    
				$a_out = &$config['nat']['advancedoutbound']['rule'];
				nat_out_rules_sort();
 
				if (isset($_POST['id']))
    				$id = $_POST['id'];	

			    /* if ($_POST['destination_type'] == "any") { */
     			    $_POST['destination_type'] = "any";
				   $_POST['destination'] = "any";
    			   $_POST['destination_subnet'] = 24;
    			/* } */
    
    			unset($input_errors);
    			$pconfig = $_POST;
 
			    /* input validation */
  			    $reqdfields = explode(" ", "interface source source_subnet");
    			$reqdfieldsn = explode(",", "Interface,Source,Source bit count");
    
    			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
 
			    if ($_POST['source'] && !is_ipaddr($_POST['source']))
      			$input_errors[] = "A valid source must be specified.";
    
				if ($_POST['source_subnet'] && !is_numericint($_POST['source_subnet']))
        			$input_errors[] = "A valid source bit count must be specified.";
    
				if ($_POST['destination_type'] != "any") {
        			if ($_POST['destination'] && !is_ipaddr($_POST['destination'])) {
            			$input_errors[] = "A valid destination must be specified.";
        			}
        			if ($_POST['destination_subnet'] && !is_numericint($_POST['destination_subnet'])) {
            			$input_errors[] = "A valid destination bit count must be specified.";
        			}
    			}
    			if ($_POST['target'] && !is_ipaddr($_POST['target']))
        			$input_errors[] = "A valid target IP address must be specified.";
    
  				/* check for existing entries */
    			$osn = gen_subnet($_POST['source'], $_POST['source_subnet']) . "/" . $_POST['source_subnet'];
    			if ($_POST['destination_type'] == "any")
        			$ext = "any";
    			else
        			$ext = gen_subnet($_POST['destination'], $_POST['destination_subnet']) . "/"
            		. $_POST['destination_subnet'];
 
				if ($_POST['target']) {
					/* check for clashes with 1:1 NAT (Server NAT is OK) */
					if (is_array($config['nat']['onetoone'])) {
						foreach ($config['nat']['onetoone'] as $natent) {
							if (check_subnets_overlap($_POST['target'], 32, $natent['external'], $natent['subnet'])) {
								$input_errors[] = "A 1:1 NAT mapping overlaps with the specified target IP address.";
								break;
							}
						}
					}
				}
    
    			foreach ($a_out as $natent) {
        			if (isset($id) && ($a_out[$id]) && ($a_out[$id] === $natent))
            			continue;
        
					if (!$natent['interface'])
						$natent['interface'] == "wan";
 
					if (($natent['interface'] == $_POST['interface']) && ($natent['source']['network'] == $osn)) {
						if (isset($natent['destination']['not']) == isset($_POST['destination_not'])) {
							if ((isset($natent['destination']['any']) && ($ext == "any")) ||
							($natent['destination']['network'] == $ext)) {
								$input_errors[] = "There is already an outbound NAT rule with the specified settings.";
								break;
							}
						}
					}
    			}
 
				$retval = 0;
                if (!$input_errors) {
					$natent = array();
                    $natent['source']['network'] = $osn;
                    $natent['descr'] = $_POST['descr'];
                    $natent['target'] = $_POST['target'];
                    $natent['interface'] = $_POST['interface'];
                    $natent['noportmap'] = $_POST['noportmap'] ? true : false;

                    if ($ext == "any")
                        $natent['destination']['any'] = true;
                    else
                        $natent['destination']['network'] = $ext;

                    if (isset($_POST['destination_not']) && $ext != "any")
                        $natent['destination']['not'] = true;

                    if (isset($id) && $a_out[$id])
                        $a_out[$id] = $natent;
                    else
                        $a_out[] = $natent;
                    write_config();
                    config_lock();
                    $retval = filter_configure();
                    config_unlock();
                    push_config('pfnats');
                }
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
			case "firewall_nat_1to1":
				if (!is_array($config['nat']['onetoone'])) {
					$config['nat']['onetoone'] = array();
				}
				nat_1to1_rules_sort();
				$a_1to1 = &$config['nat']['onetoone'];
 
				if (isset($_POST['id']))
					$id = $_POST['id'];	
				unset($input_errors);
				$pconfig = $_POST;
 
				/* input validation */
				$reqdfields = explode(" ", "interface external internal");
				$reqdfieldsn = explode(",", "Interface,External,Internal");
 
				do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
 
				if (($_POST['external'] && !is_ipaddr($_POST['external']))) {
					$input_errors[] = "A valid external ip must be specified.";
				}
				if (($_POST['internal'] && !is_ipaddr($_POST['internal']))) {
					$input_errors[] = "A valid internal ip must be specified.";
				}
 
 
				if (is_ipaddr($config['interfaces']['wan']['ipaddr'])) {
					if (check_subnets_overlap($_POST['external'], $_POST['subnet'],
						$config['interfaces']['wan']['ipaddr'], 32))
					$input_errors[] = "The WAN IP address may not be used in a 1:1 rule.";
				}
 
				if (!$input_errors) {
					$natent = array();
					$natent['external'] = $_POST['external'];
					$natent['internal'] = $_POST['internal'];
					$natent['descr'] = $_POST['descr'];
					$natent['interface'] = $_POST['interface'];
 
					if (isset($id) && $a_1to1[$id])
						$a_1to1[$id] = $natent;
				else
					$a_1to1[] = $natent;
					write_config();
                    config_lock();
                    $retval = filter_configure();
                    config_unlock();
                    push_config('pfnats');
                }
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
 echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="OK" onClick="hidediv(\'save_config\')"></center>';
			return 0;
	}
}
if ($_GET) {
     $id = $_GET['id'];
     $action = $_GET['action'];
     $type = $_GET['type'];

     if ($type == 'rules') {
          if (!is_array($config['filter']['rule']))
               $config['filter']['rule'] = array();

          filter_rules_sort();
          $a_filter = &$config['filter']['rule'];
          if ($action == 'delete') {
               unset($a_filter[$id]);
               write_config();            
          }
     }
	 if ($type == 'alias') {
          if (!is_array($config['aliases']['alias'])) {    
               $config['aliases']['alias'] = array();    
          }    
          $a_alias = &$config['aliases']['alias'];    
          aliases_sort(); 
          if ($action == 'delete') {
               unset($a_alias[$id]);
               write_config();
          }
     } 
	 if ($type == 'dnat') {
          if (!is_array($config['nat']['advancedoutbound']['rule']))
               $config['nat']['advancedoutbound']['rule'] = array();
    
          $a_dnat = &$config['nat']['advancedoutbound']['rule'];
          nat_out_rules_sort(); 
		  if ($action == 'delete') {
               unset($a_dnat[$id]);
               write_config();
          }
     }
	 if ($type == '1to1nat') {
          if (!is_array($config['nat']['onetoone'])) {
			   $config['nat']['onetoone'] = array();
		  }
		  $a_1to1 = &$config['nat']['onetoone'];
		  nat_1to1_rules_sort();
		  if ($action == 'delete') {
               unset($a_1to1[$id]);
               write_config();
          }
     }
}
?>
