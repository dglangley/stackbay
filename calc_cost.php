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
		$cost = 0;
		$query = "SELECT po_number, price FROM purchase_items WHERE partid = '".$partid."' GROUP BY po_number ORDER BY po_number DESC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			echo $r['po_number'].' '.$partid.' $'.$r['price'].' = ';
			$cost = calcCost($r['po_number'],$partid);
			echo $cost.'<BR><BR>';
		}
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
