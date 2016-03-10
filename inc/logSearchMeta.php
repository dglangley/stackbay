<?php
	function logSearchMeta($companyid,$searchlistid=false) {
		global $today,$now;
		if (! $companyid) { return false; }

		$metaid = 0;
		// have we already posted this page? replace instead of create
		$query = "SELECT id FROM search_meta WHERE companyid = '".$companyid."' ";
		$query .= "AND datetime LIKE '".$today."%' AND searchlistid = '".$searchlistid."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==1) {
			$r = mysqli_fetch_assoc($result);
			$metaid = $r['id'];
		}

		// save meta data
		$query = "REPLACE search_meta (companyid, datetime, source, searchlistid";
		if ($metaid) { $query .= ", id"; }
		$query .= ") VALUES ('".$companyid."','".$now."',NULL,";
		if ($searchlistid) { $query .= "'".$searchlistid."'"; } else { $query .= "NULL"; }
		if ($metaid) { $query .= ",'".$metaid."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (! $metaid) { $metaid = qid(); }

		return ($metaid);
	}
?>
