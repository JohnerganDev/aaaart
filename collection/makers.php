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
    <h2 >library</h2>
    </div>
    <?php if (COLLECTIONS_ARE_HUGE): ?>
    <div class="btn-toolbar">
      <div class="btn-group" id="makers-filter">
        <a class="btn" href="#">a</a>
        <a class="btn" href="#">b</a>
        <a class="btn" href="#">c</a>
        <a class="btn" href="#">d</a>
        <a class="btn" href="#">e</a>
        <a class="btn" href="#">f</a>
        <a class="btn" href="#">g</a>
        <a class="btn" href="#">h</a>
        <a class="btn" href="#">i</a>
        <a class="btn" href="#">j</a>
        <a class="btn" href="#">k</a>
        <a class="btn" href="#">l</a>
        <a class="btn" href="#">m</a>
        <a class="btn" href="#">n</a>
        <a class="btn" href="#">o</a>
        <a class="btn" href="#">p</a>
        <a class="btn" href="#">q</a>
        <a class="btn" href="#">r</a>
        <a class="btn" href="#">s</a>
        <a class="btn" href="#">t</a>
        <a class="btn" href="#">u</a>
        <a class="btn" href="#">v</a>
        <a class="btn" href="#">w</a>
        <a class="btn" href="#">x</a>
        <a class="btn" href="#">y</a>
        <a class="btn" href="#">z</a>
        <a class="btn" href="#">etc.</a>
      </div>
    </div>
    <?php endif; ?>
  <ul class="makers inline clearfix" id="makers"></ul>  
</div>

<?php

print aaaart_template_footer(array("js/maker.js"));

?>