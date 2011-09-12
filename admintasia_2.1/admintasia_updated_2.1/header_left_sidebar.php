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
	
	<link href="css/ui/ui.base.css" rel="stylesheet" media="all" />

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
<body id="sidebar-left">
	<div id="page_wrapper">
		<div id="page-header">
			<div id="page-header-wrapper">
				<div id="top">
					<a href="dashboard.php" class="logo" title="Admintasia V2.0">Admintasia V2.0</a>
					<div class="welcome">
						<span class="note">Welcome, <a href="#" title="Welcome, Horia Simon">Horia Simon</a></span>
						<a class="btn ui-state-default ui-corner-all" href="#">
							<span class="ui-icon ui-icon-wrench"></span>
							Settings
						</a>
						<a class="btn ui-state-default ui-corner-all" href="#">
							<span class="ui-icon ui-icon-person"></span>
							My account
						</a>
						<a class="btn ui-state-default ui-corner-all" href="#">
							<span class="ui-icon ui-icon-power"></span>
							Logout
						</a>						
					</div>
				</div>
				<ul id="navigation">
					<li>
						<a href="dashboard.php" class="sf-with-ul">Dashboard</a>
						<ul>
							<li><a href="dashboard.php">Administration</a></li>
							<li>
								<a href="forms.php">Forms</a>
								<ul>
									<li><a href="validate.php">Form validation</a></li>
									<li><a href="table_modal.php"><b>Add to table modal</b></a></li>
									<li><a href="editinplace.php"><b>Edit in Place</b></a></li>
									<li><a href="tinymce.php"><b>WYSIWYG Editor</b></a></li>
								</ul>
							</li>
							<li>
								<a href="tables.php">Tables</a>
								<ul>
									<li><a href="tables.php">Sortable Tables</a></li>
									<li><a href="flexigrid.php"><b>FlexiGrid</b></a></li>
								</ul>
							</li>
							<li>
								<a href="#">Widgets</a>
								<ul>
									<li><a href="accordion.php">Accordion</a></li>
									<li><a href="flexigrid.php"><b>FlexiGrid</b></a></li>
									<li><a href="editinplace.php"><b>Edit in Place</b></a></li>
									<li><a href="tinymce.php"><b>WYSIWYG Editor</b></a></li>
									<li><a href="charts.php"><b>Charts</b></a></li>
									<li><a href="tabs.php">Tabs</a></li>
									<li><a href="slider.php">Slider</a></li>
									<li><a href="datepicker.php">Datepicker</a></li>
									<li><a href="progress.php">Progress Bar</a></li>
									<li><a href="dialog.php">Dialogs and Modals</a></li>
									<li><a href="overlays.php">Overlays</a></li>
									<li><a href="photo_manager.php">Photo Manager</a></li>
									<li><a href="file_browser.php">File Browser</a></li>
								</ul>
							</li>
							<li><a href="msg.php">Response Messages</a></li>
							<li><a href="icons.php">Icons</a></li>
							<li><a href="index.php">Login Page</a></li>
							<li><a href="icons.php">Buttons and Elements</a></li>
						</ul>
					</li>
					<li>
						<a href="#" class="sf-with-ul">Unlimited Levels</a>
						<ul>
							<li>
								<a href="#" class="sf-with-ul">Menu item 1</a>
								<ul>
									<li><a href="#">Subitem 1</a></li>
									<li><a href="#">Subitem 2</a></li>
								</ul>
							</li>
							<li>
								<a href="#">Menu item 2</a>
							</li>
							<li>
								<a href="#">Menu item 3</a>
							</li>
							<li>
								<a href="#" class="sf-with-ul">Menu item 4</a>
								<ul>
									<li><a href="#">Subitem 1</a></li>
									<li>
										<a href="#" class="sf-with-ul">Subitem 2</a>
										<ul>
											<li><a href="#">Subitem 1</a></li>
											<li>
												<a href="#" class="sf-with-ul">Subitem 2</a>
												<ul>
													<li><a href="#">Subitem 1</a></li>
													<li>
														<a href="#">Subitem 2</a>
													</li>
												</ul>
											</li>
										</ul>
									</li>
								</ul>
							</li>
							<li>
								<a href="#" class="sf-with-ul">Menu item 5</a>
								<ul>
									<li><a href="#">Subitem 1</a></li>
									<li><a href="#">Subitem 2</a></li>
								</ul>
							</li>
							<li>
								<a href="#">Menu item 6</a>
							</li>
							<li>
								<a href="#">Menu item 7</a>
							</li>
						</ul>
					</li>
					<li><a href="gallery.php">Photo Gallery</a></li>
					<li>
						<a href="#" class="sf-with-ul">Layout Options</a>
						<ul>
							<li>
								<a href="three-columns-layout.php">Three columns</a>
							</li>
							<li>
								<a href="two-column-layout.php">Two columns</a>
							</li>
							<li>
								<a href="no-rounded.php">No rounded corners</a>
							</li>
							<li>
								<a href="content_boxes.php">Available content boxes</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#" class="sf-with-ul">Theme Options</a>
						<ul>
							<li>
								<a href="page_left_sidebar.php">Page with left sidebar</a>
							</li>
							<li>
								<a href="page_dynamic_sidebar.php">Page with dynamic sidebar</a>
							</li>
							<li>
								<a href="#">Avaiable Themes</a>
								<ul id="style-switcher">
									<li>
										<a class="set_theme" id="black_rose" href="#" title="Black Rose Theme">Black Rose Theme</a>
									</li>
									<li>
										<a class="set_theme" id="gray_standard" href="#" title="Gray Standard Theme">Gray Standard Theme</a>
									</li>
									<li>
										<a class="set_theme" id="gray_lightness" href="#" title="Gray Lightness Theme">Gray Lightness Theme</a>
									</li>
									<li>
										<a class="set_theme" id="apple_pie" href="#" title="Apple Pie Theme">Apple Pie Theme</a>
									</li>
									<li>
										<a class="set_theme" id="blueberry" href="#" title="Blueberry Theme">Blueberry Theme</a>
									</li>
								</ul>
							</li>
							<li>
								<a href="#"><i>Dummy Link</i></a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#" class="sf-with-ul">Widgets</a>
						<ul>
							<li><a href="accordion.php">Accordion</a></li>
							<li><a href="flexigrid.php"><b>FlexiGrid</b></a></li>
							<li><a href="editinplace.php"><b>Edit in Place</b></a></li>
							<li><a href="tinymce.php"><b>WYSIWYG Editor</b></a></li>
							<li><a href="charts.php"><b>Charts</b></a></li>
							<li><a href="tabs.php">Tabs</a></li>
							<li><a href="slider.php">Slider</a></li>
							<li><a href="datepicker.php">Datepicker</a></li>
							<li><a href="progress.php">Progress Bar</a></li>
							<li><a href="dialog.php">Dialogs and Modals</a></li>
							<li><a href="overlays.php">Overlays</a></li>
							<li><a href="photo_manager.php">Photo Manager</a></li>
							<li><a href="file_browser.php">File Browser</a></li>
						</ul>
					</li>
				</ul>
				<div id="search-bar">
					<form method="post" action="http://www.google.com/">
						<input type="text" name="q" value="live search demo" />
					</form>
				</div>
			</div>
		</div>