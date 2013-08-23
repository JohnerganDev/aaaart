<?php

require_once('../config.php');

$collection = aaaart_collection_load_from_query_string();
$can_edit = (aaaart_collection_check_perm('update', $collection)) ? true : false;
$can_add = (aaaart_collection_check_perm('add', $collection)) ? true : false;

print aaaart_template_header( $collection['title'] );

$followers = aaaart_collection_get_followers($collection);
$followers_count = count($followers);
$followers_str = ($followers_count==1) ? '1 follower' : sprintf('%s followers', $followers_count);
$followers_list = aaaart_user_format_simple_list($followers);

?>

<div id="container" class="container collection">
	<div class="page-header">
    <h2 ><?php print $collection['title']; ?> <small class="muted" rel="tooltip" data-toggle="tooltip" title="<?php print $followers_list; ?>"><?php print $followers_str; ?></small></h2>
    <h3 class="lead"><?php print $collection['short_description']; ?></h3>

	<?php if ($can_edit): ?>
    <a data-toggle="modal" href="#edit-form" class="btn btn-mini text-right" type="button">Edit</a>
    <a id="edit-contents-toggle" class="btn btn-mini text-right" type="button">Edit contents</a>
    <a data-toggle="modal" href="#invite-form" class="btn btn-mini text-right" type="button">Invite collaborator</a>
    <a data-toggle="modal" href="#delete-form" class="btn btn-mini btn-danger text-right" type="button">Delete</a>
    <?php endif; ?>
    <?php print aaaart_collection_get_follow_button($collection); ?>
    <?php print aaaart_template_comment_button(COLLECTIONS_COLLECTION, $collection['_id']); ?>
    <?php if ($can_edit): ?>
    <!-- modal edit form -->
    <div id="edit-form" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Edit</h3>
        </div>
        <form class="modal-body" action="<?php print BASE_URL; ?>collection/index.php" method="POST">
            <input type="hidden" id="collection-id" name="id" value="<?php print $collection['_id']; ?>" />
            <input type="hidden" name="action" value="update" />
            <fieldset>
              <label><h5>Name</h5></label>
	            <input type="text" class="input-xlarge" name="title" value="<?php print $collection['title'] ?>">

                <label><h5>Type</h5></label>
                <span class="help-block">Pick the type of collection you want to create. You can change it later.</span>
                <?php print aaaart_collection_type_field($collection['type']); ?>

	            <label><h5>Very Short Description</h5></label>
	            <input type="text" class="input-xxlarge" name="short_description" value="<?php print stripslashes($collection['short_description']) ?>">
	            
                <label><h5>Longer Description</h5></label>
                <textarea rows="6" class="input-xxlarge" name="description"><?php print stripslashes($collection['metadata']['description']) ?></textarea>
                
            </fieldset>
        </form>
        <div class="modal-footer">
            <button class="btn btn-success" id="save">Save</button>
            <a href="#" class="btn" data-dismiss="modal">Cancel</a>
        </div>
    </div>
    <!-- modal add section form -->
    <div id="add-section-modal" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Sections</h3>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
            <button class="btn btn-success" id="save-section">Save</button>
            <a href="#" class="btn" data-dismiss="modal">Cancel</a>
        </div>
    </div>
    <!-- modal invite form -->
    <div id="invite-form" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Invite</h3>
        </div>
        <form class="modal-body" action="<?php print BASE_URL; ?>collection/index.php" method="POST">
            <input type="hidden" id="collection-id" name="id" value="<?php print $collection['_id']; ?>" />
            <input type="hidden" name="action" value="invite" />
            <fieldset>
	            <span class="help-block">Enter the email address of the person you are inviting to edit the contents of this collection. If there is nobody on the site with that email address, we will invite them to join.</span>
	            <input class="input-xlarge" type="email" name="email" placeholder="Email address" required>
            </fieldset>
        </form>
        <div class="modal-footer">
            <button class="btn btn-success" id="invite">Invite</button>
            <a href="#" class="btn" data-dismiss="modal">Cancel</a>
        </div>
    </div>
    <!-- modal delete form -->
    <div id="delete-form" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Delete</h3>
        </div>
        <div class="modal-body">
            Are you sure that you want to delete this? If you do delete it, there is no way to bring it back!
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="delete">Delete</button>
            <a href="#" class="btn" data-dismiss="modal">Cancel</a>
        </div>
    </div>
    <?php endif; ?>
    <p></p>        
    <small><ul class="inline" id="makers-list"></ul></small>
    </div>
    <div id="metadata">
        <input type="hidden" id="collection-id" name="id" value="<?php print $collection['_id']; ?>" />	
        <?php if (!empty($collection['metadata']['description'])): ?>
        <?php print aaaart_first_paragraph_teaser($collection['metadata']['description']); ?>
        <?php endif; ?>
    </div>

    <span class="sorter muted">order by: <a href="#" data-toggle="tooltip" title="<?php print MAKERS_LABEL; ?> name" class="maker"><span class="glyphicon glyphicon-user"></span></a> / <a href="#" data-toggle="tooltip" title="Most recent" class="date"><span class="glyphicon glyphicon-calendar"></span></a></span>
    <ul class="files list-unstyled clearfix" id="gallery" data-toggle="modal-gallery" data-target="#modal-gallery"></ul>  

    <?php if ($can_edit || $can_add): ?>
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
	    <ul>
	        <li>Files you upload here will be added to this collection.</li>
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
            <input type="hidden" name="collection-id" value="<?php print $collection['_id']; ?>" />
            <div class="row">
                <label>Title:</label><input type="text" name="title">
            </div>
            <div class="row">
                <label><?php print MAKERS_LABEL; ?>:</label><input type="text" name="maker">
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
        <?php if ($can_edit): ?>
        <td>
            <button class="btn btn-danger delete" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}"{% if (file.delete_with_credentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                <i class="icon-trash icon-white"></i>
                <span>Delete</span>
            </button>
        </td>
        <?php endif; ?>
    </tr>
{% } %}
</script>

<?php

print aaaart_template_footer(array("js/edit-collection.js"));

?>