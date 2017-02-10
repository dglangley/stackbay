<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$sales = array();
	$query = "SELECT so_number, name FROM sales_orders, companies ";
	$query .= "WHERE sales_orders.companyid = companies.id ORDER BY so_number DESC LIMIT 0,10; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
//		$sales[$r['so_number']] = $r['name'];
		$sales[] = array('number'=>$r['so_number'],'company'=>$r['name']);
	}

	$purchases = array();
	$query = "SELECT po_number, name FROM purchase_orders, companies ";
	$query .= "WHERE purchase_orders.companyid = companies.id ORDER BY po_number DESC LIMIT 0,10; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$purchases[] = array('number'=>$r['po_number'],'company'=>$r['name']);
	}

	echo json_encode(array('sales'=>$sales,'purchases'=>$purchases,'message'=>''));
	exit;
?>
