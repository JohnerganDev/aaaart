<?php

require_once('../config.php');

$image = aaaart_image_load_from_query_string();

if (!empty($_GET['_v']) && $_GET['_v']=='html') {
    aaaart_image_get_html($image);
}

$can_edit = (aaaart_image_check_perm('update', $image)) ? true : false;
$can_upload = (aaaart_image_check_perm('upload')) ? true : false;
$can_download = (aaaart_image_check_perm('download')) ? true : false;
$can_save = (aaaart_image_check_perm('save')) ? true : false;
// pull from activity
aaaart_user_pull_activity('image/detail.php?id='.(string)$image['_id']);
// get sharers string
$sharers = aaaart_image_get_sharers($image);

print aaaart_template_header( $image['title'] );

?>

<div id="container" class="container image-detail">
    <div class="page-header">
    <h2 ><?php print $image['title']; ?></h2>
    <h3 class="lead"><?php print $image['makers_display']; ?></h3>

    <?php if ($can_edit): ?>
        <a data-toggle="modal" href="#edit-form" class="btn btn-default btn-xs text-right" type="button">Edit</a>
        <a data-toggle="modal" href="#delete-form" class="btn btn-xs btn-danger text-right" type="button">Delete</a>
    <?php endif; ?>
    <?php if ($can_save): ?>
        <?php print aaaart_image_format_save_button($image); ?>
    <?php endif; ?>
    <?php print aaaart_template_comment_button(IMAGES_COLLECTION, $image['_id']); ?>
    <?php if ($can_edit): ?>
        <!-- modal edit form -->
        <div id="edit-form" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <a class="close" data-dismiss="modal">×</a>  
                        <h4>Edit</h4>
                    </div>
                    <form class="modal-body" action="<?php print BASE_URL; ?>image/index.php" method="POST">
                        <input type="hidden" id="document-id" name="id" value="<?php print $image['_id']; ?>" />
                        <input type="hidden" name="action" value="update" />
                            <div class="form-group">  
                                <label>Title</label><input type="text" class="form-control" name="title" value="<?php print $image['title']; ?>">
                            </div>
                            <div class="form-group">  
                                <label><?php print MAKERS_LABEL; ?>s</label><input type="text" class="form-control" name="maker" value="<?php print $image['makers_display']; ?>">
                                <p class="help-block">Separate multiple people with commas.</p>
                            </div>
                            <?php print aaaart_utils_format_input_fields($IMAGE_FIELDS, $image); ?>
                    </form>
                    <div class="modal-footer">
                        <button class="btn btn-success" id="save">Save</button>
                        <a href="#" class="btn" data-dismiss="modal">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- modal delete form -->
        <div id="delete-form" class="modal hide fade in" style="display: none; ">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <a class="close" data-dismiss="modal">×</a>  
                        <h4>Delete</h4>
                    </div>
                    <div class="modal-body">
                        Are you sure that you want to delete this? If you do delete it, there is no way to bring it back!
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" id="delete">Delete</button>
                        <a href="#" class="btn" data-dismiss="modal">Cancel</a>
                    </div>
                </div>
            </div>
        </div>            
    <?php endif; ?>

    </div>
 	<!-- Image display here -->
    <div id="document-display" class="row">
      	<div id="metadata" class="col-md-6">
      		<input type="hidden" id="document-id" name="id" value="<?php print $image['_id']; ?>" />
      		<div class ="other">
            <?php print aaaart_utils_format_display_fields($IMAGE_FIELDS, $image, array('Contributors'=>$sharers)); ?>
            </div>
        </div>
      	<div class="image" class="col-md-6">
            <?php print aaaart_image_display_image($image, 'medium', $can_download, false); ?>
            <div class="collections wrapper col-md-6">
                <h6 class="muted">Collections</h6>
                <div id="in-collections" data-objectid="<?php print $image['_id']; ?>">
                    <small><ul class="collections list-inline"></ul></small>
                    <?php print aaaart_collection_sort_element(); ?>
                </div>
            </div>
            <?php if ($can_upload): ?>
            <div class="files wrapper col-md-6">
            <h6 class="muted">Files</h6>
            <!-- The file upload form used as target for the file upload widget -->
            <form id="fileupload" action="<?php print BASE_URL; ?>upload/index.php" method="POST" enctype="multipart/form-data">
                <!-- The table listing the files available for upload/download -->
                <table role="presentation" class="row table table-condensed table-striped">
                    <tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody>
                </table>
                <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
                <div class="row fileupload-buttonbar">
                    <div >
                        <span class="glyphicon glyphicon-question-sign uploading-help-trigger"></span> 
                        <!-- The fileinput-button span is used to style the file input field as button -->
                        <span class="btn btn-success fileinput-button">
                            <span class="glyphicon glyphicon-plus icon-white"></span>
                            <span>Add a new version...</span>
                            <input type="file" name="files[]" multiple>
                        </span>
                        <!--
                        <button type="submit" class="btn btn-primary start">
                            <i class="icon-upload icon-white"></i>
                            <span>Start upload</span>
                        </button>
                        <button type="reset" class="btn btn-warning cancel">
                            <i class="icon-ban-circle icon-white"></i>
                            <span>Cancel upload</span>
                        </button>
                        -->
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
                <ul>
                    <li>You can upload alternate versions here.</li>
                    <!--<li>The maximum file size for uploads is <strong>20 MB</strong>.</li>-->
                    <!--<li>Only image files (<strong>JPG, GIF, PNG</strong>) are allowed.</li>-->
                    <li>You can <strong>drag &amp; drop</strong> files from your desktop on this webpage with Google Chrome, Mozilla Firefox and Apple Safari.</li>
                    <li>Built with <a href="https://github.com/blueimp/jQuery-File-Upload">jQuery File Upload</a></li>
                </ul>
            </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>

    <?php if ($can_download && !$can_upload): ?>
    <form id="fileupload">
    <table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
    </form>
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
            {% if (!o.files.error && !i && !o.options.autoUpload) { %}
                <button class="btn btn-success btn-xs start">
                    <i class="icon-upload icon-white"></i>
                    <span>Start</span>
                </button>
            {% } %}
            {% if (!i) { %}
                <button class="btn btn-warning btn-xs cancel">
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cancel</span>
                </button>
            {% } %}
        </td>
        <td class="editable-fields">
        		<input type="hidden" name="document-id" value="<?php print $image['_id']; ?>" />
            <div class="row">
                <input type="text" name="comment" class="form-control" placeholder="Info about file">
            </div>
        </td>
        <td>
            <p class="size">{%=o.formatFileSize(file.size)%}</p>
            {% if (!o.files.error) { %}
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
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
        <?php if ($can_edit): ?>
        <td>
            <button class="btn btn-danger btn-xs delete" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}"{% if (file.delete_with_credentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                <i class="icon-trash icon-white"></i>
                <span>Delete</span>
            </button>
        </td>
        <?php endif; ?>
    </tr>
{% } %}
</script>

<?php

print aaaart_template_footer(array("js/edit-image.js", "js/collection-actions.js"));

?>