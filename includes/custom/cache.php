<?php

define( 'CACHE_COLLECTION', 'cache');

/*
 *
 */
function aaaart_cache_get($name) {
	global $pager;
	$pager_str = $pager['start'].'-'.$pager['amount'];
	$i = aaaart_mongo_get_one(CACHE_COLLECTION, array('cache_name'=>$name, 'page' =>$pager_str));
	if (!empty($i['value'])) {
		return $i['value'];
	} else {
		return false;
	}
}

/*
 *
 */
function aaaart_cache_set($name, $value) {
	global $pager;
	$pager_str = $pager['start'].'-'.$pager['amount'];
	aaaart_mongo_update(CACHE_COLLECTION, array('cache_name'=>$name, 'page'=>$pager_str), array('value'=>$value), true);
}

/*
 *
 */
function aaaart_cache_invalidate($name) {
	aaaart_mongo_remove(CACHE_COLLECTION, array('cache_name'=>$name));
}


/*
 *
 */
function aaaart_cache_clear() {
	aaaart_mongo_remove(CACHE_COLLECTION, array());
}

?>