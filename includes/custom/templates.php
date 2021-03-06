<?php

function aaaart_template_header($title='Website') {
	global $user;
	$script_url = BASE_URL;
	$site_name = SITE_TITLE;
	$nav = aaaart_template_nav();
	$title_bar = sprintf('%s | %s', SITE_TITLE, $title);
	$styles = array();
	$styles[] = sprintf('<link rel="stylesheet" href="%s%s">', $script_url, "css/style.css");
	if (!empty($user)) {
		$styles[] = sprintf('<link rel="stylesheet" href="%s%s">', $script_url, "css/style-user.css");
	} else {
		$styles[] = sprintf('<link rel="stylesheet" href="%s%s">', $script_url, "css/style-anon.css");
	}
	$styles[] = sprintf('<link rel="stylesheet" href="%s%s">', $script_url, "css/style-".LIST_TYPE.".css");
	$style_additions = implode("\n", $styles);
	if ($user) {
		$body_classes = "logged-in";
	} else {
		$body_classes = "logged-out";
	}
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
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
	<!--[if lt IE 7]><link rel="stylesheet" href="http://blueimp.github.com/cdn/css/bootstrap-ie6.min.css"><![endif]-->
	<!-- Stackable modals -->
	<link rel="stylesheet" href="{$script_url}css/bootstrap-modal.css">
	<!-- Bootstrap Image Gallery styles -->
	<link rel="stylesheet" href="http://blueimp.github.com/Bootstrap-Image-Gallery/css/bootstrap-image-gallery.min.css">
	<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
	<link rel="stylesheet" href="{$script_url}css/jquery.fileupload-ui.css">
	<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
	<link rel="stylesheet" href="{$script_url}css/bootstrap-markdown.min.css">
	<!-- CSS to style select boxes -->
	<link rel="stylesheet" href="{$script_url}css/bootstrap-select.min.css">
	<!-- Generic page styles -->
	{$style_additions}
	<!-- CSS adjustments for browsers with JavaScript disabled -->
	<noscript><link rel="stylesheet" href="{$script_url}css/jquery.fileupload-ui-noscript.css"></noscript>
	</head>
	<body class="{$body_classes}">
		<nav class="navbar" role="navigation">
		<div class="container">
			<ul id="nav" class="nav navbar-nav nav-pills">
        {$nav}
      </ul>
      <form class="navbar-form navbar-left form-search" role="search" action="{$script_url}collection/search.php" method="get">
			  <div class="form-group">
			    <input name="q" type="text" class="span2 search-query">
			  </div>
			  <div class="btn-group">
				  <button type="submit" class="btn btn-default">Search</button>
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				    <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu">
				    <li><a href="#" id="search-discussions">Search discussions</a></li>
				  </ul>
				</div>
			</form>
	  </div>
	  	<a id="site-title" href="{$script_url}">{$site_name}</a>
		
	  </nav>

	  
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
	$output .= aaaart_template_form_reference($js);
	$output .= aaaart_template_comment($js);
	$output .= aaaart_template_activity($js);
	$output .= aaaart_template_memex($js);
	$output .= aaaart_template_form_create_collection($js);

	$js = array_unique($js);
	foreach ($js as $f) {
		$js_additions .= "<script src=\"".$script_url.$f."\"></script>\n";
	}

	$output .= <<< EOF
	<div id="footer"></div>
	<script src="//code.jquery.com/jquery.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="{$script_url}js/vendor/jquery.ui.widget.js"></script>
<!-- The jQuery hover intent for better hovering -->
<script src="{$script_url}js/jquery.hoverIntent.minified.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="{$script_url}js/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="{$script_url}js/load-image.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="{$script_url}js/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS and Bootstrap Image Gallery -->
<script src="{$script_url}js/bootstrap.min.js"></script>
<script src="{$script_url}js/aaaart.bootstrap-image-gallery.js"></script>
<!-- Stackable modals -->
<script src="{$script_url}js/bootstrap-modal.js"></script>
<script src="{$script_url}js/bootstrap-modalmanager.js"></script>
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
<!-- markdown for bootstrap -->
<script src="{$script_url}js/bootstrap-select.min.js"></script>
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
	$library_title = MAKERS_LABEL.'s list';
	if (aaaart_user_verify_cookie()) {
		$activity_count = aaaart_user_get_activity_count();
$output = <<< EOF
      <li class="dropdown">
	      	<a class="dropdown-toggle" data-toggle="dropdown" href="#">Library <b class="caret"></b></a>
	      	<ul class="dropdown-menu">
	      		<li><a href="{$script_url}collection/makers.php"><span class="glyphicon glyphicon-cloud"></span> {$library_title}</a></li>
				    <li><a href="{$script_url}image/saved.php"><span class="glyphicon glyphicon-bookmark"></span> Saved</a></li>
				    <li><a href="{$script_url}upload/"><span class="glyphicon glyphicon-cloud-upload"></span> Add to Library</a></li>
				    <li class="divider"></li>
				    <li role="presentation" class="dropdown-header">Library requests</li>
				    <li><a href="{$script_url}collection/requests.php"><span class="glyphicon glyphicon-th"></span> See Requests</a></li>
				    <li><a data-toggle="modal" href="#request-form"><span class="glyphicon glyphicon-cloud-download"></span> Make a Request</a></li>
				  </ul>
      </li>
      <li class="dropdown">
	      	<a class="dropdown-toggle" data-toggle="dropdown" href="#">Collections
	      	<b class="caret"></b></a>
	      	<ul class="dropdown-menu">
				    <li><a href="{$script_url}collection/list.php"><span class="glyphicon glyphicon-list"></span> All</a></li>
				    <li><a href="{$script_url}collection/list.php?show=mine"><span class="glyphicon glyphicon-list-alt"></span> Mine</a></li>
				    <li><a data-toggle="modal" href="#create-collection-form"><span class="glyphicon glyphicon-plus-sign"></span> Create</a></li>
				  </ul>
      </li>
      <li class="dropdown">
	      	<a class="dropdown-toggle" data-toggle="dropdown" href="#">Discussion
	      	<span class="caret"></span></a>
	      	<ul class="dropdown-menu">
				    <li><a href="{$script_url}comment/discussions.php"><span class="glyphicon glyphicon-comment"></span> Recent (all)</a></li>
				    <li><a href="{$script_url}comment/discussions.php?show=commented"><span class="glyphicon glyphicon-comment"></span> Recent (mine)</a></li>
				    <li><a data-toggle="modal" data-target="#comments" class="comments" href="{$script_url}comment/thread.php"><span class="glyphicon glyphicon-plus-sign"></span> Create</a></li>
				  </ul>
      </li>
      <li class="dropdown">
	      	<a class="dropdown-toggle" data-toggle="dropdown" href="#">Website
	      	<span class="caret"></span></a>
	      	<ul class="dropdown-menu">
	      		<li><a href="{$script_url}about.php">About</a></li>
			      <li><a href="{$script_url}help.php">Help</a></li>
			      <li class="divider"></li>
	      		<li><a href="{$script_url}user/edit.php"><span class="glyphicon glyphicon-user"></span> Your account</a></li>
				    <li ><a data-toggle="modal" href="#site-invite-form"><span class="glyphicon glyphicon-plus-sign"></span> Invite someone</a></li>
				  	<li><a href="{$script_url}user/index.php?action=logout"><span class="glyphicon glyphicon-road"></span> Logout</a></li>
				  </ul>
      </li>
EOF;
		if ($activity_count>0) {
			$output .= 	sprintf('<li><a data-toggle="modal" data-target="#activity" href="%suser/activity.php" class="no-hover"><span class="label label-warning">%s</span></a></li> </li>',$script_url,$activity_count);
		} 
		return $output;
	} else {
return <<< EOF
			<li><a href="{$script_url}collection/makers.php">Library</a></li>
      <li><a href="{$script_url}collection/list.php">Collections</a></li>
      <li><a href="{$script_url}comment/discussions.php">Discussions</a></li>
      <li><a href="{$script_url}about.php">About</a></li>
      <li><a href="{$script_url}help.php">Help</a></li>
      <li><a data-toggle="modal" href="#modal-login-form">Login</a></li>
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
<div id="create-collection-form" class="modal " role="dialog" >
	<div class="modal-dialog">
    <div class="modal-content">
	    <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
	        <h4>Create a new collection</h4>
	    </div>
	    <form class="modal-body" action="{$script_url}collection/index.php" role="form" method="POST">
	        <input type="hidden" name="action" value="create" />
	        	<div class="form-group">
	            <label><h5>Name</h5></label>
	            <p class="help-block">Give a name for this new collection.</p>
	            <input type="text" class="form-control" name="title" value="">
	          </div>
	          <div class="form-group">
	            <label><h5>Describe</h5></label>
	            <p class="help-block">A short description, just a few words, to say what this collection will contain.</p>
	            <input type="text" class="form-control" name="short_description" value="">
	          </div>
	          <div class="form-group">  
	            <label><h5>Type</h5></label>
	            <p class="help-block">Pick the type of collection you want to create. You can change it later.</p>
	            {$collection_type}
	          </div>
	    </form>
	    <div class="modal-footer">
	        <button class="btn btn-success" id="save">Save</button>
	        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	    </div>
	  </div>
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
    <div id="site-invite-form" class="modal " role="dialog">
			<div class="modal-dialog">
    		<div class="modal-content">
	        <div class="modal-header">
	            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
	            <h3>Invite</h3>
	        </div>
	        <form class="modal-body" action="{$script_url}user/index.php" method="POST">
	            <input type="hidden" name="action" value="invite" />
	            <div class="form-group">
		            <p class="help-block">Enter the email address of the person you want to invite. The system will send them an email. Please don't abuse this.</p>
		            <input class="form-control" type="email" name="email" placeholder="Email address" required>
	            </div>
	        </form>
	        <div class="modal-footer">
	            <button class="btn btn-success" id="invite">Invite</button>
	            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	        </div>
	      </div>
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
    <div id="modal-login-form" class="modal " role="dialog">
			<div class="modal-dialog">
		    <div class="modal-content">    
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
            <h4>Login</h4>
        </div>
        <form class="modal-body">
            <input type="hidden" name="action" value="login" />
            <div class="message text-error"></div>
            	<div class="form-group">
		            <label>Enter your key or email address.</label>
		            <input class="form-control" name="key" required>
		          </div>
		          <div class="form-group">
		            <label>Password</label>
				    		<input class="form-control" type="password" name="pass" />
				    		<a href="#" class="forgot help-block">Forgotten your password?</a>
				    	</div>
        </form>
        <div class="modal-footer">
            <button class="btn btn-success login">Login</button>
            <button class="btn btn-primary reset" style="display:none;">Reset password</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
			  </div>
			</div>        
    </div>	
EOF;
}


function aaaart_template_form_reference(&$js=array()) {
	$script_url = BASE_URL;
	if (!aaaart_collection_check_perm('create')) {
		return '';
	}
	$js[] = 'js/reference.js';
	return <<< EOF
		<!-- modal reference form -->
    <div id="create-reference-form" class="modal " role="dialog">
			<div class="modal-dialog">
		    <div class="modal-content">

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
            <h4>le reference maker</h4>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="find" />
            <div class="form-group">
	            <p class="help-block">Search for something on the site.</p>
	            <div class="input-append">
						    <input name="q" type="text" class="input-xlarge">
						    <button type="submit" class="btn search">Search</button>
						  </div>
            </div>
            <ul class="makers list-inline"></ul>
            <ul class="collections list-inline"></ul>
            <ul class="list-unstyled images"></ul>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
			  </div>
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
	<div id="request-form" class="modal " role="dialog">
		<div class="modal-dialog">
	    <div class="modal-content">

	    <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
	        <h4>Create a new request</h4>
	    </div>
	    <form class="modal-body" action="{$script_url}collection/index.php" method="POST">
	        <input type="hidden" name="action" value="request" />
	        	<div class="form-group">
	            <label><h5>Title</h5></label>
	            <p class="help-block">The title of what you want to request.</p>
	            <input type="text" class="form-control" name="title" value="" placeholder="What?" required>
	          </div>
	          <div class="form-group">
	            <label><h5>Maker</h5></label>
	            <p class="help-block">Separate multiple names with commas, eg: <em>Karl Marx, Friedrich Engels</em></p>
	            <input type="text" class="form-control" name="maker" value="" placeholder="Who?" required>
	          </div>
	          <div class="form-group">
	            <label><h5>Short description</h5></label>
	            <p class="help-block">Please describe a little about what you're looking for</p>
	            <input type="text" class="form-control" name="one_liner" value="" placeholder="Short description" required>
	          </div>
EOF;
	$sorter = aaaart_collection_sort_element();
	if (!empty($sorter)) {
	$output .= <<< EOF
						<div class="form-group">
	            <label><h5>Collection</h5></label>
	            <span class="help-block">You can put this request into a collection if you want.</span>
	            {$sorter}
	          </div>
EOF;
	}
	$output .= <<< EOF
	    </form>
	    <div class="modal-footer">
	        <button class="btn btn-success" id="save">Request</button>
	        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	    </div>
		  </div>
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
    <div id="comments" class="modal" role="dialog">
		 	<div class="modal-dialog">
		    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
            <h4>Comments</h4>
        </div>
        <div class="modal-body">
        	
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
			  </div>
			</div>        
    </div>	
EOF;
}

function aaaart_template_activity($js=array()) {
	$script_url = BASE_URL;
	return <<< EOF
		<!-- modal activity form -->
    <div id="activity" class="modal " role="dialog">
			<div class="modal-dialog">
    		<div class="modal-content">

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
            <h4>Activity</h4>
        </div>
        <div class="modal-body">
        	
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
			  </div>
			</div>        
    </div>	
EOF;
}


function aaaart_template_memex(&$js=array()) {
	$script_url = BASE_URL;

	$js[] = "js/memex.js";
	return <<< EOF
		<!-- modal memex / memex form -->
    <div id="memex-modal" class="modal " role="dialog">
			<div class="modal-dialog">
		    <div class="modal-content">

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>  
            <h4>Edit trail</h4>
        </div>
        <div class="modal-body">
        	
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
			  </div>
			</div>        
    </div>	
EOF;
}


function aaaart_template_comment_button($ref_type, $id) {
	$script_url = BASE_URL;
	$threads = aaaart_comment_get_threads($ref_type, $id, true);
	$thread_items = array();
	$total_count = 0;
	// list
	foreach ($threads as $thread) {
		$post_count = (empty($thread['posts'])) ? 0 : count($thread['posts']);
		$total_count += $post_count;
		$thread_items[] = sprintf(
			'<li><a data-toggle="modal" data-target="#comments" class="comments" href="%scomment/thread.php?id=%s"><span class="glyphicon glyphicon-comment"></span> %s</a></li>',
			BASE_URL,
			$thread['_id'],
			$thread['title'] . ' ('.$post_count.')'
		);
	}
	// add
	if (aaaart_comment_check_perm('create_thread')) {
		$add_thread = sprintf('<li class="divider"></li><li><a data-toggle="modal" data-target="#comments" class="comments" href="%scomment/thread.php?ref_type=%s&ref_id=%s"><span class="glyphicon glyphicon-plus"></span> Start a new discussion thread</a></li>',
			BASE_URL,
			$ref_type,
			$id
		);
	} else {
		$add_thread = '';
	}

	return sprintf('<div class="btn-group btn-group-xs">
		<a href="#" class="btn btn-primary" data-toggle="dropdown" type="button"><span class="glyphicon glyphicon-comment icon-white"></span> comments %s</a>
		<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
		<ul class="dropdown-menu">
	    %s
	    %s
	  </ul>
	</div>', 
		'('.$total_count.')',
		implode("\n", $thread_items), 
		$add_thread);
}
?>