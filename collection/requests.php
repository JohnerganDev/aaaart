<?php

require_once('../config.php');

print aaaart_template_header('requests');

?>

<div id="container" class="container">
    <div class="page-header">
    <h3 >library requests</h3>
    </div>
    <span class="sorter muted">order by: <a href="#" data-toggle="tooltip" title="<?php print MAKERS_LABEL; ?> name" class="maker"><i class="icon-user"></i></a> / <a href="#" data-toggle="tooltip" title="Most recent" class="date"><i class="icon-calendar"></i></a></span>
    <?php if (MAKERS_ARE_HUGE): ?>
    <div class="btn-toolbar">
      <div class="btn-group" id="makers-filter" style="display:none;">
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
  <ul class="requests unstyled clearfix" id="gallery"></ul>  
  <button id="more" class="btn btn-mini btn-danger" type="button">More</button>
</div>

<?php

print aaaart_template_footer(array("js/request.js"));

?>