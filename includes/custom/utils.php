<?php

/*

These utilities depend only on config.php

*/

// Custom pager handling
$pager = array(
  'start' => 0,
  'amount' => 25,
);

if (!empty($_GET['_p']) && is_numeric($_GET['_p']) && $_GET['_p']>=0) {
  $pager['start'] = $_GET['_p'];
}
if (!empty($_GET['_n']) && is_numeric($_GET['_n']) && $_GET['_n']>=0) {
  $pager['amount'] = $_GET['_n'];
}

/**
 * Gets full URL of script
 */
function aaaart_utils_get_full_url() {
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  return
    ($https ? 'https://' : 'http://').
    (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
    (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
    ($https && $_SERVER['SERVER_PORT'] === 443 ||
    $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
    substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
}


/**
 * Gets the file upload path
 */
function aaaart_utils_script_url() {
  return aaaart_utils_get_full_url().'/';
}


/**
 *
 */
function aaaart_utils_get_field_data($arr, $field_name, $index) {
  if (!empty($arr[$field_name]) && is_array($arr[$field_name]) && !empty($arr[$field_name][$index])) {
    return $arr[$field_name][$index];
  } else if (!empty($arr[$field_name]) && is_string($arr[$field_name])) {
    return $arr[$field_name];
  } else {
    return '';
  }
}


/**
 *
 */
function aaaart_utils_get_file_size($file_path, $clear_stat_cache = false) {
  if ($clear_stat_cache) {
    clearstatcache(true, $file_path);
  }
  return aaaart_utils_fix_integer_overflow(filesize($file_path));
}


/**
 *
 */
function aaaart_utils_get_file_type($file_path) {
  switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
    case 'jpeg':
    case 'jpg':
      return 'image/jpeg';
    case 'png':
      return 'image/png';
    case 'gif':
      return 'image/gif';
    case 'pdf':
      return 'application/pdf';
    default:
      return '';
  }
}


/**
 *
 */
function aaaart_utils_load_search_from_query_string() {
  return isset($_GET['q']) ? $_GET['q'] : false;
}


/**
 * Overly simple determination of whether we're looking at an image 
 */
function aaaart_utils_is_image($filename) {
  return in_array(aaaart_utils_get_file_type($filename), array('image/jpeg','image/png','image/gif'));
}

/**
 *
 */
function aaaart_utils_trim_file_name($name, $type, $index, $content_range) {
  // Remove path information and dots around the filename, to prevent uploading
  // into different directories or replacing hidden system files.
  // Also remove control characters and spaces (\x00..\x20) around the filename:
  $name = trim(basename(stripslashes($name)), ".\x00..\x20");
  // Use a timestamp for empty filenames:
  if (!$name) {
    $name = str_replace('.', '-', microtime(true));
  }
  // Add missing file extension for known image types:
  if (strpos($name, '.') === false && preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
    $name .= '.'.$matches[1];
  } else if (strpos($name, '.') === false && preg_match('/^application\/(pdf|epub|zip|rar)/', $type, $matches)) {
    $name .= '.'.$matches[1];
  } else if (strpos($name, '.') === false && preg_match('/^image\/(djvu)/', $type, $matches)) {
    $name .= '.'.$matches[1];
  } else if (strpos($name, '.') === false) {
    switch ($type) {
      case 'application/msword': $name .= '.doc'; break;
      case 'text/plain': $name .= '.txt'; break;
      case 'text/html': $name .= '.html'; break;
    }
  }
  return $name;
}


/**
 *
 */
function aaaart_utils_get_server_var($id) {
  return isset($_SERVER[$id]) ? $_SERVER[$id] : '';
}


/**
 *
 */
function aaaart_utils_get_query_separator($url) {
  return strpos($url, '?') === false ? '?' : '&';
}


/**
 *
 */
function aaaart_utils_send_access_control_headers() {
  global $ACCESS_CONTROL_ALLOW_METHODS, $ACCESS_CONTROL_ALLOW_HEADERS;
  header('Access-Control-Allow-Origin: '.ACCESS_CONTROL_ALLOW_ORIGIN);
  header('Access-Control-Allow-Credentials: ' .(ACCESS_CONTROL_ALLOW_CREDENTIALS ? 'true' : 'false'));
  header('Access-Control-Allow-Methods: ' .implode(', ', $ACCESS_CONTROL_ALLOW_METHODS));
  header('Access-Control-Allow-Headers: ' .implode(', ', $ACCESS_CONTROL_ALLOW_HEADERS));
}


/**
 *
 */
function aaaart_utils_send_content_type_header() {
  header('Vary: Accept');
  if (strpos(aaaart_utils_get_server_var('HTTP_ACCEPT'), 'application/json') !== false) {
    header('Content-type: application/json');
  } else {
    header('Content-type: text/plain');
  }
}


/**
 *
 */
function aaaart_utils_header($str) {
  header($str);
}


/**
 *
 */
function aaaart_utils_head() {
  header('Pragma: no-cache');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Content-Disposition: inline; filename="files.json"');
  // Prevent Internet Explorer from MIME-sniffing the content-type:
  header('X-Content-Type-Options: nosniff');
  if (ACCESS_CONTROL_ALLOW_ORIGIN) {
      aaaart_utils_send_access_control_headers();
  }
  aaaart_utils_send_content_type_header();
}


/**
 *
 */
function aaaart_utils_body($str) {
  echo $str;
}

/**
 *
 */
function aaaart_utils_fix_integer_overflow($size) {
  if ($size < 0) {
    $size += 2.0 * (PHP_INT_MAX + 1);
  }
  return $size;
}


/**
 *
 */
function aaaart_utils_get_config_bytes($val) {
  $val = trim($val);
  $last = strtolower($val[strlen($val)-1]);
  switch($last) {
    case 'g':
      $val *= 1024;
    case 'm':
      $val *= 1024;
    case 'k':
      $val *= 1024;
  }
  return aaaart_utils_fix_integer_overflow($val);
}

/**
 *
 */
function aaaart_utils_generate_response($content, $print_response = true) {
  if ($print_response) {
    $json = json_encode($content);
    $redirect = isset($_REQUEST['redirect']) ?
      stripslashes($_REQUEST['redirect']) : null;
    if ($redirect) {
      header('Location: '.sprintf($redirect, rawurlencode($json)));
      return;
    }
    aaaart_utils_head();
    if (aaaart_utils_get_server_var('HTTP_CONTENT_RANGE')) {
      $files = isset($content[PARAM_NAME]) ? $content[PARAM_NAME] : null;
      if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
        header('Range: 0-'.(
            aaaart_utils_fix_integer_overflow(intval($files[0]->size)) - 1
        ));
      }
    }
    aaaart_utils_body($json);
  }
  return $content;
}


/** 
 * Send a site email
 */
function aaaart_utils_send_email($to, $subject, $message) {
  $headers = "From:" . MAIL_FROM;
  mail($to,$subject,$message,$headers);
  $mail = new PHPMailer;

  $mail->IsSMTP();                                      // Set mailer to use SMTP
  $mail->Host = SMTP_HOST;                              // Specify main and backup server
  $mail->SMTPAuth = true;                               // Enable SMTP authentication
  $mail->Username = SMTP_USER;                            // SMTP username
  $mail->Password = SMTP_PASS;                           // SMTP password
  //$mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted

  $mail->From = MAIL_FROM;
  $mail->FromName = SITE_TITLE;
  $mail->AddAddress($to);               // Name is optional

  //$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
  //$mail->AddAttachment('/var/tmp/file.tar.gz');         // Add attachments
  //$mail->AddAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
  //$mail->IsHTML(true);                                  // Set email format to HTML

  $mail->Subject = $subject;
  $mail->Body    = $message;
  $mail->AltBody = $message;  

  if(!$mail->Send()) {
    error_log('mail could not be sent to ' . $to);
  }
}


/**
 * Takes a parsed name array and formats it into a full name
 */
function aaaart_utils_format_name($name_arr) {
  //debug('aaaart_utils_format_name()');
  $t = (!empty($name_arr['title'])) ? $name_arr['title'] : '';
  $f = (!empty($name_arr['first'])) ? $name_arr['first'] : '';
  $m = (!empty($name_arr['middle'])) ? $name_arr['middle'] : '';
  $l = (!empty($name_arr['last'])) ? $name_arr['last'] : '';
    
  $name = sprintf('%s %s %s %s', $t, $f, $m, $l);
  if (!empty($name_arr['suffix'])) {
    $name .= sprintf(', %s', $name_arr['suffix']);
  }
  while(strpos($name, '  ') !== false) { 
    $name = str_replace('  ', ' ', $name); 
  } 
  return $name;
}


/**
 * Takes a parsed name array and formats it into a full name
 */
function aaaart_utils_format_names($names_arr) {
  //debug('aaaart_utils_format_names()');
  $names = array();
  foreach ($names_arr as $name_arr) {
    $names[] = aaaart_utils_format_name($name_arr);
  }
  return implode(', ', $names);
}


/**
 * Takes a name (or series of names separated by commas) and breaks them into parts 
 */
function aaaart_utils_parse_names($str) {
  //debug('aaaart_utils_parse_names()');
  $ret_arr = array();
  $names = explode(',', $str);
  $piped_names = implode('|',$names);
  if (NAME_PARSER_URL) {
    $query_string = http_build_query(array('name' => $piped_names));
    $json = file_get_contents(NAME_PARSER_URL.'?'.$query_string);
  } else {
    $json = exec( sprintf('%s "%s"', NAME_PARSER_SCRIPT, $piped_names) );
  }
  $parsed_names = json_decode($json,true);
  if (!empty($parsed_names)) {
    foreach ($parsed_names as $key=>$val) {
      $val['sortby'] = strtolower(sprintf('%s,%s,%s',$val['last'], $val['first'], $val['middle']));
      $val['display'] = aaaart_utils_format_name($val);
      $ret_arr[] = $val;
    }
  } else {
    foreach ($names as $name) {
      $ret_arr[] = array(
        // if we're here there was a problem with name parsing
        $val['sortby'] = $name,
        $val['display'] = $name,
      );
    }
  }
  return $ret_arr;
}


/**
 * Prints out a single input field
 */
function aaaart_utils_format_input_field($field, $name, $values=array()) {
  $default_value = (!empty($values) && !empty($values[$name])) ? $values[$name] : '';
  if (!empty($field['type'])) {
    switch ($field['type']) {
      case 'textarea':
        $field_markup = sprintf(
          '<label>%s</label><textarea data-provide="markdown" data-width="400" name="%s">%s</textarea><span class="help-block">%s</span>',
          (!empty($field['label'])) ? $field['label'] : '',
          $name,
          $default_value,
          (!empty($field['description'])) ? $field['description'] : ''
        );
      break;
      case 'text':
        $field_markup = sprintf(
          '<label>%s</label><input type="text" name="%s" value="%s"><span class="help-block">%s</span>',
          (!empty($field['label'])) ? $field['label'] : '',
          $name,
          str_replace('"', '\"', $default_value),
          (!empty($field['description'])) ? $field['description'] : ''
        );
      break;
      // @todo : handle other field types?
      default:
        return '';
      break; 
    }
    return sprintf('<div class="edit-%s">%s</div>', $name, $field_markup);
  } else return '';
}


/**
 * Prints out a set of input fields
 * $fields are defined in config.php
 * $doc is an optional mongo object that might have fields (within FIELDS_KEY)
 */
function aaaart_utils_format_input_fields($fields, $doc=array()) {
  $output = '';
  foreach ($fields as $name=>$arr) { 
    if (!empty($doc[FIELDS_KEY])) {
      $output .= aaaart_utils_format_input_field($arr, $name, $doc[FIELDS_KEY]);
    } else {
      $output .= aaaart_utils_format_input_field($arr, $name);
    }
  }
  return $output;
}


/**
 * Prints out a single field
 */
function aaaart_utils_format_display_field($field, $data) {
  if (!empty($field['type'])) {
    switch ($field['type']) {
      case 'textarea':
        return sprintf('<tr><td><h6 class="muted">%s</h6><p>%s</p></td></tr>',
          (!empty($field['label'])) ? $field['label'] : '',
          Slimdown::render($data));
      break;
      case 'text':
      default:
        return sprintf('<tr><td><h6 class="muted">%s</h6><p>%s</p></td></tr>',
          (!empty($field['label'])) ? $field['label'] : '',
          $data);
      break; 
    }
  }
}

/**
 * Prints out a set of output fields
 * $fields are defined in config.php
 * $doc is an optional mongo object that might have fields (within FIELDS_KEY)
 */
function aaaart_utils_format_display_fields($fields, $doc=array()) {
  $output = '<table class="table">';
  foreach ($fields as $name=>$arr) { 
    if (!empty($doc[FIELDS_KEY]) && !empty($doc[FIELDS_KEY][$name])) {
      $output .= aaaart_utils_format_display_field($arr, $doc[FIELDS_KEY][$name]);
    }
  }
  $output .= '</table>';
  return $output;
}


/**
 *
 */
function aaaart_utils_format_date($time) {
  return date("F j, Y, g:i a", $time);
}


/*
 *
 */
function aaaart_truncate($str, $limit) {
  // Make sure a small or negative limit doesn't cause a negative length for substr().
  if ($limit < 3)
  {
    $limit = 3;
  }
  // Now truncate the string if it is over the limit.
  if (strlen($str) > $limit)
  {
    return substr($str, 0, $limit - 3) . '...';
  }
  else
  {
    return $str;
  }
}


/*
 *
 */
function aaaart_array_splice(&$array, $insert, $position) {
  $new_array = array();
  foreach ($array as $k=>$v) {
    if ($k<$position) {
      $new_array[] = $v;
    } else if ($k==$position) {
      $new_array[] = $insert;
      $new_array[] = $v;
    } else if ($k>$position) {
      $new_array[] = $v;
    }
  }
  $array = $new_array;
}

function in_string($needle, $haystack, $insensitive = 0) { 
  if ($insensitive) { 
    return (false !== stristr($haystack, $needle)) ? true : false; 
  } else { 
    return (false !== strpos($haystack, $needle))  ? true : false; 
  } 
} 

/**
 * Prints something out to error log
 */
function debug($x) {
  if (DEBUG_MODE) {
    if (is_string($x)) {
      error_log($x);
    } else if (is_array($x)) {
      error_log(print_r($x, true));
    } else if (is_object($x)) {
      error_log(print_r($x, true));
    } else {
      error_log(print_r(iterator_to_array($x), true));
    }
  }
}
?>