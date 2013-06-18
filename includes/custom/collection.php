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
	);
	aaaart_mongo_update(COLLECTIONS_COLLECTION, $collection['_id'], $updated_data);
	aaaart_solr_add_to_queue(COLLECTIONS_COLLECTION, (string)$collection['_id']);
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


/**
 * The dropdown select with a list of collections available to the user
 */
function aaaart_collection_sort_element() {
	$output = '';
	$collections = aaaart_collection_get_user_collections();
	if (!empty($collections['initiated']) || !empty($collections['collaborating']) || !empty($collections['following'])) {
		$output .= '<div class="input-append">';
		$output .= '<select id="sort-into-collection" name="collection_id">';
		$output .= sprintf('<option value="%s" selected="selected">%s</option>', 'none', ' :: Add to collection ::');
		foreach ($collections as $group=>$list) {
			if (!empty($list)) {
				$output .= sprintf('<optgroup label="%s">', $group);
				foreach ($list as $collection) {
					$output .= sprintf('<option value="%s">%s</option>', $collection['_id'], $collection['title']);
				}
				$output .= '</optgroup>';
			}
		}
		$output .= '</select>';
		$output .= '<button class="btn" id="sort-into-collection-button">Add</button>';
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
			);
			aaaart_mongo_push(COLLECTIONS_COLLECTION, $collection_id, array('contents' => $addition));
			$response = array( 'message' => 'Added!', 'collection' => $collection);
		} else {
			$response = array( 'message' => 'That is already in this collection');
		}
	} else {
		$response = array( 'message' => 'You can\'t add to this collection');
	}
	aaaart_solr_add_to_queue(IMAGES_COLLECTION, $document_id);
	if ($print_response) {
		return aaaart_utils_generate_response($response);
	}
}


/**
 * Adds a document to a collection
 */
function aaaart_collection_remove_document($collection_id, $document_id) {
	$collection = aaaart_collection_get($collection_id);
	if (aaaart_collection_check_perm('remove', $collection)) {
		if (aaaart_collection_contains($collection_id, $document_id)) {
			aaaart_mongo_pull(COLLECTIONS_COLLECTION, $collection_id, array("contents" => array('object.$id' => aaaart_mongo_id($document_id))) );
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
function aaaart_collection_type_field($default = null) {
	global $COLLECTION_TYPES;
	$options = '';
	foreach ($COLLECTION_TYPES as $key=>$value) {
		$selected = (!empty($default) && ($default==$key)) ? 'selected="selected"' : '';
		$options .= sprintf('<option value="%s"%s>%s</option>', $key, $selected, $value); 
	}
	return sprintf('<select name="type">%s</select>', $options);
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
		$makers = array_slice($makers, 0, 10);
		$collections = array_slice($collections, 0, 7);
		aaaart_mongo_stringify_ids($makers);
		aaaart_mongo_stringify_ids($collections);
		$response = array( 'files' => $docs, 'makers' => $makers, 'collections'=> $collections );
		return aaaart_utils_generate_response($response);
	} else return $results;
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
			default:
				$result = array();
			break;
		}
	}
	aaaart_mongo_stringify_ids($result);
	$response = array( 'collections' => $result );
	return aaaart_utils_generate_response($response);
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
	return aaaart_collection_generate_response_from_documents($docs);
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
 * Get all images
 */
function aaaart_collection_get_all_images() {
	$documents = aaaart_mongo_get_paged(IMAGES_COLLECTION, array(), array('upload_date' => -1));
	return aaaart_collection_generate_response_from_documents($documents);
}


/**
 * Makes a request
 */
function aaaart_collection_make_request($values) {
	$file = new StdClass();
	aaaart_image_handle_form_data($values, $file, 0);
	if (!empty($values['collection_id']) && !empty($file->document_id)) {
		aaaart_collection_add_document($values['collection_id'], $file->document_id);
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
	$button_follow = sprintf('<a id="%s" class="btn btn-mini follow" href="#" type="button">Follow</a>', (string)$collection['_id']);
	$button_unfollow = sprintf('<a id="%s" class="btn btn-mini btn-inverse follow" href="#" type="button">Stop following</a>', (string)$collection['_id']);
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
function aaaart_collection_get_collected_documents($id, $print_response = false) {
	$collection = aaaart_collection_get($id);
	if (!empty($collection)) {
		$documents = array();
		foreach ($collection['contents'] as $c) {
			$document = aaaart_mongo_get_reference($c['object']);	
			$documents[ $document['makers_orderby'] ] = $document;
		}
		ksort($documents);
	}
	if ($print_response) {
		aaaart_collection_generate_response_from_documents($documents);
	}
}


/**
 * Get all images for a user
 */
function aaaart_collection_get_images_for_user($key=false) {
	if (!$key) $key = aaaart_user_get_id();
	$documents = aaaart_mongo_get(IMAGES_COLLECTION, array("uploader" => aaaart_mongo_id($key)));
	return aaaart_collection_generate_response_from_documents($documents);
}


/**
 * Generate a JSON response from an iterable collection of images
 * The iterable collection comes from a mongo query
 */
function aaaart_collection_generate_response_from_documents($documents) {
	$files = array();
	foreach ($documents as $document) {
		$files[] = aaaart_image_make_file_object($document);
	}
	$response = array( 'files' => $files );
	return aaaart_utils_generate_response($response);
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