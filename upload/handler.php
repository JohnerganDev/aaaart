<?php

require_once('../config.php');

print aaaart_template_header('Upload');

?>

<div id="container" class="container">
	<div class="page-header">
        <h3 class="lead">upload</h3>
    </div>

    <div class="tabbable">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab1" data-toggle="tab">Upload a file</a></li>
            <li><a href="#tab2" data-toggle="tab">Import HTML page</a></li>
            <li><a href="#tab3" data-toggle="tab">Link to a video</a></li>
        </ul>
        <div class="tab-content">
            <div class="panel-body tab-pane active" id="tab1">

            <!-- The file upload form used as target for the file upload widget -->
            <form id="fileupload" action="<?php print BASE_URL; ?>upload/index.php" method="POST" enctype="multipart/form-data">
                <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
                <div class="row fileupload-buttonbar">
                    <div class="span7">
                        <!-- The fileinput-button span is used to style the file input field as button -->
                        <span class="btn btn-success btn-lg fileinput-button ">
                            <i class="icon-plus icon-white"></i>
                            <span>Add files...</span>
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
                        <button type="button" class="btn btn-danger delete">
                            <i class="icon-trash icon-white"></i>
                            <span>Delete</span>
                        </button>
                        <input type="checkbox" class="toggle">
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
                <!-- The table listing the files available for upload/download -->
                <table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
            </form>
            <br>
            <div class="well">
                <h3>Upload Notes</h3>
                <ul>
                    <!--<li>The maximum file size for uploads is <strong>20 MB</strong>.</li>
                    <li>Only image files (<strong>JPG, GIF, PNG</strong>) are allowed.</li>-->
                    <li>You can <strong>drag &amp; drop</strong> files from your desktop on this webpage with Google Chrome, Mozilla Firefox and Apple Safari.</li>
                    <li>Built with <a href="https://github.com/blueimp/jQuery-File-Upload">jQuery File Upload</a></li>
                </ul>
            </div>

        </div>

        <div class="panel-body tab-pane" id="tab2">
            <form id="import-html" action="<?php print BASE_URL; ?>image/index.php" method="POST" >
                <input type="hidden" name="action" value="html">
                <div class="form-group">
                    <label>URL of the page</label>
                    <input name="url" type="url" class="form-control" required>
                    <p class="help-block">The content of this page will be imported into the library</p>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input name="title" type="text" class="form-control" required>
                    <p class="help-block">What title will this have in the library? </p>
                </div>
                <div class="form-group">
                    <label><?php print MAKERS_LABEL; ?></label>
                    <input name="maker" type="text" class="form-control" required>
                    <p class="help-block">Separate multiple names with commas</p>
                </div>
                <div class="form-group">
                    <label>Short description</label>
                    <input name="one_liner" type="text" class="form-control" required>
                    <p class="help-block">Just one line, or even a few words</p>
                </div>
                <br />
                <button class="btn btn-primary"><span>Save</span></button>
            </form>
        </div>

        <div class="panel-body tab-pane" id="tab3">
            <form id="import-video" action="<?php print BASE_URL; ?>image/index.php" method="POST" >
                <input type="hidden" name="action" value="video">
                <div class="form-group">
                    <label>URL of video (or audio)</label>
                    <input name="url" type="url" class="form-control" required>
                    <p class="help-block">Just paste the URL of the page for the media.</p>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input name="title" type="text" class="form-control" required>
                    <p class="help-block">What title will this have in the library? </p>
                </div>
                <div class="form-group">
                    <label><?php print MAKERS_LABEL; ?></label>
                    <input name="maker" type="text" class="form-control" required>
                    <p class="help-block">Separate multiple names with commas</p>
                </div>
                <div class="form-group">
                    <label>Short description (just a few words)</label>
                    <input name="one_liner" type="text" class="form-control" required>
                    <p class="help-block">Just one line, or even a few words</p>
                </div>
                <br />
                <button class="btn btn-primary"><span>Save</span></button>
            </form>
        </div>
    </div>

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
        </td>
        <td class="editable-fields">
            <div class="row">
                <label><?php print MAKERS_LABEL; ?>:</label><input type="text" name="maker[{%= file.name %}]">
            </div>
            <div class="row">
                <label>Title:</label><input type="text" name="title[{%= file.name %}]">
            </div>
            <div class="row">
                <label>Short description (just a few words)</label><input name="one_liner" type="text" class="input-xxlarge" required>
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
                <a href="{%=file.document_url%}" title="{%=file.name%}">{%=file.metadata.title%}</a><br />
                <span class="maker">{%=file.metadata.maker%}</span>
            </p>
            {% if (file.error) { %}
                <div><span class="label label-important">Error</span> {%=file.error%}</div>
            {% } %}
        </td>
        <td>
            <span class="size">{%=o.formatFileSize(file.size)%}</span>
        </td>
        <td>
            <button class="btn btn-danger delete" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}"{% if (file.delete_with_credentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                <i class="icon-trash icon-white"></i>
                <span>Delete</span>
            </button>
            <input type="checkbox" name="delete" value="1" class="toggle">
        </td>
    </tr>
{% } %}
</script>

<?php

print aaaart_template_footer(array("js/upload.js"));

?>