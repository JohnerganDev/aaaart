<?php

if (php_sapi_name() == 'cli') {
	
	require_once('../config.php');

	$action = '';
	if (count($argv)>1) {
		$action = $argv[1];
	} else {
		exit;
	}

	switch ($action) {

		case 'clear':
			aaaart_cache_clear();
		break;

		case 'clearCollections':
			aaaart_cache_clear('active_collections');
		break;

		case 'clearComments':
			aaaart_cache_clear('new_comments');
		break;

		case 'clearDocuments':
			aaaart_cache_clear('new_documents');
		break;

	}

}

?>