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

		foreach ($partids as $partid) {
			// check favorites
			$query = "SELECT * FROM favorites WHERE partid = '".$partid."' ORDER BY IF(userid = '".$userid."',0,1) LIMIT 0,1; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)>0) {
				$r = qrow($result);
				if ($r['userid']==$userid) {
					$favs[$partid] = 'fa-star text-danger';
				} else {
					$favs[$partid] = 'fa-star-half-o text-danger';
				}
			}
		}

		return ($favs);
	}
?>
