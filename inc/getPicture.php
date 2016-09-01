<?php
	$PICTURES = array();
	function getPicture($partid) {
		global $PICTURES;
		if (! $partid OR ! is_numeric($partid)) { return false; }

		if (! isset($PICTURES[$partid])) {
			$PICTURES[$partid] = array();
			$query = "SELECT * FROM picture_maps WHERE partid = '".res($partid)."' ORDER BY id ASC; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==0) { $PICTURES[$partid][0] = false; }
			while ($r = mysqli_fetch_assoc($result)) {
				$PICTURES[$partid][] = $r['image'];
			}
		}

		return ($PICTURES[$partid][0]);
	}
?>
