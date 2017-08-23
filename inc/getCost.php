<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';

	$cost_datetimes = array();
	$COSTS = array();
	function getCost($partid,$cost_basis='average',$absolute_stock=false) {
		global $cost_datetimes,$COSTS;

		$partids = array();
		$csv_partids = '';
		if (is_array($partid)) {
			$partids = $partid;
		} else if (strstr($partid,',')) {
			$csv_partids = $partid;
		} else {
			if (isset($COSTS[$partid][$cost_basis])) { return ($COSTS[$partid][$cost_basis]); }

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

		$cost = 0;
		$cost_sum = 0;
		$qty_sum = 0;
		if ($cost_basis=='average') {
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
				$cost_sum += $qty*$r['amount'];
				$cost_datetimes[$r['partid']] = $r['datetime'];
			}
			if ($qty_sum>0) {
				$cost = $cost_sum/$qty_sum;
			}
		} else if ($cost_basis=='actual') {
			$query = "SELECT i.serial_no, i.qty, i.partid, actual FROM inventory_costs c, inventory i ";
			$query .= "WHERE i.partid IN (".$csv_partids.") AND i.id = c.inventoryid; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['qty']==0 AND $r['serial_no']) { $r['qty'] = 1; }
				$cost_sum += $r['qty']*$r['actual'];
				$qty_sum += $r['qty'];
			}
			if ($qty_sum>0) {
				$cost = $cost_sum/$qty_sum;
			}
		}

		if (! is_array($partid) AND ! strstr($partid,',')) {
			$COSTS[$partid][$cost_basis] = $cost;
		}

		return ($cost);
	}
?>
