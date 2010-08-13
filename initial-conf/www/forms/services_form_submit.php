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

?>
