<?php

/**
 * Memex footer for storing navigation history.
 * It is editable, which allows you to remove items (ideally to reorder them someday)
 * They are also saveable.
 */


function aaaart_memex_check_perm($op, $memex_id=null) {
	global $user;
	if (aaaart_user_check_capability('do_anything')) {
		return true;
	}
	if (!aaaart_user_check_perm()) {
		return false;
	}
	$memex = aaaart_memex_get($memex_id);
	$is_owner = aaaart_memex_user_is_owner($user, $memex);
	switch ($op) {
		case 'create': 
			if (aaaart_user_check_capability('ban_collection_create')) return false;
			else return true;
		case 'update':
			// only collection editors can update
			if (aaaart_user_check_capability('ban_collection_update')) return false;
			else return ( $is_owner );
		break;
		case 'delete':
			// only owner or site moderator can delete
			if (aaaart_user_check_capability('ban_collection_delete')) return false;
			else return ( $is_owner );
		break;
		case 'add':
			// check issue settings (collaborators or followers)
			if (aaaart_user_check_capability('ban_collection_add')) return false;
			else return ( $is_owner );
		break;
		case 'remove':
			// only collection editors or the person who added the document can remove
			if (aaaart_user_check_capability('ban_collection_remove')) return false;
			else return ( $is_owner );
		break;
		default:
			return true;
		break;
	}
}


/**
 * Checks if a user is owner of a memex
 */
function aaaart_memex_user_is_owner($user, $memex) {
	if (!empty($memex['owner']) && !empty($user['_id'])) {
		if ($memex['owner']==$user['_id']) return true;
	} 
	// fallback
	return false;
}


/**
 * Get memex
 */
function aaaart_memex_get($id) {
	if ($id) {
		if (is_array($id) && !empty($id['_id'])) {
			// $id already is a memex object
			return $id;
		}
		$doc = aaaart_mongo_get_one(MEMEX_COLLECTION, $id);
		if (is_array($doc)) {
			array_walk_recursive($doc, create_function('&$val', '$val = stripslashes($val);'));
		}
		return $doc;
	}
	return false;
}


/**
 * Get memex from id param of query string
 */
function aaaart_memex_load_from_query_string() {
	$id = isset($_GET['id']) ? $_GET['id'] : false;
	if ($id) {
		return aaaart_memex_get($id);
	}
	return false;
}


/**
 *
 */
function aaaart_memex_item_lookup($id) {
	return aaaart_mongo_get_one(MEMEX_COLLECTION, $id, "path._id");
}


/**
 * Gets the item from a memex path
 */
function aaaart_memex_get_item($memex, $id) {
	foreach ($memex['path'] as $item) {
		if (!empty($item['_id']) && ($id==$item['_id'])) {
			return $item;
		}
	}
	return false;
}


/**
 * Saves an already existing path
 */
function aaaart_memex_save_note($arr) {
	if (!empty($arr['memex_id']) && !empty($arr['item_id']) && !empty($arr['note'])) {
		$m = aaaart_memex_get($arr['memex_id']);
		if (!empty($m)) {
			$now = time();
			foreach ($m['path'] as $key=>$item) {
				if (!empty($item['_id']) && ($arr['item_id']==(string)$item['_id'])) {
					$m['path'][$key]['note'] = $arr['note'];
				}
			}
			$changes = array(
				'changed' => $now,
				'saved' => $now,
				'path' => $m['path']
			);
			aaaart_mongo_update(
				MEMEX_COLLECTION, 
				array('_id'=>$m['_id']), 
				$changes
			);	
		}
	}
}

/**
 * "Open" means that the current user's active path is set to this one.
 * This is accomplished through making a local copy of the path
 */
function aaaart_memex_open_path(&$memex) {
	global $user;
	$now = time();
	if (aaaart_memex_user_is_owner($user, $memex)) {
		if ($memex['active']!=1) {
			aaaart_memex_close_active_path();
		}
		$memex['pointer'] = 0;
		$memex['active'] = 1;
		aaaart_mongo_update(MEMEX_COLLECTION, $memex['_id'], array('pointer' => $memex['pointer'], 'active' => $memex['active']));
	} else {
		aaaart_memex_close_active_path();
		$memex['parent'] = $memex['_id'];
		$memex['changed'] = $now;
		$memex['owner'] = $user['_id'];
		$memex['pointer'] = 0;
		$memex['active'] = 1;
		$memex['readonly'] = 1;
		$memex['saved'] = 0;
		unset($memex['_id']);
		$memex = aaaart_mongo_insert(MEMEX_COLLECTION, $memex);
	}
}


/**
 * Saves an already existing path
 */
function aaaart_memex_save_path($arr) {
	if (!empty($arr['memex_id'])) {
		$m = aaaart_memex_get($arr['memex_id']);
		if (!empty($m)) {
			$now = time();
			$changes = array(
				'changed' => $now,
				'saved' => $now,
				'title' => !empty($arr['title']) ? $arr['title'] : '',
				'description' => !empty($arr['description']) ? $arr['description'] : '',
			);
			aaaart_mongo_update(
				MEMEX_COLLECTION, 
				array('_id'=>$m['_id']), 
				$changes
			);	
		}
	}
}


/**
 * Open an already existing path for editing
 */
function aaaart_memex_edit_path($arr) {
	if (!empty($arr['memex_id'])) {
		$m = aaaart_memex_get($arr['memex_id']);
		if (!empty($m)) {
			$now = time();
			$changes = array(
				'changed' => $now,
				'readonly' => 0,
				'title' => !empty($arr['title']) ? $arr['title'] : '',
				'description' => !empty($arr['description']) ? $arr['description'] : '',
			);
			aaaart_mongo_update(
				MEMEX_COLLECTION, 
				array('_id'=>$m['_id']), 
				$changes
			);	
		}
	}
}


/**
 *
 */
function aaaart_memex_create_path($u, $set_active=1) {
	$now = time();
	$attributes = array(
		'created' => $now,
		'changed' => $now,
		'owner' => $u['_id'],
		'path' => array(),
		'pointer' => -1,
		'active' => $set_active,
		'readonly' => 0,
		'saved' => 0,
		'title' => '',
		'description' => '',
	);
	return aaaart_mongo_insert(MEMEX_COLLECTION, $attributes);
}


/*
 * Loads user's currently active path and adds to it
 * or winds the pointer back to the location
 */
function aaaart_memex_update_path($url) {
	global $user;
	$now = time();
	$m = aaaart_memex_get_active_user_path();
	if (!$m) {
		$m = aaaart_memex_create_path($user);
	}
	if (!$m) {
		return false;
	}
	$ref = aaaart_memex_parse_url($url);
	if (!empty($ref['uri'])) {
		foreach ($m['path'] as $k=>$v) {
			if ($v['uri']==$ref['uri']) {
				$m['pointer'] = $k;
				aaaart_mongo_update(
					MEMEX_COLLECTION, 
					array('_id'=>$m['_id']), 
					array('pointer' => $m['pointer'], 'changed' => $now)
				);
				return $m;
			}
		}
		if (!empty($m['readonly']) && $m['readonly']==1) {
			return $m;
		}
		// build addition
		$addition = array(
			'_id' => (string)aaaart_mongo_new_id(),
			'time' => $now,
			'uri' => $ref['uri'],
			'type' => $ref['type'],
			'title' => $ref['title'],
			'note' => '',
		);
		if ($m['pointer']>=count($m['path'])-1) {
			$m['path'][] = $addition;
			$m['pointer'] = count($m['path']) - 1;
			$m['changed'] = $now;
		} else {
			aaaart_array_splice($m['path'], $addition, $m['pointer'] + 1);
			$m['pointer'] = $m['pointer'] + 1;
			$m['changed'] = $now;
		}
		aaaart_mongo_update(
			MEMEX_COLLECTION, 
			array('_id'=>$m['_id']), 
			array('pointer' => $m['pointer'], 'path' => $m['path'], 'changed' => $m['changed'])
		);
	}
	return $m;
}


/*

*/
function aaaart_memex_prune_path($url, $id=false, $print_response=false) {
	if (!$id) {
		$m = aaaart_memex_get_active_user_path();
	} else {
		$m = aaaart_memex_get($id);
	}
	aaaart_mongo_pull(
		MEMEX_COLLECTION, 
		array('_id'=>$m['_id']), 
		array('path' => array('uri' => $url))
	);
	if ($print_response) {
		$response = array( 'result' => 'success' );
		return aaaart_utils_generate_response($response);
	} else {
		return $output;
	}
}


/*
 * Delete a path
 */
function aaaart_memex_delete($id=false, $print_response=false) {
	if (!$id) {
		$m = aaaart_memex_get_active_user_path();
	} else {
		$m = aaaart_memex_get($id);
	}
	if (!empty($m)) {
		aaaart_mongo_remove(MEMEX_COLLECTION, array('_id' => $m['_id']));
	}
	if ($print_response) {
		$response = array( 'result' => 'success' );
		return aaaart_utils_generate_response($response);
	} else {
		return true;
	}
}


/*
 * Closes an active path. If it has not been saved yet, then delete it.
 */
function aaaart_memex_close($m) {
	if (!empty($m)) {
		if (!empty($m['saved'])) {
			aaaart_mongo_pull(
				MEMEX_COLLECTION, 
				array('_id'=>$m['_id']), 
				array('path' => array('time' => array('$gt' => $m['saved'])))
			);
			aaaart_mongo_update(
				MEMEX_COLLECTION, 
				array('_id'=>$m['_id']), 
				array('readonly' => 1, 'active' => 0, 'changed' => time())
			);
		} else {
			aaaart_memex_delete($m['_id']);
		}
	}
}

/*
 * Closes an active path. If it has not been saved yet, then delete it.
 */
function aaaart_memex_close_active_path($print_response=false) {
	$m = aaaart_memex_get_active_user_path();
	aaaart_memex_close($m);
	if ($print_response) {
		$response = array( 'result' => 'success' );
		return aaaart_utils_generate_response($response);
	} else {
		return true;
	}
}

/**
 *
 */
function aaaart_memex_cleanup_paths() {}


/*
	* Tries to extract some data from a url
	*/
function aaaart_memex_parse_url($url) {
	$url = str_replace(BASE_URL, '', $url);
	$url = str_replace('#', '', $url);
	if (in_string('image/detail.php?id=', $url, 1)) {
		$id = str_replace('image/detail.php?id=', '', $url);
		$obj = aaaart_image_get($id);
		return array('uri' => $url, 'title' => $obj['title'], 'type' => IMAGES_COLLECTION);
	} else if (in_string('collection/detail.php?id=', $url, 1)) {
		$id = str_replace('collection/detail.php?id=', '', $url);
		$obj = aaaart_collection_get($id);
		return array('uri' => $url, 'title' => $obj['title'], 'type' => COLLECTIONS_COLLECTION);
	} else if (in_string('collection/maker.php?id=', $url, 1)) {
		$id = str_replace('collection/maker.php?id=', '', $url);
		$obj = aaaart_mongo_get_one(MAKERS_COLLECTION, $id);
		return array('uri' => $url, 'title' => $obj['display'], 'type' => MAKERS_COLLECTION);
	} else if (in_string('collection/search.php?q=', $url, 1)) {
		$q = str_replace('collection/search.php?q=', '', $url);
		return array('uri' => $url, 'title' => $q, 'type' => 'search');
	} else return array();
}


/**
 *
 */
function aaaart_memex_cleanup_user_paths($id=false) {
	global $user;
	$u = ($id) ? aaaart_user_get($id) : $user;
	// delete old active paths
	$cutoff_time = time() - MEMEX_LIFESPAN;
	aaaart_mongo_remove(MEMEX_COLLECTION, array('saved'=>0, 'changed' => array('$lt'=> $cutoff_time)));
}


/** 
 * Get the currently active memex path
 */
function aaaart_memex_get_active_user_path($id=false) {
	global $user;
	$u = ($id) ? aaaart_user_get($id) : $user;
	aaaart_memex_cleanup_user_paths($u);
	// return active path
	return aaaart_mongo_get_one(MEMEX_COLLECTION, array('owner'=>$u['_id'], 'active'=> 1));
}


/** 
 * Get all saved memex paths for a user
 */
function aaaart_memex_get_saved_user_paths($id=false) {
	global $user;
	$u = ($id) ? aaaart_user_get($id) : $user;
	// return array of path
	return iterator_to_array(
		aaaart_mongo_get(MEMEX_COLLECTION, array('owner'=>$u['_id'], 'saved' => array('$gt'=>0)), array('title'=>1))
	);
}


/** 
 * Get all saved memex paths site-wide
 */
function aaaart_memex_get_saved_paths($id=false) {
	// return array of path
	return iterator_to_array(
		aaaart_mongo_get(MEMEX_COLLECTION, array('saved' => array('$gt'=>0)), array('title'=>1))
	);
}


/**
 * Given a memex path, what can a user do?
 * Basically, only options are "save", "fork", "delete", or "load" previously saved memexes 
 */
function aaaart_memex_render_actions($m) {
	global $user;
	if (!aaaart_memex_check_perm('create')) {
		return '';
	}
	if (!empty($m['readonly']) && $m['readonly']==1) {
		$main_action = 'fork';
		$main_label = 'Make a copy of this trail';
		$main_icon = 'glyphicon glyphicon-pencil';
		$main_href = BASE_URL.'memex/save.php?fork=true&id='.(string)$m['_id'];
		$remove_action = false;
	} else {
		$main_action = 'save';
		$main_label = 'Save this trail';
		$main_icon = 'glyphicon glyphicon-hdd';
		$main_href = BASE_URL.'memex/save.php?id='.(string)$m['_id'];
	}
	$remove_action = 'new';
	$remove_icon = 'glyphicon glyphicon-remove';
	$remove_label = 'Start a new trail';
	// build list of saved trails
	$trails_list = sprintf("<li><a href=\"%smemex/list.php\">All trails ...</a></li>\n<li class=\"divider\"></li>", BASE_URL);
	$trails = aaaart_memex_get_saved_user_paths();
	foreach ($trails as $trail) {
		$icon = ($trail['_id']==$m['_id']) ? '<i class="icon-certificate"></i> ' : '';
		$trails_list .= sprintf("<li><a href=\"%smemex/detail.php?id=%s\">%s%s</a></li>\n", BASE_URL, (string)$trail['_id'], $icon, stripslashes($trail['title']));
	}
	if (!empty($trails)) {
		$trails_list .= '<li class="divider"></li>';
	}

	return sprintf('<div class="btn-group btn-group-xs dropup">
		<button type="button" class="btn btn-default" data-toggle="modal" title="%s" data-target="#memex-modal" href="%s"><span class="%s"></span></button>
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></button>
		<ul class="dropdown-menu ">
			%s
	    <li><a class="remove" href="#"><span class="%s"></span> %s</a></li>
	  </ul>
	</div>', $main_label, $main_href, $main_icon, $trails_list, $remove_icon, $remove_label);
}


/*
 * Renders a single button (one item in path)
 */
function aaaart_memex_render_button($item, $icon_type, $button_type, $readonly) {
	$title = $item['title'];
	$uri = $item['uri'];
	$note = (!empty($item['note'])) ? $item['note'] : '';
	$id = (!empty($item['_id'])) ? (string)$item['_id'] : false;

	if ($readonly && !empty($note)) {
		$dropdown = sprintf('<div class="dropdown-menu" style="padding: 15px;">%s</div>', Slimdown::render($note));
	} else if (!$readonly && !empty($id)) {
		$label = (empty($note)) ? 'Add a note' : 'Edit note';
		$dropdown = sprintf('<ul class="dropdown-menu"><li><a data-toggle="modal" class="remove" data-url="%s" href="#"><span class="glyphicon glyphicon-remove"></span> Remove</a></li>
	    <li><a data-toggle="modal" data-target="#memex-modal" href="%smemex/note.php?id=%s"><span class="glyphicon glyphicon-edit"></span> %s</a></li></ul>',  $uri, BASE_URL, $id, $label);
	}
	if (empty($dropdown)) {
		return sprintf('<li><div class="btn-group btn-group-xs dropup">
			<a href="%s%s" class="btn btn-mini %s" type="button"><span class="glyphicon glyphicon-%s icon-white"></span> %s</a>
		</div></li>', BASE_URL, $uri, $button_type, $icon_type, aaaart_truncate($title, 30));
	} else {
		return sprintf('<li><div class="btn-group btn-group-xs dropup">
			<a href="%s%s" class="btn %s" type="button"><span class="glyphicon glyphicon-%s icon-white"></span> %s</a>
			<a class="btn %s dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
		    %s
		</div></li>', BASE_URL, $uri, $button_type, $icon_type, aaaart_truncate($title, 30), $button_type, $dropdown);
	}
}

/*
 * Renders a memex path.
 * Default is the current active path
 */
function aaaart_memex_render_path($id=false, $print_response = true) {
	global $user;
	$buttons = array();
	if (!$id) {
		$m = aaaart_memex_get_active_user_path();
	} else {
		$m = aaaart_memex_get($id);
	}
	if (!empty($m['path'])) {
		foreach ($m['path'] as $k => $item) {
			$btn = ($k==$m['pointer']) ? 'btn-primary' : 'btn-info';
			$readonly = $m['readonly'];
			switch ($item['type']) {
				case IMAGES_COLLECTION: $buttons[] = aaaart_memex_render_button($item, 'book', $btn, $readonly); break;
				case COLLECTIONS_COLLECTION: $buttons[] = aaaart_memex_render_button($item, 'list', $btn, $readonly); break;
				case MAKERS_COLLECTION: $buttons[] = aaaart_memex_render_button($item, 'user', $btn, $readonly); break;
				case COMMENTS_COLLECTION: $buttons[] = aaaart_memex_render_button($item, 'comment', $btn, $readonly); break;
				case 'search': $buttons[] = aaaart_memex_render_button($item, 'search', $btn, $readonly); break;
			}
		}
	}
	if ($print_response) {
		$response = array( 'memex' => $buttons, 'pointer' => $m['pointer'], 'actions'=> aaaart_memex_render_actions($m) );
		return aaaart_utils_generate_response($response);
	} else {
		return $output;
	}
}

?>