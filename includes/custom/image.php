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


/**
 * Returns HTML for a version of the image (version, as defined in config.php)
 */
function aaaart_image_display_image($document, $version=false) {
	if (is_string($document)) {
		$document = aaaart_image_get($document);
	}
	$image_file = aaaart_image_find_first_image($document);
	$file = aaaart_image_make_file_object($document, $image_file);
	if (empty($file)) {
		return '';
	}
	if (!empty($version) && !empty($file->{$version.'_url'})) {
		$url = $file->{$version.'_url'};
	} else if ($file->placeholder) {
		$url = (!empty($file->name)) ? sprintf(NONIMAGE_PLACEHOLDER, PLACEHOLDER_WIDTH, PLACEHOLDER_HEIGHT) : sprintf(EMPTY_PLACEHOLDER, PLACEHOLDER_WIDTH, PLACEHOLDER_HEIGHT);
	} else {
		$url = $file->url;
	}
	return sprintf('<img src="%s">', $url);	
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
		if (empty($doc['files'])) {
			$file->is_request = true;
		}
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
	foreach ($image['files'] as $id=>$f) {
		if (!empty($f['name']) && ($f['name']==$file_name)) {
			$file = $f;
		}
	} 
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
	$now = time();
	if (!empty($request_data['document-id'])) {
		$comment = (!empty($request_data['comment'])) ? $request_data['comment'] : '';
		// this file upload is for an image that already exists, so we only need to update it, not create it
		$file = array(
			'name' => (!empty($file->name)) ? $file->name : false,
      'size' => (!empty($file->size)) ? $file->size : false,
      'type' => (!empty($file->type)) ? $file->type : false,
      'sha1' => (!empty($file->sha1)) ? $file->sha1 : false,
      'uploader' => aaaart_mongo_id($uid),
      'upload_date' => $now,
      'comment' => $comment,
		);
		aaaart_mongo_push(IMAGES_COLLECTION, $request_data['document-id'], array('files' => $file));
		aaaart_solr_add_to_queue(IMAGES_COLLECTION, $request_data['document-id']);
	} else {
		// this is a brand new image
		// $file->original_name is used so that we can recover the correct extra field information ($index doesn't work)
		$file->metadata->title = aaaart_utils_get_field_data($request_data, 'title', $file->original_name);
		// Handle the "maker" (it might be several people separated by commas)
		$makers_str = aaaart_utils_get_field_data($request_data, 'maker', $file->original_name);
		// This attributes array will be inserted
	  $attributes = array(
	  	'owner' => aaaart_mongo_id($uid),
	  	'upload_date' => $now,
	  	'last_edit' => $now,
	  	'files' => array(
	  		array(
	  			'name' => (!empty($file->name)) ? $file->name : false,
		      'size' => (!empty($file->size)) ? $file->size : false,
		      'type' => (!empty($file->type)) ? $file->type : false,
		      'sha1' => (!empty($file->sha1)) ? $file->sha1 : false,
      		'uploader' => aaaart_mongo_id($uid),
		      'upload_date' => $now,
		      'comment' => 'original upload',
	  		)
	  	),
	  	'title' => $file->metadata->title,
	  );
	  aaaart_image_process_makers_string($makers_str, $attributes);
		if (!empty($attributes['makers_display'])) {
			$file->metadata->maker = $attributes['makers_display'];
		}
		
	  if (empty($file->name) || empty($file->size) || empty($file->type)) {
	      // Don't bother saving into the database if the image is messed up
	      // - note: commenting this out because this is basically a "request"
	      //return;
	  }
	  //debug($attributes);
	  $image = aaaart_mongo_insert(IMAGES_COLLECTION, $attributes);
	  $file->document_id = (string)$image['_id'];
	  $file->document_url = BASE_URL . 'image/detail.php?id=' . (string)$image['_id'];
	  aaaart_solr_add_to_queue(IMAGES_COLLECTION, (string)$image['_id']);
	}
}

?>