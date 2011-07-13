#!/bin/php
<?php

require_once("guiconfig.inc");

global $config, $g;

define("ADMINUSER", $config['system']['username']);
define("ADMINPASSWORD", base64_decode($config['system']['password']));
define("ADMINHOME", "index.php");

$loginAttempts = !isset($_POST['loginAttempts'])?1:$_POST['loginAttempts'];
$formuser = !isset($_POST['username'])?NULL:$_POST['username'];
$formpassword = !isset($_POST['password'])?NULL:$_POST['password'];

// Non-Admin login attempt:
if($formuser != ADMINUSER) {
	if (is_array($config['system']['accounts']['user'])) {
		foreach ($config['system']['accounts']['user'] as $user) {
			if($formuser == $user['name']) {
				if ($formpassword == base64_decode($user['password'])) {
					session_start();
					$_SESSION['basic_is_logged_in'] = true;
					$_SESSION['session_start_time'] = time();
					$_SESSION['User'] = $user['name'];
					$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
					$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
					$SID = session_id();
					mwexec("/sbin/pfctl -t {$_SESSION['User']} -Tadd {$_SESSION['IPaddress']}");
					mwexec("/usr/bin/logger -p local0.info -t webui user: {$_SESSION['User']} successfully logged in from: {$_SESSION['IPaddress']}");
					echo 'USEROK'; // this response is checked in 'process-login.js'
					exit;
				} else {
					$auth_error = '<div id="notification_error">The login info is not correct.</div>';
					echo $auth_error;
				}
			}
		}
	}
} else {
	if($formpassword != ADMINPASSWORD ) {
		echo '<script type="text/javascript">
                                        setTimeout(function(){ window.location = "/login.php"; }, 1000);
                                        </script>';
	}
}

// Sucessful admin login
if (($formuser == ADMINUSER ) && ($formpassword == ADMINPASSWORD )) {
	session_start();
	$_SESSION['basic_is_logged_in'] = true;
	$_SESSION['session_start_time'] = time();
	$_SESSION['adminUser'] = ADMINUSER;
	$_SESSION['adminPassword'] = ADMINPASSWORD;
	$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
	$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
	$SID = session_id();
	$adminHome = ADMINHOME;
	mwexec("/usr/bin/logger -p local0.info -t webui user: {$_SESSION['adminUser']} successfully logged in from: {$_SERVER
	['REMOTE_ADDR']}");
	echo '<script type="text/javascript">
          $("#login_nswall").dialog("close");
		  window.location = "/#index";
          </script>';
}
// Successful nonadmin login

?>
