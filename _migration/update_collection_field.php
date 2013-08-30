<?php
/*
mb_internal_encoding("UTF-8");
require("../config.php");

$collection = 'collections';

$cursor = $db->{$collection}->find();
foreach ($cursor as $obj) {
		$changed = false;
		if (!empty($obj['contents'])) {
			foreach ($obj['contents'] as $k=>$v) {
				if (is_string($v['added'])) {
					$obj['contents'][$k]['added'] = intval($obj['contents'][$k]['added']);
					$changed = true;
				}
			}
		}
		if ($changed) {
			$db->{$collection}->save($obj);
		}
}
*/
?>