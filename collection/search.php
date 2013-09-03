<?php

require_once('../config.php');

$search_string = aaaart_utils_load_search_from_query_string();

print aaaart_template_header( 'looking for: ' . $search_string );

?>

<div id="container" class="container">
    <div class="page-header">
        <h2 >search: <?php print $search_string; ?></h2>
        <small><ul class="list-inline" id="makers-and-collections-list"></ul></small>
    </div>
  <ul class="files clearfix search-results list-unstyled" id="gallery" data-toggle="modal-gallery" data-target="#modal-gallery"></ul>  
  <ul class="list-unstyled" id="discussions-list"></ul>
</div>

<!-- modal-gallery is the modal dialog used for the image gallery -->
<div id="modal-gallery" class="modal modal-gallery hide fade" data-filter=":odd" tabindex="-1">
    <div class="modal-header">
        <a class="close" data-dismiss="modal">&times;</a>
        <h3 class="modal-title"></h3>
    </div>
    <div class="modal-body"><div class="modal-image"></div></div>
    <div class="modal-footer">
        <a class="btn modal-download" target="_blank">
            <i class="icon-download"></i>
            <span>Download</span>
        </a>
        <a class="btn modal-detail">
            <i class="icon-info-sign"></i>
            <span>Info</span>
        </a>
        <a class="btn btn-info modal-prev">
            <i class="icon-arrow-left icon-white"></i>
            <span>Previous</span>
        </a>
        <a class="btn btn-primary modal-next">
            <span>Next</span>
            <i class="icon-arrow-right icon-white"></i>
        </a>
    </div>
</div>

<?php

print aaaart_template_footer(array("js/base.js"));

?>