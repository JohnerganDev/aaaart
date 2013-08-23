<?php
require_once('../config.php');

// that's a bad key. redirect home
if (empty($user['activity'])) {
    print 'nothing!';
	exit;
}

$activity = array_reverse( $user['activity'] );

?>

<ul class="activity list-unstyled">

<?php foreach ($activity as $item) { ?>
<li>
    <?php print aaaart_user_format_activity($item); ?>
</li>
<?php } ?>

</ul>

