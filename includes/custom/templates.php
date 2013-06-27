<?php

function aaaart_template_header($title='Website') {
	global $user;
	$script_url = BASE_URL;
	$site_name = SITE_TITLE;
	$nav = aaaart_template_nav();
	$title_bar = sprintf('%s | %s', SITE_TITLE, $title);
	$styles = array();
	if (!empty($user)) {
		$styles[] = sprintf('<link rel="stylesheet" href="%s%s">', $script_url, "css/style-user.css");
	} else {
		$styles[] = sprintf('<link rel="stylesheet" href="%s%s">', $script_url, "css/style-anon.css");
	}
	$styles[] = sprintf('<link rel="stylesheet" href="%s%s">', $script_url, "css/style-".LIST_TYPE.".css");
	$style_additions = implode("\n", $styles);
	$output = <<< EOF

	<!DOCTYPE HTML>
	<html lang="en">
	<head>
	<!-- Force latest IE rendering engine or ChromeFrame if installed -->
	<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
	<meta charset="utf-8">
	<title>{$title_bar}</title>
	<meta name="description" content="gallery">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Bootstrap CSS Toolkit styles -->
	<link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap.min.css">
	<!-- Generic page styles -->
	{$style_additions}
	<!-- Bootstrap styles for responsive website layout, supporting different screen sizes -->
	<link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap-responsive.min.css">
	<!-- Bootstrap CSS fixes for IE6 -->
	<!--[if lt IE 7]><link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap-ie6.min.css"><![endif]-->
	<!-- Bootstrap Image Gallery styles -->
	<link rel="stylesheet" href="http://blueimp.github.com/Bootstrap-Image-Gallery/css/bootstrap-image-gallery.min.css">
	<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
	<link rel="stylesheet" href="{$script_url}css/jquery.fileupload-ui.css">
	<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
	<link rel="stylesheet" href="{$script_url}css/bootstrap-markdown.min.css">
	<!-- CSS adjustments for browsers with JavaScript disabled -->
	<noscript><link rel="stylesheet" href="{$script_url}css/jquery.fileupload-ui-noscript.css"></noscript>
	</head>
	<body>
	<div class="navbar navbar-fixed-top">
	    <div class="navbar-inner">
	    <a class="brand" href="{$script_url}">{$site_name}</a>
	        <div class="container">
	            <div class="nav-collapse btn-group">
	                <ul id="nav" class="nav">
	                    {$nav}
	                    <li class="btn-grp search">
		                    <form class="form-search" action="{$script_url}collection/search.php" method="get">
											  <div class="input-append">
											    <input name="q" type="text" class="span2 search-query">
											    <button type="submit" class="btn">Search</button>
											  </div>
											</form>
										</li>
	                </ul>
	            </div>
	        </div>
	    </div>
	</div>
EOF;

	return $output;
}


function aaaart_template_footer($js=array()) {
	global $user;
	$script_url = BASE_URL;
	$js[] = "js/base-".LIST_TYPE.".js";
	$js[] = "js/base.js";
	if (LIST_TYPE=='grid') {
		$js[] = 'js/masonry.pkgd.min.js';
		$js[] = 'js/imagesloaded.js';
	}
	$js_additions = '';
	$output = '';

	$output .= aaaart_template_form_login($js);
	$output .= aaaart_template_form_invite($js);
	$output .= aaaart_template_form_request($js);
	$output .= aaaart_template_comment($js);
	$output .= aaaart_template_memex($js);
	$output .= aaaart_template_form_create_collection($js);

	$js = array_unique($js);
	foreach ($js as $f) {
		$js_additions .= "<script src=\"".$script_url.$f."\"></script>\n";
	}

	$output .= <<< EOF
	<div id="footer"></div>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="{$script_url}js/vendor/jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="{$script_url}js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="{$script_url}js/load-image.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="{$script_url}js/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS and Bootstrap Image Gallery are not required, but included for the demo -->
<script src="{$script_url}js/bootstrap.min.js"></script>
<script src="{$script_url}js/aaaart.bootstrap-image-gallery.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="{$script_url}js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="{$script_url}js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="{$script_url}js/jquery.fileupload-process.js"></script>
<!-- The File Upload image resize plugin -->
<script src="{$script_url}js/jquery.fileupload-resize.js"></script>
<!-- The File Upload validation plugin -->
<script src="{$script_url}js/jquery.fileupload-validate.js"></script>
<!-- The File Upload user interface plugin -->
<script src="{$script_url}js/jquery.fileupload-ui.js"></script>
<!-- markdown for bootstrap -->
<script src="{$script_url}js/bootstrap-markdown.js"></script>
<!-- preview markdown -->
<script src="{$script_url}js/markdown.js"></script>
<!-- A useful global variable -->
<script type="text/javascript">var base_url="{$script_url}";</script>
<!-- The main application script -->
{$js_additions}
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE8+ -->
<!--[if gte IE 8]><script src="{$script_url}js/cors/jquery.xdr-transport.js"></script><![endif]-->
</body> 
</html>
EOF;
	return $output;
}


function aaaart_template_nav() {
	$script_url = BASE_URL;
	$library_title = MAKERS_LABEL.'s';
	if (aaaart_user_verify_cookie()) {
return <<< EOF
      <li class="btn-group">
	      	<a class="btn" data-toggle="dropdown" href="#">Library <span class="caret"></span></a>
	      	<ul class="dropdown-menu">
				    <li><a href="{$script_url}collection/makers.php"><i class="icon-stop"></i> {$library_title}</a></li>
				    <li><a href="{$script_url}upload/"><i class="icon-arrow-up"></i> Upload</a></li>
				    <li><a data-toggle="modal" href="#request-form"><i class="icon-magnet"></i> Request</a></li>
				  </ul>
      </li>
      <li class="btn-group">
	      	<a class="btn" data-toggle="dropdown" href="#">Collections
	      	<span class="caret"></span></a>
	      	<ul class="dropdown-menu">
				    <li><a href="{$script_url}collection/list.php"><i class="icon-list"></i> All</a></li>
				    <li><a href="{$script_url}collection/list.php?show=mine"><i class="icon-list-alt"></i> Mine</a></li>
				    <li><a data-toggle="modal" href="#create-collection-form"><i class="icon-plus-sign"></i> Create</a></li>
				  </ul>
      </li>
      <li class="btn-group">
	      	<a class="btn" data-toggle="dropdown" href="#">Discussion
	      	<span class="caret"></span></a>
	      	<ul class="dropdown-menu">
				    <li><a href="{$script_url}comment/discussions.php"><i class="icon-comment"></i> Recent</a></li>
				    <li><a data-toggle="modal" data-target="#comments" class="comments" href="{$script_url}comment/thread.php"><i class="icon-plus-sign"></i> Create</a></li>
				  </ul>
      </li>
      <li  class="btn-group">
      		<a class="btn" data-toggle="modal" href="#site-invite-form">+ Invite</a></li>
      <li class="btn-group">
      		<a class="btn" href="{$script_url}user/index.php?action=logout">Logout</a>
      </li>
EOF;
	} else {
return <<< EOF
			<li class="btn">
	      	<a href="{$script_url}collection/makers.php">Library</a>
      </li>
      <li class="btn">
		    <a href="{$script_url}collection/list.php">Collections</a>
      </li>
      <li class="btn">
			  <a href="{$script_url}comment/discussions.php">Discussions</a>
      </li>
      <li class="btn">
      		<a data-toggle="modal" href="#modal-login-form">Login</a>
      </li>
EOF;
	}
}



function aaaart_template_form_create_collection(&$js = array()) {
	$script_url = BASE_URL;
	if (aaaart_collection_check_perm('create')) {
		$js[] = "js/collection-actions.js";
		$collection_type = aaaart_collection_type_field();
		return <<< EOF
<!-- modal form for creating a collection -->
<div id="create-collection-form" class="modal hide fade in" style="display: none; ">
    <div class="modal-header">
        <a class="close" data-dismiss="modal">×</a>  
        <h3>Create a new collection</h3>
    </div>
    <form class="modal-body" action="{$script_url}collection/index.php" method="POST">
        <input type="hidden" name="action" value="create" />
        <fieldset>
            <label><h5>Name</h5></label>
            <span class="help-block">Give a name for this new collection.</span>
            <input type="text" name="title" value="">
            
            <label><h5>Describe</h5></label>
            <span class="help-block">A short description, just a few words, to say what this collection will contain.</span>
            <input type="text" name="short_description" value="">
            
            <label><h5>Type</h5></label>
            <span class="help-block">Pick the type of collection you want to create. You can change it later.</span>
            {$collection_type}
        </fieldset>
    </form>
    <div class="modal-footer">
        <button class="btn btn-success" id="save">Save</button>
        <a href="#" class="btn" data-dismiss="modal">Cancel</a>
    </div>
</div>
EOF;
	}
	return '';
}

function aaaart_template_form_invite(&$js=array()) {
	$script_url = BASE_URL;
	if (!aaaart_user_check_perm('invite')) {
		return '';
	}
	$js[] = 'js/user.js';
	return <<< EOF
		<!-- modal invite form -->
    <div id="site-invite-form" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Invite</h3>
        </div>
        <form class="modal-body" action="{$script_url}user/index.php" method="POST">
            <input type="hidden" name="action" value="invite" />
            <fieldset>
	            <span class="help-block">Enter the email address of the person you want to invite. The system will send them an email. Please don\'t abuse this.</span>
	            <input class="input-xlarge" type="email" name="email" placeholder="Email address" required>
            </fieldset>
        </form>
        <div class="modal-footer">
            <button class="btn btn-success" id="invite">Invite</button>
            <a href="#" class="btn" data-dismiss="modal">Cancel</a>
        </div>
    </div>	
EOF;
}


function aaaart_template_form_login(&$js=array()) {
	$script_url = BASE_URL;
	if (!aaaart_user_check_perm('login')) {
		return '';
	}
	$js[] = 'js/user.js';
	return <<< EOF
		<!-- modal login form -->
    <div id="modal-login-form" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Login</h3>
        </div>
        <form class="modal-body">
            <input type="hidden" name="action" value="login" />
            <div class="message text-error"></div>
            <fieldset>
	            <label>Enter your key or email address.</label>
	            <input class="input-xlarge" name="key" required>
	            <label>Password</label>
			    		<input type="password" name="pass" />
            </fieldset>
        </form>
        <div class="modal-footer">
            <button class="btn btn-success login">Login</button>
            <a href="#" class="btn" data-dismiss="modal">Cancel</a>
        </div>
    </div>	
EOF;
}

function aaaart_template_form_request(&$js=array()) {
	$script_url = BASE_URL;
	if (!aaaart_collection_check_perm('request')) {
		return '';
	}
	$js[] = "js/collection-actions.js";
	$output = <<< EOF
	<!-- modal form for creating a request -->
	<div id="request-form" class="modal hide fade in" style="display: none; ">
	    <div class="modal-header">
	        <a class="close" data-dismiss="modal">×</a>  
	        <h3>Create a new request</h3>
	    </div>
	    <form class="modal-body" action="{$script_url}collection/index.php" method="POST">
	        <input type="hidden" name="action" value="request" />
	        <fieldset>
	            <label><h5>Title</h5></label>
	            <span class="help-block">The title of what you want to request.</span>
	            <input type="text" name="title" value="" placeholder="What?" required>

	            <label><h5>Maker</h5></label>
	            <span class="help-block">Separate multiple names with commas, eg: <em>Karl Marx, Friedrich Engels</em></span>
	            <input type="text" name="maker" value="" placeholder="Who?" required>
EOF;
	$sorter = aaaart_collection_sort_element();
	if (!empty($sorter)) {
	$output .= <<< EOF
	            <label><h5>Collection</h5></label>
	            <span class="help-block">You can put this request into a collection if you want.</span>
	            {$sorter}
EOF;
	}
	$output .= <<< EOF
	        </fieldset>
	    </form>
	    <div class="modal-footer">
	        <button class="btn btn-success" id="save">Request</button>
	        <a href="#" class="btn" data-dismiss="modal">Cancel</a>
	    </div>
	</div>
EOF;
	return $output;
}


function aaaart_template_comment($js=array()) {
	$script_url = BASE_URL;
	$js[] = "js/comment.js";
	return <<< EOF
		<!-- modal comments / comment form -->
    <div id="comments" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Comments</h3>
        </div>
        <div class="modal-body">
        	
        </div>
        <div class="modal-footer">
            <a href="#" class="btn" data-dismiss="modal">Close</a>
        </div>
    </div>	
EOF;
}


function aaaart_template_memex(&$js=array()) {
	$script_url = BASE_URL;

	$js[] = "js/memex.js";
	return <<< EOF
		<!-- modal memex / memex form -->
    <div id="memex-modal" class="modal hide fade in" style="display: none; ">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>  
            <h3>Edit trail</h3>
        </div>
        <div class="modal-body">
        	
        </div>
        <div class="modal-footer">
            <a href="#" class="btn" data-dismiss="modal">Close</a>
        </div>
    </div>	
EOF;
}


function aaaart_template_comment_button($ref_type, $id) {
	$script_url = BASE_URL;
	$threads = aaaart_comment_get_threads($ref_type, $id, true);
	$thread_items = array();
	// list
	foreach ($threads as $thread) {
		$thread_items[] = sprintf(
			'<li><a data-toggle="modal" data-target="#comments" class="comments" href="%scomment/thread.php?id=%s"><i class="icon-comment"></i> %s</a></li>',
			BASE_URL,
			$thread['_id'],
			$thread['title']
		);
	}
	// add
	if (aaaart_comment_check_perm('create_thread')) {
		$add_thread = sprintf('<li class="divider"></li><li><a data-toggle="modal" data-target="#comments" class="comments" href="%scomment/thread.php?ref_type=%s&ref_id=%s"><i class="icon-plus"></i> Start a new discussion thread</a></li>',
			BASE_URL,
			$ref_type,
			$id
		);
	} else {
		$add_thread = '';
	}

	return sprintf('<div class="btn-group">
		<a href="#" class="btn btn-mini btn-primary" data-toggle="dropdown" type="button"><i class="icon-comment icon-white"></i> comments</a>
		<a class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
		<ul class="dropdown-menu">
	    %s
	    %s
	  </ul>
	</div>', implode("\n", $thread_items), $add_thread);
}
?>