<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcCost.php';

	$s = '';
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) { $s = trim($_REQUEST['s']); }

	$pos = array();
	$results = hecidb($s);
	foreach ($results as $partid => $P) {
		$query = "SELECT po_number FROM purchase_items WHERE partid = '".$partid."' GROUP BY po_number; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$pos[$r['po_number']] = $r['po_number'];
		}
	}

	foreach ($pos as $po) {
		echo $po.': '.calcCost($po);
		echo '<BR><BR>';
	}
/*
	$partids = array();
	$results = hecidb($s);
	foreach ($results as $partid => $r) {
		$partids[] = $partid;

		echo '<strong>'.$r['search'].': '.$r['heci'].' '.$r['part'].'</strong><BR>'.
			' &nbsp; '.getCost($partid).'<BR><BR>';
	}

	echo 'Total avg cost: '.getCost($partids);
*/
?>
