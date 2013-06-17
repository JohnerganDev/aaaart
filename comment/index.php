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
				case 'get_comments':
					if (!empty($_GET['type'])) { // thread id
						if (!empty($_GET['thread_id'])) {
							aaaart_comment_list_comments($_GET['type'], $_GET['thread_id'], true);
						} else {
							aaaart_comment_list_comments($_GET['type'], false, true);
						}
					} else {
						print ''; exit;
					}
				break;
				case 'get_threads':
				if (!empty($_GET['type']) && !empty($_GET['id'])) { // reference type & id
						aaaart_comment_list_threads($_GET['type'], $_GET['id'], true);
					} else {
						print ''; exit;
					}
				break;
				default:
				break;
			}
		}
    break;
  case 'PATCH':
  case 'PUT':
  case 'POST':
    if (!empty($_POST['action'])) {
			switch ($_POST['action']) {
				case 'create_thread':
					if (!empty($_POST)) {
						aaaart_comment_create_thread($_POST);
					}
				break;
				case 'update_thread':
					// updates a thread
					if (!empty($_POST['thread_id'])) {
						aaaart_comment_update_thread($_POST['thread_id'], $_POST);
					}
				break;
				case 'create':
					// creates a comment in a thread
					if (!empty($_POST['thread_id'])) {
						aaaart_comment_create($_POST['thread_id'], $_POST);
					}
				break;
				case 'update':
					// updates a comment
					if (!empty($_POST['post_id'])) {
						aaaart_comment_update($_POST['post_id'], $_POST);
					}
				break;
			}
		}
    break;
  case 'DELETE':
    if (!empty($_GET['id'])) {
			aaaart_comment_delete($_GET['id']);
		} else if (!empty($_GET['thread_id'])) {
			aaaart_comment_delete_thread($_GET['thread_id']);
		}
    break;
  default:
    aaaart_utils_header('HTTP/1.1 405 Method Not Allowed');
}


?>