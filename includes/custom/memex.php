<?php

/**
 * Memex footer for storing navigation history.
 * It is editable, which allows you to remove items (ideally to reorder them someday)
 * They are also saveable.
 */

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
		return $doc;
	}
	return false;
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
		// build addition
		$addition = array(
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


function aaaart_memex_parse_url($url) {
	$url = str_replace(BASE_URL, '', $url);
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


function aaaart_memex_get_active_user_path($id=false) {
	global $user;
	$u = ($id) ? aaaart_user_get($id) : $user;
	return aaaart_mongo_get_one(MEMEX_COLLECTION, array('owner'=>$u['_id'], 'active'=> 1));
}

function aaaart_memex_render_button($title, $uri, $icon_type, $button_type) {
	return sprintf('<div class="btn-group dropup">
		<a href="%s%s" class="btn btn-mini %s" type="button"><i class="icon-%s icon-white"></i> %s</a>
		<a class="btn btn-mini %s dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
		<ul class="dropdown-menu">
	    <li><a data-toggle="modal" href="#"><i class="icon-remove"></i> Remove</a></li>
	    <li><a data-toggle="modal" data-target="#comments" class="comments" href="#"><i class="icon-comment"></i> Add a note</a></li>
	  </ul>
	</div>', BASE_URL, $uri, $button_type, $icon_type, aaaart_truncate($title, 30), $button_type);
}

/*
 * Renders a memex path.
 * Default is the current active path
 */
function aaaart_memex_render_path($id=false, $print_response = true) {
	global $user;
	$output = '';
	if (!$id) {
		$m = aaaart_memex_get_active_user_path();
	} else {
		$m = aaaart_memex_get($id);
	}
	if (!empty($m['path'])) {
		foreach ($m['path'] as $k => $item) {
			 $btn = ($k==$m['pointer']) ? 'btn-primary' : 'btn-info';
			switch ($item['type']) {
				case IMAGES_COLLECTION: $output .= aaaart_memex_render_button($item['title'], $item['uri'], 'book', $btn); break;
				case COLLECTIONS_COLLECTION: $output .= aaaart_memex_render_button($item['title'], $item['uri'], 'list', $btn); break;
				case MAKERS_COLLECTION: $output .= aaaart_memex_render_button($item['title'], $item['uri'], 'user', $btn); break;
				case COMMENTS_COLLECTION: $output .= aaaart_memex_render_button($item['title'], $item['uri'], 'comment', $btn); break;
				case 'search': $output .= aaaart_memex_render_button($item['title'], $item['uri'], 'search', $btn); break;
			}
		}
	}
	if ($print_response) {
		$response = array( 'memex' => $output );
		return aaaart_utils_generate_response($response);
	} else {
		return $output;
	}
}

?>