<?php
	function getFavorites($partids) {
		if (! $partids OR ! is_array($partids)) {
			if (is_numeric($partids)) {
				$arr = array($partids);
				$partids = $arr;
			} else {
				$partids = array();
			}
		}

		$userid = $GLOBALS['U']['id'];

		$favs = array();

		if (count($partids)==0) { return ($favs); }

		$partid_csv = '';
		foreach ($partids as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		// check favorites
		$query = "SELECT userid, partid FROM favorites WHERE partid IN (".$partid_csv.") ";
		$query .= "ORDER BY IF(userid = '".$userid."',0,1); ";// LIMIT 0,1; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			// no duplicates because then we'll end up showing the wrong star icon below
			if (isset($favs[$r['partid']])) { continue; }

			if ($r['userid']==$userid) {
				$favs[$r['partid']] = 'fa-star text-danger';
			} else {
				$favs[$r['partid']] = 'fa-star-half-o text-danger';
			}
		}

		return ($favs);
	}
?>
