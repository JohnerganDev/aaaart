<?php

function aaaart_comment_check_perm($op, $comment=false) {
	if (aaaart_user_check_capability('do_anything')) {
		return true;
	}
	if (!aaaart_user_check_perm()) {
		return false;
	}
	switch ($op) {
		case 'create': 
			return true;
		case 'create_thread': 
			return true;
		case 'update':
			// only comment owners or site moderators can update
		break;
		case 'update_thread':

		break;
		case 'delete':
			// only comment owners or site moderators can update
		break;
		case 'delete_thread':
			// 
		break;
		default:
			return true;
		break;
	}
}


/**
 *
 */
function aaaart_comment_get_thread($id) {
	if ($id) {
		if (is_array($id) && !empty($id['_id'])) {
			// $id already is a collection object
			return $id;
		}
		$thread = aaaart_mongo_get_one(COMMENTS_COLLECTION, $id);
		return $thread;
	}
	return false;
}


/**
 * Gets posts in descending time order
 */
function aaaart_comment_get_ordered_posts($thread) {
	$posts = array();
	if (!empty($thread['posts'])) {
		foreach ($thread['posts'] as $post) {
			aaaart_comment_prepare_for_display($post);
			$posts[ $post['created'] ] = $post;
		}
		krsort($posts);
	}
	return $posts;
}


/**
 * Gets posts in descending time order AND adds formatting fields for display
 */
function aaaart_comment_get_formatted_posts($thread) {
	$posts = array();
	if (!empty($thread['posts'])) {
		foreach ($thread['posts'] as $post) {
			$posts[ $post['created'] ] = $post;
		}
		arsort($posts);
	}
	return $posts;
}

/**
 *
 */
function aaaart_comment_load_thread_from_query_string() {
	$id = isset($_GET['id']) ? $_GET['id'] : false;
	if ($id) {
		return aaaart_mongo_get_one(COMMENTS_COLLECTION, $id);
	}
	return false;
}


/**
 * Create a thread
 */
function aaaart_comment_create_thread($arr) {
	global $user;
	$now = time();
	$attributes = array(
		'title' => isset($arr['title']) ? $arr['title'] : 'Untitled discussion',
		'created' => $now,
		'changed' => $now,
		'owner' => $user['_id'],
		'posts' => array(),
	);
	if (!empty($arr['ref_type']) && !empty($arr['ref_id'])) {
		$ref = aaaart_mongo_get_one($arr['ref_type'], $arr['ref_id']);
		if (!empty($ref)) {
			$attributes['owner'] = "-1";
			$attributes['ref'] = aaaart_mongo_create_reference($arr['ref_type'], $arr['ref_id']);
		}
	}
	if (!empty($arr['message'])) {
		$attributes['posts'][] = array(
			'id' => aaaart_mongo_new_id(),
			'text' => $arr['message'],
			'owner' => $user['_id'],
			'created' => $now,
			'changed' => $now
		);
	}
	$thread = aaaart_mongo_insert(COMMENTS_COLLECTION, $attributes);
	aaaart_solr_add_to_queue(COMMENTS_COLLECTION, (string)$thread['_id']);
	return $thread;
}

/**
 * Create a comment in a thread
 */
function aaaart_comment_create($thread_id, $arr) {
	global $user;
	$now = time();
	$post = array(
		'id' => aaaart_mongo_new_id(),
		'text' => $arr['message'],
		'owner' => $user['_id'],
		'created' => $now,
		'changed' => $now
	);
	aaaart_mongo_push(COMMENTS_COLLECTION, $thread_id, array('posts' => $post));
	aaaart_solr_add_to_queue(COMMENTS_COLLECTION, $thread_id);
}

/**
 * Create a thread
 */
function aaaart_comment_update_thread($id, $arr) {

}

/**
 * Create a comment in a thread
 */
function aaaart_comment_update($id, $arr) {
	
}


/**
 * Delete a thread
 */
function aaaart_comment_delete_thread($id) {
	
}

/**
 * Delete a comment in a thread
 */
function aaaart_comment_delete($id) {
	
}


/**
 * List threads
 * $show can be "new" for newest threads, or a reference type
 * $arg is the reference id
 */
function aaaart_comment_list_threads($show, $arg=false, $print_response = false) {
	if ($show=="new") {
		$result = aaaart_comment_get_new_threads();
	} else if (in_array($show, array(IMAGES_COLLECTION, COLLECTIONS_COLLECTION, MAKERS_COLLECTION))) {
		$result = aaaart_comment_get_threads($show, $arg);
	} else {
		$result = array();
	}
	foreach ($result as $id=>$thread) {
		if (!empty($thread['posts'])) {
			unset($result[$id]['posts']);
		}
	}
	if ($print_response) {
		aaaart_mongo_stringify_ids($result);
		$response = array( 'threads' => $result );
		return aaaart_utils_generate_response($response);
	} else return $result;
}


/**
 * List comments
 * $show can be "new" for newest comments, or "thread"
 * $arg is the thread id
 */
function aaaart_comment_list_comments($show, $arg=false, $print_response = false) {
	if ($show=="new") {
		$result = aaaart_comment_get_new_comments();
	} else if ($show=="thread") {
		$result = aaaart_comment_get_comments($$arg);
	} else {
		$result = array();
	}
	if ($print_response) {
		aaaart_mongo_stringify_ids($result);
		$response = array( 'comments' => $result );
		return aaaart_utils_generate_response($response);
	} else return $result;
}


/**
 * Gets a list of new threads
 */
function aaaart_comment_get_new_threads($num = 50) {

}


/**
 * Gets a list of threads
 */
function aaaart_comment_get_threads($type, $id, $create_if_none=false) {
	$result = array();
	//
	if (!empty($type) && !empty($id)) {
		$result = iterator_to_array(aaaart_mongo_get(
				COMMENTS_COLLECTION, 
				array('ref' => aaaart_mongo_create_reference($type, $id)), 
				array('created'=> 1)
		));
	}
	//
	if (empty($result) && $create_if_none) {
		$result[] = aaaart_comment_create_thread(array(
			'title' => 'General discussion',
			'ref_type' => $type,
			'ref_id' => $id,
		));
	}
	return $result;
}


/**
 * Gets a list of new comments
 */
function aaaart_comment_get_new_comments($num = 50) {
	$result = array();
	$threads = aaaart_mongo_get_paged(
		COMMENTS_COLLECTION, 
		array(),
		array('posts.created'=> -1)
	);
	foreach ($threads as $thread) {
		$newest_post = current(aaaart_comment_get_ordered_posts($thread));
		if (!empty($newest_post)) {
			aaaart_comment_prepare_for_display($newest_post);
			$newest_post['thread_id'] = (string)$thread['_id'];
			$newest_post['thread_title'] = $thread['title'];
			if ($thread['title']=='General discussion') {
				$ref = aaaart_mongo_get_reference($thread['ref']);
				if (!empty($ref['title'])) {
					$newest_post['thread_title'] = sprintf('%s (discussion)', $ref['title']);
				} else if (!empty($ref['display'])) {
					$newest_post['thread_title'] = sprintf('%s (discussion)', $ref['display']);
				}
			}
			$newest_post['thread_url'] = sprintf('%scomment/thread.php?id=%s', BASE_URL, $thread['_id']);
			$result[] = $newest_post;
		}
	}
	return $result;
}



/**
 * Adds extra fields to post object for display
 */
function aaaart_comment_prepare_for_display(&$post) {
	array_walk_recursive($post, create_function('&$val', '$val = stripslashes($val);'));
	$post['display_date'] = !empty($post['created']) ? aaaart_utils_format_date($post['created']) : '';
	$post['display_user'] = !empty($post['owner']) ? aaaart_user_format_display_name($post['owner']) : '';		
	$post['text'] = Slimdown::render($post['text']);
}


/**
 * Gets a list of comments for a thread
 */
function aaaart_comment_get_comments($thread_id) {
	
}


?>