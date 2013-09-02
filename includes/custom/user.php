<?php
/*

Depends on mongo.php, utils.php, config.php

*/

// Global variable for currently logged in user
$user = false;

function aaaart_user_check_perm($op='') {
	// for now, let's just say anyone logged in is OK
	$c = aaaart_user_verify_cookie();
	switch ($op) {
		case 'login':
			return (!$c);
		default:
			if (aaaart_user_check_capability('banned')) {
				return false;
			} else {
				return $c;
			}
	}
}

/**
 * Save data into the Mongo database
 */
function aaaart_user_save($data) {
	return aaaart_mongo_insert(PEOPLE_COLLECTION, $data);
}


/**
 *
 */
function aaaart_user_load_from_query_string() {
	$id = isset($_GET['key']) ? $_GET['key'] : false;
	if ($id) {
		return aaaart_mongo_get_one(PEOPLE_COLLECTION, $id);
	}
	return false;
}


/**
 * Get user from the Mongo database
 */
function aaaart_user_get($value=false, $attr=false) {
	if (!$value) {
		$value = aaaart_user_get_id();
	}
	if ($value) {
		if (is_array($value) && !empty($value['_id'])) {
			// $value already is a user object
			return $value;
		}
		if ($attr) {
			$p = aaaart_mongo_get_one(PEOPLE_COLLECTION, $value, $attr);
		} else {
			$p = aaaart_mongo_get_one(PEOPLE_COLLECTION, $value);
		}
		return $p;
	}
	return false;
}


/**
 * Gets a display name for a user (id)
 */
function aaaart_user_format_display_name($key) {
	$u = aaaart_user_get($key);
	return (!empty($u['display_name'])) ? $u['display_name'] : 'anonymous';
}


/*
 * Outputs a comma separated list
 */
function aaaart_user_format_simple_list($users) {
	$anon_count = 0;
	$names_list = array();
	foreach ($users as $u) {
		if (!empty($u['display_name']) && $u['display_name']!='x') {
			$names_list[] = $u['display_name'];
		} else $anon_count++;
	}
	$ret_str = (count($names_list)>0) ? implode(', ', $names_list) : '';
	$ret_str .= (count($names_list)>0 && $anon_count>0) ? ' and ' : '';
	$ret_str .= ($anon_count>0) ? sprintf('%s anonymous', $anon_count) : '';
	return $ret_str;
}

/*

*/
function aaaart_user_get_activity_count() {
	global $user;
	if (empty($user['activity'])) {
		return 0;
	} else {
		$count = 0;
		foreach ($user['activity'] as $a) {
			if ($a['unread']==1) $count++;
		}
		return $count;
	}
}


/*
 * Mark activity as read
 */
function aaaart_user_pull_activity($path) {
	global $user;
	if (empty($user['activity'])) return;
	$cutoff_read = time() - 7*24*60*60;
	$cutoff_unread = time() - 14*24*60*60;
	$changed = false;
	foreach ($user['activity'] as $k=>$a) {
		if ($a['path']==$path && $a['unread']!==0) {
			$user['activity'][$k]['unread'] = 0;
			$changed = true;
		}
		if ( ($a['unread']!=1 && $a['time']<$cutoff_read) || ($a['unread']==1 && $a['time']<$cutoff_unread) ) {
			unset($user['activity'][$k]);
			$changed = true;
		}
	}
	if ($changed) {
		aaaart_user_update(array('activity'=>$user['activity']), $user);
	}
}


/*
 * Pushed activity onto a user's stack
 */
function aaaart_user_push_activity($users, $html, $path=false, $modal_target=false) {
	$addition = array(
		'time' => time(),
		'html' => $html,
		'path' => $path,
		'modal' => $modal_target,
		'unread' => 1,
	);
	foreach ($users as $k=>$v) {
		if (empty($v)) {
			unset($users[$k]);
		}
	}
	aaaart_mongo_push(PEOPLE_COLLECTION, array('_id'=>array('$in'=>$users)), array('activity' => $addition), array('multiple'=>TRUE));
}


/*
 * Pushed activity onto a user's stack
 */
function aaaart_user_format_activity($item) {
	if (!empty($item['modal'])) {
		return sprintf('<a href="%s" data-toggle="modal" data-target="%s">%s</a><span class="date">%s</span>', BASE_URL.$item['path'], $item['modal'], $item['html'], aaaart_utils_format_date($item['time']));
	} else if (!empty($item['path'])) {
		return sprintf('<a href="%s">%s</a>', BASE_URL.$item['path'], $item['html'], aaaart_utils_format_date($item['time']));
	} else {
		return sprintf('<span>%s</span><span class="date">%s</span>', $item['html'], aaaart_utils_format_date($item['time']));
	}
}

/**
 * Certain capabilities can be set in user object
 */
function aaaart_user_capabilities($u=false) {
	global $user;
	if (!$u) {
		$u = $user;
	}
	if (!empty($u['capabilities'])) {
		return $u['capabilities'];
	}
	return array();
}


/**
 * Certain capabilities can be set in user object
 */
function aaaart_user_check_capability($capability, $user=false) {
	$capabilities = aaaart_user_capabilities($user);	
	return in_array($capability, $capabilities);
}


/**
 * Updates a user. The user can be specified in various ways (string, object, current session)
 */
function aaaart_user_update($data, $user=false) {
	if (empty($user)) {
		$id = aaaart_user_get_id();
	} else if (is_array($user)) {
		$id = $user["_id"];
	} else if (is_string($user)) {
		$id = $user;
	} else {
		return false;
	}
	return aaaart_mongo_update(PEOPLE_COLLECTION, array("_id" => $id), $data);
}


############################
# LOGIN AND AUTHENTICATION STUFF
############################

/**
 *
 */
function aaaart_user_generate_cookie( $id, $expiration ) {
	$key = hash_hmac( 'md5', $id . $expiration, SECRET_KEY );
	$hash = hash_hmac( 'md5', $id . $expiration, $key );
	$cookie = $id . '|' . $expiration . '|' . $hash;
	return $cookie;
}

/**
 *
 */
function aaaart_user_verify_cookie() {
	if ( empty($_COOKIE[COOKIE_AUTH]) )
		return false;
	list( $id, $expiration, $hmac ) = explode( '|', $_COOKIE[COOKIE_AUTH] );
	$expired = $expiration;
	if ( $expired < time() )
		return false;
	$key = hash_hmac( 'md5', $id . $expiration, SECRET_KEY );
	$hash = hash_hmac( 'md5', $id . $expiration, $key );
	if ( $hmac != $hash )
		return false;
	return true;
}

/**
 *
 */
function aaaart_user_get_id() { 
	if (aaaart_user_verify_cookie()) {
	  list( $id, $expiration, $hmac ) = explode( '|', $_COOKIE[COOKIE_AUTH] ); 	  
  	return $id; 
  } else {
  	return false;
  }
} 


/**
 * Logs out
 */
function aaaart_user_logout() { 
  setcookie( COOKIE_AUTH, "", time() - 1209600, COOKIE_PATH ); 
  aaaart_user_get_id();
  header('Location: '.BASE_URL);
} 


/*
 * Try to log in
 */
function aaaart_user_attempt_login($name, $password) {
	if (!$password) {
		//$u = aaaart_user_get($name);
	} else {
		if (strpos($name, '@')>0) {
			$regexObj = new MongoRegex("/^".$name."$/i"); 
			$u = aaaart_mongo_get_one(PEOPLE_COLLECTION, array('email'=>$regexObj, 'pass'=>md5($password)));
			if (empty($u)) {
				aaaart_utils_generate_response(array('result' => false, 'message' => 'The password didn\'t work for the email address you used: '.$name));
				exit;
			}
		} else {
			$u = aaaart_mongo_get_one(PEOPLE_COLLECTION, array('_id'=>$name, 'pass'=>md5($password)));
			if (empty($u)) {
				aaaart_utils_generate_response(array('result' => false, 'message' => 'The password didn\'t work for the key you used: '.$name . ' You got your key will look something like this: 51c581276c3a0ee00b23f100. If you don\'t remember it, use your email address.'));
				exit;
			}			
		}
	}
	if (!empty($u)) {
		aaaart_user_update(array('last_login'=>time()));
		$expiration = time() + 1209600;
		$id = (string)$u['_id'];
		$cookie = aaaart_user_generate_cookie( $id, $expiration ); 
		setcookie( COOKIE_AUTH, $cookie, $expiration, COOKIE_PATH );
		aaaart_utils_generate_response(array('result' => true, 'message' => 'success'));
	} else {
		aaaart_utils_generate_response(array('result' => false, 'message' => 'that didn\'t work'));
	}
}


/*
 * Try to log in
 */
function aaaart_user_attempt_first_login($name, $params) {
	$u = aaaart_user_get($name);
	if (!empty($u)) {
		// add fields
		$data = array(
			'pass' => md5($params['pass']),
			'display_name' => $params['display_name'],
			'reset' => array(),
		);
		aaaart_mongo_update(PEOPLE_COLLECTION, $name, $data);
		// finish login
		$expiration = time() + 1209600;
		$cookie = aaaart_user_generate_cookie( $name, $expiration ); 
		setcookie( COOKIE_AUTH, $cookie, $expiration, COOKIE_PATH );
		aaaart_utils_generate_response(array('result' => true, 'message' => 'success'));
	} else {
		aaaart_utils_generate_response(array('result' => false, 'message' => 'that didn\'t work'));
	}
}


/*
 * Reset password
 */
function aaaart_user_reset($key) {
	if (strpos($key, '@')>0) {
		$u = aaaart_user_get($key, 'email');
	} else {
		$u = aaaart_user_get($key);
	}
	if (!empty($u)) {
		$reset = array(
			'key' => uniqid(),
			'expires' => time() + 24*60*60 // expires in one day
		);
		aaaart_mongo_update(PEOPLE_COLLECTION, $u['_id'], array('reset' => $reset));
		$message = sprintf("you (or someone using your email address) has requested a password reset for %s. if you do want to reset your password, visit\n\n%suser/login.php?key=%s&reset=%s\n\n where you can log in and pick a new password. this link will expire in 24 hours.\n\n",
			SITE_TITLE,
			BASE_URL,
			(string)$u['_id'],
			$reset['key']);
		aaaart_utils_send_email($u['email'], sprintf('%s password reset', SITE_TITLE), $message);
		aaaart_utils_generate_response(array('result' => true, 'message' => 'success'));
	} else {
		aaaart_utils_generate_response(array('result' => false, 'message' => 'There is no account like that'));
	}
}


############################
# INVITATION STUFF
############################

/**
 *
 */
function aaaart_user_log_invitation($inviter, $invitee) {
	$invited = (!empty($inviter["invited"])) ? $inviter["invited"] : array();
	aaaart_mongo_push(PEOPLE_COLLECTION, (string)$inviter['_id'], array('invited' => $invitee['_id']));
}


/**
 *
 */
function aaaart_user_create_invitation($email, $print_response=false) {
	$u = aaaart_user_get($email, 'email');
	if (!empty($u)) {
		return false;
	} else {
		$account = array('email' => $email);
		$account = aaaart_user_save($account);
		if (!empty($account['_id'])) {
			$inviter = aaaart_user_get();
			aaaart_user_log_invitation($inviter, $account);
			$message = sprintf("this email is an invitation to %s. you were invited by %s. visit\n\n%suser/login.php?key=%s\n\n to pick a password and log in. once you are logged in you can upload/ download and make collections.\n\nFor future reference, your key (which you need, along with your password, to log in) is:\n\n%s",
				SITE_TITLE,
				$inviter['email'],
				BASE_URL,
				(string)$account['_id'],
				(string)$account['_id']);
			aaaart_utils_send_email($email, sprintf('an invitation to %s', SITE_TITLE), $message);
			return $account;
		} else {
			return false;
		}
	}
}

?>