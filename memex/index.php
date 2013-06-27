<?php
/*

Routes "memex" requests

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
				case 'get_path':
					if (!empty($_GET['id'])) {
						aaaart_memex_get_path($_GET['id'], true);
					} else {
						print ''; exit;
					}
				break;
				case 'list_paths':
				if (!empty($_GET['user'])) {
						aaaart_memex_list_paths($_GET['user'], true);
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
				case 'update_path':
					if (!empty($_POST['url'])) {
						aaaart_memex_update_path($_POST['url']);
					}
				break;
				case 'save':
					if (!empty($_POST['memex_id'])) {
						aaaart_memex_save_path($_POST);
					}
				break;
				case 'edit':
					if (!empty($_POST['memex_id'])) {
						aaaart_memex_edit_path($_POST);
					}
				break;
				case 'update_path_and_reload':
					if (!empty($_POST['url'])) {
						$m = aaaart_memex_update_path($_POST['url']);
						aaaart_memex_render_path($m);
					}
				break;
				case 'save_note':
					if (!empty($_POST['memex_id']) && !empty($_POST['item_id'])) {
						aaaart_memex_save_note($_POST);
					}
				break;
			}
		}
    break;
  case 'DELETE':
    if (!empty($_GET['prune'])) {
			aaaart_memex_prune_path($_GET['prune'], false, true);
		} else if (!empty($_GET['id'])) {
			aaaart_memex_delete($_GET['id'], true);
		} else {
			aaaart_memex_close_active_path(true);
		}
    break;
  default:
    aaaart_utils_header('HTTP/1.1 405 Method Not Allowed');
}


?>