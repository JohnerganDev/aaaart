<?php

/**
 * Adds a document to the solr queue for later processing
 */
function aaaart_solr_add_to_queue($coll, $id) {
	$addition = array(
		'coll' => $coll,
		'id' => $id,
		'added' => time(),
	);
	$queue = aaaart_mongo_get_one(SYSTEM_COLLECTION, array('name' => 'solr_queue'));
	if (empty($queue)) {
		aaaart_mongo_insert(SYSTEM_COLLECTION, array('name' => 'solr_queue', 'queue'=>array()));
	}
	// check if it is already in the queue
	foreach ($queue['queue'] as $item) {
		if ($item['id']==$id && $item['coll']==$coll) return false;
	}
	// add it to queue
	aaaart_mongo_push(SYSTEM_COLLECTION, array('name' => 'solr_queue'), array('queue'=>$addition));
}


class Solr {
	
	protected $solr;
	public $available;

	public function __construct() {
		$this->initializeSolr();
	}

	private function initializeSolr() {
		$this->solr = new Apache_Solr_Service( SOLR_HOST, SOLR_PORT, SOLR_PATH );
		if ( ! $this->solr->ping() ) {
			$this->available = FALSE;
	  } else {
	  	$this->available = TRUE;
	  }
	}

	public function escapeString($string) {
    $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
    $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
    $string = str_replace($match, $replace, $string);
    return $string;
  }

	public function parseSearchString($search_expression) {
		return preg_split("/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/", $search_expression, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	}

	public function getFacets($content_type, $field, $filter_field, $filter_value) {
		$additionalParameters = array(
			'fq' => array('content_type:'.$content_type, $filter_field.':'.$filter_value),
			'facet' => 'true',
			'facet.field' => $field,
		);
		$response = $this->solr->search('*:*', 0, 10, $additionalParameters);
		$facets = (array)$response->facet_counts->facet_fields->{$field};
		return $facets;
	}


	/* Executes a simple query */
	public function simpleQuery($query_string, $content_type, $additional_fields_to_search=array(), $facet_fields=array(), $offset=0, $limit=30) {
		if (!$this->available) {
			return array();
		}
		$query_string = $this->escapeString($query_string);
		$fields_to_search = array_merge( array('title','description','makers_display'),  $additional_fields_to_search);
		
		$words = $this->parseSearchString($query_string);
		if (count($words)>1) {
			$words[] = '"'.implode(' ', $words). '"~1000000';
		}
		//$words = array(sprintf('"%s"~1000000', $query_string));
		$modified_query_string = '';
		foreach ($fields_to_search as $field_name) {
			if ($field_name!='title') { $modified_query_string.=' OR ';}
			$modified_query_string .= $field_name.':';
			if (count($words)>1) { $modified_query_string.='('; }
			$modified_query_string.= implode(' OR ',$words);
			if (count($words)>1) { $modified_query_string.=')'; }
		}
		$query = $modified_query_string;
		$additionalParameters = array(
			'fq' => 'content_type:'.$content_type,
		);
		if (!empty($facet_fields)) {
			$additionalParameters['facet'] = 'true';
			$additionalParameters['facet.field'] = $facet_fields;
		}
		//print_r($query);
		$response = $this->solr->search($query, $offset, $limit, $additionalParameters);
		//$response = $this->solr->search( $query_string, $offset, $limit );
    $results = array();
    if ( $response->getHttpStatus() == 200 ) { 
       //debug( $response->getRawResponse() );
      
      if ( $response->response->numFound > 0 ) {
        //echo "$query_string <br />";

        foreach ( $response->response->docs as $doc ) { 
          //echo "$doc->id $doc->title <br />";
        	$keys = $doc->getFieldNames();
        	$vals = $doc->getFieldValues();
        	$results[] = array_combine($keys, $vals);
        }
      }
      if (!empty($facet_fields)) {
      	$results['docs'] = $results;
				foreach ($facet_fields as $field) {
					$results['facets'][$field] = (array)$response->facet_counts->facet_fields->{$field};
				}
			}
    }
    else {
      echo $response->getHttpStatusMessage();
      print_r( $response->getRawResponse() );
    }
    return $results;
	}

	/* Create a single solr doc */
	public function makeDoc($fields) {
		$part = new Apache_Solr_Document();
    foreach ( $fields as $key => $value ) {
      if ( is_array( $value ) ) {
        foreach ( $value as $data ) {
          $part->setMultiValue( $key, $data );
        }
      }
      else {
        $part->$key = $value;
      }
    }
    return $part;
	}

	/* Index a php array representing items */
	public function indexItems($arr) {
		if (!$this->available) {
			return;
		}
		$documents = array();
		foreach ( $arr as $item => $fields ) {
    	$documents[] = $this->makeDoc($fields);
  	}
  	try {
  		echo 'indexed ' . count($documents) . ' documents';
	    $this->solr->addDocuments( $documents );
	    $this->solr->commit();
	    $this->solr->optimize();
	  }
	  catch ( Exception $e ) {
	    echo $e->getMessage();
	  }
	}

	public function clearSolr() {
		if (!$this->available) {
			return;
		}
		$this->solr->deleteByQuery("*:*");	
		$this->solr->commit();
		$this->solr->optimize();
	}

	/**
	 * To add fields, solr schema.xml needs updating
	 */
	public function indexDocument($obj) {
		$item = array(
			'id' => (string)$obj['_id'],
			'title' => !empty($obj['title']) ? $obj['title'] : '',
			'content_type' => IMAGES_COLLECTION,
			'makers_display' => $obj['makers_display'],
			'makers_sort' => !empty($obj['makers_sortby']) ? $obj['makers_sortby'] : '',
			'description' => !empty($obj['metadata']['description']) ? $obj['metadata']['description'] : '',
			'one_liner' => !empty($obj['metadata']['one_liner']) ? $obj['metadata']['one_liner'] : '',
			'makers' => array(),
		);
		// index makers 
		if (!empty($obj['makers'])) {
			foreach ($obj['makers'] as $m) {
				if (!empty($m['$id'])) {
					$item['makers'][] = (string)$m['$id'];
				}
			}
		}
		// index collections
		$collections = aaaart_collection_get_document_collections($item['id'], true);
		foreach ($collections as $c) {
			$item['collections'][] = (string)$c['_id'];
		}
		// add to solr
	  $solr_doc = $this->makeDoc($item);
	  try {
  		$this->solr->addDocument( $solr_doc );
	    $this->solr->commit();
	    $this->solr->optimize();
	  }
	  catch ( Exception $e ) {
	    echo $e->getMessage();
	  }
	}


	/**
	 * To add fields, solr schema.xml needs updating
	 */
	public function indexCollection($obj) {
		$item = array(
			'id' => (string)$obj['_id'],
			'title' => !empty($obj['title']) ? $obj['title'] : '',
			'content_type' => COLLECTIONS_COLLECTION,
			'description' => !empty($obj['metadata']['description']) ? $obj['metadata']['description'] : '',			
		); 
		
		// add to solr
	  $solr_doc = $this->makeDoc($item);
	  try {
  		$this->solr->addDocument( $solr_doc );
	    $this->solr->commit();
	    $this->solr->optimize();
	  }
	  catch ( Exception $e ) {
	    echo $e->getMessage();
	  }
	}


	/**
	 * To add fields, solr schema.xml needs updating
	 */
	public function indexDiscussion($obj) {
		$item = array(
			'id' => (string)$obj['_id'],
			'title' => !empty($obj['title']) ? $obj['title'] : '',
			'content_type' => COMMENTS_COLLECTION,
			'description' => '',
		); 
		if (!empty($obj['posts'])) {
			foreach ($obj['posts'] as $post) {
				$item['description'] .= $post['text'] . ' ';
			}
		}
		// add to solr
	  $solr_doc = $this->makeDoc($item);
	  try {
  		$this->solr->addDocument( $solr_doc );
	    $this->solr->commit();
	    $this->solr->optimize();
	  }
	  catch ( Exception $e ) {
	    echo $e->getMessage();
	  }
	}


	/**
	 * Reindex all documents
	 */
	public function reindexAllDocuments() {
		global $pager;
		$keep_going = true;
		while ($keep_going) {
			$docs =	aaaart_mongo_get_paged(IMAGES_COLLECTION, array());
			if ($docs->count()==0) {
				$keep_going = false;
			} else {
				foreach ($docs as $doc) {
					$this->indexDocument($doc);
				}
				$pager['start'] = $pager['start']	+ 1;
			}	
		}
	}


	/**
	 * Reindex all documents
	 */
	public function reindexAllComments() {
		global $pager;
		$keep_going = true;
		while ($keep_going) {
			$docs =	aaaart_mongo_get_paged(COMMENTS_COLLECTION, array());
			if ($docs->count()==0) {
				$keep_going = false;
			} else {
				foreach ($docs as $doc) {
					$this->indexDiscussion($doc);
				}
				$pager['start'] = $pager['start']	+ 1;
			}	
		}
	}	

	/**
	* Indexes every document in the solr queue
	*/
	function processQueue($num=25) {
		$queue = aaaart_mongo_get_one(SYSTEM_COLLECTION, array('name' => 'solr_queue'));
		$todo = array();
		$count = 0;
		if (!empty($queue['queue'])) {
			foreach ($queue['queue'] as $item) {
				if ($count<$num) {
					$obj = aaaart_mongo_get_one($item['coll'], $item['id']);
					switch ($item['coll']) {
						case IMAGES_COLLECTION: $this->indexDocument($obj); break;
						case COLLECTIONS_COLLECTION: $this->indexCollection($obj); break;
						case COMMENTS_COLLECTION: $this->indexDiscussion($obj); break;
					}
					aaaart_mongo_pull(SYSTEM_COLLECTION, array('name' => 'solr_queue'), array('queue' => array('id'=>$item['id'])));
					$count = $count + 1;
				}
			}
		}
	}

}


?>