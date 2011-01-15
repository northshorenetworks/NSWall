<?php
global $config, $g;

session_start();

if(   (!isset($_SESSION['adminUser'])) || (!isset($_SESSION['adminPassword'])) ) {
    echo '<script language="javascript">
                  window.location = "/login.htm";
              </script>';
    exit;
}
define("ADMINUSER", $config['system']['username']);
define("ADMINPASSWORD", base64_decode($config['system']['password']));

if( ($_SESSION['adminUser'] != ADMINUSER) || ($_SESSION['adminPassword'] != ADMINPASSWORD) ) {
    header("Location: login.htm");
    exit;
/* send user to initial wizard if it hasn't been completed yet */
}elseif (file_exists("/conf/set_wizard_initial")) {
         header("Location: wizard_initial.php");
}else{?>

<?php }?>
