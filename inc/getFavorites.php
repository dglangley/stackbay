<?php
	function getFavorites($partids=false,$userid=0) {
		$show_all = false;//whether this request is to get ALL favs
		if ($partids===true) { $show_all = true; }

		if (! $partids OR ! is_array($partids)) {
			if (is_numeric($partids)) {
				$arr = array($partids);
				$partids = $arr;
			} else {
				$partids = array();
			}
		}

		$favs = array();

		if (count($partids)==0 AND ! $show_all) { return ($favs); }

		$partid_csv = '';
		foreach ($partids as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		// get favorites
		$query = "SELECT f.userid, f.partid, f.datetime, f.id, p.part, p.heci ";
		$query .= "FROM favorites f, parts p ";
		$query .= "WHERE f.partid = p.id ";
		if ($partid_csv) { $query .= "AND f.partid IN (".$partid_csv.") "; }
		$query .= "ORDER BY ";
		if ($userid) { $query .= "IF(f.userid = '".$userid."',0,1), "; }
		$query .= "p.part, p.heci; ";// LIMIT 0,1; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			// no duplicates because then we'll end up showing the wrong star icon below
			if (isset($favs[$r['partid']]) AND isset($favs[$r['partid']][$r['userid']])) { continue; }

			if (! isset($favs[$r['partid']])) { $favs[$r['partid']] = array(); }
			$favs[$r['partid']][$r['userid']] = $r['datetime'];
		}

		return ($favs);
	}
?>
