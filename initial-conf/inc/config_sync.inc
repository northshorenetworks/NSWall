<?php
/*
	$Id: config_sync.inc,v 1.1 2009/04/21 21:46:13 jrecords Exp $
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
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

/* include all configuration functions */
require_once("functions.inc");


/**
 * Sends a HTTP request using fsockopen
 *
 * To check if the function worked use === false and not just
 * == false in case the return HTML, normally a thank you page,
 * is just 0. Unlikely but possible.
 *
 * @param string $url      The url to post the data to
 * @param array  $data An associate array of the data to send. Should be exactly like how you want the $_POST or $_GET to be
 * @param string $ua       The useragent string to send
 * @return false or string False on fail or response HTML i.e. the forms thank you page
 */
function http_request( $username, $password, $url, $data, $method, $useragent = 'NSWallMGR/1.0' )
{
    global $config, $g;

    $url_info = parse_url( $url );
    $senddata = '';

if($method == 'POST') {
        /* data must be an array */
        if( !is_array( $data ) )
            return false;

        /* loop data and convert it */
        foreach( $data as $name => $value )
        {
            /* add & if it isn't the first */
            if( !empty( $senddata ) )
                $senddata .= '&';
            $senddata .= urlencode( $name ) . '=' . urlencode( $value );
        }   
}

    /* open in secure socket layer or not */
    if( $url_info['scheme'] == 'https' )
        $fp = @fsockopen( 'ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
    else
        $fp = @fsockopen( $url_info['host'], 80, $errno, $errstr, 10);

    /* log any errors */
    if( $errno != '0') 
	 mwexec("/usr/bin/logger -p local0.info -t sync Error connecting to {$url_info['host']}, Error code: $errno, Reason: $errstr");

    /* make sure opened ok */
    if( !$fp ) 
        return $false;

    /* HTTP headers */
    $out = "$method " . (isset($url_info['path'])?$url_info['path']:'/') .
       	(isset($url_info['query'])?'?' . $url_info['query']:'') . ' HTTP/1.0' . "\r\n";
    $out .= 'Host: ' . $url_info['host'] . "\r\n";
    $out .= 'Authorization: Basic ' . base64_encode("$username:$password") . "\r\n";
    $out .= 'User-Agent: ' .  $useragent . "\r\n";
    $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $out .= 'Content-Length: ' . strlen( $senddata ) . "\r\n";
    $out .= 'Connection: Close' . "\r\n\r\n";
    $out .= $senddata;
    fwrite($fp, $out);

    /* read any response */
    for( ;!feof( $fp ); )
        $contents .= fgets($fp, 128);

    /* seperate content and headers */
    list($headers, $content) = explode( "\r\n\r\n", $contents, 2 );

    /* if we failed to authenticate, log it and exit */
    if( preg_match("/HTTP\/1.0 401 Unauthorized/", $headers)) {
	mwexec("/usr/bin/logger -p local0.info -t sync connection to {$url_info['host']} failed: 401 Unauthorized, check login credentials");
        return false;
    }
    return $content;
}

function sync_config_area($area, $action, $newconfig) {
	global $config, $g;
	if ($area == 'rules') {
		if (!is_array($config['filter']['rule'])) {
        		$config['filter']['rule'] = array();
        	}
		$ruleset = &$config['filter']['rule'];
		if($action == 'get')
			return $ruleset;
		if($action == 'set') {
			$ruleset = $newconfig; 
			write_config();
			$retval = filter_configure();
			return;
		}
	} elseif ($area == 'aliases') {
		if (!is_array($config['aliases'])) {
                       $config['aliases'] = array();
                }
                $aliases = &$config['aliases']; 
		if($action == 'get')
			return $aliases;
		if($action == 'set') {
                        $aliases = $newconfig;
                        write_config();
			$retval = filter_configure();
			return;
                }
	} elseif ($area == 'relays') {
		if (!is_array($config['relays'])) {
                       $config['relays'] = array();
                }
                $relays = &$config['relays'];
		if($action == 'get')	
			return $relays;
		if($action == 'set') {
                        $relays = $newconfig;
                 	write_config();
			$retval = relay_relayd_reload();
			return;
                }
	} elseif ($area == 'nats') {
		if (!is_array($config['nat'])) {
                       $config['nat'] = array();
                }
                $nats = &$config['nat'];
		if($action == 'get')
			return $nats;
		if($action == 'set') {
                        $nats = $newconfig;
                        write_config();
			$retval = filter_configure();
			return;
                }
	} elseif ($area == 'pfoptions') {
		if (!is_array($config['filter']['options'])) {
                       $config['filter']['options'] = array();
                }
                $pfoptions = &$config['filter']['options'];
		if($action == 'get')	
			return $pfoptions;
		if($action == 'set') {
                        $pfoptions = $newconfig;
                        write_config();
			$retval = filter_configure();
			return;
                }
	} elseif ($area == 'altq') {
		if (!is_array($config['altq'])) {
                       $config['altq'] = array();
                }
                $altq = &$config['altq'];
		if($action == 'get')
			return $altq;
		if($action == 'set') {
                        $altq = $newconfig;
                        write_config();
			$retval = filter_configure();
			return;
                }
	} elseif ($area == 'dhcpd') {
		if (!is_array($config['dhcpd'])) {
                       $config['dhcpd'] = array();
                }
                $dhcpd = &$config['dhcpd'];
		if($action == 'get')
			return $dhcpd;
		if($action == 'set') {
                        $dhcpd = $newconfig;
                        write_config();
			$retval = services_dhcpd_configure();	
			return;
                }
	} elseif ($area == 'staticroutes') {
		if (!is_array($config['staticroutes'])) {
                       $config['staticroutes'] = array();
                }
                $staticroutes = &$config['staticroutes'];
		if($action == 'get')
			return $staticroutes;
		if($action == 'set') {
                        $staticroutes = $newconfig;
                	write_config();
		        $retval = system_routing_configure();
                        $retval |= filter_configure(); 
			return;
                }
	} elseif ($area == 'ipsec') {	
		if (!is_array($config['ipsec'])) {
                       $config['ipsec'] = array();
                }
                $ipsec = &$config['ipsec'];
	 	if($action == 'get')	
			return $ipsec;
 		if($action == 'set') {
                        $ipsec = $newconfig;
                        write_config();
			$retval = vpn_ipsec_configure();
			return;
                }
	} elseif ($area == 'dnsmasq') {	
		if (!is_array($config['dnsmasq'])) {
                       $config['dnsmasq'] = array();
                }
                $dnsmasq = &$config['dnsmasq'];
		if($action == 'get')
			return $dnsmasq;
		if($action == 'set') {
                        $dnsmasq = $newconfig;
                	write_config(); 
	 	        $retval = services_dnsmasq_configure(); 
			return;
                }
	} elseif ($area == 'certmgr') {
		if (!is_array($config['system']['certmgr'])) {
                       $config['system']['certmgr'] = array();
                }
                $certmgr = &$config['system']['certmgr'];
		if($action == 'get')
			return $certmgr;
		if($action == 'set') {
                        $certmgr = $newconfig;
                	write_config();
		        return;
                }
	} elseif ($area == 'accounts') {
		if (!is_array($config['system']['accounts'])) {
                       $config['system']['accounts'] = array();
                }
                $accounts = &$config['system']['accounts'];
		if($action == 'get')	
			return $accounts;
		if($action == 'set') {
                        $accounts = $newconfig;
                        write_config();
			return;
                }
	} elseif ($area == 'networking') {
		if (!is_array($config['system']['networking'])) {
                       $config['system']['networking'] = array();
                }
                $networking = &$config['system']['networking'];
		if($action == 'get')
			return $networking;
		if($action == 'set') {
                        $networking = $newconfig;
        		write_config();
	                return;
                }
	} elseif ($area == 'general') {
		if (!is_array($config['system']['general'])) {
                       $config['system']['general'] = array();
                }
                $general = &$config['system']['general'];
		if($action == 'get')
			return $general;
		if($action == 'set') {
                        $general = $newconfig;
                	write_config();
		        return;
                }
	}
}

/* serialze and encode an array */
function encode_array($array) {
	$array = serialize($array);
	$array = rawurlencode($array);
	return $array;
}

/* decode and unserialize an array */
function decode_array($array) {
        $array = rawurldecode($array);
        $array = unserialize($array);
	return $array;
}

/* send post data to each config sync hosts */
function config_sync_hosts_post($array, $type, $md5) {
	global $config, $g;
	if (is_array($config['carp']['configsynchost'])) {
        	foreach($config['carp']['configsynchost'] as $synchost) {
        		$remotemd5 = http_request( $synchost['username'], $synchost['password'], "http://{$synchost['ip']}/config_sync.php?action=md5compare&type=$type", '', 'GET' );
			if($remotemd5 == '' )
				return false;
			if($remotemd5 == $md5) {
				mwexec("/usr/bin/logger -p local0.info -t sync DEBUG: $type config, local md5: $md5 == remote md5 $remotemd5 on {$synchost['ip']}");
			} else {
				mwexec("/usr/bin/logger -p local0.info -t sync DEBUG: $type config, local md5: $md5 != remote md5 $remotemd5 on {$synchost['ip']}");
				$retval = http_request( $synchost['username'], $synchost['password'], "http://{$synchost['ip']}/config_sync.php", $array, 'POST' );
        			if ($retval == 'SUCCESS') {
        				mwexec("/usr/bin/logger -p local0.info -t sync Successfully pushed $type to {$synchost['ip']}");
       				} else {
					mwexec("/usr/bin/logger -p local0.info -t sync Failed to push $type to {$synchost['ip']}");
				}
			}
		}
        }
} 

/* get config area or md5s from policy server and request config if it doesn't match current md5 */
function config_sync_policy_server_request($action, $type) {
        global $config, $g;
        if ($config['system']['configserver']) { 
        	$configserver = $config['system']['configserver'];
		$retval = http_request( $configserver['username'], $configserver['password'], "http://{$configserver['ip']}/config_sync.php?action=$action&type=$type", $array, 'GET' );
		return $retval;
        }
}

function push_config($pushtype) {
	global $config, $g;
	
	switch ($pushtype) {
    		case "rules":
			$ruleset = &$config['filter']['rule'];
			$ruleset = encode_array($ruleset);
			$post = array(
                		'ruleset'  => "$ruleset", 
        			'type'   => 'rules',
	        		'action' => 'load'
        		);
			config_sync_hosts_post($post, 'rules', md5($ruleset));
			break;
		case "aliases":
			$aliases = &$config['aliases']['alias'];
                        $aliases = encode_array($aliases);
                        $post = array(
                                'aliases'  => "$aliases", 
                                'type'   => 'aliases',
				'action' => 'load'
                        );
                        config_sync_hosts_post($post, 'aliases', md5($aliases)); 
			break;
		case "relays":
                        $relays = &$config['relays'];
                        $relays = encode_array($relays);
                        $post = array(
                                'relays'  => "$relays", 
                                'type'   => 'relays',
                                'action' => 'load'
                        );
			config_sync_hosts_post($post, 'relays', md5($relays));
			break;
		 case "nat":
                        $nats = &$config['nat'];
			$nats = encode_array($nats);
                        $post = array(
                                'nats'  => "$nats", 
                                'type'   => 'nats',
                                'action' => 'load'
                        );
			config_sync_hosts_post($post, 'NAT rules', md5($nats));
			break;
		case "pfoptions":
                        $pfoptions = &$config['filter']['options'];
			$pfoptions = encode_array($pfoptions);
                        $post = array(
                                'pfoptions'  => "$pfoptions", 
                                'type'   => 'pfoptions',
                                'action' => 'load'
                        );
			config_sync_hosts_post($post, 'PF options', md5($pfoptions));
			break;
		 case "altq":
                        $altq = &$config['altq'];
			$altq = encode_array($altq);
                        $post = array(
                                'altq'  => "$altq",
                                'type'   => 'altq',
                                'action' => 'load'
                        );
			config_sync_hosts_post($post, 'ALTQ configuration', md5($altq));
			break;
	   	case "dhcpd":
                        $dhcpd = &$config['dhcpd'];
			$dhcpd = encode_array($dhcpd);
                        $post = array(
                                'dhcpd'  => "$dhcpd",
                                'type'   => 'dhcpd',
                                'action' => 'load'
                        );
			config_sync_hosts_post($post, 'dhcpd configuration', md5($dhcpd));
			break;
  		case "staticroutes":
                        $staticroutes = &$config['staticroutes'];
			$staticroutes = encode_array($staticroutes);
                        $post = array(
                                'staticroutes'  => "$staticroutes",
                                'type'   => 'staticroutes',
                                'action' => 'load'
                        );
                        config_sync_hosts_post($post, 'Static Routes', md5($staticroutes));
			break;
                case "ipsec":
                        $ipsec = &$config['ipsec'];
			$ipsec = encode_array($ipsec);
                        $post = array(
                                'ipsec'  => "$ipsec",
                                'type'   => 'ipsec',
                                'action' => 'load'
                        );
                        config_sync_hosts_post($post, 'IPSEC configuration', md5($ipsec));
			break;
		case "dnsmasq":
                        $dnsmasq = &$config['dnsmasq'];
			$dnsmasq = encode_array($dnsmasq);
                        $post = array(
                                'dnsmasq'  => "$dnsmasq",
                                'type'   => 'dnsmasq',
                                'action' => 'load'
                        );
                        config_sync_hosts_post($post, 'DNS forwarder configuration', md5($dnsmasq));
			break;
		case "certmgr":
                        $certmgr = &$config['system']['certmgr'];
			$certmgr = encode_array($certmgr);
                        $post = array(
                                'certmgr'  => "$certmgr",
                                'type'   => 'certmgr',
                                'action' => 'load'
                        );
                       	config_sync_hosts_post($post, 'Certificate Manager configuration', md5($certmgr)); 
			break;
		case "networking":
                        $networking = &$config['system']['networking'];
			$networking = encode_array($networking);
                        $post = array(
                                'networking'  => "$networking",
                                'type'   => 'networking',
                                'action' => 'load'
                        );
                        config_sync_hosts_post($post, 'Advanced Networking configuration', md5($networking));
			break;
		case "accounts":
                        $accounts = &$config['system']['accounts'];
			$accounts = encode_array($accounts);
                        $post = array(
                                'accounts'  => "$accounts",
                                'type'   => 'accounts',
                                'action' => 'load'
                        );
                        config_sync_hosts_post($post, 'User Accounts', md5($accounts));
			break;
		case "general":
                        $general = &$config['system']['general'];
			$general = encode_array($general);
                        $post = array(
                                'general'  => "$general",
                                'type'   => 'general',
                                'action' => 'load'
                        );
                        config_sync_hosts_post($post, 'General Settings', md5($general));
			break;
	}
return $retval;
}

?>
