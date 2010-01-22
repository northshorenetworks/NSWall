<?php 
/*
	$Id: interfaces_wlan.inc,v 1.1.1.1 2008/08/01 07:56:20 root Exp $
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

$wlchannels = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14);

function wireless_config_init() {
	global $optcfg, $pconfig;
	
        $pconfig['ifmode'] = $optcfg['wireless']['ifmode'];
	$pconfig['mode'] = $optcfg['wireless']['mode'];
	$pconfig['ssid'] = $optcfg['wireless']['ssid'];
	$pconfig['channel'] = $optcfg['wireless']['channel'];
	$pconfig['encmode'] = $optcfg['wireless']['encmode'];
	$pconfig['wep_enable'] = isset($optcfg['wireless']['wep']['enable']);
	$pconfig['wpa_enable'] = isset($optcfg['wireless']['wpa']['enable']);
	$pconfig['wpacipher'] = $optcfg['wireless']['wpacipher'];
	$pconfig['wpamode'] = $optcfg['wireless']['wpamode'];
	$pconfig['wpapsk'] = $optcfg['wireless']['wpapsk'];

	if (is_array($optcfg['wireless']['wep']['key'])) {
		$i = 1;
		foreach ($optcfg['wireless']['wep']['key'] as $wepkey) {
			$pconfig['key' . $i] = $wepkey['value'];
			if (isset($wepkey['txkey']))
				$pconfig['txkey'] = $i;
			$i++;
		}
		if (!isset($wepkey['txkey']))
			$pconfig['txkey'] = 1;
	}
}

function wireless_config_post() {
	global $optcfg, $pconfig;

	unset($input_errors);

	/* input validation */
	if ($_POST['enable']) {
		$reqdfields = explode(" ", "ssid channel");
		$reqdfieldsn = explode(",", "SSID,Channel");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
		
		if (!$input_errors) {
			/* bridge check (hostap only!) */
			if ($pconfig['bridge'] && ($pconfig['mode'] != "hostap"))
				$input_errors[] = "Bridging a wireless interface is only possible in hostap mode.";
		}
	}

	if (!$input_errors) {
	
		unset($optcfg['wireless']);

	        $optcfg['wireless']['ifmode'] = $_POST['ifmode'];
                $optcfg['wireless']['mode'] = $_POST['mode'];
		$optcfg['wireless']['ssid'] = $_POST['ssid'];
		$optcfg['wireless']['channel'] = $_POST['channel'];
		$optcfg['wireless']['encmode'] = $_POST['encmode'];
		if ($optcfg['wireless']['encmode'] == 'wpa') {
			$optcfg['wireless']['wpa']['enable'] = $_POST['wpa_enable'] ? true : false;
                	$optcfg['wireless']['wpamode'] = $_POST['wpamode'];
                	$optcfg['wireless']['wpacipher'] = $_POST['wpacipher'];
                	$optcfg['wireless']['wpapsk'] = $_POST['wpapsk'];
		}
		if ($optcfg['wireless']['encmode'] == 'wep') {
			$optcfg['wireless']['wep']['enable'] = $_POST['wep_enable'] ? true : false;
			$optcfg['wireless']['wep']['key'] = array();
			for ($i = 1; $i <= 4; $i++) {
				if ($_POST['key' . $i]) {
					$newkey = array();
					$newkey['value'] = $_POST['key' . $i];
					if ($_POST['txkey'] == $i)
						$newkey['txkey'] = true;
					$optcfg['wireless']['wep']['key'][] = $newkey;
				}
			}
		}
	}
	return $input_errors;
}

function wireless_config_print() {
	global $optcfg, $pconfig, $wlchannels;
?>
                <div>
                    <label for="ssid">SSID</label>
                    <input name="ssid" type="text" class="formfld" id="ssid" size="20" value="<?=htmlspecialchars($pconfig['ssid']);?>">
                </div>
                <div>
                    <label for="channel">Channel</label>
                    <select name="channel" class="formfld" id="channel">
                      <?php
	                  foreach ($wlchannels as $channel): ?>
                              <option <?php if ($channel == $pconfig['channel']) echo "selected";?> value="<?=$channel;?>">
                                  <?=$channel;?>
                              </option>
                          <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="encmode">Encryption</label>
                    <select name="encmode" class="formfld" id="encmode">
                      <?php $modes = array('open' => 'Open System', 'wep' => 'WEP', 'wpa' => 'WPA');
                                foreach ($modes as $mode => $modename): ?>
                                        <option value="<?=$mode;?>" <?php if ($mode == $pconfig['encmode']) echo "selected"; ?>>
                                        <?=htmlspecialchars($modename);?>
                                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="wpadiv">
                  <label for="wpamode">WPA Mode</label>
                      <select name="wpamode" class="formfld" id="mode">
                          <?php 
			      $opts = array();
                              $opts[] = "Auto";		
			      $opts[] = "WPA-1";
			      $opts[] = "WPA-2";
			      foreach ($opts as $opt): ?>
                                  <option <?php if ($opt == $pconfig['wpamode']) echo "selected";?>> 
                                      <?=htmlspecialchars($opt);?>
                                  </option>
                              <?php endforeach; ?>
                    </select><br><br>
                    <label for="wpacipher">WPA Cipher</label>
                        <select name="wpacipher" class="formfld" id="mode">
                            <?php 
			        $opts = array();
                                $opts[] = "Auto";		
			        $opts[] = "TKIP";
			        $opts[] = "CCMP";
			        foreach ($opts as $opt): ?>
                                    <option <?php if ($opt == $pconfig['wpacipher']) echo "selected";?>> 
                                        <?=htmlspecialchars($opt);?>
                                    </option>
                                <?php endforeach; ?>
                        </select><br><br>
                    <label for="wpacipher">Preshared Key</label>
                        <input name="wpapsk" type="password" class="formfld" id="wpapsk" size="30" value="<?=htmlspecialchars($pconfig['wpapsk']);?>">
                </div>
<?php } ?>
