<?php
	// this is the same date range used in getSupply() so keeping here for consistency
	$rfq_back_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-21));//last 3 weeks
	function getRFQ($partids,$companyid) {
		global $rfq_back_date;

		$partid_csv = '';
		if (! is_array($partids)) {
			$partids = array($partids);
		}
		foreach ($partids as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		$rfqs = array();
		if (! $partid_csv) { return ($rfqs); }

		$query = "SELECT * FROM rfqs WHERE partid IN (".$partid_csv.") ";
		$query .= "AND companyid = '".res($companyid)."' AND datetime >= '".$rfq_back_date."'; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$rfqs[] = $r;
		}

		return ($rfqs);
	}
?>
