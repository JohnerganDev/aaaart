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
				case 'get_files':
					if (!empty($_GET['id'])) {
						aaaart_image_get_files_for_image($_GET['id'], true);
					} else {
						print ''; exit;
					}
				break;
				case 'list_makers':
				if (!empty($_GET['id'])) {
						aaaart_image_get_makers_for_document($_GET['id'], true);
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
				case 'update':
					if (!empty($_POST['id'])) {
						aaaart_image_update_image($_POST['id'], $_POST);
					}
				break;
			}
		}
    break;
  case 'DELETE':
    if (!empty($_GET['file'])) {
			aaaart_image_delete_file($_GET['file']);
		} else if (!empty($_GET['image'])) {
			aaaart_image_delete_image($_GET['image']);
		}
    break;
  default:
    aaaart_utils_header('HTTP/1.1 405 Method Not Allowed');
}


?>