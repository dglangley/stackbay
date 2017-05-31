<?php
	 set_time_limit(0);
	ini_set('memory_limit', '2000M');

	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';

	$s = '';
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) { $s = trim($_REQUEST['s']); }

	if ($s) {
		$pos = array();
		$results = hecidb($s);
		foreach ($results as $partid => $P) {
			$cost = 0;
			$query = "SELECT po_number, price FROM purchase_items WHERE partid = '".$partid."' ";
			$query .= "GROUP BY po_number ORDER BY po_number DESC; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				//echo $r['po_number'].' '.$partid.' $'.$r['price'].' = ';
				$cost = setCost($r['po_number'],$partid);
				//echo $cost.'<BR><BR>';
			}
		}
	} else {
		$query = "SELECT po_number, partid FROM purchase_items ";
		$query .= "GROUP BY po_number, partid ORDER BY po_number ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
//			$cost = setCost($r['po_number'],$r['partid']);
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
