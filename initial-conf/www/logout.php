#!/bin/php
<?php 
require_once("guiconfig.inc");

session_start();

if (isset($_SESSION['session_start_time']))
        $session_time = round((time() - $_SESSION['session_start_time']) / 60);

if (isset($_SESSION['User'])) {
	mwexec("/sbin/pfctl -t {$_SESSION['User']} -Tdelete {$_SESSION['IPaddress']}");	
	mwexec("/usr/bin/logger -p local0.info -t webui user: {$_SESSION['User']} successfully logged out from {$_SESSION['IPaddress']} after $session_time minutes of activity");
}
if (isset($_SESSION['adminUser']))
	mwexec("/usr/bin/logger -p local0.info -t webui user: {$_SESSION['adminUser']} successfully logged out from {$_SESSION['IPaddress']} after $session_time minutes of activity");

function session_clear() {
// if session exists, unregister all variables that exist and destroy session
  $exists = "no";
  $session_array = explode(";",session_encode());
  for ($x = 0; $x < count($session_array); $x++) {
    $name  = substr($session_array[$x], 0, strpos($session_array[$x],"|")); 
	if (session_is_registered($name)) {
	  session_unregister('$name');
	  $exists = "yes";
	}
  }
if ($exists != "no") {
	session_destroy();
	}
}
session_clear();
if(!session_is_registered(session_name())) {
}
if (isset($_SESSION['basic_is_logged_in'])) {
   unset($_SESSION['basic_is_logged_in']);
}
// mwexec("/sbin/pfctl -k {$_SESSION['IPaddress']}");
header("Location: login.htm");
exit;
?>
