<?php
	function getShelflife($partid_csv) {
		$now = $GLOBALS['now'];

		$shelflife = "";
		if (! $partid_csv) { return ($shelflife); }

		$results = array();
		$query = "SELECT * FROM inventory WHERE partid IN (".$partid_csv."); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$in = $r['date_created'];
			$out = $now;

			$query2 = "SELECT date_changed FROM inventory_history ";
			$query2 .= "WHERE invid = '".$r['id']."' AND field_changed = 'sales_item_id' AND value > 0; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$out = $r2['date_changed'];
			}
   
			$date1 = new DateTime(substr($in,0,10));
			$date2 = new DateTime(substr($out,0,10));
			$days = $date2->diff($date1)->format("%a");

			$results[] = $days;
		}

		$num_results = count($results);
		if ($num_results>0) {
			$days = round(array_sum($results)/$num_results);
			$s = '';
			if ($days<>1) { $s = 's'; }
			$shelflife = $days.' day'.$s;
		}

		return ($shelflife);
	}
?>
