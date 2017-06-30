<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';

	$inventoryid = 0;
	if (isset($_REQUEST['id'])) { $inventoryid = trim($_REQUEST['id']); }

	$serial = '';
	if (isset($_REQUEST['s'])) { $serial = trim($_REQUEST['s']); }

	$set_avg = '';
	if (isset($_REQUEST['set_avg'])) { $set_avg = trim($_REQUEST['set_avg']); }

	$query = "SELECT serial_no, partid, date_created, id FROM inventory WHERE ";
	if ($inventoryid) { $query .= "id = $inventoryid "; }
	if ($inventoryid AND $serial) { $query .= "AND "; }
	if ($serial) { $query .= "serial_no = '".$serial."' "; }
	$query .= "; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		die("No results: ".$query);
	}
	$r = mysqli_fetch_assoc($result);
	$serial = $r['serial_no'];
	$partid = $r['partid'];
	$inventoryid = $r['id'];
	$datetime = $r['date_created'];

	echo 'Using inventoryid '.$inventoryid.' for serial '.$serial.'<BR><BR>';

	$avg_cost = '0.00';
	$query = "SELECT * FROM average_costs WHERE partid = $partid ORDER BY datetime DESC LIMIT 0,1; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		echo "No average costs for partid!<BR>".$query."<BR><BR>";
	} else {
		$r = mysqli_fetch_assoc($result);
		$avg_cost = $r['amount'];
	}

	$actual = '0.00';
	$query = "SELECT actual FROM inventory_costs WHERE inventoryid = $inventoryid; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)<>1) {
		echo "No singular cost record for inventory record.<BR>".$query."<BR><BR>";
	} else {
		$r = mysqli_fetch_assoc($result);
		$actual = $r['actual'];
	}

	echo 'Serial '.$serial.' starts with actual cost $'.$actual.', and an average cost $'.$avg_cost.' for partid '.$partid.'<BR><BR>';
	if (! $set_avg) { exit; }

	setCost($inventoryid,$set_avg,$datetime);

	$query = "SELECT * FROM average_costs WHERE partid = $partid ORDER BY datetime DESC LIMIT 0,1; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		die("No average costs: ".$query);
	}
	$r = mysqli_fetch_assoc($result);
	$avg_cost = $r['amount'];

	$query = "SELECT actual FROM inventory_costs WHERE inventoryid = $inventoryid; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)<>1) {
		die("No single costs for inventory record: ".$query);
	}
	$r = mysqli_fetch_assoc($result);
	$actual = $r['actual'];

	echo 'New actual cost is $'.$actual.', with an updated average cost of $'.$avg_cost.' for partid '.$partid.'<BR>';
?>
