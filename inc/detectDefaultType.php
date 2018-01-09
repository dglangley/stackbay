<?php
	function detectDefaultType($items,$type='') {
		if (array_key_exists('partid',$items) OR (array_key_exists('item_label',$items) AND ($items['item_label']=='partid' OR (! $items['item_label'] AND $type<>'Service' AND $type<>'Outsourced')))) {
			$def_type = 'Part';
//		} else if (array_key_exists('addressid',$items) OR (array_key_exists('item_label',$items) AND $items['item_label']=='addressid')) {
//			$def_type = 'Site';
		} else {
//			$def_type = '';
			$def_type = 'Site';
		}

		return ($def_type);
	}
?>
