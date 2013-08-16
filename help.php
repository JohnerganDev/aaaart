<?php

require_once('config.php');

print aaaart_template_header('help');

?>

<div id="container" class="container">
	<h2>help</h2>
	<p>Content goes here</p>
	<?php if ($user): ?>
  <p>You can add additional help for logged in users here</p>
	<?php endif; ?>
</div>

<?php

print aaaart_template_footer();

?>