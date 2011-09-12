#!/bin/php
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>Admintasia v2</title>
	<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="js/ui/ui.core.js"></script>
	<script type="text/javascript" src="js/ui/ui.widget.js"></script>
	<script type="text/javascript" src="js/ui/ui.mouse.js"></script>
	<script type="text/javascript" src="js/superfish.js"></script>
	<script type="text/javascript" src="js/live_search.js"></script>
	<script type="text/javascript" src="js/tooltip.js"></script>
	<script type="text/javascript" src="js/cookie.js"></script>
	<script type="text/javascript" src="js/ui/ui.sortable.js"></script>
	<script type="text/javascript" src="js/ui/ui.draggable.js"></script>
	<script type="text/javascript" src="js/ui/ui.resizable.js"></script>
	<script type="text/javascript" src="js/ui/ui.position.js"></script>
	<script type="text/javascript" src="js/ui/ui.button.js"></script>
	<script type="text/javascript" src="js/ui/ui.dialog.js"></script>
	<script type="text/javascript" src="js/custom.js"></script>

	<script language="javascript">

     $(document).ready(function() {

        // When a user clicks on the submit button, post the form.
          $("#submitbutton").click(function () {
               var QueryString = $("#login_form").serialize();
               $.post("../do-login.php", QueryString, function(output) {
                   if(output == 'success') {
		   	window.location = "/#index"; 
		   } else {
			$(".response-msg").show();
		   }	
     	       });

               return false;
          });
     });
</script>

	
	<link href="css/ui/ui.base.css" rel="stylesheet" media="all" />

	<link href="css/ui/ui.login.css" rel="stylesheet" media="all" />

	<link href="css/themes/black_rose/ui.css" rel="stylesheet" media="all" />

	<link href="css/themes/black_rose/ui.css" rel="stylesheet" title="style" media="all" />

	<!--[if IE 6]>
	<link href="css/ie6.css" rel="stylesheet" media="all" />
	
	<script src="js/pngfix.js"></script>
	<script>
	  /* Fix IE6 Transparent PNG */
	  DD_belatedPNG.fix('.logo, .other ul#dashboard-buttons li a');

	</script>
	<![endif]-->
	<!--[if IE 7]>
	<link href="css/ie7.css" rel="stylesheet" media="all" />
	<![endif]-->
</head>
<body>
<div id="login_nswall" title="NSWall Login">
	<div id="page_wrapper">
		<div id="page-header">
			<div id="page-header-wrapper">
				<div id="top">
					<a href="#" class="logo" title="Admintasia V2.0">Admintasia V2.0</a>
				</div>
			</div>
		</div>
<script type="text/javascript" src="js/ui/ui.tabs.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	// Tabs
	$('#tabs, #tabs2, #tabs5').tabs();
});
</script>
		<div id="sub-nav">
			<div class="page-title">
				<h1>NSWall Login</h1>
				<span>Please enter your username and password</span>
			</div>
			<div id="dialog" title="Dialog Title">
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
			</div>
			<div id="modal_confirmation" title="An example modal title ?">
				<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
			</div>
		</div>
		<div class="clear"></div>
		<div id="page-layout">
			<div id="page-content">
				<div id="page-content-wrapper">

				<div id="tabs">
					<ul>

						<li><a href="#login">Login</a></li>
					</ul>
					<div id="login">
						<div class="response-msg error ui-corner-all" style="display:none;">
							<span>Login Failed</span>
							The Username/Password combination was incorrect!
						</div>
						<form id="login_form">
							<ul>
								<li>
									<label for="username" class="desc">
				
										Username:
									</label>
									<div>
										<input type="text" tabindex="1" maxlength="255" value="" class="field text full" name="username" id="username" />
									</div>
								</li>
								<li>
									<label for="password" class="desc">
										Password:
									</label>
				
									<div>
										<input type="password" tabindex="1" maxlength="255" value="" class="field text full" name="password" id="password" />
									</div>
								</li>
								<li class="buttons">
									<div>
										<button id="submitbutton" class="ui-state-default ui-corner-all float-right ui-button" type="submit">Go to dashboard</button>
									</div>
								</li>
							</ul>
						</form>
					</div>
				</div>



				</div>
				<div class="clear"></div>
			</div>
		</div>
	</div>
</body>
</html>
