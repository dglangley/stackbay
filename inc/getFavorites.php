<?php
	function getFavorites($partids) {
		if (! $partids OR ! is_array($partids)) { $partids = array(); }

		$favs = array();

		foreach ($partids as $partid => $P) {
			$search = '';
			if (isset($P['search'])) { $search = $P['search']; }

			// check favorites
			$query = "SELECT * FROM favorites WHERE partid = '".$partid."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$favs[$partid] = $search;
			}
		}

		return ($favs);
	}
?>
