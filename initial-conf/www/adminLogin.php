#!/bin/php
<?php

require_once("guiconfig.inc");

global $config, $g;

define("ADMINUSER", $config['system']['username']);
define("ADMINPASSWORD", base64_decode($config['system']['password']));
define("ADMINHOME", "index.php");

$loginAttempts = !isset($_POST['loginAttempts'])?1:$_POST['loginAttempts'];
$formuser = !isset($_POST['formuser'])?NULL:$_POST['formuser'];
$formpassword = !isset($_POST['formpassword'])?NULL:$_POST['formpassword'];

// Initail page load
if ($formuser == "" && $formpassword == "") {
	include("adminLoginForm.php");
	exit;
}

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
	       				header("Location: sessioninfo.php");
               				exit;
            }
                        }
                }
                if ($loginAttempts == 0) {
         $_POST['loginAttempts'] = 1;
         include("adminLoginForm.php");
         exit;
  }else{
         if ( $loginAttempts >= 3 ) {
            echo "<p align='center' style=\"font-weight:bold;font-size:10 points;color:red;font-family:sans-serif;\">Log In F
ailed.<br>
                           Try again Please...</p>";
                     include("adminLoginForm.php");
                     exit;
         }else{
            include("adminLoginForm.php");
            exit;
         }
      }
        }
} else {
        if($formpassword != ADMINPASSWORD ) {
                if ($loginAttempts == 0) {
                        $_POST['loginAttempts'] = 1;
                        include("adminLoginForm.php");
                        exit;
                }else{
                        if ( $loginAttempts >= 3 ) {
                                echo "<p align='center' style=\"font-weight:bold;font-size:10 points;color:red;font-family:sa
ns-serif;\">Log In Failed.<br>
                                Try again Please...</p>";
                                                        include("adminLoginForm.php");
                                                        exit;
                        }else{
                                include("adminLoginForm.php");
                                exit;
                        }
                }
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
        header("Location: $adminHome");
}
// Successful nonadmin login

?>
