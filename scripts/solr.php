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
			// reindex everything
			$solr = new Solr();
			$solr->clearSolr();
		break;

		case 'reindexAll':
			// reindex everything
			$solr = new Solr();
			$solr->reindexAllDocuments();
		break;

		case 'processQueue':
			// only reindex queued items
			$solr = new Solr();
			$solr->processQueue();
		break;

	}

}

?>