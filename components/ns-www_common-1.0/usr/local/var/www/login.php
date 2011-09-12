#!/bin/php

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" 
"http://www.w3.org/TR/html4/strict.dtd">

<html>
<head>
<title>NSWall User Login</title>

<link href="gui.css" rel="stylesheet" type="text/css">
<link type="text/css" href="style/jquery-ui-1.7.2.custom.css"
        rel="stylesheet" />

<STYLE type="text/css">
/*  Base Rules
------------------------------------------------------------------------------*/
body {
        background: #c4c2be;
        font: 75% sans-serif;
}

a {
        color: #36c;
        text-decoration: none;
}

a:hover {
        color: #36f;
        text-decoration: underline;
        cursor: pointer;
}

img {
        border: 0;
}

p {
        margin: 0 0 15px;
}

.badtext {
        color: red;
}

.goodtext {
        color: green;
}

.smalltext {
        font-size: 90%;
}

/*  Primary Containers
------------------------------------------------------------------------------*/
#wrapper {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 650px;
        margin: -225px 0 0 -325px;
}

#window {
        float: left;
        width: 100%;
        padding: 5px;
        border: 1px solid #999;
        background: #eae9e5;
}

#banner {
        position: relative;
        float: left;
        width: 100%;
        margin-bottom: 5px;
        background: #738495 url(../images/background_banner.png);
}

#sidebar {
        float: left;
        width: 170px;
        padding: 15px;
}

#deviceinfo {
        width: 100%;
        overflow: hidden;
}

#message {
        display: none;
}

#loginform {
        margin: 0;
        padding: 0;
}

iframe {
        float: right;
        width: 445px;
        height: 325px;
        border: 1px solid #bebebe;
        background: #fff;
        color: #333;
}

#copyright {
        float: left;
        width: 100%;
        margin-top: 5px;
        color: #333;
        text-align: center;
}

/* Banner */
#banner #logo {
        float: left;
        margin: 10px;
}

#banner h1 {
        margin: 20px 0 0 220px;
        font-size: 136%;
        color: #dce0e4;
}

#banner h1 sup {
        margin-left: 2px;
        font-size: small;
}

#banner h2 {
        margin: 0 0 10px 220px;
        font-size: 122%;
        color: #b9c2ca;
}

#banner h3 {
        position: absolute;
        left: 70px;
        top: 32px;
        font-size: 85%;
        color: #fff;
}

/* Sidebar */
#deviceinfo label {
        display: block;
        margin-bottom: 5px;
        border-bottom: 1px dotted #ccc;
        font-weight: bold;
}

#deviceinfo p {
        color: #666;
}

/* Login Form */
#loginform label {
        display: block;
        margin-bottom: 3px;
        font-weight: bold;
}

#loginform input {
        width: 168px;
        margin-bottom: 10px;
        padding: .25em 0;
        border: 1px solid #bebebe;
        background: #fff9db;
        color: #333;
        font-size: 107%;
}

/* Copyright */
#copyright a {
        line-height: 1.5em;
}

/*  Modal
------------------------------------------------------------------------------*/
#modal {
        position: fixed;
        z-index: 200;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
}

#modal .overlay {
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: #c4c2be;
        opacity: .75;
}

#modal .overlay {
        filter: alpha(opacity =   75);
}

#modal .content {
        position: fixed;
        top: 50%;
        left: 50%;
        width: 410px;
        margin: -100px 0 0 -225px;
        padding: 20px;
        border: 1px solid #999;
        background: #fff;
}

/*  IE6 Hacks
------------------------------------------------------------------------------*/
* html {
        height: 100%;
}

* html body {
        height: 100%;
}

* html #modal {
        position: absolute;
        height: expression(document.body.scrollHeight >     document.body.offsetHeight ?
                  
                 document.body.scrollHeight :     document.body.offsetHeight +    
                'px');
}

* html #modal .overlay {
        position: absolute;
}

* html #modal .content {
        position: absolute;
        top: expression((   document.documentElement.clientHeight/ 2) -     100
                +   
                 'px' );
        margin-top: 0;
}
</STYLE>

</head>

<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="js/ui.core.js"></script>
<script type="text/javascript" src="js/ui.dialog.js"></script>
<script type="text/javascript" src="js/jquery.form.js"></script>
<script language="javascript">

     $(document).ready(function() {
                    
                                $.ui.dialog.defaults.bgiframe = true;
                                $("#login_nswall").dialog({ 
                    width: 650, 
                    height: 490, 
                    hide: 'scale', 
                    show: 'scale', 
                    resizable: false, 
                    draggable: false, 
                    closeOnEscape: false, 
                    modal: true,
                    open: function(event, ui) {
                        $(".ui-dialog-titlebar-close").hide();
                        $(".ui-dialog-titlebar").css('display','none');
                    }
                });

        // When a user clicks on the submit button, post the form.
          $("#submitbutton").click(function () {
               var QueryString = $("#login").serialize();
               $.post("do-login.php", QueryString, function(output) {
                    $("#login_nswall").html(output);
               });
           return false;
          });
     });
</script>

<body>
<div id="login_nswall" title="NSWall Login">
<div id="wrapper">
<div id="window">
<div id="banner"><!--[if gt IE 6]><!--> <img
        alt="Northshore Software Logo" src="../images/logo.jpg" width="50px"
        height="50px" id="logo"> <!--<![endif]--> <!--[if IE 6]>
                    <img id="logo" src="../images/logo.jpg" width="50px" height="50px" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='../images/logo.jpg',sizingMethod='auto');" alt="Northshore Software Logo">
                <![endif]-->
<h1>NSWall<sup>&reg;</sup> Web Interface</h1>
<h2>Northshore Software, Inc.</h2>
<!--<h3>IT Agility. Your Way.&trade;</h3>--></div>
<div id="sidebar">
<div id="deviceinfo"><label>Hostname</label>
<p title="cm.northshoresoftware.com">cm.northshoresoftware.com</p>
<label>IP Address</label>
<?php
     exec('/sbin/ifconfig | /usr/bin/grep ^[a-z] | /usr/bin/cut -d : -f 1', &$interfaces);
     foreach ($interfaces as $interface) {
         exec("/usr/bin/netstat -nI " . escapeshellarg($interface) . " -f inet | /usr/bin/awk '{ print \$4 }' | /usr/bin/grep '^[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\$' | /usr/bin/tail -1", &$iplist);
         foreach ($iplist as $line) {
             $ip_1 = $line;
             $ip_0 = sprintf("%u", ip2long($ip_1));
             if (($ip_0 >= 2130706432) && ($ip_0 < 2147483648)) { 
                 // 127.0.0.0/8 
             } elseif (($ip_0 >= 167772160) && ($ip_0 < 184549376)) {
                 // 10.0.0.0/8
             } elseif (($ip_0 >= 2886729728) && ($ip_0 < 2887843840)) {
                 // 172.16.0.0/12
             } elseif (($ip_0 >= 3232235520) && ($ip_0 < 3232301056)) { 
                 // 192.168.0.0/16
             } elseif (($ip_0 >= 2851995648) && ($ip_0 < 2852061184)) {
                 // 169.254.0.0/16
             } else {
                 // we could do more stuff there, probably should, for multicast, etc
                 $wanip = "$ip_1"; 
             }
         }
     }
     echo '<p title="' . $wanip . '">' . $wanip . '</p>';
?>
</div>
<p class="badtext" id="message"></p>
<form style="display: block;" id="login" name="loginform" id="login">
<label>Username</label> <input type="text" autocomplete="off"
        tabindex="1" id="username" name="username" class="login"> <label>Password</label>
<input type="password" autocomplete="off" tabindex="2" id="password"
        name="password" class="login">
<button tabindex="3" id="submitbutton" type="submit">Log in</button>

</form>
</div>
<iframe scrolling="auto" frameborder="no" name="contentframe"
        id="contentframe">Welcome to NSWall WebUI, please enter your
username and password and click the Login button</iframe></div>
</div>
</div>
</body>
</html>
