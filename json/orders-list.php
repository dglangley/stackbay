<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';

	$sales = array();
	$query = "SELECT so_number, name FROM sales_orders, companies ";
	$query .= "WHERE sales_orders.companyid = companies.id ORDER BY so_number DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
//		$sales[$r['so_number']] = $r['name'];
		$sales[] = array('number'=>$r['so_number'],'company'=>$r['name']);
	}

	$purchases = array();
	$query = "SELECT po_number, name FROM purchase_orders, companies ";
	$query .= "WHERE purchase_orders.companyid = companies.id ORDER BY po_number DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$purchases[] = array('number'=>$r['po_number'],'company'=>$r['name']);
	}

	$repairs = array();
	$query = "SELECT ro_number, name FROM repair_orders, companies ";
	$query .= "WHERE repair_orders.companyid = companies.id ORDER BY ro_number DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$repairs[] = array('number'=>$r['ro_number'],'company'=>$r['name']);
	}
	
	$returns = array();
	$query = "SELECT rma_number, name FROM returns, companies ";
	$query .= "WHERE returns.companyid = companies.id ORDER BY rma_number DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$returns[] = array('number'=>$r['rma_number'],'company'=>$r['name']);
	}

	$builds = array();
	$query = "SELECT repair_orders.ro_number, builds.id as bid FROM builds, repair_orders ";
	$query .= "WHERE builds.ro_number = repair_orders.ro_number ORDER BY repair_orders.ro_number DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$builds[] = array('number'=>$r['bid'],'company'=>'', 'build_number'=>$r['bid']);
	}

	$services = array();
	$query = "SELECT so_number, name FROM service_orders, companies ";
	$query .= "WHERE service_orders.companyid = companies.id ORDER BY so_number DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "SELECT line_number, task_name FROM service_items WHERE so_number = '".$r['so_number']."'; ";
		$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$services[] = array('number'=>$r['so_number'].'-'.$r2['line_number'],'company'=>$r2['task_name'].' '.$r['name']);
		}
	}

	$service_quotes = array();
	$query = "SELECT q.id, c.name FROM service_quotes q, companies c ";
	$query .= "WHERE q.companyid = c.id ORDER BY q.id DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$service_quotes[] = array('number'=>$r['id'],'company'=>$r['name']);
	}

	$invoices = array();
	$query = "SELECT invoice_no, name FROM invoices, companies ";
	$query .= "WHERE invoices.companyid = companies.id ORDER BY invoice_no DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$invoices[] = array('number'=>$r['invoice_no'],'company'=>$r['name']);
	}

	$bills = array();
	$query = "SELECT bill_no, name FROM bills, companies ";
	$query .= "WHERE bills.companyid = companies.id ORDER BY bill_no DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$bills[] = array('number'=>$r['bill_no'],'company'=>$r['name']);
	}

	echo json_encode(array(
		'sales'=>$sales,
		'purchases'=>$purchases,
		'repairs'=>$repairs,
		'returns'=>$returns,
		'builds'=>$builds,
		'services'=>$services,
		'service_quotes'=>$service_quotes,
		'invoices'=>$invoices,
		'bills'=>$bills,
		'message'=>''
	));
	exit;
?>
