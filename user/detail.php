<?php

require_once('../config.php');

$can_view = (aaaart_user_check_perm('view_user')) ? true : false;
if (!$can_view) {
    header('Location: '.BASE_URL);
    exit;
}
$person = aaaart_user_load_from_query_string();
if (empty($person)) {
    header('Location: '.BASE_URL);
    exit;
}
$display_name = aaaart_user_format_display_name($person, false);
$invited_by = aaaart_user_invited_by($person);

print aaaart_template_header( $display_name );

?>

<div id="container" class="container image-detail">
    <div class="page-header">
    <h2 ><?php print $display_name; ?></h2>
    <h3 class="lead"><?php print $person['email']; ?></h3>

    <?php if (!empty($person['created'])): ?>
    <h5>Account created</h5>
    <p><?php print aaaart_utils_format_date($person['created']); ?></p>
    <?php endif; ?>

    <?php if (!empty($invited_by)): ?>
    <h5>Invited by</h5>
    <?php print aaaart_user_format_simple_list($invited_by); ?>
    <?php endif; ?>

    <?php if (!empty($person['invited'])): ?>
    <h5>Invited</h5>
    <?php print aaaart_user_format_simple_list($person['invited']); ?>
    
    <?php endif; ?>    

    <h3>Documents and requests</h3>
    <ul class="files clearfix list-unstyled" id="profile" data-id="<?php print (string)$person['_id']; ?>"></ul> 

</div>

<?php

print aaaart_template_footer(array("js/saved.js"));

?>