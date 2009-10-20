<?php 

echo '<div style=""><label>Uptime:</label>';
				  	exec("/sbin/sysctl -n kern.boottime", $boottime);
					preg_match("/(\d+)/", $boottime[0], $matches);
					$boottime = $matches[1];
					$uptime = time() - $boottime;
					
					if ($uptime > 60)
						$uptime += 30;
					$updays = (int)($uptime / 86400);
					$uptime %= 86400;
					$uphours = (int)($uptime / 3600);
					$uptime %= 3600;
					$upmins = (int)($uptime / 60);
					
					$uptimestr = "";
					if ($updays > 1)
						$uptimestr .= "$updays days, ";
					else if ($updays > 0)
						$uptimestr .= "1 day, ";
					$uptimestr .= sprintf("%02d:%02d", $uphours, $upmins);
					echo htmlspecialchars($uptimestr);
				  echo '</div>
<div style=""><label>Load:</label>';
                     
                        exec("/sbin/sysctl -n vm.loadavg", $loadavgstr);
                        list($one, $five, $fifteen) = split(' ', $loadavgstr[0]);
                        echo htmlspecialchars("$one $five $fifteen");
                     
echo '</div>';
?>
