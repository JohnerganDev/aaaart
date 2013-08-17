<?php

require_once('../config.php');

$maker = aaaart_collection_load_maker_from_query_string();
$can_upload = (aaaart_image_check_perm('upload')) ? true : false;

print aaaart_template_header( $maker['display'] );

?>

<div id="container" class="container">
    <div class="page-header">
        <h2 ><?php print $maker['display']; ?></h2>
        <small><ul class="inline" id="collections-list"></ul></small>
        <?php print aaaart_template_comment_button(MAKERS_COLLECTION, $maker['_id']); ?>
    </div>
    <div id="metadata">
		<input type="hidden" id="maker-id" name="id" value="<?php print $maker['_id']; ?>" />	
	</div>
  <ul class="files clearfix" id="gallery" data-toggle="modal-gallery" data-target="#modal-gallery"></ul>  

    <?php if ($can_upload): ?>
	<!-- The file upload form used as target for the file upload widget -->
	<form id="fileupload" action="<?php print BASE_URL; ?>upload/index.php" method="POST" enctype="multipart/form-data">
	    <!-- Redirect browsers with JavaScript disabled to the origin page -->
	    <noscript><input type="hidden" name="redirect" value="http://blueimp.github.com/jQuery-File-Upload/"></noscript>
	    <!-- The table listing the files available for upload/download -->
	    <table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
	    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
	    <div class="row fileupload-buttonbar">
	        <div class="span7">
                <i class="icon-question-sign uploading-help-trigger"></i> 
	            <!-- The fileinput-button span is used to style the file input field as button -->
	            <span class="btn btn-success fileinput-button">
	                <i class="icon-plus icon-white"></i>
	                <span>Add something...</span>
	                <input type="file" name="files[]" multiple>
	            </span>
	            <button type="submit" class="btn btn-primary start">
	                <i class="icon-upload icon-white"></i>
	                <span>Start upload</span>
	            </button>
	            <button type="reset" class="btn btn-warning cancel">
	                <i class="icon-ban-circle icon-white"></i>
	                <span>Cancel upload</span>
	            </button>
	            <!-- The loading indicator is shown during file processing -->
	            <span class="fileupload-loading"></span>
	        </div>
	        <!-- The global progress information -->
	        <div class="span5 fileupload-progress fade">
	            <!-- The global progress bar -->
	            <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
	                <div class="bar" style="width:0%;"></div>
	            </div>
	            <!-- The extended global progress information -->
	            <div class="progress-extended">&nbsp;</div>
	        </div>
	    </div>
	</form>
	<br>
	<div id="uploading-help" class="well" style="display:none">
	    <h3>Upload Notes</h3>
	    <ul>
	        <li>Files you upload here will be assigned to this author.</li>
	        <!--<li>The maximum file size for uploads is <strong>20 MB</strong>.</li>
	        <li>Only image files (<strong>JPG, GIF, PNG</strong>) are allowed.</li>-->
	        <li>You can <strong>drag &amp; drop</strong> files from your desktop on this webpage with Google Chrome, Mozilla Firefox and Apple Safari.</li>
	        <li>Built with <a href="https://github.com/blueimp/jQuery-File-Upload">jQuery File Upload</a></li>
	    </ul>
	</div> 
    <?php endif; ?>   
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
<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td>
            <span class="preview"></span>
        </td>
        <td>
            <p class="name">{%=file.name%}</p>
            {% if (file.error) { %}
                <div><span class="label label-important">Error</span> {%=file.error%}</div>
            {% } %}
        </td>
        <td class="editable-fields">
            <input type="hidden" name="maker-id" value="<?php print $maker['_id']; ?>" />
            <div class="row">
                <label>Title:</label><input type="text" name="title">
            </div>
            <div class="row">
                <label>Maker:</label><input type="text" name="maker" value="<?php print $maker['display']; ?>">
            </div>
        </td>
        <td>
            <p class="size">{%=o.formatFileSize(file.size)%}</p>
            {% if (!o.files.error) { %}
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
            {% } %}
        </td>
        <td>
            {% if (!o.files.error && !i && !o.options.autoUpload) { %}
                <button class="btn btn-primary start">
                    <i class="icon-upload icon-white"></i>
                    <span>Start</span>
                </button>
            {% } %}
            {% if (!i) { %}
                <button class="btn btn-warning cancel">
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cancel</span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        <td>
            <span class="preview">
                {% if (file.thumbnail_url) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" data-gallery="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a>
                {% } %}
            </span>
        </td>
        <td>
            <p class="name">
                <a href="{%=file.url%}" title="{%=file.name%}" data-gallery="{%=file.thumbnail_url&&'gallery'%}" download="{%=file.name%}">{%=file.name%}</a><br />
            </p>
            {% if (file.error) { %}
                <div><span class="label label-important">Error</span> {%=file.error%}</div>
            {% } %}
        </td>
        <td>
            <span class="size">{%=o.formatFileSize(file.size)%}</span>
        </td>
    </tr>
{% } %}
</script>

<?php

print aaaart_template_footer(array("js/maker.js"));

?>