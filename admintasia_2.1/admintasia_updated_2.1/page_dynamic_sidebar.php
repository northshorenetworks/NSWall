<?php include('header.php'); ?>
<script type="text/javascript" src="js/sidebar_position.js"></script>
		<div id="sub-nav"><div class="page-title">
			<h1>Example Page</h1>
			<span><a href="#" title="Layout Options">Breadcrumb</a> > <a href="#" title="Elements">Elements</a> > Icons</span>
		</div>
<?php include('top_buttons.php'); ?></div>
		<div id="page-layout">
			<div id="page-content">
				<div id="page-content-wrapper">
					<div class="inner-page-title">
						<h2>Dynamic Sidebar Position</h2>
						<span>You can add an option with which visitors will be able to switch the position of the sidebar. The position is remembered on all pages.</span>
					</div>
					<div class="content-box content-box-header">
								<div class="content-box-wrapper">
									<h3>To see the dynamic sidebar in action, click the buttons below or the ones from the sidebar.</h3>
									<ul class="sidebar-position">
										<li class="float-left">
											<a title="Left Sidebar" id="sidebar-left" href="javascript:void(0);" class="btn ui-state-default ui-corner-all">
												<span class="ui-icon ui-icon ui-icon-arrowthick-1-w"></span>
												Left Sidebar
											</a>
										</li>
										<li class="float-right">
											<a title="Right Sidebar" id="sidebar-right" href="javascript:void(0);" class="btn ui-state-default ui-corner-all">
												<span class="ui-icon ui-icon ui-icon-arrowthick-1-e"></span>
												Right Sidebar
											</a>
										</li>
									</ul>
									<div class="clearfix"></div>
								</div>
							</div>
					<div class="clearfix"></div>
					<?php include('sidebar_dynamic.php'); ?>
				</div>
				<div class="clear"></div>
			</div>
		</div>
<?php include('footer.php'); ?></div>
</body>
</html>