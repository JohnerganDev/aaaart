<?php


$COLLECTION_TYPES = array(
	'private' => 'Private: It should not appear in the list of collections. Only you (or people you invite) can add and remove items.',
	'semi-public' => 'Public: It should be visible in the list of collections. But only invited collaborators can change what is in it.',
	'public' => 'Shared: The list is visible and anyone at all can add to this list - only collaborators can remove things.',
);
###

function aaaart_collection_check_perm($op, $collection_id=null, $document=false) {
	global $user;
	if (aaaart_user_check_capability('do_anything')) {
		return true;
	}
	if (!aaaart_user_check_perm()) {
		return false;
	}
	$collection = aaaart_collection_get($collection_id);
	$is_owner = aaaart_collection_user_is_owner($user, $collection);
	$is_editor = aaaart_collection_user_is_editor($user, $collection);
	$is_follower = aaaart_collection_user_is_following($user, $collection);
	switch ($op) {
		case 'create': 
			if (aaaart_user_check_capability('ban_collection_create')) return false;
			else return true;
		case 'follow': 
			if (aaaart_user_check_capability('ban_collection_follow')) return false;
			else return true;
		case 'update':
			// only collection editors can update
			if (aaaart_user_check_capability('ban_collection_update')) return false;
			else return ( $is_owner || $is_editor);
		break;
		case 'delete':
			// only owner or site moderator can delete
			if (aaaart_user_check_capability('ban_collection_delete')) return false;
			else return ( $is_owner );
		break;
		case 'add':
			// check issue settings (collaborators or followers)
			if (aaaart_user_check_capability('ban_collection_add')) return false;
			else if (!aaaart_image_check_perm('upload')) return false;
			else if ( $is_owner || $is_editor) return true;
			else if (!empty($collection['type']) && ($collection['type']=='private')) return false;
			else return ( $is_follower );
		break;
		case 'remove':
			// only collection editors or the person who added the document can remove
			if (aaaart_user_check_capability('ban_collection_remove')) return false;
			else if ( $is_owner || $is_editor) return true;
			// @todo: allow person who added something to remove it
			else return false;
		break;
		default:
			return true;
		break;
	}
}

/**
 * Get all images for a collection
 */
function aaaart_collection_get($id) {
	if ($id) {
		if (is_array($id) && !empty($id['_id'])) {
			// $id already is a collection object
			return $id;
		}
		$doc = aaaart_mongo_get_one(COLLECTIONS_COLLECTION, $id);
		return $doc;
	}
	return false;
}


/**
 *
 */
function aaaart_collection_load_maker_from_query_string() {
	$id = isset($_GET['id']) ? $_GET['id'] : false;
	if ($id) {
		return aaaart_mongo_get_one(MAKERS_COLLECTION, $id);
	}
	return false;
}


/**
 *
 */
function aaaart_collection_load_from_query_string() {
	$id = isset($_GET['id']) ? $_GET['id'] : false;
	if ($id) {
		return aaaart_collection_get($id);
	}
	return false;
}


/**
 * Does the collection contain a document?
 */
function aaaart_collection_contains($collection_id, $document_id) {
	$collection = aaaart_collection_get($collection_id);
	if (!empty($collection['contents'])) {
		foreach ($collection['contents'] as $c) {
			if (!empty($c['object']['$id']) && (string)$c['object']['$id']==$document_id) {
				return true;
			}
		}
	} 
	// fallback
	return false;
}


/**
 * Creates a new collection
 */
function aaaart_collection_create($values) {
	$uid = aaaart_user_get_id();
	$now = time();
	$attributes = array(
		'owner' => aaaart_mongo_id($uid),
  	'created' => $now,
  	'changed' => $now,
  	'type' => $values['type'],
  	'title' => $values['title'],
  	'short_description' => $values['short_description'],
  	'editors' => array(), // a list of user ids of people who can edit 
  	'invitations' => array(), // a list of email addresses
  	'contents' => array(), // what is in this collection
	);
	$collection = aaaart_mongo_insert(COLLECTIONS_COLLECTION, $attributes);
	aaaart_solr_add_to_queue(COLLECTIONS_COLLECTION, (string)$collection['_id']);
}


/**
 * Get all images for a collection
 */
function aaaart_collection_update($id, $values) {
	$collection = aaaart_collection_get($id);
	$now = time();
	$updated_data = array(
		'changed' => $now,
  	'type' => $values['type'],
  	'title' => $values['title'],
  	'short_description' => $values['short_description'],
  	'metadata' => array('description' => $values['description']),
	);
	aaaart_mongo_update(COLLECTIONS_COLLECTION, $collection['_id'], $updated_data);
	aaaart_solr_add_to_queue(COLLECTIONS_COLLECTION, (string)$collection['_id']);
}


/*
 * Updates a note
 */
function aaaart_collection_update_note($collection_id, $document_id, $note) {
	$collection = aaaart_collection_get($collection_id);
	foreach ($collection['contents'] as $key=>$item) {
		if ((string)$item['object']['$id']==$document_id) {
			$collection['contents'][$key]['notes'] = $note;
			aaaart_mongo_update(COLLECTIONS_COLLECTION, $collection_id, array('contents' => $collection['contents']));
			return true;
		}
	}
	return false;
}


/**
 * Delete a collection
 * JSON response 
 */
function aaaart_collection_delete($id) {
	$collection = aaaart_collection_get($id);
	if (!empty($collection)) {
		aaaart_mongo_remove(COLLECTIONS_COLLECTION, $collection['_id']);
		$response = array( 'message' => 'It worked' );
	} else {
		$response = array( 'message' => 'It didn\'t work');
	}
	return aaaart_utils_generate_response($response);
}


/*
 * Adds a section into the collection
 */
function aaaart_collection_create_section($id, $values) {
	$uid = aaaart_user_get_id();
	$now = time();
	$order = (!empty($values['order'])) ? $values['order'] : 1;
	$collection = aaaart_collection_get($id);
	$new_section = array(
		'_id' => aaaart_mongo_new_id(),
		'owner' => aaaart_mongo_id($uid),
  	'created' => $now,
  	'changed' => $now,
  	'title' => $values['title'],
  	'description' => $values['description'],
  	'order' => $order,
	);
	// push other sections depending on order
	if (!empty($collection['sections'])) {
		foreach ($collection['sections'] as $key=>$section) {
			if ($section['order']>=$order) {
				// bump up the others
				$collection['sections'][$key]['order'] = $collection['sections'][$key]['order'] + 1;
			}
		}
		aaaart_mongo_update(COLLECTIONS_COLLECTION, $id, array('sections' => $collection['sections']));
	} 
	aaaart_mongo_push(COLLECTIONS_COLLECTION, $id, array('sections' => $new_section));	
}


/*
 * Adds a section into the collection
 */
function aaaart_collection_update_section($id, $section_id, $arr) {
	$now = time();
	$order = (!empty($arr['order'])) ? $arr['order'] : 1;
	$collection = aaaart_collection_get($id);
	if (!empty($collection) && !empty($collection['sections'])) {
		foreach ($collection['sections'] as $key=>$section) {
			if ($section_id==(string)$section['_id']) {
				$collection['sections'][$key]['title'] = $arr['title'];
				$collection['sections'][$key]['description'] = $arr['description'];
				$collection['sections'][$key]['changed'] = $now;
				$collection['sections'][$key]['order'] = $order;
			} else if ($section['order']>=$order) {
				// bump up the others
				$collection['sections'][$key]['order'] = $collection['sections'][$key]['order'] + 1;
			}
		}
		aaaart_mongo_update(COLLECTIONS_COLLECTION, $id, array('sections' => $collection['sections']));
	}
}


/*
 * Sorts a document into a section within a collection
 */
function aaaart_collection_sort_into_section($collection_id, $section_id, $document_id) {
	$collection = aaaart_collection_get($collection_id);
	foreach ($collection['contents'] as $key=>$item) {
		if ((string)$item['object']['$id']==$document_id) {
			$collection['contents'][$key]['section'] = $section_id;
			aaaart_mongo_update(COLLECTIONS_COLLECTION, $collection_id, array('contents' => $collection['contents']));
			return true;
		}
	}
	return false;
}


/*
 * Gets a section from a collection
 */
function aaaart_collection_get_section($id, $section_id) {
	$collection = aaaart_collection_get($id);
	if (!empty($collection) && !empty($collection['sections'])) {
		foreach ($collection['sections'] as $key=>$section) {
			if ($section_id==(string)$section['_id']) {
				return $section;
			}
		}
	}
	return false;
}


/**
 * The dropdown select with a list of collections available to the user
 */
function aaaart_collection_sort_element() {
	global $COLLECTION_TYPES;
	$output = '';
	$collections = aaaart_collection_get_user_collections();
	if (!empty($collections['initiated']) || !empty($collections['collaborating']) || !empty($collections['following'])) {
		$output .= '<div class="input-group" style="width:100px">';
		$output .= '<select id="sort-into-collection" class="selectpicker" data-style="btn-default btn-xs" multiple title="Sort into collection(s)" name="collection_id">';
		//$output .= sprintf('<option value="%s" selected="selected">%s</option>', 'none', ' :: Add to collection ::');
		foreach ($collections as $group=>$list) {
			if (!empty($list)) {
				$output .= sprintf('<optgroup label="%s">', $group);
				foreach ($list as $collection) {
					if (!aaaart_user_check_capability('do_anything') && $group=='following' && $collection['type']!='public' ) {
						$output .= sprintf('<option disabled="disabled" value="%s">%s</option>', $collection['_id'], $collection['title']);
					} else {
						$output .= sprintf('<option value="%s">%s</option>', $collection['_id'], $collection['title']);
					}
				}
				$output .= '</optgroup>';
			}
		}
		$output .= '</select>';
		$output .= '<span class="input-group-btn"><button class="btn btn-default btn-xs" id="sort-into-collection-button">Add</button></span>';
		$output .= '</div>';
	}
	return $output;
}


/**
 * Adds a document to a collection
 */
function aaaart_collection_add_document($collection_id, $document_id, $print_response=false) {
	$collection = aaaart_collection_get($collection_id);
	if (aaaart_collection_check_perm('add', $collection)) {
		if (!aaaart_collection_contains($collection_id, $document_id)) {
			$uid = aaaart_user_get_id();
			$now = time();
			$addition = array(
				'adder' => aaaart_mongo_id($uid),
				'object' => aaaart_mongo_create_reference(IMAGES_COLLECTION, $document_id),
				'added' => $now,
				'notes' => '',
			);
			aaaart_mongo_push(COLLECTIONS_COLLECTION, $collection_id, array('contents' => $addition));
			aaaart_cache_invalidate('active_collections');
			$response = array( 'message' => 'Added!', 'collection' => $collection);
		} else {
			$response = array( 'message' => 'That is already in this collection');
		}
	} else {
		$response = array( 'message' => 'You can\'t add to this collection');
	}
	aaaart_collection_push_activity($collection, $addition);
	aaaart_solr_add_to_queue(IMAGES_COLLECTION, $document_id);
	if ($print_response) {
		return aaaart_utils_generate_response($response);
	}
}


/**
 * Adds a document to a collection
 */
function aaaart_collection_add_document_to_collections($collection_ids, $document_id, $print_response=false) {
	$uid = aaaart_user_get_id();
	$now = time();
	$ref = aaaart_mongo_create_reference(IMAGES_COLLECTION, $document_id);
	$added = array();
	foreach ($collection_ids as $collection_id) {
		$collection = aaaart_collection_get($collection_id);
		if (aaaart_collection_check_perm('add', $collection)) {
			if (!aaaart_collection_contains($collection_id, $document_id)) {
				$addition = array(
					'adder' => aaaart_mongo_id($uid),
					'object' => $ref,
					'added' => $now,
					'notes' => '',
				);
				aaaart_mongo_push(COLLECTIONS_COLLECTION, $collection_id, array('contents' => $addition));
				$added[] = $collection;
			} else {
				//$response = array( 'message' => 'That is already in this collection');
			}
		} else {
			//$response = array( 'message' => 'You can\'t add to this collection');
		}
		aaaart_collection_push_activity($collection, $addition);
		
	}
	if (!empty($added)) {
		aaaart_solr_add_to_queue(IMAGES_COLLECTION, $document_id);
		aaaart_cache_invalidate('active_collections');
		$response = array( 'message' => sprintf('Added to %s collections!', count($added)), 'collections' => $added);
	} else {
		$response = array( 'message' => 'Sorry, there were errors.');
	}
	if ($print_response) {
		return aaaart_utils_generate_response($response);
	}
}

/*
 * Formats activity and pushes it to all relevant users
 */
function aaaart_collection_push_activity($collection, $addition) {
	$document = aaaart_mongo_get_reference($addition['object']);	
	$formatted = sprintf('<span class="user">%s</span> added <span class="document">%s</span> to <span class="collection">%s</span><span class="note">%s</span>',
		aaaart_user_format_display_name($addition['adder']),
		$document['title'],
		$collection['title'],
		aaaart_truncate($addition['notes'], 200)
	);
	// now find the users that this activity applies to
	$users = array();
	$followers = aaaart_collection_get_followers($collection);
	foreach ($followers as $f) {
		if ($addition['adder']!=(string)$f['_id'] ) {
			$users[ (string)$f['_id'] ] = $f['_id'];
		}
	}
	aaaart_user_push_activity($users, $formatted, 'image/detail.php?id='.(string)$document['_id']);
}

/**
 * Adds a document to a collection
 */
function aaaart_collection_remove_document($collection_id, $document_id) {
	$collection = aaaart_collection_get($collection_id);
	if (aaaart_collection_check_perm('remove', $collection)) {
		if (aaaart_collection_contains($collection_id, $document_id)) {
			aaaart_mongo_pull(COLLECTIONS_COLLECTION, $collection_id, array("contents" => array('object.$id' => aaaart_mongo_id($document_id))) );
			aaaart_cache_invalidate('active_collections');
			$response = array( 'message' => 'Removed!');
		} else {
			$response = array( 'message' => 'That is not in this collection');
		}
	} else {
		$response = array( 'message' => 'You can\'t remove from this collection');
	}
	aaaart_solr_add_to_queue(IMAGES_COLLECTION, $document_id);
	return aaaart_utils_generate_response($response);
}

/*
 * When creating or editing a collection, the owner can set what "type" it is.
 * This function outputs the form element
 */
function aaaart_collection_type_field($default = 'semi-public') {
	global $COLLECTION_TYPES;
	$options = '';
	/*
	foreach ($COLLECTION_TYPES as $key=>$value) {
		$selected = (!empty($default) && ($default==$key)) ? 'selected="selected"' : '';
		$options .= sprintf('<option value="%s"%s>%s</option>', $key, $selected, $value); 
	}
	return sprintf('<select name="type" class="selectpicker">%s</select>', $options);
	*/
	foreach ($COLLECTION_TYPES as $key=>$value) {
		$selected = (!empty($default) && ($default==$key)) ? ' checked' : '';
		$options .= sprintf('<div class="radio"><label><input type="radio" name="type" id="options-%s" value="%s"%s>%s</label></div>',
			$key,
			$key,
			$selected,
			$value
		);
	}
	return $options;
}

/**
 * Runs a search. Displays results as a kind of collection
 */
function aaaart_collection_search($query, $print_response=false) {
	$solr = new Solr();
	// we query for facets as well as results
	$results = $solr->simpleQuery($query, IMAGES_COLLECTION, array(), array('makers','collections'));
	if ($print_response) {
		$docs = array();
		if (!empty($results['docs'])) {
			foreach ($results['docs'] as $result) {
				$docs[] = aaaart_image_make_file_object($result['id']);
			}
		}
		if (!empty($results['facets']['makers'])) {
			$look_for = array();
			foreach ($results['facets']['makers'] as $id=>$count) {
				if ($count>0) {
					$lookfor[] = aaaart_mongo_id($id);
				}
			}
			if (!empty($lookfor)) {
				$makers = iterator_to_array(
					aaaart_mongo_get(
						MAKERS_COLLECTION, 
						array('_id' => array('$in' => $lookfor))
				));
			} else {
				$makers = array();
			}
		}
		// direct maker query
		$regexObj = new MongoRegex("/".$query."/i"); 
		$names = aaaart_mongo_get( MAKERS_COLLECTION, array('display'=>$regexObj), array('last'=>1));
		foreach ($names as $name) {
			array_unshift($makers, $name);
		}
		if (!empty($results['facets']['collections'])) {
			$look_for = array();
			foreach ($results['facets']['collections'] as $id=>$count) {
				if ($count>0) {
					$lookfor[] = aaaart_mongo_id($id);
				}
			}
			if (!empty($lookfor)) {
				$collections = iterator_to_array(
					aaaart_mongo_get(
						COLLECTIONS_COLLECTION, 
						array('_id' => array('$in' => $lookfor))
				));
			} else {
				$collections = array();
			}
		}

		$makers = (!empty($makers)) ? array_slice($makers, 0, 10) : array();
		$collections = (!empty($collections)) ? array_slice($collections, 0, 7) : array();
		aaaart_mongo_stringify_ids($makers);
		aaaart_mongo_stringify_ids($collections);
		$response = array( 'files' => $docs, 'makers' => $makers, 'collections'=> $collections );
		return aaaart_utils_generate_response($response);
	} else return $results;
}


/**
 * Gets recently active collections
 */
function aaaart_collections_get_active_collections($num=15) {
	$ret_arr = iterator_to_array(
		aaaart_mongo_get(COLLECTIONS_COLLECTION, array('type' => array('$ne' => 'private')), array('contents.added'=>-1, 'title'=> 1), array('metadata'=>0))
	);
	$ret_arr = array_slice($ret_arr, 0, $num);
	return $ret_arr;
}

/*
 * Formats a set of active collections, showing recently added texts
 */
function aaaart_collection_format_active_collections($num=15, $time_window=86400, $max_per_collection=4) {
	if ($cached = aaaart_cache_get('active_collections')) {
			return $cached;
	}
	$collections = aaaart_collections_get_active_collections($num);
	$cutoff = time() - $time_window;
	$output = '';
	foreach ($collections as $collection) {
		$count = 0;
		$to_display = array();
		$most_recent_collection = false;
		foreach ($collection['contents'] as $c) {
			if ($c['added']>$cutoff) {
				$to_display[ $c['added'] ] = $c;
			}
			if (!$most_recent_collection || (!empty($most_recent_collection['added']) && ($most_recent_collection['added']<$c['added']))) {
				$most_recent_collection = $c;
			}
		}
		if (empty($to_display) && $most_recent_collection) {
			$to_display[ $most_recent_collection['added'] ] = $most_recent_collection;
		}
		if (!empty($to_display)) {
			$output_title = sprintf('<h4><a class="text-danger" href="%scollection/detail.php?id=%s">%s</a></h4>', BASE_URL, (string)$collection['_id'], $collection['title']);
			$output_inner = '';
			$count = 0;
			krsort($to_display);
			foreach ($to_display as $c) {
				if ($count<$max_per_collection) {
					$document = aaaart_mongo_get_reference($c['object']);	
					$output_inner .= sprintf('<li><h5><a href="%simage/detail.php?id=%s">%s</a></h5>%s</li>', 
						BASE_URL, 
						(string)$document['_id'], 
						$document['title'],
						(!empty($document['metadata']['one_liner'])) ? '<p class="muted">'.$document['metadata']['one_liner'].'</p>' : ''
					);
					$count++;
				}
			}
			if (!empty($output_inner)) {
				$output .= sprintf('<li class="list-group-item">%s<ul class="list-unstyled">%s</ul></li>', $output_title, $output_inner);
			}
		}
	}
	if (!empty($output)) {
		//$output = sprintf('<li>%s</li>%s','<h5 class="muted">recently sorted</h5>',$output);
	}
	aaaart_cache_set('active_collections', $output);
	return $output;
}

/**
 * Gets collections that a user owns
 */
function aaaart_collections_get_nonprivate_collections($filter_letter=false) {
	if ($filter_letter) {
		$regexObj = new MongoRegex("/^".$filter_letter."/i"); 
		return iterator_to_array(
			aaaart_mongo_get(COLLECTIONS_COLLECTION, array('title' => $regexObj, 'type' => array('$ne' => 'private')), array('title'=> 1), array('contents'=>0, 'metadata'=>0))
		);
	} else {
		return iterator_to_array(
			aaaart_mongo_get(COLLECTIONS_COLLECTION, array('type' => array('$ne' => 'private')), array('title'=> 1), array('contents'=>0, 'metadata'=>0))
		);
	}
}


/**
 * Gets collections that a user owns
 */
function aaaart_collections_get_collections_for_owner($u) {
	if ($u) {
		return iterator_to_array(
			aaaart_mongo_get(COLLECTIONS_COLLECTION, array('owner' => $u['_id']), array('title'=> 1))
		);
	} else return array();
}


/**
 * Gets collections that a user owns
 */
function aaaart_collections_get_collections_for_editor($u) {
	if ($u) {
		return iterator_to_array(
			aaaart_mongo_get(COLLECTIONS_COLLECTION, array('editor' => $u['_id']), array('title'=> 1))
		);
	} else return array();
}


/**
 * Gets collections that a user owns
 */
function aaaart_collections_get_collections_for_follower($u) {
	if ($u && !empty($u['following']['collections'])) {
		$following = array();
		foreach ($u['following']['collections'] as $f) {
			$following[] = $f['ref']['$id'];
		}
		return iterator_to_array(
			aaaart_mongo_get(COLLECTIONS_COLLECTION, array('_id' => array('$in' => $following)), array('title'=> 1))
		);
	} else return array();
}


/**
 * Gets a list of all collections that a user (a) owns (b) edits or (c) follows
 */
function aaaart_collection_get_user_collections($u=false) {
	global $user;
	$collections = array();
	if (empty($u)) {
		$u = $user;
	}
	if (!empty($u)) {
		$collections = array(
			'initiated' => aaaart_collections_get_collections_for_owner($u),
			'collaborating' => aaaart_collections_get_collections_for_editor($u),
			'following' => aaaart_collections_get_collections_for_follower($u),
		);
	}
	return $collections;
}


/**
 * $show = all, mine (needs no other arguments), user, document, filter
 * $arg = user id, document _id, or filter letter
 */
function aaaart_collection_list_collections($show, $arg=false, $print_response = false) {
	global $user;
	if (empty($show)) {
		$result = array();
	} else {
		switch ($show) {
			case 'all':
				$result = aaaart_collections_get_nonprivate_collections();
			break;
			case 'filter':
				$result = aaaart_collections_get_nonprivate_collections($arg);
			break;
			case 'mine':
				global $user;
				$result = aaaart_collection_get_user_collections($user);
			break;
			case 'user':
				$result = aaaart_collection_get_user_collections( aaaart_user_get($arg) );
			break;
			case 'document':
				$result = aaaart_collection_get_document_collections($arg);
			break;
			case 'active':
				$result = aaaart_collections_get_active_collections();
			break;
			default:
				$result = array();
			break;
		}
	}
	aaaart_mongo_stringify_ids($result);
	$response = array( 'collections' => $result );
	return aaaart_utils_generate_response($response);
}


/*
 * Get all requests
 * $num only applies to the most requested
 */
function aaaart_collection_get_requests($sort, $filter=false) {
	if ($sort=='date') {
		$documents = aaaart_mongo_get_paged(IMAGES_COLLECTION, array('files'=>array(), 'content'=>array('$exists'=>false), 'media'=>array('$exists'=>false)), array('_id' => -1));
		return aaaart_image_generate_response_from_documents($documents);
	} else if ($sort=='maker') {
		if (!$filter) $filter=='a';
		$regexObj = new MongoRegex("/^".$filter."/i"); 
		$documents = aaaart_mongo_get( IMAGES_COLLECTION, array('makers_sortby'=>$regexObj, 'files'=>array(), 'content'=>array('$exists'=>false), 'media'=>array('$exists'=>false)), array('last'=>1));
		return aaaart_image_generate_response_from_documents($documents);
	} else if ($sort=='most') {
		$documents = iterator_to_array(
			aaaart_mongo_get_paged(IMAGES_COLLECTION, array('files'=>array(), 'content'=>array('$exists'=>false), 'media'=>array('$exists'=>false)), array('saved_by_count' => -1)
		));
		return aaaart_image_generate_response_from_documents($documents);
	}
} 


/**
 * Get all documents for a maker 
 */
function aaaart_collection_get_documents_by_maker($key, $print_response=false) {
	$docs = array();
	if (!empty($key)) {
		$docs = aaaart_mongo_get(
				IMAGES_COLLECTION, 
				array('makers' => aaaart_mongo_create_reference(MAKERS_COLLECTION, $key)), 
				array('title'=> 1)
		);
	}
	return aaaart_image_generate_response_from_documents($docs);
}


/**
 * Gets all collections associated with a maker
 */
function aaaart_collection_get_collections_for_maker($maker_id, $print_response=false) {
	$solr = new Solr();
	$facets = $solr->getFacets(IMAGES_COLLECTION, 'collections', 'makers', $maker_id);
	$look_for = array();
	foreach ($facets as $id=>$count) {
		if ($count>0) {
			$lookfor[] = aaaart_mongo_id($id);
		}
	}
	if (!empty($lookfor)) {
		$result = iterator_to_array(
			aaaart_mongo_get(
				COLLECTIONS_COLLECTION, 
				array('_id' => array('$in' => $lookfor))
		));
		// hide private collections not owned by this user
		global $user;
		if (!empty($user['_id']))
		foreach ($result as $k=>$v) {
			if ($v['type']=='private' && $v['owner']!=$user['_id']) {
				unset($result[$k]);
			}
		}
	} else {
		$result = array();
	}
	if ($print_response) {
		aaaart_mongo_stringify_ids($result);
		$response = array( 'collections' => $result );
		return aaaart_utils_generate_response($response);
	} else return $result;
}


/**
 * Gets all collections associated with a maker
 */
function aaaart_collection_get_makers_for_collection($collection_id, $print_response=false) {
	$solr = new Solr();
	$facets = $solr->getFacets(IMAGES_COLLECTION, 'makers', 'collections', $collection_id);
	$look_for = array();
	foreach ($facets as $id=>$count) {
		if ($count>0) {
			$lookfor[] = aaaart_mongo_id($id);
		}
	}
	if (!empty($lookfor)) {
		$result = iterator_to_array(
			aaaart_mongo_get(
				MAKERS_COLLECTION, 
				array('_id' => array('$in' => $lookfor))
		));
	} else {
		$result = array();
	}
	if ($print_response) {
		aaaart_mongo_stringify_ids($result);
		$response = array( 'makers' => $result );
		return aaaart_utils_generate_response($response);
	} else return $result;
}


/**
 * Same as function below, but it creates an RSS feed
 */
function aaaart_collection_new_documents_rss() {
	$documents = aaaart_mongo_get_paged(IMAGES_COLLECTION, array(), array('_id' => -1));
	aaaart_image_generate_rss_feed_from_documents($documents);
}

/**
 * Get all images
 */
function aaaart_collection_get_all_images() {
	global $user;
	if (empty($user) && ($cached = aaaart_cache_get('new_documents'))) {
		return aaaart_utils_generate_response($cached);
	}
	$documents = aaaart_mongo_get_paged(IMAGES_COLLECTION, array(), array('_id' => -1));
	$response = aaaart_image_generate_response_from_documents($documents);
	if (empty($user)) {
	 	aaaart_cache_set('new_documents', $response);
	}
	return $response;
}


/**
 * Makes a request
 */
function aaaart_collection_make_request($values) {
	$file = new StdClass();
	aaaart_image_handle_form_data($values, $file, 0);
	if (!empty($values['collection_id']) && !empty($file->document_id)) {
		if (is_array($values['collection_id'])) {
			aaaart_collection_add_document_to_collections($values['collection_id'], $file->document_id);
		} else {
			aaaart_collection_add_document($values['collection_id'], $file->document_id);
		}
	}
}


/**
 * Invites someone to collaborate on an issue
 */
function aaaart_collection_invite_collaborator($id, $email) {
	$collection = aaaart_collection_get($id);
	$invitee = aaaart_user_get($email, 'email');
	if (!$invitee) {
		$invitee = aaaart_user_create_invitation();
	}
	if (!empty($collection['editors'])) {
		foreach ($collection['editors'] as $editor) {
			if ((string)$editor['invitee'] == (string)$invitee['_id']) {
				return; // already an editor
			}
		}
	}
	if (!empty($invitee['_id'])) {
		$uid = aaaart_user_get_id();
		$now = time();
		$addition = array(
			'inviter' => aaaart_mongo_id($uid),
			'invitee' => $invitee['_id'],
			'invited' => $now,
		);
		aaaart_mongo_push(COLLECTIONS_COLLECTION, $id, array('editors' => $addition));

		$message = sprintf("you were invited to collaborate on a collection, %s, here\n\n%scollection/detail.php?id=%s\n\n you now have permissions to edit the collection, add and remove contents, etc.",
				$collection['title'],
				BASE_URL,
				(string)$collection['_id']);
		aaaart_utils_send_email($email, sprintf('an invitation: %s', $collection['title']), $message);
	}
}


/*
 * Gets a list of followers
 */
function aaaart_collection_get_followers($collection) {
	return iterator_to_array(
		aaaart_mongo_get(
			PEOPLE_COLLECTION, 
			array('following.collections.ref' => aaaart_mongo_create_reference(COLLECTIONS_COLLECTION, (string)$collection['_id']))
	));
}

/**
 * Formats a string showing collaborators and followers
 */
function aaaart_collection_format_followers($collection) {
	if (aaaart_collection_check_perm('update', $collection)) {
		// link to invite
		// link to stop following
	} else {
		// link to follow/ stop following	
	}
	return "hello";
}


/**
 * Returns the follow button that a user should see.
 * $toggle will perform the appropriate action and return the resulting button
 */
function aaaart_collection_get_follow_button($collection_id, $user_id=false, $toggle=false) {
	$user = aaaart_user_get($user_id);
	$collection = aaaart_collection_get($collection_id);
	$is_following = aaaart_collection_user_is_following($user, $collection);
	$is_editor = aaaart_collection_user_is_editor($user, $collection);
	$is_owner = aaaart_collection_user_is_owner($user, $collection);
	$button_follow = sprintf('<a id="%s" class="btn btn-xs follow" href="#" type="button">Follow</a>', (string)$collection['_id']);
	$button_unfollow = sprintf('<a id="%s" class="btn btn-xs btn-inverse follow" href="#" type="button">Stop following</a>', (string)$collection['_id']);
	$show_button = ($is_following) ? $button_unfollow : $button_follow;
	if ($is_owner) {
		// owners can't do any following
		return '';
	}
	if (!empty($collection['type']) && ($collection['type']=='private') && !$is_editor) {
		// only editors can see follow button on private collections
		return '';
	}
	if ($toggle) {
		if ($is_following) {
			if (aaaart_collection_unfollow($user, $collection)) {
				$show_button = $button_follow;
			}
		} else {
			if (aaaart_collection_follow($user, $collection)) {
				$show_button = $button_unfollow;
			}
		}
	}
	return $show_button;
}


/**
 * Adds a collection to user's following list
 */
function aaaart_collection_follow($user, $collection) {
	if (empty($user['_id']) || empty($collection['_id'])) {
		return false;
	}
	if (!aaaart_collection_user_is_following($user, $collection)) {
		$now = time();
		$addition = array(
			'ref' => aaaart_mongo_create_reference(COLLECTIONS_COLLECTION, (string)$collection['_id']),
			'time' => $now,
		);
		aaaart_mongo_push(PEOPLE_COLLECTION, $user['_id'], array('following.collections' => $addition));
		return true;
	} else {
		return false;
	}
}


/**
 * Removes a collection to user's following list
 */
function aaaart_collection_unfollow($user, $collection) {
	if (aaaart_collection_user_is_following($user, $collection)) {
		if (empty($user['_id']) || empty($collection['_id'])) {
			return false;
		}
		aaaart_mongo_pull(PEOPLE_COLLECTION, $user['_id'], array("following.collections" => array('ref.$id' => $collection['_id'])) );
		return true;
	}
	return true;
}


/**
 * Checks if a user is following a collection
 */
function aaaart_collection_user_is_following($user, $collection) {
	if (!empty($user['following']['collections'])) {
		foreach ($user['following']['collections'] as $c) {
			if (!empty($c['ref']['$id']) && $c['ref']['$id']==$collection['_id']) {
				return true;
			}
		}
	} 
	// fallback
	return false;
}


/**
 * Checks if a user is owner of a collection
 */
function aaaart_collection_user_is_owner($user, $collection) {
	if (!empty($collection['owner']) && !empty($user['_id'])) {
		if ($collection['owner']==$user['_id']) return true;
	} 
	// fallback
	return false;
}


/**
 * Checks if a user is editor of a collection
 */
function aaaart_collection_user_is_editor($user, $collection) {
	if (!empty($collection['editors']) && !empty($user['_id'])) {
		foreach ($collection['editors'] as $editor_id) {
			if ($editor_id==$user['_id']) return true;
		}
	} 
	// fallback
	return false;
}


/**
 * Gets a list of all collections that have a document
 */
function aaaart_collection_get_document_collections($key, $include_private=false, $print_response=false) {
	if (!empty($key)) {
		$result = iterator_to_array(
			aaaart_mongo_get(
				COLLECTIONS_COLLECTION, 
				array('contents.object' => aaaart_mongo_create_reference(IMAGES_COLLECTION, $key)), 
				array('title'=> 1)
		));
		// remove all private collections not owned by this user
		global $user;
		if (!empty($user['_id']))
		foreach ($result as $k=>$v) {
			if ($v['type']=='private' && $v['owner']!=$user['_id']) {
				unset($result[$k]);
			}
		}
	} else {
		$result = array();
	}
	if ($print_response) {
		$response = array('collections' => $result);
		aaaart_utils_generate_response($response);
	} else return $result;
}

/**
 * Gets a list of all collections that have a document
 */
function aaaart_collection_format_document_collections($key, $include_private=false, $include_makers=false) {
	$output = '';
	$collections = aaaart_collection_get_document_collections($key, $include_private);
	if (!empty($collections)) {
		$output .= '<ul class="collections inline">';
		if ($include_makers) {
			$doc = aaaart_image_get($key);
			foreach ($doc['makers'] as $mref) {
				$m = aaaart_mongo_get_one(MAKERS_COLLECTION, $mref['$id']);
				$output .= sprintf(
					'<li><a href="%s">%s</a></li>', 
					sprintf('%scollection/maker.php?id=%s', BASE_URL, $m['_id']),
					$m['display']
				);
			}
		}
		foreach ($collections as $collection) {
			$output .= sprintf(
				'<li><a href="%s">%s</a></li>', 
				sprintf('%scollection/detail.php?id=%s', BASE_URL, $collection['_id']),
				$collection['title']
			);
		}
		$output .= '</ul>';
	}
	return $output;
}

/**
 * Get all images for a collection
 */
function aaaart_collection_get_collected_documents($id, $order_by_maker = true, $print_response = false) {
	$collection = aaaart_collection_get($id);
	if (!empty($collection)) {
		$documents = array();
		foreach ($collection['contents'] as $c) {
			$document = aaaart_mongo_get_reference($c['object']);	
			if ($order_by_maker) {
				$key = $document['makers_sortby'] . '-' . $document['title'] . '-' . (string)$document['_id'];
			} else {
				$key = $c['added']. '-' . (string)$document['_id'];
			}
			$documents[ $key ] = $document;
		}
		if ($order_by_maker) {
			ksort($documents);
		} else {
			krsort($documents);
		}
	}
	if ($print_response) {
		aaaart_image_generate_response_from_documents($documents);
	}
}


/**
 * Get all documents for a collection, along with section info
 */
function aaaart_collection_get_documents_and_sections($id, $print_response = false) {
	$collection = aaaart_collection_get($id);
	$sections = array();
	$map = array();
	if (!empty($collection['sections'])) {
		foreach ($collection['sections'] as $s) {
			$order = (!empty($s['order'])) ? (int)$s['order'] : 1;
			$id = (string)$s['_id'];
			$sections[$id] = array(
				'id' => $id,
				'order' => $order,
				'title' => $s['title'],
				'description' => aaaart_first_paragraph_teaser($s['description']), 
			);
		}
		uasort($sections, create_function('$a, $b',
   'if ($a["order"] == $b["order"]) return 0; return ($a["order"] < $b["order"]) ? -1 : 1;'));
	}

	if (!empty($collection)) {
		$documents = array();
		foreach ($collection['contents'] as $c) {
			$document = aaaart_mongo_get_reference($c['object']);	
			$key = $document['makers_sortby'] . '-' . $document['title'] . '-' . (string)$document['_id'];
			$documents[ $key ] = $document;
			// If the document is in a section consult the map
			if (!empty($c['section']) && array_key_exists($c['section'], $sections)) {
				$map[ (string)$document['_id'] ]['section'] = (string)$c['section'];
			}
			if (!empty($c['notes'])) {
				$map[ (string)$document['_id'] ]['notes'] = $c['notes'];
			}
		}
		ksort($documents);
	}
	
	if ($print_response) {
		$files = array();
		foreach ($documents as $document) {
			$f = aaaart_image_make_file_object($document);
			if (!empty($f->document_id)) {
				$id = $f->document_id;
				if (array_key_exists($id, $map)) {
					if (!empty($map[$id]['section'])) {
						$f->section = $map[$id]['section'];
					}
					if (!empty($map[$id]['notes'])) {
						$f->metadata->one_liner .= ' '.stripslashes($map[$id]['notes']);
						$f->metadata->additional_notes = stripslashes($map[$id]['notes']);
					}
				}
				$files[] = $f;
			}
		}
		$response = array( 'sections'=>$sections, 'files' => $files );
		return aaaart_utils_generate_response($response);
	}
}


/**
 * Get all images for a user
 */
function aaaart_collection_get_images_for_user($key=false) {
	if (!$key) $key = aaaart_user_get_id();
	$documents = aaaart_mongo_get(IMAGES_COLLECTION, array("uploader" => aaaart_mongo_id($key)));
	return aaaart_image_generate_response_from_documents($documents);
}


/**
 * Callback for when an image has been successfully uploaded
 */
function aaaart_collection_handle_form_data($request_data, $file, $index) {
	if (!empty($file->document_id) && !empty($request_data['collection-id'])) {
		aaaart_collection_add_document($request_data['collection-id'] , $file->document_id);
	}
}
?>