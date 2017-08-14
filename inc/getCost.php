<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';

	$cost_datetimes = array();
	function getCost($partid,$cost_basis='average',$absolute_stock=false) {
		global $cost_datetimes;

		$partids = array();
		$csv_partids = '';
		if (is_array($partid)) {
			$partids = $partid;
		} else if (strstr($partid,',')) {
			$csv_partids = $partid;
		} else {
			$partids[] = trim($partid);
		}

		// $csv_partids can be passed in as $partid so only do the following if an array or variable has been passed in
		if (! $csv_partids) {
			foreach ($partids as $partid) {
				if (! $partid OR ! is_numeric($partid)) { continue; }

				if ($csv_partids) { $csv_partids .= ','; }
				$csv_partids .= $partid;
			}
		}

		if (! $csv_partids) { return false; }

		$actual_sum = 0;
		$average_sum = 0;
		$qty_sum = 0;
		$query = "SELECT * FROM average_costs WHERE id IN (SELECT max(id) FROM average_costs WHERE partid IN (".$csv_partids.") GROUP BY partid) ORDER BY datetime DESC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// if we want to know the last-available average cost (for example, when shipping the last item in stock for COGS purposes),
			// absolute stock is set to true so that we assume an absolute value of qty 1
			if ($absolute_stock) {
				$qty = 1;
			} else {
				$qty = getQty($r['partid']);
			}
			$qty_sum += $qty;
			$average_sum += $qty*$r['amount'];
			$cost_datetimes[$r['partid']] = $r['datetime'];
		}
		$actual_cost = 0;
		$average_cost = 0;
		if ($qty_sum>0) {
			$actual_cost = $actual_sum/$qty_sum;
			$average_cost = $average_sum/$qty_sum;
		}

		if ($cost_basis=='average' AND (! $actual_cost OR $average_cost>0)) {
			return ($average_cost);
		} else {
			return ($actual_cost);
		}
	}
?>
