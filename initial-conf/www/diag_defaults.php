#!/bin/php
<?php

$pgtitle = array("Diagnostics", "Factory defaults");

require("guiconfig.inc");

if ($_POST) {
        if ($_POST['Submit'] != " No ") {
                reset_factory_defaults();
                system_reboot();
                $rebootmsg = "The system has been reset to factory defaults and is now rebooting. This may take one minute.";
        } else {
                header("Location: index.php");
                exit;
        }
}
?>
<?php include("fbegin.inc"); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php
        $tabs = array('Ping' => 'diag_ping.php',
                          'Traceroute' => 'diag_traceroute.php',
                          'TCPDump' => 'diag_tcpdump.php',
                          'Backup/Restore' => 'diag_backup.php',
                          'Factory Default' => 'diag_defaults.php',
                          'Reboot' => 'reboot.php');
        dynamic_tab_menu($tabs);
?>
  </ul>
  </td></tr>
<tr>
<table width="100%" border="0" cellpadding="6" cellspacing="0">
<tr>
<?php if ($rebootmsg): echo print_info_box($rebootmsg); else: ?>
<form action="diag_defaults.php" method="post">
              <p><strong>If you click &quot;Yes&quot;, the firewall will be reset
                to factory defaults and will reboot immediately. The entire system
                configuration will be overwritten. The LAN IP address will be
                reset to 192.168.1.1, the system will be configured as a DHCP
                server, and the password will be set to 'mono'.<br>
                <br>
                Are you sure you want to proceed?</strong></p>
        <p>
          <input name="Submit" type="submit" class="formbtn" value=" Yes ">
          <input name="Submit" type="submit" class="formbtn" value=" No ">
        </p>
      
      </form>
<?php endif; ?>
</td></tr></table>
<?php include("fend.inc"); ?>
