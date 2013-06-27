<?php

require_once('../config.php');

$memex = aaaart_memex_load_from_query_string();
aaaart_memex_open_path($memex);
$first_item = (!empty($memex['path'])) ? current($memex['path']) : array();

print aaaart_template_header( $memex['title'] );

?>

<div id="container" class="container">
    <div class="page-header">
        <h2 ><?php print $memex['title']; ?></h2>
        <?php if (!empty($first_item)): ?>
        <small><a href="<?php print BASE_URL.$first_item['uri']; ?>">This trail begins with: <?php print $first_item['title']; ?> <i class="icon-arrow-right"></i></a></small>
        <?php endif; ?>
    </div>
    <?php print Slimdown::render($memex['description']); ?>
</div>

<?php

print aaaart_template_footer();

?>