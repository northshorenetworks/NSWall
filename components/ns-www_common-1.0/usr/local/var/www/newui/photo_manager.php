<?php include('header.php'); ?>
		<script type="text/javascript" src="js/ui/ui.draggable.js"></script>
		<script type="text/javascript" src="js/ui/ui.droppable.js"></script>
		<script type="text/javascript" src="js/ui/ui.resizable.js"></script>
		<script type="text/javascript" src="js/ui/ui.dialog.js"></script>

		<style type="text/css">
			#gallery { float: left; width: 45%; min-height: 12em; } * html #gallery { height: 12em; } /* IE6 */
			.gallery.custom-state-active { background: #eee; }
			.gallery { padding:5px; }
			.gallery h5 { margin:0 0 0.4em; }
			.gallery li { float: left; width: 96px; padding: 0.4em; margin: 0 0.4em 0.4em 0; text-align: center; }
			#trash .ui-draggable h5 { display:none;margin: 0 0 0.4em; cursor: move; }
			.gallery li a { float: right; }
			.gallery li a.ui-icon-zoomin { float: left; }
			.gallery li img { width: 100%; cursor: move; }

			#trash { float:right;width: 52%;min-height: 18em;} * html #trash { height: 18em; } /* IE6 */
		</style>
		<script type="text/javascript">
			$(function() {
				// there's the gallery and the trash
				var $gallery = $('#gallery'), $trash = $('#trash');

				// let the gallery items be draggable
				$('li',$gallery).draggable({
					cancel: 'a.ui-icon',// clicking an icon won't initiate dragging
					revert: 'invalid', // when not dropped, the item will revert back to its initial position
					containment: $('#demo-frame').length ? '#demo-frame' : 'document', // stick to demo-frame if present
					helper: 'clone',
					cursor: 'move'
				});

				// let the trash be droppable, accepting the gallery items
				$trash.droppable({
					accept: '#gallery > li',
					activeClass: 'ui-state-highlight',
					drop: function(ev, ui) {
						deleteImage(ui.draggable);
					}
				});

				// let the gallery be droppable as well, accepting items from the trash
				$gallery.droppable({
					accept: '#trash li',
					activeClass: 'custom-state-active',
					drop: function(ev, ui) {
						recycleImage(ui.draggable);
					}
				});

				// image deletion function
				var recycle_icon = '<a href="link/to/recycle/script/when/we/have/js/off" title="Recycle this image" class="ui-icon ui-icon-refresh">Recycle image</a>';
				function deleteImage($item) {
					$item.fadeOut(function() {
						var $list = $('ul',$trash).length ? $('ul',$trash) : $('<ul class="gallery ui-helper-reset"/>').appendTo($trash);

						$item.find('a.ui-icon-trash').remove();
						$item.append(recycle_icon).appendTo($list).fadeIn(function() {
							$item.animate({ width: '48px' }).find('img').animate({ height: '36px' });
						});
					});
				}

				// image recycle function
				var trash_icon = '<a href="link/to/trash/script/when/we/have/js/off" title="Delete this image" class="ui-icon ui-icon-trash">Delete image</a>';
				function recycleImage($item) {
					$item.fadeOut(function() {
						$item.find('a.ui-icon-refresh').remove();
						$item.css('width','96px').append(trash_icon).find('img').css('height','72px').end().appendTo($gallery).fadeIn();
					});
				}

				// image preview function, demonstrating the ui.dialog used as a modal window
				function viewLargerImage($link) {
					var src = $link.attr('href');
					var title = $link.siblings('img').attr('alt');
					var $modal = $('img[src$="'+src+'"]');

					if ($modal.length) {
						$modal.dialog('open')
					} else {
						var img = $('<img alt="'+title+'" width="384" height="288" style="display:none;padding: 8px;" />')
							.attr('src',src).appendTo('body');
						setTimeout(function() {
							img.dialog({
									title: title,
									width: 515,
									modal: true
								});
						}, 1);
					}
				}

				// resolve the icons behavior with event delegation
				$('ul.gallery > li').click(function(ev) {
					var $item = $(this);
					var $target = $(ev.target);

					if ($target.is('a.ui-icon-trash')) {
						deleteImage($item);
					} else if ($target.is('a.ui-icon-zoomin')) {
						viewLargerImage($target);
					} else if ($target.is('a.ui-icon-refresh')) {
						recycleImage($item);
					}

					return false;
				});
			});
		</script>


		<div id="sub-nav"><div class="page-title">
			<h1>Photo Manager</h1>
			<span><a href="#" title="Others">Others</a> > <a href="#" title="Gallery">Gallery</a> > Photo Manager</span>
		</div>
<?php include('top_buttons.php'); ?></div>
		<div id="page-layout"><div id="page-content">
			<div id="page-content-wrapper">
				<div class="inner-page-title">
					<h3>A gallery example</h3>
				</div>
				<div class="content-box">
					<div class="demo ui-widget ui-helper-clearfix">
			
						<ul id="gallery" class="gallery ui-helper-reset ui-helper-clearfix">
							<li class="ui-widget-content ui-corner-tr">
								<h5 class="ui-widget-header">High Tatras</h5>
								<img src="http://farm4.static.flickr.com/3261/2538183196_8baf9a8015_s.jpg" alt="The peaks of High Tatras" width="96" height="72" />
								<a href="http://farm4.static.flickr.com/3261/2538183196_8baf9a8015.jpg" title="View larger image" class="ui-icon ui-icon-zoomin">View larger</a>
			
								<a href="link/to/trash/script/when/we/have/js/off" title="Delete this image" class="ui-icon ui-icon-trash">Delete image</a>
							</li>
							<li class="ui-widget-content ui-corner-tr">
								<h5 class="ui-widget-header">High Tatras 2</h5>
								<img src="http://farm4.static.flickr.com/3205/2538164270_4369bbdd23_s.jpg" alt="The chalet at the Green mountain lake" width="96" height="72" />
								<a href="http://farm4.static.flickr.com/3205/2538164270_4369bbdd23.jpg" title="View larger image" class="ui-icon ui-icon-zoomin">View larger</a>
								<a href="link/to/trash/script/when/we/have/js/off" title="Delete this image" class="ui-icon ui-icon-trash">Delete image</a>
			
							</li>
							<li class="ui-widget-content ui-corner-tr">
								<h5 class="ui-widget-header">High Tatras 3</h5>
								<img src="http://farm3.static.flickr.com/2167/2082738157_436d1eb280_s.jpg" alt="Planning the ascent" width="96" height="72" />
								<a href="http://farm3.static.flickr.com/2167/2082738157_436d1eb280.jpg" title="View larger image" class="ui-icon ui-icon-zoomin">View larger</a>
								<a href="link/to/trash/script/when/we/have/js/off" title="Delete this image" class="ui-icon ui-icon-trash">Delete image</a>
							</li>
			
							<li class="ui-widget-content ui-corner-tr">
								<h5 class="ui-widget-header">High Tatras 4</h5>
								<img src="http://farm3.static.flickr.com/2342/2083508720_fa906f685e_s.jpg" alt="On top of Kozi kopka" width="96" height="72" />
								<a href="http://farm3.static.flickr.com/2342/2083508720_fa906f685e.jpg" title="View larger image" class="ui-icon ui-icon-zoomin">View larger</a>
								<a href="link/to/trash/script/when/we/have/js/off" title="Delete this image" class="ui-icon ui-icon-trash">Delete image</a>
							</li>
						</ul>
			
						<div id="trash" class="portlet ui-widget ui-widget-content ui-helper-clearfix ui-corner-all">
							<div class="portlet-header ui-widget-header">
								Shopping
								<span class="ui-icon ui-icon-circle-arrow-s"></span>
							</div>
						</div>
			
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