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
	aaaart_cache_invalidate('new_comments');
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
	aaaart_comment_push_activity($thread_id, $post);
	aaaart_solr_add_to_queue(COMMENTS_COLLECTION, $thread_id);
	aaaart_cache_invalidate('new_comments');
}


/*
 * Formats activity and pushes it to all relevant users
 */
function aaaart_comment_push_activity($thread_id, $post) {
	$thread = aaaart_comment_get_thread($thread_id);
	$formatted = sprintf('<span class="user">%s</span> posted a comment to <span class="thread">%s</span>: "%s"',
		aaaart_user_format_display_name($post['owner']),
		aaaart_comment_format_thread_title($thread),
		stripslashes(Slimdown::render(aaaart_truncate($post['text'], 50)))
	);
	// now find the users that this activity applies to
	$users = array();
	foreach ($thread['posts'] as $p) {
		if ($post['owner']!=$p['owner']) {
			$users[ (string)$p['owner'] ] = $p['owner'];
		}
	}
	$ref = aaaart_mongo_get_reference($thread['ref']);
	if (!empty($ref)) {
		switch ($thread['ref']['$ref']) {
			case IMAGES_COLLECTION:
			case COLLECTIONS_COLLECTION: 
				if ($post['owner']!=$ref['owner']) {
					$users[ (string)$ref['owner'] ] = $ref['owner'];
				}
			break;
		}
	}
	aaaart_user_push_activity($users, $formatted, 'comment/thread.php?id='.$thread_id, '#comments');
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
 * Runs a search. Displays results as a kind of collection
 */
function aaaart_comment_search($query, $print_response=false) {
	$solr = new Solr();
	// we query for facets as well as results
	$results = $solr->simpleQuery($query, COMMENTS_COLLECTION, array());
	if ($print_response) {
		$docs = array();
		if (!empty($results)) {
			foreach ($results as $result) {
				$thread = aaaart_comment_get_thread($result['id']);
				$first_post = aaaart_comment_prepare_first_post($thread);
				if ($first_post) {
					$docs[] = $first_post;
				}
			}
		}
		$response = array( 'discussions' => $docs );
		return aaaart_utils_generate_response($response);
	} else return $results;
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
		if ($cached = aaaart_cache_get('new_comments')) {
			$result = $cached;
		} else {
			$result = aaaart_comment_get_new_comments();
			aaaart_cache_set('new_comments', $result);
		}
	} else if ($show=="new_filtered") { // exclude the general discussion of documents	
		if ($cached = aaaart_cache_get('new_filtered_comments')) {
			$result = $cached;
		} else {
			$result = aaaart_comment_get_new_filtered_comments();
			aaaart_cache_set('new_filtered_comments', $result);
		}
	} else if ($show=="thread") {
		$result = aaaart_comment_get_comments($arg);
	} else if ($show=="commented") {
		$result = aaaart_comment_get_new_comments(true);
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
function aaaart_comment_get_new_comments($filter_by_user = false, $num = 50) {
	$result = array();
	global $user;
	$condition = ($filter_by_user) ? array('posts.owner' => $user['_id']) : array();
	$threads = aaaart_mongo_get_paged(
		COMMENTS_COLLECTION, 
		$condition,
		array('posts.created'=> -1)
	);
	foreach ($threads as $thread) {
		$newest_post = aaaart_comment_prepare_newest_post($thread);
		if ($newest_post) {
			$result[] = $newest_post;
		}
	}
	return $result;
}


/**
 * Gets a list of new comments (excluding the general discussion)
 */
function aaaart_comment_get_new_filtered_comments($filter_by_user = false, $num = 50) {
	$result = array();
	global $user;
	$condition = ($filter_by_user) ? array('title' => array('$ne' => 'General discussion'), 'posts.owner' => $user['_id']) : array('title'=> array('$ne' => 'General discussion'));
	$threads = aaaart_mongo_get_paged(
		COMMENTS_COLLECTION, 
		$condition,
		array('posts.created'=> -1)
	);
	foreach ($threads as $thread) {
		$newest_post = aaaart_comment_prepare_newest_post($thread);
		if ($newest_post) {
			$result[] = $newest_post;
		}
	}
	return $result;
}

/*
 * Takes a thread and shows a specific post from it for display in a list of threads
 */
function aaaart_comment_prepare_newest_post($thread) {
	$newest_post = current(aaaart_comment_get_ordered_posts($thread));
	if (!empty($newest_post)) {
		aaaart_comment_prepare_for_display($newest_post);
		$newest_post['thread_id'] = (string)$thread['_id'];
		$newest_post['thread_title'] = aaaart_comment_format_thread_title($thread);
		$newest_post['thread_url'] = sprintf('%scomment/thread.php?id=%s', BASE_URL, $thread['_id']);
		return $newest_post;
	} else return false;
}

/*
 * Takes a thread and shows a specific post from it for display in a list of threads
 */
function aaaart_comment_prepare_first_post($thread) {
	if (!empty($thread['posts'])) {
		$first_post = current($thread['posts']);
		if (!empty($first_post)) {
			aaaart_comment_prepare_for_display($first_post);
			$first_post['thread_id'] = (string)$thread['_id'];
			$first_post['thread_title'] = aaaart_comment_format_thread_title($thread);
			$first_post['thread_url'] = sprintf('%scomment/thread.php?id=%s', BASE_URL, $thread['_id']);
			return $first_post;
		} 
	}
	return false;
}


/*
 * Format a thread title
 */
function aaaart_comment_format_thread_title($thread) {
	$title = $thread['title'];
	if ($thread['title']=='General discussion') {
		$ref = aaaart_mongo_get_reference($thread['ref']);
		if (!empty($ref['makers_display']) && !empty($ref['title'])) {
			$title = sprintf('<em>%s</em> - %s', $ref['title'], $ref['makers_display']);
		} else if (!empty($ref['title'])) {
			$title = sprintf('<em>%s</em>', $ref['title']);
		} else if (!empty($ref['display'])) {
			$title = sprintf('%s', $ref['display']);
		}
	}
	return $title;
}

/*
 *
 */
function aaaart_comment_get_reference_link($thread) {
	if (!empty($thread['ref']['$ref'])) {
		$ref = aaaart_mongo_get_reference($thread['ref']);
		switch ($thread['ref']['$ref']) {
			case IMAGES_COLLECTION: return '<a href="'.BASE_URL.'image/detail.php?id='.(string)$ref['_id'].'">'.$ref['title'].'</a>';
			case COLLECTIONS_COLLECTION: return '<a href="'.BASE_URL.'collection/detail.php?id='.(string)$ref['_id'].'">'.$ref['title'].'</a>';
			case MAKERS_COLLECTION: return '<a href="'.BASE_URL.'collection/maker.php?id='.(string)$ref['_id'].'">'.$ref['display'].'</a>';
		}
	}
	return false;
}

/**
 * Adds extra fields to post object for display
 */
function aaaart_comment_prepare_for_display(&$post) {
	array_walk_recursive($post, create_function('&$val', '$val = stripslashes($val);'));
	$post['display_date'] = !empty($post['created']) ? aaaart_utils_format_date($post['created']) : '';
	$post['display_user'] = !empty($post['owner']) ? aaaart_user_format_display_name($post['owner']) : 'Anonymous';		
	$post['text'] = stripslashes(Slimdown::render($post['text']));
}


/**
 * Gets a list of comments for a thread
 */
function aaaart_comment_get_comments($thread_id) {
	
}


?>