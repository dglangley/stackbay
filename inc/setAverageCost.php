<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';

	function setAverageCost($partid,$diff,$setAbsolute=false,$setDatetime='') {
		if ($setAbsolute) {
			$average_cost = $diff;
		} else {
			$existing_avg = getCost($partid);

			if (isset($GLOBALS['QTYS']) AND isset($GLOBALS['QTYS'][$partid])) { $pieces = $GLOBALS['QTYS'][$partid]; }
			else { $pieces = getQty($partid); }
			if ($GLOBALS['DEBUG']) { $pieces += 1; }

			// multiply average cost by the total number of PIECES in stock at this time, because when we
			// add $diff, we'll likewise divide that by the number of total pieces
			$average_cost = 0;

			if ($pieces > 0) {
				$ext_avg = $existing_avg * $pieces;
				$average_cost = ($ext_avg+$diff) / $pieces;
			}

			if ($GLOBALS['DEBUG']) {
				echo 'DEBUG: '.$existing_avg.' existing avg / '.$pieces.' stock pieces = '.$average_cost.'<BR>';
			}
		}

		$datetime = $GLOBALS['now'];
		if ($setDatetime) { $datetime = $setDatetime; }

		$query = "INSERT INTO average_costs (partid, amount, datetime) ";
		$query .= "VALUES ('".res($partid)."','".$average_cost."','".$datetime."'); ";
		$result = qedb($query);

		return ($average_cost);
	}
?>
