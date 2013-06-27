<?php

require_once('../config.php');

$trails = aaaart_memex_get_saved_paths();

print aaaart_template_header('trails');

/*

Collections are groups of documents created by users.

* Personal collection (only I will use it, only I should see it)
* Public personal collection (only I will use it, but other people can see it)
* Shared collection (I can invite other people, who can also invite other people to use it)
* Open collection (anyone is able to add things into the collection)

*/

?>

<div id="container" class="container">
    <div class="page-header">
    <h2 >trails</h2>
    </div>
  <ul class="collections unstyled clearfix" id="collections">
    <?php foreach ($trails as $trail) { ?>
    <li><a href="<?php print BASE_URL.'memex/detail.php?id='.(string)$trail['_id']; ?>"><?php print $trail['title']; ?></a></li>
    <?php } ?>
  </ul>  
</div>

<?php

print aaaart_template_footer();

?>