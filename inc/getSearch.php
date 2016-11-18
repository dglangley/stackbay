<?php
	$IDSEARCHES = array();

	function getSearch($searchid) {
		if (! $searchid OR ! is_numeric($searchid)) { return (""); }
		global $IDSEARCHES;

		if (isset($IDSEARCHES[$searchid])) { return ($IDSEARCHES[$searchid]); }
		$IDSEARCHES[$searchid] = '';

		$query = "SELECT * FROM searches WHERE id = '".res($searchid)."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { return (""); }
		$r = mysqli_fetch_assoc($result);
		$IDSEARCHES[$searchid] = $r['search'];
		return ($r['search']);
	}
?>
