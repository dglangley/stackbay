<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';

	// Do a slight modification here to only show assigned if they are not admin, manager, or sales
	$permit = (($GLOBALS['U']['admin'] OR $GLOBALS['U']['manager'] OR array_intersect($GLOBALS['USER_ROLES'], array(5)))?true:false);

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
	$query = "SELECT service_orders.so_number, companies.name, service_orders.classid FROM service_orders, companies " . (! $permit ? ', service_assignments, service_items ' : '');
	$query .= "WHERE service_orders.companyid = companies.id ";
	if(! $permit) {
		// Add in service_assigments here as a requirement
		$query .= "AND service_assignments.userid = ".res($GLOBALS['U']['id'])." AND service_items.so_number = service_orders.so_number AND service_assignments.item_id = service_items.id AND service_assignments.item_id_label = 'service_item_id' ";
	}
	$query .="ORDER BY so_number DESC LIMIT 0,50; ";

	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "SELECT line_number, task_name FROM service_items WHERE so_number = '".$r['so_number']."'; ";
		$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			if ($r2['task_name']) { $class = $r2['task_name']; }
			else { $class = getClass($r['classid']); }
			if ($class=='Internal') { continue; }

			$services[] = array('class'=>$class,'number'=>$r['so_number'].'-'.$r2['line_number'],'company'=>$r['name']);
		}
	}

	$service_quotes = array();
	$query = "SELECT q.quoteid, c.name FROM service_quotes q, companies c ";
	$query .= "WHERE q.companyid = c.id ORDER BY q.quoteid DESC LIMIT 0,50; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$service_quotes[] = array('number'=>$r['quoteid'],'company'=>$r['name']);
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
