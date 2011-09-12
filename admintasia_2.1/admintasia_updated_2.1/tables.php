<?php include('header_left_sidebar.php'); ?>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/tablesorter-pager.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	/* Table Sorter */
	$("#sort-table")
	.tablesorter({
		widgets: ['zebra'],
		headers: { 
		            // assign the secound column (we start counting zero) 
		            0: { 
		                // disable it by setting the property sorter to false 
		                sorter: false 
		            }, 
		            // assign the third column (we start counting zero) 
		            6: { 
		                // disable it by setting the property sorter to false 
		                sorter: false 
		            } 
		        } 
	})
	
	.tablesorterPager({container: $("#pager")}); 
	
	$(".header").append('<span class="ui-icon ui-icon-carat-2-n-s"></span>');

	
});

 	/* Check all table rows */

var checkflag = "false";
function check(field) {
if (checkflag == "false") {
for (i = 0; i < field.length; i++) {
field[i].checked = true;}
checkflag = "true";
return "check_all"; }
else {
for (i = 0; i < field.length; i++) {
field[i].checked = false; }
checkflag = "false";
return "check_none"; }
}


</script>
		<div id="sub-nav"><div class="page-title">
			<h1>Tables</h1>
			<span><a href="#" title="Layout Options">Home</a> > <a href="#" title="Two column layout">Dashboard</a> > Tables</span>
		</div>
<?php include('top_buttons.php'); ?></div>
		<div id="page-layout"><div id="page-content">
			<div id="page-content-wrapper">
				<div class="inner-page-title">
					<h2>Some Examples</h2>
					<span>Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit...</span>
				</div>
				<div class="hastable">
					<form name="myform" class="pager-form" method="post" action="">
						<table id="sort-table"> 
						<thead> 
						<tr>
							<th><input type="checkbox" value="check_none" onclick="this.value=check(this.form.list)" class="submit"/></th>
						    <th>Last Name</th> 
						    <th>First Name</th> 
						    <th>Email</th> 
						    <th>Due</th> 
						    <th>Web Site</th>
							<th style="width:128px">Options</th> 
						</tr> 
						</thead> 
						<tbody> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="3" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr> 
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td> 
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="3" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr> 
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="3" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr> 
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="3" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr> 
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="3" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr> 
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="1" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>John</td> 
						    <td>email@example.com</td> 
						    <td>$50.00</td> 
						    <td>http://www.example.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="2" name="list" class="checkbox"/></td> 
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="3" name="list" class="checkbox"/></td> 
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr> 
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Doe</td> 
						    <td>Jason</td> 
						    <td>jdoe@hotmail.com</td> 
						    <td>$100.00</td> 
						    <td>http://www.jdoe.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr> 
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Conway</td> 
						    <td>Tim</td> 
						    <td>tconway@earthlink.net</td> 
						    <td>$50.00</td> 
						    <td>http://www.timconway.com</td>
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						<tr>
							<td class="center"><input type="checkbox" value="4" name="list" class="checkbox"/></td>
						    <td>Bach</td> 
						    <td>Frank</td> 
						    <td><a href="#" title="test">fbach@yahoo.com</a></td> 
						    <td>$50.00</td> 
						    <td>http://www.frank.com</td> 
						    <td>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Edit this example" href="#">
									<span class="ui-icon ui-icon-wrench"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Favourite this example" href="#">
									<span class="ui-icon ui-icon-heart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Add to shopping card example" href="#">
									<span class="ui-icon ui-icon-cart"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all tooltip" title="Delete this example" href="#">
									<span class="ui-icon ui-icon-circle-close"></span>
								</a>
							</td>
						</tr>
						
						</tbody>
						</table>
						<div id="pager">
					
								<a class="btn_no_text btn ui-state-default ui-corner-all first" title="First Page" href="#">
									<span class="ui-icon ui-icon-arrowthickstop-1-w"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all prev" title="Previous Page" href="#">
									<span class="ui-icon ui-icon-circle-arrow-w"></span>
								</a>
							
								<input type="text" class="pagedisplay"/>
								<a class="btn_no_text btn ui-state-default ui-corner-all next" title="Next Page" href="#">
									<span class="ui-icon ui-icon-circle-arrow-e"></span>
								</a>
								<a class="btn_no_text btn ui-state-default ui-corner-all last" title="Last Page" href="#">
									<span class="ui-icon ui-icon-arrowthickstop-1-e"></span>
								</a>
								<select class="pagesize">
									<option value="10" selected="selected">10 results</option>
									<option value="20">20 results</option>
									<option value="30">30 results</option>
									<option value="40">40 results</option>
								</select>								
						</div>
					</form>
					<div class="clear"></div>
					<i class="note">Sort multiple columns simultaneously by holding down the shift key and clicking a second, third or even fourth column header!</i>
					<div class="inner-page-title">
						<h3>Another simple table</h3>
					</div>
					<table cellspacing="0">
						<thead>
							<tr>
								<td class="center"><input type="checkbox" class="checkbox" value=""/></td>
								<td>Name</td>
								<td>Email</td>
								<td>Website</td>
								<td>Description</td>
								<td>Options</td>
							</tr>
						</thead>
						<tbody>
							<tr>  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
							<tr class="alt">  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
							<tr>  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
							<tr class="alt">  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
						</tbody>
					</table>
					<br /><br />
					<div class="inner-page-title">
						<h3>Table inside a content box</h3>
					</div>
				<div class="content-box">
					<table cellspacing="0">
						<thead>
							<tr>
								<td class="center"><input type="checkbox" class="checkbox" value=""/></td>
								<td>Name</td>
								<td>Email</td>
								<td>Website</td>
								<td>Description</td>
								<td>Options</td>
							</tr>
						</thead>
						<tbody>
							<tr>  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
							<tr class="alt">  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
							<tr>  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
							<tr class="alt">  	  	
								<td>
									<input type="checkbox" class="checkbox" value=""/>
								</td>
								<td>
									John Doe
								</td>
								<td>
									john@doe.com
								</td>
								<td>
									http://john.doe.com/
								</td>
								<td>
									Lorem ipsum dolor sit amet, consectetur adipiscing elit.
								</td>
								<td>
									Options
								</td>
							</tr>
						</tbody>
					</table>

				</div>
				<div class="inner-page-title">
					<h3>Over 180 available icons</h3>
				</div>
				<ul id="icons" class="ui-widget ui-helper-clearfix">
													
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-n"><span class="ui-icon ui-icon-carat-1-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-ne"><span class="ui-icon ui-icon-carat-1-ne"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-e"><span class="ui-icon ui-icon-carat-1-e"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-se"><span class="ui-icon ui-icon-carat-1-se"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-s"><span class="ui-icon ui-icon-carat-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-sw"><span class="ui-icon ui-icon-carat-1-sw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-w"><span class="ui-icon ui-icon-carat-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-nw"><span class="ui-icon ui-icon-carat-1-nw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-2-n-s"><span class="ui-icon ui-icon-carat-2-n-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-2-e-w"><span class="ui-icon ui-icon-carat-2-e-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-n"><span class="ui-icon ui-icon-triangle-1-n"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-ne"><span class="ui-icon ui-icon-triangle-1-ne"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-e"><span class="ui-icon ui-icon-triangle-1-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-se"><span class="ui-icon ui-icon-triangle-1-se"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-s"><span class="ui-icon ui-icon-triangle-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-sw"><span class="ui-icon ui-icon-triangle-1-sw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-w"><span class="ui-icon ui-icon-triangle-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-nw"><span class="ui-icon ui-icon-triangle-1-nw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-2-n-s"><span class="ui-icon ui-icon-triangle-2-n-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-2-e-w"><span class="ui-icon ui-icon-triangle-2-e-w"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-n"><span class="ui-icon ui-icon-arrow-1-n"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-ne"><span class="ui-icon ui-icon-arrow-1-ne"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-e"><span class="ui-icon ui-icon-arrow-1-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-se"><span class="ui-icon ui-icon-arrow-1-se"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-s"><span class="ui-icon ui-icon-arrow-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-sw"><span class="ui-icon ui-icon-arrow-1-sw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-w"><span class="ui-icon ui-icon-arrow-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-nw"><span class="ui-icon ui-icon-arrow-1-nw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-n-s"><span class="ui-icon ui-icon-arrow-2-n-s"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-ne-sw"><span class="ui-icon ui-icon-arrow-2-ne-sw"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-e-w"><span class="ui-icon ui-icon-arrow-2-e-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-se-nw"><span class="ui-icon ui-icon-arrow-2-se-nw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-n"><span class="ui-icon ui-icon-arrowstop-1-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-e"><span class="ui-icon ui-icon-arrowstop-1-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-s"><span class="ui-icon ui-icon-arrowstop-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-w"><span class="ui-icon ui-icon-arrowstop-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-n"><span class="ui-icon ui-icon-arrowthick-1-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-ne"><span class="ui-icon ui-icon-arrowthick-1-ne"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-e"><span class="ui-icon ui-icon-arrowthick-1-e"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-se"><span class="ui-icon ui-icon-arrowthick-1-se"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-s"><span class="ui-icon ui-icon-arrowthick-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-sw"><span class="ui-icon ui-icon-arrowthick-1-sw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-w"><span class="ui-icon ui-icon-arrowthick-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-nw"><span class="ui-icon ui-icon-arrowthick-1-nw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-n-s"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-ne-sw"><span class="ui-icon ui-icon-arrowthick-2-ne-sw"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-e-w"><span class="ui-icon ui-icon-arrowthick-2-e-w"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-se-nw"><span class="ui-icon ui-icon-arrowthick-2-se-nw"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-n"><span class="ui-icon ui-icon-arrowthickstop-1-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-e"><span class="ui-icon ui-icon-arrowthickstop-1-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-s"><span class="ui-icon ui-icon-arrowthickstop-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-w"><span class="ui-icon ui-icon-arrowthickstop-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-w"><span class="ui-icon ui-icon-arrowreturnthick-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-n"><span class="ui-icon ui-icon-arrowreturnthick-1-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-e"><span class="ui-icon ui-icon-arrowreturnthick-1-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-s"><span class="ui-icon ui-icon-arrowreturnthick-1-s"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-w"><span class="ui-icon ui-icon-arrowreturn-1-w"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-n"><span class="ui-icon ui-icon-arrowreturn-1-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-e"><span class="ui-icon ui-icon-arrowreturn-1-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-s"><span class="ui-icon ui-icon-arrowreturn-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-w"><span class="ui-icon ui-icon-arrowrefresh-1-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-n"><span class="ui-icon ui-icon-arrowrefresh-1-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-e"><span class="ui-icon ui-icon-arrowrefresh-1-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-s"><span class="ui-icon ui-icon-arrowrefresh-1-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-4"><span class="ui-icon ui-icon-arrow-4"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-4-diag"><span class="ui-icon ui-icon-arrow-4-diag"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-extlink"><span class="ui-icon ui-icon-extlink"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-newwin"><span class="ui-icon ui-icon-newwin"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-refresh"><span class="ui-icon ui-icon-refresh"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-shuffle"><span class="ui-icon ui-icon-shuffle"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-transfer-e-w"><span class="ui-icon ui-icon-transfer-e-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-transferthick-e-w"><span class="ui-icon ui-icon-transferthick-e-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-folder-collapsed"><span class="ui-icon ui-icon-folder-collapsed"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-folder-open"><span class="ui-icon ui-icon-folder-open"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-document"><span class="ui-icon ui-icon-document"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-document-b"><span class="ui-icon ui-icon-document-b"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-note"><span class="ui-icon ui-icon-note"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-mail-closed"><span class="ui-icon ui-icon-mail-closed"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-mail-open"><span class="ui-icon ui-icon-mail-open"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-suitcase"><span class="ui-icon ui-icon-suitcase"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-comment"><span class="ui-icon ui-icon-comment"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-person"><span class="ui-icon ui-icon-person"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-print"><span class="ui-icon ui-icon-print"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-trash"><span class="ui-icon ui-icon-trash"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-locked"><span class="ui-icon ui-icon-locked"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-unlocked"><span class="ui-icon ui-icon-unlocked"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-bookmark"><span class="ui-icon ui-icon-bookmark"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-tag"><span class="ui-icon ui-icon-tag"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-home"><span class="ui-icon ui-icon-home"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-flag"><span class="ui-icon ui-icon-flag"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-calculator"><span class="ui-icon ui-icon-calculator"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-cart"><span class="ui-icon ui-icon-cart"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-pencil"><span class="ui-icon ui-icon-pencil"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-clock"><span class="ui-icon ui-icon-clock"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-disk"><span class="ui-icon ui-icon-disk"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-calendar"><span class="ui-icon ui-icon-calendar"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-zoomin"><span class="ui-icon ui-icon-zoomin"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-zoomout"><span class="ui-icon ui-icon-zoomout"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-search"><span class="ui-icon ui-icon-search"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-wrench"><span class="ui-icon ui-icon-wrench"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-gear"><span class="ui-icon ui-icon-gear"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-heart"><span class="ui-icon ui-icon-heart"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-star"><span class="ui-icon ui-icon-star"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-link"><span class="ui-icon ui-icon-link"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-cancel"><span class="ui-icon ui-icon-cancel"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-plus"><span class="ui-icon ui-icon-plus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-plusthick"><span class="ui-icon ui-icon-plusthick"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-minus"><span class="ui-icon ui-icon-minus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-minusthick"><span class="ui-icon ui-icon-minusthick"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-close"><span class="ui-icon ui-icon-close"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-closethick"><span class="ui-icon ui-icon-closethick"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-key"><span class="ui-icon ui-icon-key"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-lightbulb"><span class="ui-icon ui-icon-lightbulb"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-scissors"><span class="ui-icon ui-icon-scissors"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-clipboard"><span class="ui-icon ui-icon-clipboard"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-copy"><span class="ui-icon ui-icon-copy"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-contact"><span class="ui-icon ui-icon-contact"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-image"><span class="ui-icon ui-icon-image"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-video"><span class="ui-icon ui-icon-video"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-script"><span class="ui-icon ui-icon-script"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-alert"><span class="ui-icon ui-icon-alert"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-info"><span class="ui-icon ui-icon-info"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-notice"><span class="ui-icon ui-icon-notice"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-help"><span class="ui-icon ui-icon-help"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-check"><span class="ui-icon ui-icon-check"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-bullet"><span class="ui-icon ui-icon-bullet"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-radio-off"><span class="ui-icon ui-icon-radio-off"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-radio-on"><span class="ui-icon ui-icon-radio-on"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-pin-w"><span class="ui-icon ui-icon-pin-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-pin-s"><span class="ui-icon ui-icon-pin-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-play"><span class="ui-icon ui-icon-play"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-pause"><span class="ui-icon ui-icon-pause"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-next"><span class="ui-icon ui-icon-seek-next"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-prev"><span class="ui-icon ui-icon-seek-prev"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-end"><span class="ui-icon ui-icon-seek-end"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-first"><span class="ui-icon ui-icon-seek-first"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-stop"><span class="ui-icon ui-icon-stop"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-eject"><span class="ui-icon ui-icon-eject"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-volume-off"><span class="ui-icon ui-icon-volume-off"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-volume-on"><span class="ui-icon ui-icon-volume-on"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-power"><span class="ui-icon ui-icon-power"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-signal-diag"><span class="ui-icon ui-icon-signal-diag"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-signal"><span class="ui-icon ui-icon-signal"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-0"><span class="ui-icon ui-icon-battery-0"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-1"><span class="ui-icon ui-icon-battery-1"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-2"><span class="ui-icon ui-icon-battery-2"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-3"><span class="ui-icon ui-icon-battery-3"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-plus"><span class="ui-icon ui-icon-circle-plus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-minus"><span class="ui-icon ui-icon-circle-minus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-close"><span class="ui-icon ui-icon-circle-close"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-e"><span class="ui-icon ui-icon-circle-triangle-e"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-s"><span class="ui-icon ui-icon-circle-triangle-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-w"><span class="ui-icon ui-icon-circle-triangle-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-n"><span class="ui-icon ui-icon-circle-triangle-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-e"><span class="ui-icon ui-icon-circle-arrow-e"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-s"><span class="ui-icon ui-icon-circle-arrow-s"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-w"><span class="ui-icon ui-icon-circle-arrow-w"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-n"><span class="ui-icon ui-icon-circle-arrow-n"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-zoomin"><span class="ui-icon ui-icon-circle-zoomin"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-zoomout"><span class="ui-icon ui-icon-circle-zoomout"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-check"><span class="ui-icon ui-icon-circle-check"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circlesmall-plus"><span class="ui-icon ui-icon-circlesmall-plus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circlesmall-minus"><span class="ui-icon ui-icon-circlesmall-minus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-circlesmall-close"><span class="ui-icon ui-icon-circlesmall-close"></span></li>
				
					<li class="ui-state-default ui-corner-all" title=".ui-icon-squaresmall-plus"><span class="ui-icon ui-icon-squaresmall-plus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-squaresmall-minus"><span class="ui-icon ui-icon-squaresmall-minus"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-squaresmall-close"><span class="ui-icon ui-icon-squaresmall-close"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-dotted-vertical"><span class="ui-icon ui-icon-grip-dotted-vertical"></span></li>
					
					<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-dotted-horizontal"><span class="ui-icon ui-icon-grip-dotted-horizontal"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-solid-vertical"><span class="ui-icon ui-icon-grip-solid-vertical"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-solid-horizontal"><span class="ui-icon ui-icon-grip-solid-horizontal"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-gripsmall-diagonal-se"><span class="ui-icon ui-icon-gripsmall-diagonal-se"></span></li>
					<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-diagonal-se"><span class="ui-icon ui-icon-grip-diagonal-se"></span></li>
				</ul>
				<div class="inner-page-title">
						<h3>Pagination Example</h3>
				</div>
				<ul class="pagination">
					<li class="previous-off">&laquo;Previous</li>
					<li class="active">1</li>
					<li><a href="#">2</a></li>
					<li><a href="#">3</a></li>
					<li>...........</li>
					<li><a href="#">7</a></li>
					<li><a href="#">8</a></li>
					<li><a href="#">9</a></li>

					<li><a href="#">10</a></li>
					<li class="next"><a href="#">Next &raquo;</a></li>
				</ul>
				</div>
				<div class="clear"></div>
				<?php include('sidebar.php'); ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
<?php include('footer.php'); ?></div>
</body>
</html>