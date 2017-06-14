<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';

	if (! isset($debug)) { $debug = 1; }

	function mergeInventories($s) {
		global $debug;

		$s = trim($s);
		if (! $s) {
			echo "Where is your search string, foo?!<BR>".chr(10);
			return false;
		}

		// confirm two records
		$serials = array();
		$query = "SELECT * FROM inventory WHERE serial_no = '".$s."' ORDER BY qty DESC; ";
        $result = qdb($query) OR die(qe().'<BR>'.$query);
		$num_records = mysqli_num_rows($result);
		if ($num_records<>2) {
			echo $num_records.' record(s) found to query: '.$query.'<BR>'.chr(10);
			return false;
		}
		while ($r = mysqli_fetch_assoc($result)) {
			$serials[] = $r;
		}
		// save typing
		$s1 = $serials[0];
		$s2 = $serials[1];

		// one record must be in stock, the other not; one record must carry the purchase record, the other not;
		// one record must not carry the sales record, the second must
		if (($s1['qty']<>1 OR $s2['qty']<>0) OR (! $s1['purchase_item_id'] OR $s2['purchase_item_id']) OR ($s1['sales_item_id'] OR ! $s2['sales_item_id'])) {
			echo "One of the following fields don't align: qty, purchase_item_id, or sales_item_id<BR>".chr(10);
			return false;
		}

		echo '0. '.$s1['serial_no'].'<BR>'.chr(10);
		echo '1. '.$s2['serial_no'].'<BR>'.chr(10);
		// use the date created on the first (in stock) record - this was the originating record
		$date_created = $s1['date_created'];

		// get brians average cost for the sold item - this will almost always, if not always, already be the average cost
		// in our database for the second (sold) item
		$query = "SELECT avg_cost FROM inventory_solditem WHERE serial = '".$s."'; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		if (mysqli_num_rows($result)<>1) {
			echo 'Could not determine a singular solditem source for "'.$s.'"<BR>'.chr(10);
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		$avg_cost = $r['avg_cost'];

		// find inventory_costs record with matching average cost and discard the other(s)
		$query = "SELECT inventoryid FROM inventory_costs WHERE (inventoryid = '".$s1['id']."' OR inventoryid = '".$s2['id']."') AND average = '".$avg_cost."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.chr(10).$query);
		if (mysqli_num_rows($result)>1) {
			echo 'Could not determine a singular inventory_costs source for "'.$s.'" with average cost '.$avg_cost.'<BR>'.chr(10);
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		if ($r['inventoryid']==$s1['id']) {
			$save = 0;
			$del = 1;
		} else {
			$save = 1;
			$del = 0;
		}

		$returns_item_id = 0;
		if ($s1['returns_item_id']) { $returns_item_id = $s1['returns_item_id']; }
		else if ($s2['returns_item_id']) { $returns_item_id = $s2['returns_item_id']; }

		// count deleting records for safe-keeping
		$query = "SELECT * FROM inventory_costs WHERE inventoryid = '".$serials[$del]['id']."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.chr(10).$query);
		$query = "DELETE FROM inventory_costs WHERE inventoryid = '".$serials[$del]['id']."'; ";
		echo 'Matching '.mysqli_num_rows($result).' inventory_costs result(s) for deletion: '.$query.'<BR>'.chr(10);
		if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.chr(10).$query); }

		$query = "SELECT * FROM inventory_costs_log WHERE inventoryid = '".$serials[$del]['id']."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.chr(10).$query);
		$query = "UPDATE inventory_costs_log SET inventoryid = '".$serials[$save]['id']."' WHERE inventoryid = '".$serials[$del]['id']."'; ";
		echo 'Matching '.mysqli_num_rows($result).' inventory_costs_log result(s) for update: '.$query.'<BR>'.chr(10);
		if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.chr(10).$query); }

		$query = "UPDATE inventory SET qty = 0, partid = '".$s2['partid']."', status = 'manifest', date_created = '".$date_created."', ";
		$query .= "purchase_item_id = '".$s1['purchase_item_id']."', sales_item_id = '".$s2['sales_item_id']."' ";
		if ($returns_item_id) { $query .= ", returns_item_id = '".$returns_item_id."' "; }
		$query .= "WHERE id = '".$serials[$save]['id']."'; ";
		echo $query.'<BR>'.chr(10);
		if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.chr(10).$query); }

		$query = "DELETE FROM inventory WHERE id = '".$serials[$del]['id']."'; ";
		echo $query.'<BR>'.chr(10);
		if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.chr(10).$query); }

/*
		$query = "UPDATE inventory_history SET date_changed = '".$date_created."' ";
		$query .= "WHERE invid = '".$r['id']."'; ";
//		if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
echo $query.'<BR>'.chr(10);
*/

		$query = "UPDATE package_contents SET serialid = '".$serials[$save]['id']."' WHERE serialid = '".$serials[$del]['id']."'; ";
		echo $query.'<BR>'.chr(10);
		if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.$query); }

		if ($returns_item_id) {
			$query = "UPDATE inventory_history SET invid = '".$serials[$save]['id']."' WHERE invid = '".$serials[$del]['id']."'; ";
			if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
			echo $query.'<BR>'.chr(10);

			$query = "UPDATE return_items SET inventoryid = '".$serials[$save]['id']."' WHERE inventoryid = '".$serials[$del]['id']."'; ";
			if (! $debug) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
			echo $query.'<BR>'.chr(10);
		}

echo '<BR>'.chr(10);
	}
?>
