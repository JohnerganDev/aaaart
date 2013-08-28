<?php
/*

Depends on utils.php, config.php

*/

$db = false;

/**
 * Connect to the Mongo database database
 */
function aaaart_mongo_init() {
	global $db;
  try {
    // open connection to MongoDB server
    $conn = new Mongo(DB_HOST);
    // access database
    $db = $conn->{DB_NAME};
  } catch (MongoConnectionException $e) {
    die('Error connecting to MongoDB server');
  } catch (MongoException $e) {
    die('Error: ' . $e->getMessage());
  }
}


/**
 * Gets a range of records from a mongo collection
 * key: if false, then it is assumed to be
 */
function aaaart_mongo_get_paged($collection, $criterea, $sort=array()) {
  global $db;
  global $pager;
  $c = $db->selectCollection($collection);
  _aaaart_mongo_convert_ids($criterea);
  if (!empty($sort)) {
    $r = $c->find($criterea)->sort($sort);
  } else {
    $r = $c->find($criterea);
  }
  return $r->skip($pager['start']*$pager['amount'])->limit($pager['amount']);
}


/**
 * Gets a range of records from a mongo collection
 * key: if false, then it is assumed to be
 */
function aaaart_mongo_get($collection, $criterea, $sort=array(), $projection=array()) {
  global $db;
  global $pager;
  $c = $db->selectCollection($collection);
  _aaaart_mongo_convert_ids($criterea);
  if (!empty($sort)) {
    return $c->find($criterea, $projection)->sort($sort);
  } else {
    return $c->find($criterea, $projection);
  }
}


/**
 * Gets a single record from a mongo collection
 * key: if false, then it is assumed to be
 */
function aaaart_mongo_get_one($collection, $value, $key="_id") {
  global $db;
  $c = $db->selectCollection($collection);
  if (is_array($value)) {
    $criterea = $value;
  } else {
    $criterea = array($key => $value);
  }
  _aaaart_mongo_convert_ids($criterea);
  return $c->findOne($criterea);
}


/**
 * Gets a document pointed to by a reference
 * key: if false, then it is assumed to be
 */
function aaaart_mongo_get_reference($ref, $collection=null) {
  global $db;
  if ($collection && MongoDBRef::isRef($ref)) {
    $c = $db->selectCollection($collection);
    return $c->getDBRef($ref);
  } else if (MongoDBRef::isRef($ref)) {
    return $db->getDBRef($ref);
  } else return null;
}


/**
 * Creates a database reference
 */
function aaaart_mongo_create_reference($coll, $id) {
  if (is_string($id)) {
    $id = aaaart_mongo_id($id);
  }
  return MongoDBRef::create($coll, $id);
}


/**
 * Save data into the Mongo database
 */
function aaaart_mongo_insert($collection, $data) {
  global $db;
  $c = $db->selectCollection($collection);
  $c->insert($data);
  return $data;
}


/**
 * Removes a document from a mongo collection
 */
function aaaart_mongo_remove($collection, $criterea) {
  global $db;
  $c = $db->selectCollection($collection);
  _aaaart_mongo_convert_ids($criterea);
  if (!empty($criterea)) {
    $c->remove($criterea);
  }
}


/**
 * Updates a mongo collection
 */
function aaaart_mongo_update($collection, $criterea, $data, $upsert=false) {
	global $db;
  $c = $db->selectCollection($collection);
  _aaaart_mongo_convert_ids($criterea);
	if (!empty($criterea)) {
    $set = array('$set' => $data);
    if ($upsert) {
      return $c->update($criterea, $set, array('upsert'=>true));
    } else {
  		return $c->update($criterea, $set);
    }
  }
}


/**
 * Pushes data into a field in the collection
 */
function aaaart_mongo_push($collection, $criterea, $data, $options=array()) {
  global $db;
  $c = $db->selectCollection($collection);
  _aaaart_mongo_convert_ids($criterea);
  if (!empty($criterea)) {
    $push = array('$push' => $data);
    $c->update($criterea, $push, $options);
  }
}


/**
 * Pulls (removes) data from a list field in the collection
 */
function aaaart_mongo_pull($collection, $criterea, $criterea2) {
  global $db;
  $c = $db->selectCollection($collection);
  _aaaart_mongo_convert_ids($criterea);
  _aaaart_mongo_convert_ids($criterea2);
  if (!empty($criterea) && !empty($criterea2)) {
    $pull = array('$pull' => $criterea2);
    $c->update($criterea, $pull);
  }
}


/**
 * Converts a string to a mongo id
 */
function aaaart_mongo_id($str) {
	if (is_string($str)) {
		return new MongoId($str);
	} else {
		return $str;
	}
}


/**
 * Converts a string to a mongo id
 */
function aaaart_mongo_new_id() {
  return new MongoId();
}


/**
 * Cleans mongo ids into string ids
 */
function aaaart_mongo_stringify_ids(&$arr) {
  foreach ($arr as $k=>$v) {
    if ($k=='_id' && ($v instanceof MongoId)) {
      $arr[$k] = (string)$v;
    } else if (is_array($v)) {
      aaaart_mongo_stringify_ids($arr[$k]);
    }
  }
  /*
  if (isset($arr['_id']) && !is_string($arr['_id'])) {
    $arr['_id'] = (string)$arr['_id'];
  }
  foreach ($arr as $k=>$v) {
    if (isset($arr[$k]['_id']) && !is_string($arr[$k]['_id'])) {
      $arr[$k]['_id'] = (string)$arr[$k]['_id'];
    }
  }
  */
}


/**
 * Loops through an array and converts all the _id items to Mongo objects
 */
function _aaaart_mongo_convert_ids(&$arr) {
  if (is_string($arr)) {
    $arr = array("_id" => $arr);
  } else if ($arr instanceof MongoId) {
    $arr = array("_id" => $arr);
    return;
  }
	foreach ($arr as $key=>$val) {
		if ($key=="_id" && is_string($val)) {
			$arr[$key] = aaaart_mongo_id($val);
		}
	}
}


function aaaart_mongo_apply_range($start, $num) {

}

?>