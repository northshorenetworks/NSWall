<?php include('header.php'); ?>
<script type="text/javascript" src="js/file_tree.js"></script>
<link href="css/file_tree.css" rel="stylesheet" media="all" />
		<script type="text/javascript">
			
			$(document).ready( function() {
			    $('#fileTreeDemo_1').fileTree({ root: 'file_tree_example/' }, function(file) {
			        alert(file);
			    });			
			});
		</script>

		<div id="sub-nav"><div class="page-title">
			<h1>File Tree Example</h1>
			<span><a href="#" title="Others">Others</a> > <a href="#" title="Gallery">Gallery</a> > All Photos</span>
		</div>
<?php include('top_buttons.php'); ?></div>
		<div id="page-layout"><div id="page-content">
			<div id="page-content-wrapper">

				<div class="content-box">
					<div class="example">
						<h2>Default options</h2>
						<br />
						<div id="fileTreeDemo_1" class="demo"></div>
					</div>
				</div>
				<div class="clearfix"></div>
				<?php include('sidebar.php'); ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
<?php include('footer.php'); ?></div>
</body>
</html>