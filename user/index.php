<?php
/*

Routes "user" requests

*/

require_once('../config.php');


switch (aaaart_utils_get_server_var('REQUEST_METHOD')) {
  case 'OPTIONS':
  case 'HEAD':
	  aaaart_utils_head();
	  break;
  case 'GET':
  	if (!empty($_GET['action'])) {
  		switch ($_GET['action']) {
  			case 'logout':
  				aaaart_user_logout();
  			break;
  		}
  	}
    break;
  case 'PATCH':
  case 'PUT':
  case 'POST':
    if (!empty($_POST['action'])) {
			switch ($_POST['action']) {
				case 'invite':
					if (!empty($_POST['email']) && aaaart_user_check_perm('invite')) {
						aaaart_user_create_invitation($_POST['email']);
					}
				break;
				case 'first_login':
					if (!empty($_POST['key'])) {
						aaaart_user_attempt_first_login($_POST['key'], $_POST);
					}
				break;
        case 'login':
          if (!empty($_POST['key']) && !empty($_POST['pass'])) {
            aaaart_user_attempt_login($_POST['key'], $_POST['pass']);
          }
        break;
			}
		}
    break;
  case 'DELETE':
    break;
  default:
    aaaart_utils_header('HTTP/1.1 405 Method Not Allowed');
}

?>