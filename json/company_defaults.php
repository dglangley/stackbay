<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/order_type.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/cmp.php';
	include_once '../inc/format_address.php';
	include_once '../inc/getFreightAccount.php';
	include_once '../inc/getTerms.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$order_type = 'Sale';
	if (isset($_REQUEST['order_type'])) { $order_type = $_REQUEST['order_type']; }
	$termsid = 0;
	if (isset($_REQUEST['termsid'])) { $termsid = $_REQUEST['termsid']; }
	$freight_account_id = '';
	if (isset($_REQUEST['freight_account_id'])) { $freight_account_id = $_REQUEST['freight_account_id']; }
	$carrierid = 0;
	if (isset($_REQUEST['carrierid'])) { $carrierid = $_REQUEST['carrierid']; }
	$bill_to_id = 0;
	if (isset($_REQUEST['bill_to_id'])) { $bill_to_id = $_REQUEST['bill_to_id']; }
	$ship_to_id = 0;
	if (isset($_REQUEST['ship_to_id'])) { $ship_to_id = $_REQUEST['ship_to_id']; }

	$T = order_type($order_type);

	$freight_carriers = array();
	$freight_accounts = array();
	$billings = array();
	$shippings = array();
	$terms = array();

	// in stages of months (3 months, 6 months, etc), try to get a minimum number of results from which
	// we can build auto-defaults based on use cases
	$ranges = array(3,6,12,36);
	$i = 0;
	foreach ($ranges as $n) {
		$past_date = format_date($today,'Y-m-d 00:00:00',array('m'=>-$n));

		$query = "SELECT ".$T['order']." order_number, ".$T['addressid']." bill_to_id, ship_to_id, freight_carrier_id carrierid, freight_account_id, termsid ";
		$query .= "FROM ".$T['orders']." WHERE companyid = '".res($companyid)."' AND ".$T['datetime']." >= '".res($past_date)."' ";
		if ($bill_to_id) { $query .= "AND ".$T['addressid']." = '".res($bill_to_id)."' "; }
		if ($ship_to_id) { $query .= "AND ship_to_id = '".res($ship_to_id)."' "; }
		if ($termsid) { $query .= "AND termsid = '".res($termsid)."' "; }
		if ($carrierid) { $query .= "AND freight_carrier_id = '".res($carrierid)."' "; }
		$query .= "ORDER BY ".$T['datetime']." DESC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (! $r['bill_to_id']) { continue; }

			if ($r['carrierid']) { $freight_carriers[$r['carrierid']][] = true; }
			if ($r['freight_account_id']) { $freight_accounts[$r['freight_account_id']][] = true; }
			$billings[$r['bill_to_id']][] = true;
			if ($r['ship_to_id']) { $shippings[$r['ship_to_id']][] = true; }
			if ($r['termsid']) { $terms[$r['termsid']][] = true; }
			$i++;
		}

		if ($i>0) { break; }
//		if (mysqli_num_rows($result)>0) { break; }
	}

	uasort($freight_carriers, 'cmp_rcount');
	// get first result which is sorted by most frequent first
	foreach ($freight_carriers as $id => $r) {
		$carrierid = $id;
	}

	uasort($freight_accounts, 'cmp_rcount');
	// get first result which is sorted by most frequent first
	foreach ($freight_accounts as $id => $r) {
		$freight_account_id = $id;
	}

	uasort($shippings, 'cmp_rcount');
	// get first result which is sorted by most frequent first
	foreach ($shippings as $id => $r) {
		$ship_to_id = $id;
	}

	uasort($billings, 'cmp_rcount');
	// get first result which is sorted by most frequent first
	foreach ($billings as $id => $r) {
		$bill_to_id = $id;
	}

	uasort($terms, 'cmp_rcount');
	// get first result which is sorted by most frequent first
	foreach ($terms as $id => $r) {
		$termsid = $id;
	}

	header("Content-Type: application/json", true);
	$results = array(
		'carrierid'=>$carrierid,
		'freight_account_id'=>$freight_account_id,
		'freight_account'=>getFreightAccount($freight_account_id),
		'bill_to_id'=>$bill_to_id,
		'bill_to_address'=>format_address($bill_to_id, ', ', false),
		'ship_to_id'=>$ship_to_id,
		'ship_to_address'=>format_address($ship_to_id, ', ', false),
		'termsid'=>$termsid,
		'terms'=>getTerms($termsid,'id','terms'),
	);
	echo json_encode($results);
	exit;
?>
