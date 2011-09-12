<?php include('header.php'); ?>
	<script type="text/javascript" src="js/ui/ui.draggable.js"></script>

	<script type="text/javascript" src="js/ui/ui.resizable.js"></script>
	<script type="text/javascript" src="js/ui/ui.dialog.js"></script>
	<script type="text/javascript" src="js/ui/effects.core.js"></script>
	<script type="text/javascript" src="js/ui/effects.highlight.js"></script>
	<script type="text/javascript" src="js/bgiframe.js"></script>

	<script type="text/javascript">
	$(function() {
		
		var name = $("#name"),
			email = $("#email"),
			password = $("#password"),
			allFields = $([]).add(name).add(email).add(password),
			tips = $("#validateTips");

		function updateTips(t) {
			tips.text(t).effect("highlight",{},1500);
		}

		function checkLength(o,n,min,max) {

			if ( o.val().length > max || o.val().length < min ) {
				o.addClass('ui-state-error');
				updateTips("Length of " + n + " must be between "+min+" and "+max+".");
				return false;
			} else {
				return true;
			}

		}

		function checkRegexp(o,regexp,n) {

			if ( !( regexp.test( o.val() ) ) ) {
				o.addClass('ui-state-error');
				updateTips(n);
				return false;
			} else {
				return true;
			}

		}
		
		$("#dialog").dialog({
			bgiframe: true,
			autoOpen: false,
			height: 330,
			modal: true,
			buttons: {
				'Create an account': function() {
					var bValid = true;
					allFields.removeClass('ui-state-error');

					bValid = bValid && checkLength(name,"username",3,16);
					bValid = bValid && checkLength(email,"email",6,80);
					bValid = bValid && checkLength(password,"password",5,16);

					bValid = bValid && checkRegexp(name,/^[a-z]([0-9a-z_])+$/i,"Username may consist of a-z, 0-9, underscores, begin with a letter.");
					// From jquery.validate.js (by joern), contributed by Scott Gonzalez: http://projects.scottsplayground.com/email_address_validation/
					bValid = bValid && checkRegexp(email,/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i,"eg. office@webdesignchimera.com");
					bValid = bValid && checkRegexp(password,/^([0-9a-zA-Z])+$/,"Password field only allow : a-z 0-9");
					
					if (bValid) {
						$('#users tbody').append('<tr>' +
							'<td>' + name.val() + '</td>' + 
							'<td>' + email.val() + '</td>' + 
							'<td>' + password.val() + '</td>' +
							'</tr>'); 
						$(this).dialog('close');
					}
				},
				Cancel: function() {
					$(this).dialog('close');
				}
			},
			close: function() {
				allFields.val('').removeClass('ui-state-error');
			}
		});
		
		
		
		$('#create-user').click(function() {
			$('#dialog').dialog('open');
		})
		.hover(
			function(){ 
				$(this).addClass("ui-state-hover"); 
			},
			function(){ 
				$(this).removeClass("ui-state-hover"); 
			}
		).mousedown(function(){
			$(this).addClass("ui-state-active"); 
		})
		.mouseup(function(){
				$(this).removeClass("ui-state-active");
		});

	});
	</script>


		<div id="sub-nav"><div class="page-title">
			<h1>Modal dialog UI form</h1>
			<span><a href="#" title="Widgets">Widgets</a> > Add to table modal</span>
		</div>
<?php include('top_buttons.php'); ?></div>
		<div id="page-layout"><div id="page-content">
			<div id="page-content-wrapper">
				<div class="inner-page-title">
					<h2>Example 1</h2>
					<span>Use a modal dialog to require that the user enter data during a multi-step process.</span>
				</div>
				<div class="content-box">
					<div id="dialog" title="Create new user">
						<p id="validateTips">All form fields are required.</p>
					
						<form>
					
						<fieldset>
							<ul>
								<li>
									<label class="desc" for="name">Name</label>
									<input type="text" name="name" id="name" class="field text full" />
								</li>
								<li>
									<label class="desc" for="email">Email</label>
									<input type="text" name="email" id="email" value="" class="field text full" />
								</li>
								<li>
									<label class="desc" for="password">Password</label>
									<input type="password" name="password" id="password" value="" class="field text full" />
								</li>
							</ul>
					
						</fieldset>
						</form>
					</div>
					
					
					<div id="users-contain" class="ui-widget">
					
							<h2>Existing Users:</h2>
					
						<div class="hastable">
							<table id="users">
								<thead>
						
									<tr>
										<th>Name</th>
										<th>Email</th>
										<th>Password</th>
									</tr>
								</thead>
								<tbody>
						
									<tr>
										<td>John Doe</td>
										<td>john.doe@example.com</td>
										<td>johndoe1</td>
									</tr>
								</tbody>
							</table>
						</div>
					
					</div>
					<br />
					<button id="create-user" class="ui-button float-right ui-state-default ui-corner-all">Create new user</button>

					<div class="clearfix"></div>
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