<?php

/*

// @todo: I started calling everything "image" but midway through changed to "document" - change it all!


Possible fields could be defined in config?

Document JSON structure in DB
{ 
	upload_date: timestamp
	uploader: object id
	title
	makers: { name 1, name 2 , ...}
	files: {
		{ name, size, type },
		{ name, size, type }
	}
	other: {
		something 1: string
		something 2: { string 1, string 2, ...}
	}

}

*/


function aaaart_image_check_perm($op, $image=false, $file=false) {
	if (aaaart_user_check_capability('do_anything')) {
		return true;
	}
	if ($op=='download' && ALLOW_ANON_DOWNLOADS) {
		return true;
	}
	if (!aaaart_user_check_perm()) {
		return false;
	}
	global $user;
	switch ($op) {
		case 'create': 
			return true;
		case 'update':
			// only image owners or site moderators can update
			return (aaaart_image_user_is_owner($user, $image));
		break;
		case 'delete':
			// only image owners or site moderators can update
		return (aaaart_image_user_is_owner($user, $image));
		case 'delete_file':
			// only image owners or site moderators can update
		return (aaaart_image_user_is_owner($user, $image) || aaaart_image_file_user_is_owner($user, $file));
		break;
		case 'request':
			if (aaaart_user_check_capability('ban_document_request')) return false;
			else return true;
		break;
		case 'upload':
			if (aaaart_user_check_capability('ban_document_upload')) return false;
			else return true;
		break;
		case 'download':
			if (aaaart_user_check_capability('ban_document_download')) return false;
			else return true;
		break;
		case 'save': // save to personal library
			if (aaaart_user_check_capability('ban_save')) return false;
			else return true;
		break;
		default:
			return true;
		break;
	}
}


/**
 * Checks if a user is owner of a collection
 */
function aaaart_image_user_is_owner($user, $document) {
	if (!empty($document['owner']) && !empty($user['_id'])) {
		if ($document['owner']==$user['_id']) return true;
	} 
	// fallback
	return false;
}

/**
 * Checks if a user is owner of a file
 */
function aaaart_image_file_user_is_owner($user, $file) {
	if (!empty($file['uploader']) && !empty($user['_id'])) {
		if ($file['uploader']==$user['_id']) return true;
	} 
	// fallback
	return false;
}


/**
 *
 */
function aaaart_image_get($id) {
	if ($id) {
		if (is_array($id) && !empty($id['_id'])) {
			// $id already is a document object
			return $id;
		}
		$doc = aaaart_mongo_get_one(IMAGES_COLLECTION, $id);
		if (is_array($doc)) {
			array_walk_recursive($doc, create_function('&$val', '$val = stripslashes($val);'));
		}
		return $doc;
	}
	return false;
}

/**
 *
 */
function aaaart_image_sha1_lookup($sha1) {
	return aaaart_mongo_get_one(IMAGES_COLLECTION, $sha1, "files.sha1");
}


/**
 *
 */
function aaaart_image_file_lookup($file_name) {
	return aaaart_mongo_get_one(IMAGES_COLLECTION, $file_name, "files.name");
}


/**
 *
 */
function aaaart_image_load_from_query_string() {
	$id = isset($_GET['id']) ? $_GET['id'] : false;
	if ($id) {
		return aaaart_image_get($id);
	}
	return false;
}


/*
 * Returns a document from a file
 */
function aaaart_image_get_document_from_file($mode, $id, $print_response=false) {
	$doc = array();
	switch ($mode) {
		case 'sha1':
			$doc = aaaart_image_sha1_lookup($id);
		break;
		case 'filename':
			$doc = aaaart_image_file_lookup($id);
		break;
	}
	if ($print_response) {
		if (empty($doc)) {
			$response = array( 'success' => false, 'message' => 'no document for that file' );
		} else {
			$response = array( 'success' => true, 'document' => $doc );	
		}
		return aaaart_utils_generate_response($response);
	} else {
		return $doc;
	}
}


/*
 * Is this a request?
 */
function aaaart_image_is_request($doc) {
	return (empty($doc['media']) && empty($doc['files']) && empty($doc['content']['content']));
}


/**
 * Returns HTML for a version of the image (version, as defined in config.php)
 */
function aaaart_image_display_image($document, $version=false, $download_full=false, $show_placeholder=true) {
	if (is_string($document)) {
		$document = aaaart_image_get($document);
	}
	// is this linked media?
	if (!empty($document['media'])) {
		$media = array_shift(array_values($document['media']));
		if (!empty($media['embed'])) {
			return $media['embed'];
		}
	}
	$image_file = aaaart_image_find_first_image($document);
	$file = aaaart_image_make_file_object($document, $image_file);
	if (empty($file)) {
		return '';
	}
	if (!$show_placeholder && empty($image_file)) {
		return '';
	}
	if (!empty($version) && !empty($file->{$version.'_url'})) {
		$url = $file->{$version.'_url'};
	} else if ($file->placeholder) {
		$url = (!empty($file->name)) ? sprintf(NONIMAGE_PLACEHOLDER, PLACEHOLDER_WIDTH, PLACEHOLDER_HEIGHT) : sprintf(EMPTY_PLACEHOLDER, PLACEHOLDER_WIDTH, PLACEHOLDER_HEIGHT);
	} else {
		$url = $file->url;
	}
	if ($download_full && !empty($file->url)) return sprintf('<a href="%s"><img src="%s"></a>', $file->url, $url);
	else return sprintf('<img src="%s">', $url);	
}


/** 
 * Adds placeholder images into a file object
 * If the doc is given, then we figure out what kind of placeholder should go in
 */
function aaaart_image_add_placeholders(&$file, $doc=false) {
	global $IMAGE_UPLOAD_OPTIONS;
	if (empty($IMAGE_UPLOAD_OPTIONS['image_versions'])) {
		return;
	}
	if (is_string($doc)) {
		$doc = aaaart_image_get($doc);
	}
	foreach ($IMAGE_UPLOAD_OPTIONS['image_versions'] as $version=>$options) {
		if (empty($file->{$version.'_url'}) && !empty($options['max_height']) && !empty($options['max_width'])) {
			$ratio = PLACEHOLDER_WIDTH / PLACEHOLDER_HEIGHT;
			$w = ($ratio<=1) ? $ratio*$options['max_width'] : $options['max_width'];
			$h = ($ratio>1) ? $options['max_height']/$ratio : $options['max_height'];
			$file->{$version.'_url'} = (!empty($file->name)) ? sprintf(NONIMAGE_PLACEHOLDER, $w, $h) : sprintf(EMPTY_PLACEHOLDER, $w, $h);
		}
	}
	// Keep track of whether 
	if (empty($file->name)) {
		$file->placeholder = true;
		$file->url = sprintf(EMPTY_PLACEHOLDER, PLACEHOLDER_WIDTH, PLACEHOLDER_HEIGHT);
	} else if (!aaaart_utils_is_image($file->name)) {
		$file->placeholder = true;
		$file->url = sprintf(NONIMAGE_PLACEHOLDER, PLACEHOLDER_WIDTH, PLACEHOLDER_HEIGHT);
	}
}

/*
 * Gets a string of all users who have contributed to a document
 */
function aaaart_image_get_sharers($image) {
	$users = array();
	if (!empty($image['owner'])) {
		$users[] = aaaart_user_get($image['owner']);
	}
	foreach ($image['files'] as $f) {
		if (!empty($f['uploader']) && $f['uploader']!=$image['owner']) {
			$users[] = aaaart_user_get($f['uploader']);
		}
	}
	return aaaart_user_format_simple_list($users);
}

/**
 * Get all versions of files for an image 
 */
function aaaart_image_get_files_for_image($id, $print_response=false) {
	global $IMAGE_UPLOAD_OPTIONS;
	$files = array();
	if (aaaart_image_check_perm('download')) {
		if ($id) {
			$doc = aaaart_mongo_get_one(IMAGES_COLLECTION, $id);
			if (!empty($doc['files'])) {
				$upload_handler = new AaaartUploadHandler($IMAGE_UPLOAD_OPTIONS);
				foreach ($doc['files'] as $file) {
					if (!empty($file['name'])) {
						$files[] = $upload_handler->make_file_object($file);
					}
				}
			}
			// add in special case of where we have content that is not in a file
			if (!empty($doc['content']['content'])) {
				$html = new StdClass();
				$html->name = 'HTML';
				$html->type = 'text/html';
				$html->size = strlen($doc['content']['content']);
				$html->url = sprintf('%simage/detail.php?id=%s&_v=html', BASE_URL, (string)$doc['_id']);
				$files[] = $html;
			}
		}
	}
	if (!$print_response) {
		return $files;
	} else {
		$response = array( 'files' => $files );
		return aaaart_utils_generate_response($response);
	}
}


/**
 * Gets all makers for a document
 */
function aaaart_image_get_makers_for_document($document_id, $print_response=false) {
	$doc = aaaart_image_get($document_id);
	$result = array();
	foreach ($doc['makers'] as $mref) {
		$m = aaaart_mongo_get_one(MAKERS_COLLECTION, $mref['$id']);
		$result[] = $m;
	}
	if ($print_response) {
		aaaart_mongo_stringify_ids($result);
		$response = array( 'makers' => $result );
		return aaaart_utils_generate_response($response);
	} else return $result;
}


/**
 * A document might have several files - if we are looking for an image to 
 * display (as a thumbnail or something like that) then this looks through 
 * each of the attached files to locate the first image file that it can use.
 */
function aaaart_image_find_first_image($doc) {
	if (!empty($doc['files'])) {
		foreach ($doc['files'] as $id=>$f) {
			if (!empty($f['name']) && aaaart_utils_is_image($f['name'])) {
				return $f;
			}
		}
	}
	return false;
}


/**
 * Creates a file object from Mongo document
 * The file object is what is sent to front end via JSON
 */
function aaaart_image_make_file_object($doc, $file_to_use = false) {
	global $IMAGE_UPLOAD_OPTIONS;
	if (is_string($doc)) {
		$doc = aaaart_image_get($doc);
	}
	if (!is_array($doc)) {
		return null;
	}
	/*
	// removed because requests can have null file
	if (empty($doc['files'])) {
		return null;
	}
	*/
	if (!empty($doc['files'])) {
		$first_file = array_shift(array_values($doc['files']));
		if (empty($first_file['name'])) {
			foreach ($doc['files'] as $f) {
				if (!empty($f['name'])) {
					// first file with a file name!
					$first_file = $f;
				}
			}
		}
	}
	if ($file_to_use) {
		$file_name = $file_to_use['name'];
		$upload_handler = new AaaartUploadHandler($IMAGE_UPLOAD_OPTIONS);
		$file = $upload_handler->make_file_object($file_to_use);
	} else if (empty($first_file['name'])) {
		$file = new StdClass();	
	} else {
		$file_name = $first_file['name'];
		$upload_handler = new AaaartUploadHandler($IMAGE_UPLOAD_OPTIONS);
		$file = $upload_handler->make_file_object($first_file);
	}
	if (!empty($file) || is_object($file)) {
		// mark if this is a request
		if (!empty($doc['media'])) {
			$file->is_media = true;
		} else if (empty($doc['files']) && empty($doc['content']['content'])) {
			$file->is_request = true;
		}
		// creation date
		$file->date = aaaart_utils_format_date($doc['created'],'s');
		// document id
		$file->document_id = (string)$doc['_id'];
		// Add in a link to detail page
		$file->detail_url = BASE_URL . 'image/detail.php?id=' . (string)$doc['_id'];
		// Add in more metadata from DB
		$file->metadata = new StdClass();
		$file->metadata->title = stripslashes($doc['title']);
		if (!empty($doc['metadata']['one_liner'])) {
			$file->metadata->one_liner = stripslashes($doc['metadata']['one_liner']);
	  }
	  $file->metadata->maker = $doc['makers_display'];
		// add in placeholder images
		aaaart_image_add_placeholders($file, $doc);
		return $file;
	}
	return null;
}


/**
 * Delete a file from an image
 * JSON response 
 */
function aaaart_image_delete_file($file_name) {
	global $IMAGE_UPLOAD_OPTIONS;
	$image = aaaart_image_file_lookup($file_name);
	$file = false;
	$count = 0;
	foreach ($image['files'] as $id=>$f) {
		if (!empty($f['name']) && ($f['name']==$file_name)) {
			$file = $f;
			$count++;
		}
	} 
	// special case so we dont delete files when we are just clearing our redundancies!
	if (!empty($image) && !empty($file) && aaaart_image_check_perm('delete_file', $image, $file) && $count>1) {
		aaaart_mongo_pull(IMAGES_COLLECTION, $image['_id'], array("files" => array("name" => $file_name)) );
		aaaart_mongo_push(IMAGES_COLLECTION, $image['_id'], array('files' => $file));
		$response = array( 'message' => 'It worked' );
		return aaaart_utils_generate_response($response);
	}
	// normal operation
	if (!empty($image) && !empty($file) && aaaart_image_check_perm('delete_file', $image, $file)) {
		$upload_handler = new AaaartUploadHandler($IMAGE_UPLOAD_OPTIONS);
		$success = $upload_handler->delete($file_name);
		if ($success) {
			aaaart_mongo_pull(IMAGES_COLLECTION, $image['_id'], array("files" => array("name" => $file_name)) );
			$response = array( 'message' => 'It worked' );
		} else {
			$response = array( 'message' => 'Sorry. I couldn\'t find the file to delete');
		}
	} else {
		$response = array( 'message' => 'It didn\'t work');
	}
	return aaaart_utils_generate_response($response);
}


/**
 * Delete an image and all its files
 * JSON response 
 */
function aaaart_image_delete_image($id) {
	global $IMAGE_UPLOAD_OPTIONS;
	$image = aaaart_image_get($id);
	$files = aaaart_image_get_files_for_image($id);
	if (!empty($image)) {
		$upload_handler = new AaaartUploadHandler($IMAGE_UPLOAD_OPTIONS);
		foreach ($files as $file) {
			$upload_handler->delete($file->name);
		}
		aaaart_mongo_remove(IMAGES_COLLECTION, $image['_id']);
		aaaart_cache_invalidate('new_documents');
		$response = array( 'message' => 'It worked' );
	} else {
		$response = array( 'message' => 'It didn\'t work');
	}
	return aaaart_utils_generate_response($response);
}

/**
 * Updates an image with values (includes final permission check)
 * JSON response
 */
function aaaart_image_update_image($id, $values) {
	$image = aaaart_image_get($id);
	if (aaaart_image_check_perm('update', $image)) {
		_aaaart_image_update_image($image, $values);
		$response = array( 'message' => 'It worked' );
	} else {
		$response = array( 'message' => 'It didn\'t work');
	}
	return aaaart_utils_generate_response($response);
}


/**
 * Does the actual work of updating the image (without checking any permissions)
 */
function _aaaart_image_update_image($image, $values) {
	global $IMAGE_FIELDS;
	$updated_data = array();
	$uid = aaaart_user_get_id();
	// standard fields
	if (!empty($values['title'])) {
		$updated_data['title'] = $values['title'];
	}

	if (!empty($values['maker'])) {
		aaaart_image_process_makers_string($values['maker'], $updated_data);
	}
	// all the other fields defined in fields array
	foreach ($IMAGE_FIELDS as $name=>$arr) {
		if (isset($values[$name])) {
			$updated_data[FIELDS_KEY][$name] = $values[$name];
		}
	}
	if (!empty($updated_data)) {
		$now = time();
		// update the data
		$updated_data['last_edit'] = $now;
		aaaart_mongo_update(IMAGES_COLLECTION, $image['_id'], $updated_data);
		// log this update @todomaybe: data revisions?!
		$this_edit = array(
			'time' => $now,
			'editor' => aaaart_mongo_id($uid),
			//'fields' => array_keys($updated_data)
		);
		aaaart_mongo_push(IMAGES_COLLECTION, $image['_id'], array('edits' => $this_edit));
		aaaart_solr_add_to_queue(IMAGES_COLLECTION, (string)$image['_id']);
		aaaart_cache_invalidate('new_documents');
	}
}


/**
 * Gets a list of makers beginning with a certain pattern
 * $filter can be a single letter or a range (like ab-an)
 */
function aaaart_image_filter_makers($filter, $print_response=false) {
	if (!empty($filter)) {
		$parts = explode('-', $filter);
		if (count($parts)==2) {
			$filter_str = substr($parts[0], 0, strlen($parts[0])-1);
			$filter_str .= '['.substr($parts[0], strlen($parts[0])-1, 1).'-'.substr($parts[1], strlen($parts[1])-1, 1).']';
		} else if (count($parts)==1) {
			$filter_str = $filter;
		} else return array();
		if ($filter_str=='.etc') {
			$regexObj = new MongoRegex("/^[^a-zA-z]/i"); 
		} else {
			$regexObj = new MongoRegex("/^".$filter_str."/i"); 
		}
		//$regexObj = new MongoRegex("/^a[b-f]/i"); 
		$names = aaaart_mongo_get( MAKERS_COLLECTION, array('last'=>$regexObj), array('last'=>1));
	} else {
		$names = aaaart_mongo_get( MAKERS_COLLECTION, array(), array('last'=>1));
	}
	$results = array();
	foreach ($names as $obj) {
		$results[] = $obj;
	}
	if ($print_response) {
		aaaart_mongo_stringify_ids($results);
		$response = array( 'makers' => $results );
		return aaaart_utils_generate_response($response);
	} else {
		return $results;
	}
}


/**
 * Gets a list of "saved" (by a user) texts whose makers beginning with a certain pattern
 * $filter can be a single letter or a range (like ab-an)
 */
function aaaart_image_filter_saved_documents($filter, $print_response=false) {
	global $user;
	if (empty($user['_id'])) {
		$results = array();
	} else {
		if (!empty($filter)) {
			$parts = explode('-', $filter);
			if (count($parts)==2) {
				$filter_str = substr($parts[0], 0, strlen($parts[0])-1);
				$filter_str .= '['.substr($parts[0], strlen($parts[0])-1, 1).'-'.substr($parts[1], strlen($parts[1])-1, 1).']';
			} else if (count($parts)==1) {
				$filter_str = $filter;
			} else return array();
			if ($filter_str=='.etc') {
				$regexObj = new MongoRegex("/^[^a-zA-z]/i"); 
			} else {
				$regexObj = new MongoRegex("/^".$filter_str."/i"); 
			}
			//$regexObj = new MongoRegex("/^a[b-f]/i"); 
			$names = aaaart_mongo_get( IMAGES_COLLECTION, array('saved_by' => $user['_id'], 'makers_sortby'=>$regexObj), array('makers_sortby'=>1));
		} else {
			$names = aaaart_mongo_get( IMAGES_COLLECTION, array('saved_by' => $user['_id']), array('makers_sortby'=>1));
		}
		$results = array();
		foreach ($names as $obj) {
			$results[] = $obj;
		}
	}
	if ($print_response) {
		aaaart_mongo_stringify_ids($results);
		return aaaart_image_generate_response_from_documents($results);
	} else {
		return $results;
	}
}


/*
 * Saves a document to a user's "saved" library (or unsaves it)
 */
function aaaart_image_save_document($id) {
	global $user;
	if ($user && aaaart_image_check_perm('save')) {
		$doc = aaaart_image_get($id);
		$current_count = (!empty($doc['saved_by'])) ? count($doc['saved_by']) : 0;
		$new_count = $current_count;
		if ($doc && !empty($doc['saved_by'])) {
			$uids = array_map('strval', $doc['saved_by']);
			if (in_array((string)$user['_id'], $uids)) {
				$new_count = $current_count - 1;
				aaaart_mongo_pull(IMAGES_COLLECTION, $doc['_id'], array("saved_by" => $user['_id']) );
				aaaart_mongo_update(IMAGES_COLLECTION, $doc['_id'], array("saved_by_count" => $new_count) );
				return;
			}
		}
		// If we got this far, we have to add it!
		$new_count = $current_count + 1;
		aaaart_mongo_push(IMAGES_COLLECTION, $doc['_id'], array('saved_by' => $user['_id']));
		aaaart_mongo_update(IMAGES_COLLECTION, $doc['_id'], array("saved_by_count" => $new_count) );
	}
}

/*
 * Makes a save button
 */
function aaaart_image_format_save_button($doc) {
	$uid = aaaart_user_get_id();
	if ($uid) {
		$already_saved = false;
		if (!empty($doc['saved_by'])) {
			$uids = array_map('strval', $doc['saved_by']);
			$already_saved = in_array($uid, $uids);
		}
		$is_request = aaaart_image_is_request($doc);
		$text_on = $is_request ? '+1 this request' : 'add to your library';
		$text_off = $is_request ? 'requested!' : 'saved!';
		return sprintf('<button class="btn btn-default btn-xs saver %s" data-add="%s" data-remove="%s">%s</button>',
			($already_saved) ? 'btn-success do-remove' : 'do-add',
			$text_on,
			$text_off,
			($already_saved) ? $text_off : $text_on
		);
	} else return '';
}

/**
 * Given a text string and a document
 * Parses the string into an array of names (with title, first, middle, last, suffix, display, sortby)
 * If the name is new, it creates a new name in the makers collection; otherwise it gets an existing reference
 * Finally it updates the $doc fields appropriately
 */
function aaaart_image_process_makers_string($str, &$doc) {
	$makers = aaaart_utils_parse_names($str);
	aaaart_image_process_makers_array($makers, $doc);
	$doc['makers_display'] = $str;
}


/** 
 * See aaaart_image_process_makers_string()
 */
function aaaart_image_process_makers_array($arr, &$doc) {
	$doc['makers'] = array();
	$sort_str = '';
	foreach ($arr as $name_arr) {
		$maker = aaaart_image_get_or_create_maker($name_arr);
		if (!empty($maker['_id'])) {
			$doc['makers'][] = aaaart_mongo_create_reference(MAKERS_COLLECTION, $maker['_id']);
			$sort_str .= sprintf('%s,%s,', $maker['last'], $maker['first']);
		}
	}
	$doc['makers_sortby'] = $sort_str;
}


/**
 * Looks for an existing maker, and creates it if it doesn't already exist
 */
function aaaart_image_get_or_create_maker($name) {
	$name_arr = (is_string($name)) ? array_shift(array_values(aaaart_utils_parse_names($name))) : $name;
	// match on first + last - other variations might just be detailed versions of the same name
	$maker = aaaart_mongo_get_one(MAKERS_COLLECTION, array('first'=>$name['first'], 'last'=>$name['last']));
	if (!empty($maker['_id'])) {
		return $maker;
	} else {
		return aaaart_mongo_insert(MAKERS_COLLECTION, $name_arr);
	}
}



/**
 * Callback for when an image has been successfully uploaded
 */
function aaaart_image_handle_form_data($request_data, $file, $index) {
	$uid = aaaart_user_get_id();
	$owner = aaaart_mongo_id($uid);
	$now = time();
	if (!empty($request_data['document-id'])) {
		$comment = (!empty($request_data['comment'])) ? $request_data['comment'] : '';
		// this file upload is for an image that already exists, so we only need to update it, not create it
		$file = array(
			'name' => (!empty($file->name)) ? $file->name : false,
      'size' => (!empty($file->size)) ? $file->size : false,
      'type' => (!empty($file->type)) ? $file->type : false,
      'sha1' => (!empty($file->sha1)) ? $file->sha1 : false,
      'uploader' => $owner,
      'upload_date' => $now,
      'comment' => $comment,
		);
		aaaart_mongo_push(IMAGES_COLLECTION, $request_data['document-id'], array('files' => $file));
		//aaaart_solr_add_to_queue(IMAGES_COLLECTION, $request_data['document-id']);
	} else {
		// this is a brand new image
		// $file->original_name is used so that we can recover the correct extra field information ($index doesn't work)
		$file->metadata->title = aaaart_utils_get_field_data($request_data, 'title', $file->original_name);
		// Handle the "maker" (it might be several people separated by commas)
		$makers_str = aaaart_utils_get_field_data($request_data, 'maker', $file->original_name);
		// there might be a one-line description
		$one_liner = aaaart_utils_get_field_data($request_data, 'one_liner', '');
		// This attributes array will be inserted
	  $attributes = array(
	  	'owner' => $owner,
	  	'created' => $now,
	  	'last_edit' => $now,
	  	'files' => array(
	  		array(
	  			'name' => (!empty($file->name)) ? $file->name : false,
		      'size' => (!empty($file->size)) ? $file->size : false,
		      'type' => (!empty($file->type)) ? $file->type : false,
		      'sha1' => (!empty($file->sha1)) ? $file->sha1 : false,
      		'uploader' => $owner,
		      'upload_date' => $now,
		      'comment' => 'original upload',
	  		)
	  	),
	  	'title' => $file->metadata->title,
	  	'saved_by' => array( $owner ),
	  	'saved_by_count' => 1,
	  	'metadata' => array(
	  		'one_liner' => $one_liner
	  	)
	  );
	  aaaart_image_process_makers_string($makers_str, $attributes);
		if (!empty($attributes['makers_display'])) {
			$file->metadata->maker = $attributes['makers_display'];
		}
		
	  if (empty($file->name) || empty($file->size) || empty($file->type)) {
	  	$attributes['files'] = array();
	      // Don't bother saving into the database if the image is messed up
	      // - note: commenting this out because this is basically a "request"
	      //return;
	  }
	  //debug($attributes);
	  $image = aaaart_mongo_insert(IMAGES_COLLECTION, $attributes);
	  $file->document_id = (string)$image['_id'];
	  $file->document_url = BASE_URL . 'image/detail.php?id=' . (string)$image['_id'];
	  aaaart_solr_add_to_queue(IMAGES_COLLECTION, (string)$image['_id']);
	  aaaart_cache_invalidate('new_documents');
	}
}

/*
 * Imports a video
 */
function aaaart_image_import_video($arr) {
	$embevi = new EmbeVi();
	if($embevi->parseUrl($arr['url'])){
		$now = time();
		$uid = aaaart_user_get_id();
		$owner = aaaart_mongo_id($uid);
    $media = array(
    	'url' => $arr['url'],
    	'embed' => $embevi->getCode(),
    	'embed_generated' => $now
    );
    $attributes = array(
	  	'owner' => $owner,
	  	'created' => $now,
	  	'last_edit' => $now,
	  	'files' => array(),
	  	'title' => $arr['title'],
	  	'media' => array($media), // embed in array so we could potentially have multiple? 
	  	'metadata' => array(
	  		'one_liner' => $arr['one_liner'],
	  	),
	  	'saved_by' => array( $owner ),
	  	'saved_by_count' => 1,
	  );
	  aaaart_image_process_makers_string($arr['maker'], $attributes);
		$image = aaaart_mongo_insert(IMAGES_COLLECTION, $attributes);
		aaaart_solr_add_to_queue(IMAGES_COLLECTION, (string)$image['_id']);
		aaaart_cache_invalidate('new_documents');
		$response = array( 'success' => true, 'document_id' => (string)$image['_id'] );
		return aaaart_utils_generate_response($response);
  } else {
  	// Can't import it!
  	$response = array( 'success' => false );
		return aaaart_utils_generate_response($response);
  }
}


/*
 * Imports a video
 */
function aaaart_image_import_html($arr) {
	$result = json_decode(file_get_contents(sprintf('http://www.readability.com/api/content/v1/parser?url=%s&token=%s', $arr['url'], READABILITY_API_KEY)));
	$data = (array)$result;
	if(!empty($data['content'])){
		$now = time();
		$uid = aaaart_user_get_id();
		$owner = aaaart_mongo_id($uid);
    $content = $data;
    $attributes = array(
	  	'owner' => $owner,
	  	'created' => $now,
	  	'last_edit' => $now,
	  	'files' => array(),
	  	'title' => $arr['title'],
	  	'metadata' => array(
	  		'one_liner' => $arr['one_liner'],
	  	),
	  	'content' => $content,
	  	'saved_by' => array( $owner ),
	  	'saved_by_count' => 1,
	  );
	  aaaart_image_process_makers_string($arr['maker'], $attributes);
		$image = aaaart_mongo_insert(IMAGES_COLLECTION, $attributes);
		aaaart_solr_add_to_queue(IMAGES_COLLECTION, (string)$image['_id']);
		aaaart_cache_invalidate('new_documents');
		$response = array( 'success' => true, 'document_id' => (string)$image['_id'] );
		return aaaart_utils_generate_response($response);
  } else {
  	// Can't import it!
  	$response = array( 'success' => false );
		return aaaart_utils_generate_response($response);
  }
}


/*
 * Prints out html content, if available
 */
function aaaart_image_get_html($document) {
	if (!empty($document['content']['content'])) {
		printf('<h1>%s</h1>', $document['title']); 
		print $document['content']['content'];
		exit;
	}
}


/**
 * Generate a JSON response from an iterable collection of images
 * The iterable collection comes from a mongo query
 */
function aaaart_image_generate_response_from_documents($documents, $extra=array()) {
	$uid = aaaart_user_get_id();
	$files = array();
	$saved = array(); // an array of the documents that the user has already saved
	foreach ($documents as $document) {
		$files[] = aaaart_image_make_file_object($document);
		if (!empty($uid) && !empty($document['saved_by'])) {
			$uids = array_map('strval', $document['saved_by']);
			if (in_array($uid, $uids)) {
				$saved[] = (string)$document['_id'];
			}
		}
	}
	$response = array_merge($extra, array( 'files' => $files, 'saved' => $saved ));
	return aaaart_utils_generate_response($response);
}

?>