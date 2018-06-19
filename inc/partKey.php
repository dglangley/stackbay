<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';

	$PART_KEYS = array();
	function partKey($partid) {
		global $PART_KEYS;

		if (isset($PART_KEYS[$partid])) { return ($PART_KEYS[$partid]); }

		$H = hecidb($partid,'id');
		$r = $H[$partid];

		if ($r['heci7']) {
			$PART_KEYS[$partid] = $r['heci7'];
		} else {
			$PART_KEYS[$partid] = $r['primary_part'];
		}

		return ($PART_KEYS[$partid]);
	}
?>
