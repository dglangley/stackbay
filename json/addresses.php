<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/order_type.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/format_address.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$order_type = 'Sale';
	if (isset($_REQUEST['order_type'])) { $order_type = $_REQUEST['order_type']; }
	$address_field = 'bill_to_id';
	if (isset($_REQUEST['address_field'])) { $address_field = $_REQUEST['address_field']; }
	if ($order_type=='Purchase' AND $address_field=='bill_to_id') { $address_field = 'remit_to_id'; }

	$addresses = array();

	if (! $companyid AND ! $q) { return ($addresses); }

	$T = order_type($order_type);

	$query = "SELECT *, addresses.id addyid FROM ".$T['orders']." o, addresses WHERE ";
	if ($companyid) { $query .= "o.companyid = '".res($companyid)."' "; }
	if ($companyid AND $q) { $query .= "AND "; }
	if ($q) { $query .= "(street RLIKE '".res($q)."' OR addr2 RLIKE '".res($q)."' OR city RLIKE '".res($q)."' OR notes RLIKE '".res($q)."') "; }
	$query .= "AND o.".res($address_field)." = addresses.id ";//AND o.".res($address_field)." IS NOT NULL ";
	$query .= "GROUP BY addresses.id ORDER BY ".$T['datetime']." DESC; ";
	$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$addresses[] = array('id'=>$r['addyid'],'text'=>format_address($r['addyid'],', ',false));
	}

	header("Content-Type: application/json", true);
	echo json_encode($addresses);
	exit;
?>
