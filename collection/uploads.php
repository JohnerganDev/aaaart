<?php

require_once('../config.php');

print aaaart_template_header('shared');

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
    <h1><?php print SITE_TITLE; ?></h1>
    <h2 class="lead">shared</h2>
  </div>
  <ul class="files clearfix" id="gallery" data-toggle="modal-gallery" data-target="#modal-gallery"></ul>    
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
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    {% if (file.thumbnail_url) { %}
        <li class="image">
            <a href="{%=file.url%}" title="{%=file.name%}" data-gallery="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a>
            <div class="title">
                <a href="{%=file.url%}" title="{%=file.metadata.title%}" data-gallery="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.metadata.title%}</a>
            </div>
        </li>
    {% } %}
    
{% } %}
</script>


<?php

print aaaart_template_footer(array("js/collection.js"));

?>