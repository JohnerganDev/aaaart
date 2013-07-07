<?php

require_once('../config.php');


switch (aaaart_utils_get_server_var('REQUEST_METHOD')) {
  case 'OPTIONS':
  case 'HEAD':
	  aaaart_utils_head();
	  break;
  case 'GET':
  	if (!empty($_GET['action'])) {
  		switch ($_GET['action']) {
  			case 'documents':
  				if (!empty($_GET['id'])) {
  					aaaart_collection_get_collected_documents($_GET['id'], true);
  				}
  			break;
        case 'documents_and_sections':
          if (!empty($_GET['id'])) {
            aaaart_collection_get_documents_and_sections($_GET['id'], true);
          }
        break;
  			case 'documents_by_maker':
  				if (!empty($_GET['id'])) {
  					aaaart_collection_get_documents_by_maker($_GET['id'], true);
  				}
  			break;
  			case 'collections_for_maker':
  				if (!empty($_GET['id'])) {
  					aaaart_collection_get_collections_for_maker($_GET['id'], true);
  				}
  			break;
  			case 'makers_for_collection':
  				if (!empty($_GET['id'])) {
  					aaaart_collection_get_makers_for_collection($_GET['id'], true);
  				}
  			break;
  			case 'list_makers':
  				if (!MAKERS_ARE_HUGE) {
            aaaart_image_filter_makers('', true);
          } else if (!empty($_GET['filter'])) {
  					aaaart_image_filter_makers($_GET['filter'], true);
  				}
  			break;
        case 'list_collections':
          if (isset($_GET['arg'])) {
            // filter by first letter
            aaaart_collection_list_collections($_GET['show'], $_GET['arg'], true);
          } else {
            // "mine" or all
            aaaart_collection_list_collections($_GET['show'], true);
          }
        break;
        case 'search':
          if (!empty($_GET['q'])) {
            aaaart_collection_search($_GET['q'], true);
          }
        break;
        case 'follow':
          if (!empty($_GET['id']) && aaaart_collection_check_perm('follow')) {
            aaaart_utils_generate_response(
              array( 'button' => aaaart_collection_get_follow_button($_GET['id']) )
            );
          }
        break;
  		}
  	} else {
  		// All the images
			aaaart_collection_get_all_images();
		}
    break;
  case 'PATCH':
  case 'PUT':
  case 'POST':
    if (!empty($_POST['action'])) {
			switch ($_POST['action']) {
				case 'create':
					if (!empty($_POST) && aaaart_collection_check_perm('create')) {
						aaaart_collection_create($_POST);
					}
				break;
				case 'update':
					if (!empty($_POST['id']) && aaaart_collection_check_perm('update', $_POST['id'])) {
						aaaart_collection_update($_POST['id'], $_POST);
					}
				break;
        case 'update_note':
          if (!empty($_POST['document_id']) && !empty($_POST['collection_id']) && aaaart_collection_check_perm('add', $_POST['collection_id'])) {
            aaaart_collection_update_note($_POST['collection_id'], $_POST['document_id'], $_POST['note']);
          }
        break;
        case 'add_section':
          if (!empty($_POST['collection_id']) && aaaart_collection_check_perm('update', $_POST['collection_id'])) {
            aaaart_collection_create_section($_POST['collection_id'], $_POST);
          }
        break;
        case 'save_section':
          if (!empty($_POST['collection_id']) && !empty($_POST['section_id']) && aaaart_collection_check_perm('update', $_POST['collection_id'])) {
            aaaart_collection_update_section($_POST['collection_id'], $_POST['section_id'], $_POST);
          }
        break;
        case 'sort_section':
          if (!empty($_POST['collection_id']) && !empty($_POST['section_id']) && !empty($_POST['document_id']) && aaaart_collection_check_perm('update', $_POST['collection_id'])) {
            aaaart_collection_sort_into_section($_POST['collection_id'], $_POST['section_id'], $_POST['document_id']);
          }
        break;
				case 'invite':
					if (!empty($_POST['id']) && aaaart_collection_check_perm('update', $_POST['id']) && !empty($_POST['email'])) {
						aaaart_collection_invite_collaborator($_POST['id'], $_POST['email']);
					}
				break;
				case 'add':
					if (!empty($_POST['document_id']) && !empty($_POST['collection_id']) && aaaart_collection_check_perm('add', $_POST['collection_id'])) {
            aaaart_collection_add_document($_POST['collection_id'], $_POST['document_id']);
					}
				break;
				case 'request':
					if (!empty($_POST['title']) && !empty($_POST['maker']) && aaaart_collection_check_perm('request')) {
						aaaart_collection_make_request($_POST);
					}
				break;
        case 'follow':
          if (!empty($_POST['id']) && aaaart_collection_check_perm('follow')) {
            aaaart_utils_generate_response(
              array( 'button' => aaaart_collection_get_follow_button($_POST['id'], false, true) )
            );
          }
        break;
			}
		}
    break;
  case 'DELETE':
    if (!empty($_GET['document']) && !empty($_GET['collection']) && aaaart_collection_check_perm('remove', $_GET['collection'], $_GET['document'])) {
			aaaart_collection_remove_document($_GET['collection'] , $_GET['document']);
		} else if (!empty($_GET['collection']) && aaaart_collection_check_perm('delete', $_GET['collection'])) {
			aaaart_collection_delete($_GET['collection']);
		}
    break;
  default:
    aaaart_utils_header('HTTP/1.1 405 Method Not Allowed');
}

?>