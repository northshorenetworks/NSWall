#!/bin/php
<?php 
/*
	$Id: config_sync.php,v 1.2 2009/04/21 21:45:25 jrecords Exp $
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

require("guiconfig.inc");
global $config, $g;

if ($_GET['action'] == 'getconfig') {
	 if ($_GET['type'] == 'rules') {
		echo encode_array(sync_config_area('rules', 'get', ''));	 
		exit;
	 } elseif ($_GET['type'] == 'aliases') {
		echo encode_array(sync_config_area('aliases', 'get', ''));
	        exit;
	 } elseif ($_GET['type'] == 'relays') {
		echo encode_array(sync_config_area('relays', 'get', ''));
		exit;
	 } elseif ($_GET['type'] == 'nats') {
		echo encode_array(sync_config_area('nats', 'get', ''));
		exit;
	 } elseif ($_GET['type'] == 'pfoptions') {
		echo encode_array(sync_config_area('pfoptions', 'get', ''));
		exit;
	 } elseif ($_GET['type'] == 'altq') {
		echo encode_array(sync_config_area('altq', 'get', ''));
		exit;
	 } elseif ($_GET['type'] == 'dhcpd') {
		echo encode_array(sync_config_area('dhcpd', 'get', ''));
		exit;
	} elseif ($_GET['type'] == 'staticroutes') {
		echo encode_array(sync_config_area('staticroutes', 'get', ''));
		exit;
	} elseif ($_GET['type'] == 'ipsec') {
		echo encode_array(sync_config_area('ipsec', 'get', ''));
		exit;
	} elseif ($_GET['type'] == 'dnsmasq') {
		echo encode_array(sync_config_area('dnsmasq', 'get', ''));	
		exit;
	} elseif ($_GET['type'] == 'certmgr') {
		echo encode_array(sync_config_area('certmgr', 'get', ''));
		exit;
	} elseif ($_GET['type'] == 'accounts') {
		echo encode_array(sync_config_area('accounts', 'get', ''));
		exit;
	} elseif ($_GET['type'] == 'networking') {
                echo encode_array(sync_config_area('networking', 'get', ''));
                exit;
	} elseif ($_GET['type'] == 'general') {
		echo encode_array(sync_config_area('general', 'get', ''));
		exit;
        }
}

if ($_GET['action'] == 'getmd5') {
	$rules = md5(encode_array(sync_config_area('rules', 'get', '')));
	$aliases = md5(encode_array(sync_config_area('aliases', 'get', '')));
	$relays = md5(encode_array(sync_config_area('relays', 'get', '')));
	$nats = md5(encode_array(sync_config_area('nats', 'get', '')));
	$pfoptions = md5(encode_array(sync_config_area('pfoptions', 'get', '')));
	$altq = md5(encode_array(sync_config_area('altq', 'get', '')));
	$dhcpd = md5(encode_array(sync_config_area('dhcpd', 'get', '')));
	$staticroutes = md5(encode_array(sync_config_area('staticroutes', 'get', '')));
	$ipsec = md5(encode_array(sync_config_area('ipsec', 'get', '')));
	$dnsmasq = md5(encode_array(sync_config_area('dnsmasq', 'get', '')));
	$certmgr = md5(encode_array(sync_config_area('certmgr', 'get', '')));
	$accounts = md5(encode_array(sync_config_area('accounts', 'get', '')));
	$networking = md5(encode_array(sync_config_area('networking', 'get', '')));
	$general = md5(encode_array(sync_config_area('general', 'get', '')));

	$md5s = array('rules' => $rules,
         		'aliases' => $aliases,
   	                'relays' => $relays,
		        'nats' => $nats,
			'pfoptions' => $pfoptions,
			'altq' => $altq,
			'dhcpd' => $dhcpd,
			'staticroutes' => $staticroutes,
			'ipsec' => $ipsec,
			'dnsmasq' => $dnsmasq, 
			'certmgr' => $certmgr,
			'accounts' => $accounts,
			'networking' => $networking,
			'general' => $general
		);
	reset($md5s);
	echo encode_array($md5s);
}

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['action'] == 'load' && $_POST['type'] == 'rules') {
		if (!is_array($config['filter']['rule'])) {
 		       $config['filter']['rule'] = array();
		}
		$a_filter = &$config['filter']['rule'];
		$retval = 0;
		$ruleset = rawurldecode($_POST['ruleset']);
		$ruleset = unserialize($ruleset);
		$a_filter = $ruleset;
		mwexec("/usr/bin/logger -p local0.info -t webui syncing config rules to referring host");
                write_config();
		$retval = filter_configure();
		echo "SUCCESS";
		exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'aliases') {
                	if (!is_array($config['aliases']['alias'])) {
 	                      $config['aliases']['alias'] = array();
        	        }
                	$a_aliases = &$config['aliases']['alias'];
			$retval = 0;
                	$aliases = rawurldecode($_POST['aliases']);
                	$aliases = unserialize($aliases);
                	$a_aliases = $aliases;
                	mwexec("/usr/bin/logger -p local0.info -t webui syncing aliases to referring host");
                	write_config();
                	$retval = filter_configure();
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'relays') {
                        if (!is_array($config['relays'])) {
                              $config['relays'] = array();
                        }
                        $a_relays = &$config['relays'];
                        $retval = 0;
                        $relays = rawurldecode($_POST['relays']);
                        $relays = unserialize($relays);
                        $a_relays = $relays;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing relays to referring host");
                        write_config();
			$retval = filter_configure();
                        $retval = relay_relayd_configure();
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'nats') {
                        if (!is_array($config['nat'])) {
                              $config['nat'] = array();
                        }
                        $a_nats = &$config['nat'];
                        $retval = 0;
                        $nats = rawurldecode($_POST['nats']);
                        $nats = unserialize($nats);
                        $a_nats = $nats;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing nats to referring host");
                        write_config();
                        $retval = filter_configure();
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'pfoptions') {
                        if (!is_array($config['filter']['options'])) {
                              $config['filter']['options'] = array();
                        }
                        $a_pfoptions = &$config['filter']['options'];
                        $retval = 0;
                        $pfoptions = rawurldecode($_POST['pfoptions']);
                        $pfoptions = unserialize($pfoptions);
                        $a_pfoptions = $pfoptions;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing PF options to referring host");
                        write_config();
			$retval = filter_configure();
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'altq') {
                        if (!is_array($config['altq'])) {
                              $config['altq'] = array();
                        }
                        $a_altq = &$config['altq'];
                        $retval = 0;
                        $altq = rawurldecode($_POST['altq']);
                        $altq = unserialize($altq);
                        $a_altq = $altq;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing ALTQ to referring host");
                        write_config();
                        $retval = filter_configure();
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'dhcpd') {
                        if (!is_array($config['dhcpd'])) {
                              $config['dhcpd'] = array();
                        }
                        $a_dhcpd = &$config['dhcpd'];
                        $retval = 0;
                        $dhcpd = rawurldecode($_POST['dhcpd']);
                        $dhcpd = unserialize($dhcpd);
                        $a_dhcpd = $dhcpd;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing DHCP to referring host");
                        write_config();
                        services_dhcpd_configure();	
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'staticroutes') {
                        if (!is_array($config['staticroutes'])) {
                              $config['staticroutes'] = array();
                        }
                        $a_staticroutes = &$config['staticroutes'];
                        $retval = 0;
                        $staticroutes = rawurldecode($_POST['staticroutes']);
                        $staticroutes = unserialize($staticroutes);
                        $a_staticroutes = $staticroutes;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing Static Routes to referring host");
                        write_config();
                        $retval = system_routing_configure();
                        $retval |= filter_configure();	
			echo "SUCCESS";
			exit;
	 } elseif ($_POST['action'] == 'load' && $_POST['type'] == 'ipsec') {
                        if (!is_array($config['ipsec'])) {
                              $config['ipsec'] = array();
                        }
                        $a_ipsec = &$config['ipsec'];
                        $retval = 0;
                        $ipsec = rawurldecode($_POST['ipsec']);
                        $ipsec = unserialize($ipsec);
                        $a_ipsec = $ipsec;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing IPSEC to referring host");
                        write_config();
                        $retval = vpn_ipsec_configure();
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'dnsmasq') {
                        if (!is_array($config['dnsmasq'])) {
                              $config['dnsmasq'] = array();
                        }
                        $a_dnsmasq = &$config['dnsmasq'];
                        $retval = 0;
                        $dnsmasq = rawurldecode($_POST['dnsmasq']);
                        $dnsmasq = unserialize($dnsmasq);
                        $a_dnsmasq = $dnsmasq;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing dnsmasq to referring host");
                        write_config();
                        $retval = services_dnsmasq_configure();	
			echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'certmgr') {
                        if (!is_array($config['system']['certmgr'])) {
                              $config['system']['certmgr'] = array();
                        }
                        $a_certmgr = &$config['system']['certmgr'];
                        $retval = 0;
                        $certmgr = rawurldecode($_POST['certmgr']);
                        $certmgr = unserialize($certmgr);
                        $a_certmgr = $certmgr;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing Certificate Manager to referring host");
                        write_config();
                        echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'networking') {
                        if (!is_array($config['system']['networking'])) {
                              $config['system']['networking'] = array();
                        }
                        $a_networking = &$config['system']['networking'];
                        $retval = 0;
                        $networking = rawurldecode($_POST['networking']);
                        $networking = unserialize($networking);
                        $a_networking = $networking;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing Advanced  Networking to referring host");
                        write_config();
        		echo "SUCCESS";
 	                exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'accounts') {
                        if (!is_array($config['system']['accounts'])) {
                              $config['system']['accounts'] = array();
                        }
                        $a_accounts = &$config['system']['accounts'];
                        $retval = 0;
                        $accounts = rawurldecode($_POST['accounts']);
                        $accounts = unserialize($accounts);
                        $a_accounts = $accounts;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing User Accounts to referring host");
                        write_config();
                        echo "SUCCESS";
			exit;
	} elseif ($_POST['action'] == 'load' && $_POST['type'] == 'general') {
                        if (!is_array($config['system']['general'])) {
                              $config['system']['general'] = array();
                        }
                        $a_general = &$config['system']['general'];
                        $retval = 0;
                        $general = rawurldecode($_POST['general']);
                        $general = unserialize($general);
                        $a_general = $general;
                        mwexec("/usr/bin/logger -p local0.info -t webui syncing General Ssytem config to referring host");
                        write_config();
                        echo "SUCCESS";
			exit;
	} else {
		echo "Not a supported sync request\n"; 
		print_r($_POST);
	}
}

?>
