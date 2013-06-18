<?php

require_once('../config.php');

print aaaart_template_header('Makers');

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
    <h2 >recent discussions</h2>
    </div>
  <table class="discussions table table-striped clearfix" id="discussions"></table>  
</div>

<?php

print aaaart_template_footer(array("js/comment.js"));

?>