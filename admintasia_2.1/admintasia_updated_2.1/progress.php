<?php include('header.php'); ?>
<script type="text/javascript" src="js/ui/ui.progressbar.js"></script>
	<script type="text/javascript">
	$(function() {
		$("#progressbar").progressbar({
			value: 37
		});
	});

	$(function() {
		$("#progressbar_animated, #progressbar_animated2").progressbar({
			value: 63
		});
	});
	</script>


		<div id="sub-nav"><div class="page-title">
			<h1>Slider</h1>
			<span><a href="#" title="Widgets">Widgets</a> > Slider</span>
		</div>
<?php include('top_buttons.php'); ?></div>
		<div id="page-layout"><div id="page-content">
			<div id="page-content-wrapper">
				<div class="inner-page-title">
					<h2>Static Progressbar</h2>
					<span>A simple static progress bar example</span>
				</div>
				<div id="progressbar"></div>
				<div class="clearfix"></div>
				<br /><br />
				<div class="inner-page-title">
					<h2>Animated Progressbar</h2>
					<span>An animated progress bar example</span>
				</div>
				<div id="progressbar_animated" class="progress_animated"></div>
				<div class="clearfix"></div>
				<div class="inner-page-title">
					<h3>Endless possibilities</h3>
				</div>
				<div id="progressbar_animated2" class="content-box progress_animated"></div>
				<div class="clearfix"></div>
				<?php include('sidebar.php'); ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
<?php include('footer.php'); ?></div>
</body>
</html>