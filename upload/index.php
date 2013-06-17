<?php

require_once('../config.php');


switch (aaaart_utils_get_server_var('REQUEST_METHOD')) {
  case 'OPTIONS':
  case 'HEAD':
    aaaart_utils_head();
  break;
  case 'GET':
    // include the handler
    require_once('handler.php');
    exit;
  break;
  case 'PATCH':
  case 'PUT':
  case 'POST':
    $upload_handler = new AaaartUploadHandler($IMAGE_UPLOAD_OPTIONS);
    $upload_handler->post();
  break;
  case 'DELETE':
    // @todo: aaaart_image_delete();
  break;
  default:
    $this->header('HTTP/1.1 405 Method Not Allowed');
}

?>