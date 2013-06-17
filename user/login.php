<?php
require_once('../config.php');

if (!empty($user['_id'])) {
	header("Location: " . BASE_URL);
	exit;
}

$user = aaaart_user_load_from_query_string();

// that's a bad key. redirect home
if (empty($user)) {
	header("Location: " . BASE_URL);
	exit;

// user already has username and pass... they need to log in
} else if (!empty($user['pass'])) {
	$show_login = true;
	$page_title = 'Log in';

// user has to set up their account
} else {
	$show_login = false;
	$page_title = 'Finish creating your account';

}

print aaaart_template_header( $page_title );

?>

<div class="container">
    <div class="page-header">
        <h2 class="lead"><?php print $page_title; ?></h2>
    </div>
  	<div id="login-form">

    <?php if ($show_login): ?>
    	<form>
    		<input type="hidden" name="action" value="login" />
    		<input type="hidden" name="key" value="<?php print $user['_id']; ?>" />
		    <label>Password</label>
    		<input type="password" name="pass" />
		    <button type="submit" class="btn">Log in</button>
    	</form>
	  <?php else: ?>
	  	<form id="login-form">
	  		<input type="hidden" name="action" value="first_login" />
    		<input type="hidden" name="key" value="<?php print $user['_id']; ?>" />
		    <label>Password</label>
    		<input type="password" name="pass" required/>
    		<span class="help-block">You will need your password to log in.</span>
		    <label>Display name</label>
    		<input type="text" name="display_name" required/>
    		<span class="help-block">This is what will be displayed publicly on the website.</span>
		    <button type="submit" class="btn login">Create account</button>
    	</form>
		<?php endif; ?>

		</div>
    <div id="message"></div>
    
</div>

<?php

print aaaart_template_footer(array("js/user.js"));

?>
