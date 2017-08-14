<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';

	if (! isset($debug)) { $debug = 0; }
	function setAverageCost($partid,$diff,$setAbsolute=false,$setDatetime='') {
		global $debug;

		if ($setAbsolute) {
			$average_cost = $diff;
		} else {
			$existing_avg = getCost($partid);

			if (isset($GLOBALS['QTYS']) AND isset($GLOBALS['QTYS'][$partid])) { $pieces = $GLOBALS['QTYS'][$partid]; }
			else { $pieces = getQty($partid); }

			// multiply average cost by the total number of PIECES in stock at this time, because when we
			// add $diff, we'll likewise divide that by the number of total pieces
			$average_cost = 0;

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
