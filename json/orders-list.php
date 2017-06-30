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

	$repairs = array();
	$query = "SELECT ro_number, name FROM repair_orders, companies ";
	$query .= "WHERE repair_orders.companyid = companies.id ORDER BY ro_number DESC LIMIT 0,10; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$repairs[] = array('number'=>$r['ro_number'],'company'=>$r['name']);
	}
	
	$returns = array();
	$query = "SELECT rma_number, name FROM returns, companies ";
	$query .= "WHERE returns.companyid = companies.id ORDER BY rma_number DESC LIMIT 0,10; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$returns[] = array('number'=>$r['rma_number'],'company'=>$r['name']);
	}

	$builds = array();
	$query = "SELECT repair_orders.ro_number, builds.id as bid FROM builds, repair_orders ";
	$query .= "WHERE builds.ro_number = repair_orders.ro_number ORDER BY repair_orders.ro_number DESC LIMIT 0,10; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$builds[] = array('number'=>$r['bid'],'company'=>'', 'build_number'=>$r['bid']);
	}

	echo json_encode(array('sales'=>$sales,'purchases'=>$purchases, 'repairs'=>$repairs, 'returns'=>$returns, 'builds'=>$builds,'message'=>''));
	exit;
?>
