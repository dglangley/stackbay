<?php
	function logSearch($search,$search_field=1,$sfb=false,$qty_field=false,$qfb=false,$price_field=false,$pfb=false) {
		global $U;

		if ($sfb) { $search_field = 10-$sfb; }//from back
		if (! $qty_field) { $qty_field = 0; }
		if ($qfb) { $qty_field = 10-$qfb; }//from back
		if (! $price_field) { $price_field = 0; }
		if ($pfb) { $price_field = 10-$pfb; }//from back
		$fields = $search_field.$qty_field.$price_field;

		$userid = 1;
		if ($U['id']) { $userid = $U['id']; }
		$listid = 0;

		$query = "SELECT * FROM search_lists WHERE search_text = '".res(trim($search))."' ";
		$query .= "AND userid = '".res($userid)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$listid = $r['id'];
		}

		$query = "REPLACE search_lists (search_text, fields, datetime, userid";
		if ($listid) { $query .= ", id"; }
		$query .= ") VALUES ('".res(trim($search))."','".res($fields)."','".res($GLOBALS['now'])."','".res($userid)."'";
		if ($listid) { $query .= ",'".$listid."'"; }
		$query .= "); ";
		$result = qedb($query);
		if (! $listid) { $listid = qid(); }

		return ($listid);
	}
?>
