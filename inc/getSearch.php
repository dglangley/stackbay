<?php
	function getSearch($searchid) {
		if (! $searchid OR ! is_numeric($search)) { return (""); }
		$query = "SELECT * FROM searches WHERE id = '".res($searchid)."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { return (""); }
		$r = mysqli_fetch_assoc($result);
		return ($r['search']);
	}
?>
