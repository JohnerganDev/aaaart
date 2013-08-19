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
				case 'info':
					if (!empty($_GET['sha1'])) {
						aaaart_image_get_document_from_file('sha1', $_GET['sha1'], true);
					} else if (!empty($_GET['filename'])) {
						aaaart_image_get_document_from_file('filename', $_GET['filename'], true);
					} else {
						print ''; exit;
					}
				break;
				case 'saved_documents':
					if (!MAKERS_ARE_HUGE) {
            aaaart_image_filter_saved_documents('', true);
          } else if (!empty($_GET['filter'])) {
  					aaaart_image_filter_saved_documents($_GET['filter'], true);
  				}
				break;
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
				case 'video':
					if (!empty($_POST['url'])) {
						aaaart_image_import_video($_POST);
					}
				break;
				case 'html':
					if (!empty($_POST['url'])) {
						aaaart_image_import_html($_POST);
					}
				break;
				// note: this saves to a user's "saved" library (or unsaves it)
				case 'save_document':
					if (!empty($_POST['id'])) {
						aaaart_image_save_document($_POST['id']);
						aaaart_utils_generate_response(
              array( 'result' => true )
            );
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