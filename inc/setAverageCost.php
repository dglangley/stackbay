<?php
	function setAverageCost($partid,$diff,$setAbsolute=false) {
		if ($setAbsolute) {
			$average_cost = $diff;
		} else {
			$existing_avg = 0;//assume we start at 0 with no existing average cost data
			$query = "SELECT * FROM average_costs WHERE partid = '".res($partid)."' ORDER BY datetime DESC LIMIT 0,1; ";
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
			$query .= "WHERE partid = '".res($partid)."' AND (status = 'shelved' OR status = 'received') AND conditionid >= 0; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['qty']>0) { $pieces = $r['qty']; }
				else if ($r['serial_no']) { $pieces = $r['serial_no']; }
			}
			if ($pieces>0) {
				$ext_avg = $existing_avg*$pieces;
				$average_cost = ($ext_avg+$diff)/$pieces;
			}
		}

		$query = "INSERT INTO average_costs (partid, amount, datetime) ";
		$query .= "VALUES ('".res($partid)."','".$average_cost."','".$GLOBALS['now']."'); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);

		return ($average_cost);
	}
?>
