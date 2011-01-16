<?php
/*
 $Id: globals.inc,v 1.2 2009/02/24 06:12:12 jrecords Exp $
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

$g = array(
    "varrun_path" => "/var/run",
    "varetc_path" => "/etc",
    "vardb_path" => "/var/db",
    "varlog_path" => "/var/log",
    "bsd_path" => "/bsd",
    "etc_path" => "/etc",
    "tmp_path" => "/tmp",
    "conf_path" => "/conf",
    "ftmp_path" => "/ftmp",
    "conf_default_path" => "/conf.default",
    "cf_path" => "/conf",
    "cf_conf_path" => "/conf",
    "www_path" => "/usr/www",
    "captiveportal_path" => "/usr/captiveportal",
    "captiveportal_element_path" => "/var/db/cpelements",
    "captiveportal_element_sizelimit" => 262144,
    "xml_rootobj" => "nswall",
    "pppoe_interface" => "ng0",
    "n_pptp_units" => 10,
    "debug" => true,
    "latest_config" => "1.6",
    "nopccard_platforms" => array("wrap", "net48xx"),
    "wireless_regex" => "/^(ral|ath)/"
);

?>
