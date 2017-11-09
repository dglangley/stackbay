<?php
	function detectDefaultType($items) {
		if (array_key_exists('partid',$items) OR (array_key_exists('item_label',$items) AND $items['item_label']=='partid')) {
			$def_type = 'Part';
		} else {
			$def_type = 'Site';
		}

		return ($def_type);
	}
?>
