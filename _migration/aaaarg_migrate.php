<?php

require("../config.php");

$mysql_user = 'grr';
$mysql_pass = 'grr';
$mysql_db = 'grr_drupal6';
$mysql_host = 'localhost';

$solr = new Solr();

/*
* Connect to MySQL
*/
function grr_mysql_connect($username, $password, $server='localhost', $link = 'db_link') {
    global $$link, $db_error;
    $db_error = false;
    if (!$server) {
        $db_error = 'No Server selected.';
        return false;
    }
    $$link = @mysql_connect($server, $username, $password) or $db_error = mysql_error();
    return $$link;
}

/*
* Get the maximum drupal nid
*/
function grr_mongo_get_maximum_drupal_id($collection) {
    global $db;
    $cursor = $db->{$collection}->find()->sort( array("grr_id"=> -1) )->limit(1);
    foreach ($cursor as $obj) {
    		if (!empty($obj['grr_id']))
	        return $obj['grr_id'];
    }
    return FALSE;
}


/*
* Adds a bunch of texts to our mongo db
*/
function grr_mongo_add_documents($arr, $collection) {
    global $db;
    global $solr;
    foreach ($arr as $a) {
        $db->{$collection}->insert($a);
        /*
        switch($collection) {
            case IMAGES_COLLECTION: $solr->indexDocument($a); break;
            case COLLECTIONS_COLLECTION: $solr->indexCollection($a); break;
        }
        */
    }
}


/*
* Converts all string elements of an array to utf-8
*/
function grr_utf8_the_array(&$arr) {
    if (!empty($arr)) {
        foreach ($arr as $k=>$v) {
            if (is_string($v)) {
            	try {
        			$str = iconv(mb_detect_encoding($v, mb_detect_order(), true), "UTF-8", $v);
                } catch (Exception $e) {
                	$str = '';
                }
                $arr[$k] = $str;
            }
        }
    }
}


/*
* Get un-migrated nodes from Drupal DB
*/
function grr_get_unmigrated_texts($max=FALSE, $num=100) {
    global $mysql_db;
    global $db;
    $uid = aaaart_user_get_id();
		$now = time();
    $nodes = array();
    $max_nid = (!empty($max)) ? $max : 0;
    $query = sprintf("SELECT * FROM node WHERE type='aaaarg_text' AND nid>%s ORDER BY nid ASC LIMIT 0,%s", $max_nid, $num);
    $result = mysql_db_query($mysql_db, $query);
    while ($r = mysql_fetch_array($result, MYSQL_ASSOC)) { // Begin while
        grr_utf8_the_array($r);
        $attributes = array(
        	'grr_id' => intval($r['nid']),
		  	'uploader' => aaaart_mongo_id($uid),
		  	'created' => $r['created'],
		  	'changed' => $r['changed'],
		  	'files' => array(),
		  	'title' => $r['title'],
        );

        // add author stuff
        $query_author = sprintf("SELECT a.*, t.weight FROM aaaarg_text_author t JOIN aaaarg_authors a ON t.aid=a.aid WHERE t.nid=%s ORDER BY t.weight", $r['nid']);
        $result_author = mysql_db_query($mysql_db, $query_author);
        $makers = array();
        while ($r_author = mysql_fetch_array($result_author, MYSQL_ASSOC)) {
	        	grr_utf8_the_array($r_author);
            $makers[] = array(
                'title' => '',
                'first' => $r_author['first_name'],
                'middle' => '',
                'last' => $r_author['last_name'],
                'suffix' => '',
                'sortby' => $r_author['last_name'].','.$r_author['first_name'],
                'display' => trim(sprintf('%s %s', $r_author['first_name'], $r_author['last_name'])),
            );
        }
        aaaart_image_process_makers_array($makers, $attributes);
        // add file stuff
        //@todo
        // finally add aaarg text stuff
        $query_text = sprintf("SELECT t.* FROM aaaarg_texts t WHERE t.nid=%s", $r['nid']);
        $result_text = mysql_db_query($mysql_db, $query_text);
        $fid = FALSE;
        if ($r_text = mysql_fetch_array($result_text, MYSQL_ASSOC)) {
            grr_utf8_the_array($r_text);
            $attributes['makers_display'] = $r_text['display_author'];
            $attributes['makers_orderby'] = $r_text['order_by'];
            $attributes['metadata']['one_liner'] = $r_text['one_liner'];
        }
        // body
        $query_body = sprintf("SELECT body FROM node_revisions r JOIN node n ON r.vid=n.vid WHERE r.nid=%s AND n.nid=%s", $r['nid'], $r['nid']);
        $result_body = mysql_db_query($mysql_db, $query_body);
        if ($r_body = mysql_fetch_array($result_body, MYSQL_ASSOC)) {
            grr_utf8_the_array($r_body);
            $attributes['metadata']['description'] = $r_body['body']; //Markdown($r_body['body']);
            $attributes['metadata']['description_format'] = 'html';
        }
        // look for one more text
        // ??
        // add external links
        // @todo
        
        $nodes[] = $attributes;
    }
    return $nodes;
}


/*
* Get un-migrated nodes from Drupal DB
*/
function grr_get_unmigrated_issues($max=FALSE, $num=100) {
    global $mysql_db;
    global $db;
    $uid = aaaart_mongo_id(aaaart_user_get_id());
    $nodes = array();
    $max_nid = (!empty($max)) ? $max : 0;
    $query = sprintf("SELECT n.*, i.free_text FROM node n JOIN aaaarg_issues i ON n.nid=i.nid WHERE n.type='aaaarg_issue' AND n.nid>%s ORDER BY n.nid ASC LIMIT 0,%s", $max_nid, $num);
    $result = mysql_db_query($mysql_db, $query);
    $attributes = array();
    while ($r = mysql_fetch_array($result, MYSQL_ASSOC)) { // Begin while
        $now = time();
        grr_utf8_the_array($r);
        $attributes = array(
            'owner' => $uid,
            'created' => $r['created'],
            'changed' => $r['changed'],
            'type' => ($r['promote']) ? 'public' : 'private',
            'title' => $r['title'],
            'short_description' => '',
            'editors' => array(), // a list of user ids of people who can edit 
            'invitations' => array(), // a list of email addresses
            'contents' => array(), // what is in this collection
        );
        // body
        $query_body = sprintf("SELECT body FROM node_revisions r JOIN node n ON r.vid=n.vid WHERE r.nid=%s AND n.nid=%s", $r['nid'], $r['nid']);
        $result_body = mysql_db_query($mysql_db, $query_body);
        if ($r_body = mysql_fetch_array($result_body, MYSQL_ASSOC)) {
            grr_utf8_the_array($r_body);
            $attributes['metadata']['notes'] = $r['free_text'];
            $attributes['metadata']['description'] = $r_body['body']; //Markdown($r_body['body']);
            $attributes['metadata']['description_format'] = 'html';
        }
        $attributes['grr_id'] = intval($r['nid']);
        grr_utf8_the_array($r);
        // get texts
        $query_texts = sprintf("SELECT * FROM aaaarg_node_issue WHERE issue_nid=%s", $r['nid']);
        $result_texts = mysql_db_query($mysql_db, $query_texts);
        while ($r_text = mysql_fetch_array($result_texts, MYSQL_ASSOC)) {
            if (!empty($r_text['nid'])) {
                $t = aaaart_mongo_get_one(IMAGES_COLLECTION, array('grr_id' => (int)$r_text['nid']));
                //debug($t);
                if (!empty($t['_id'])) {
                    $attributes['contents'][] = array(
                        'adder' => $uid, // @todo
                        'object' => aaaart_mongo_create_reference(IMAGES_COLLECTION, $t['_id']),
                        'added' => $r_text['added'],
                    );
                }
            }
        }
        /*
        // get editors
        $query_contributor = sprintf("SELECT DISTINCT(uid) as uid FROM aaaarg_node_issue WHERE issue_nid=%s", $r['nid']);
        $result_contributor = mysql_db_query($mysql_db, $query_contributor);
        $r['editors'] = array($r['user_id']);
        while ($r_contributor = mysql_fetch_array($result_contributor, MYSQL_ASSOC)) {
            if (!empty($r_contributor['uid']) && !in_array($r_contributor['uid'], $r['editors'])) {
                $r['editors'][] = intval($r_contributor['uid']);
            }
        }
        $r['num_editors'] = count($r['editors']);
        // get followers
        $query_follower = sprintf("SELECT DISTINCT(uid) as uid FROM flag_content WHERE content_id=%s", $r['nid']);
        $result_follower = mysql_db_query($mysql_db, $query_follower);
        $r['followers'] = array();
        while ($r_follower = mysql_fetch_array($result_follower, MYSQL_ASSOC)) {
            $r['followers'][] = intval($r_follower['uid']);
        }
        $r['num_followers'] = count($r['followers']);
        // aaarg data
        $query_issue = sprintf("SELECT i.* FROM aaaarg_issues i WHERE i.nid=%s", $r['nid']);
        $result_issue = mysql_db_query($mysql_db, $query_issue);
        if ($r_issue = mysql_fetch_array($result_issue, MYSQL_ASSOC)) {
            grr_utf8_the_array($r_text);
            $r['num_texts'] = $r_issue['num_texts'];
            $r['notes'] = $r_issue['free_text'];
            unset($r_issue['featured_date']);
            $r['list_type'] = 'bibliography';
            if ($r_issue['view_custom_ordering']==1) {
                $r['list_type'] = 'ordered';
            }
        }
        // unset a couple fields
        unset($r['promote']);
        unset($r['nid']);
        */
        $nodes[] = $attributes;
    }
    return $nodes;
}

grr_mysql_connect($mysql_user,$mysql_pass);

//$solr->clearSolr();
// migrate texts
if ($_GET['step']=='texts') {
    $max_nid = grr_mongo_get_maximum_drupal_id(IMAGES_COLLECTION);
    $nodes = grr_get_unmigrated_texts($max_nid, 8000);
    printf('Got %s texts bigger than %s', count($nodes), $max_nid);
    grr_mongo_add_documents($nodes, IMAGES_COLLECTION);
}

// migrate issues
if ($_GET['step']=='issues') {
    $max_nid = grr_mongo_get_maximum_drupal_id(COLLECTIONS_COLLECTION);
    $nodes = grr_get_unmigrated_issues($max_nid, 2500);
    printf('Got %s issues bigger than %s', count($nodes), $max_nid);
    grr_mongo_add_documents($nodes, COLLECTIONS_COLLECTION);
}
?>