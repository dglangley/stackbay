<?php
	if (! isset($debug)) { $debug = 0; }
	function setAverageCost($partid,$diff,$setAbsolute=false,$setDatetime='') {
		global $debug;

		if ($setAbsolute) {
			$average_cost = $diff;
		} else {
			$existing_avg = 0;//assume we start at 0 with no existing average cost data
			$query = "SELECT * FROM average_costs WHERE partid = '".res($partid)."' ORDER BY datetime DESC LIMIT 0,1; ";
//			if ($debug) { echo $query.'<BR>'; }
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$existing_avg = $r['amount'];
			}

			// multiply average cost by the total number of PIECES in stock at this time, because when we
			// add $diff, we'll likewise divide that by the number of total pieces
			$pieces = 0;
			$average_cost = 0;
			$query = "SELECT qty, serial_no FROM inventory ";
			$query .= "WHERE partid = '".res($partid)."' AND (status = 'shelved' OR status = 'received') AND conditionid >= 0 AND qty > 0; ";
//			if ($debug) { echo $query.'<BR>'; }
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['qty']>0) { $pieces += $r['qty']; }
				else if ($r['serial_no']) { $pieces++; }
			}

			if ($pieces > 0) {
				$ext_avg = $existing_avg * $pieces;
				$average_cost = ($ext_avg+$diff) / $pieces;
			}
		}

		$datetime = $GLOBALS['now'];
		if ($setDatetime) { $datetime = $setDatetime; }

		$query = "INSERT INTO average_costs (partid, amount, datetime) ";
		$query .= "VALUES ('".res($partid)."','".$average_cost."','".$datetime."'); ";
		if ($debug) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }

		return ($average_cost);
	}
?>
