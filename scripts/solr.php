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

		case 'reindexAll':
			// reindex everything
			$solr = new Solr();
			$solr->clearSolr();
			$solr->reindexAllDocuments();
		break;

		case 'processQueue':
			// only reindex queued items
			$solr = new Solr();
			$solr->clearSolr();
			$solr->processQueue();
		break;

	}

}

?>