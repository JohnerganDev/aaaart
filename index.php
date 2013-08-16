<?php

require_once('config.php');

//$solr = new Solr();
//$results = $solr->getFacets(IMAGES_COLLECTION);
//$results = aaaart_image_filter_makers('ab-ar');
//debug($results);

print aaaart_template_header('');

/*

Collections are groups of documents created by users.

* Personal collection (only I will use it, only I should see it)
* Public personal collection (only I will use it, but other people can see it)
* Shared collection (I can invite other people, who can also invite other people to use it)
* Open collection (anyone is able to add things into the collection)

*/

?>

<div id="container" class="container">
  <?php if (LIST_TYPE=='list'): ?><ul id="active-collections"></ul><?php endif; ?>
  <ul class="files clearfix" id="gallery" data-toggle="modal-gallery" data-target="#modal-gallery"></ul>  
  <button id="more" class="btn btn-mini btn-danger" type="button">More</button>
</div>

<!-- modal-gallery is the modal dialog used for the image gallery -->
<div id="modal-gallery" class="modal modal-gallery hide fade" data-filter=":odd" tabindex="-1">
    <div class="modal-header">
        <a class="close" data-dismiss="modal">&times;</a>
        <h3 class="modal-title"></h3>
    </div>
    <div class="modal-body"><div class="modal-image"></div><div class="modal-collections"><?php print aaaart_collection_sort_element(); ?></div></div>
    <div class="modal-footer">
        <a class="btn modal-download" target="_blank">
            <i class="icon-download"></i>
            <span>Download</span>
        </a>
        <a class="btn modal-detail">
            <i class="icon-info-sign"></i>
            <span>Info</span>
        </a>
        <a class="btn btn-success modal-play modal-slideshow" data-slideshow="5000">
            <i class="icon-play icon-white"></i>
            <span>Slideshow</span>
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

print aaaart_template_footer(array("js/home.js"));

?>