<?php
require_once('../config.php');

// that's a bad key. redirect home
if (empty($user)) {
	header("Location: " . BASE_URL);
	exit;
// user already has username and pass... they need to log in
}

$page_title = 'Update your account: '.$user['email'];

print aaaart_template_header( $page_title );

?>

<div class="container">
    <div class="page-header">
        <h2 class="lead"><?php print $page_title; ?></h2>
    </div>
  	<div id="login-form">

	  	<form id="login-form">
	  		<input type="hidden" name="action" value="first_login" />
    		<input type="hidden" name="key" value="<?php print $user['_id']; ?>" />
		    <label>Password</label>
    		<input type="password" name="pass" required/>
    		<label>Display name</label>
    		<input type="text" name="display_name" required/>
    		<span class="help-block">This is what will be displayed publicly on the website.</span>
		    <button type="submit" class="btn login">Update</button>
    	</form>

		</div>
    <div id="message"></div>
    
</div>

<?php

print aaaart_template_footer(array("js/user.js"));

?>
