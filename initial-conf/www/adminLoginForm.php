<html>
<head>
</head>
<body onload="window.document.adminLoginForm.formuser.focus()">
	<form method="post" name="adminLoginForm" action="adminLogin.php">
	<?php if ($_POST['basic_is_logged_in'] == 'true')?>
                <input type="hidden" name="status" value="<?php echo "Currently Logged In";?>">
	<?php $loginAttempts = !isset($_POST['loginAttempts'])?1:$_POST['loginAttempts'] + 1;?>
		<input type="hidden" name="loginAttempts" value="<?php echo $loginAttempts;?>">
<br>
<br>
<br>
<br>
<center>
  <table width="350" border="0" cellspacing="0" cellpadding="0">
    <tr> 
      <td width="50%">Admin Login</td>
      <td width="50%">&nbsp;</td>
    </tr>
    <tr> 
      <td width="50%">Login to Admin Center</td>
      <td width="50%">&nbsp;</td>
    </tr>
    <tr> 
      <td>&nbsp;</td>
      <td width="50%">&nbsp;</td>
    </tr>
    <tr> 
      <td width="50%">User Name:</td>
      <td width="50%"><input type="text" name="formuser" value="<?php echo $formuser;?>"></td>
    </tr>
    <tr> 
      <td width="50%">Password:</td>
      <td width="50%"><input type="password" name="formpassword" value="<?php echo $formpassword;?>"></td>
    </tr>
    <tr> 
      <td width="50%">&nbsp;</td>
      <td width="50%"><input class="submit" type="submit" name="submit" value="Login"></td>
    </tr>
  </table>
  <br>
  Login attempts: 
  <?php echo $loginAttempts; ?>
  <br>
  Status:
  <?php echo $_POST['basic_is_logged_in']; ?>
  </center>
</form>
</body>
</html>
