<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/order_type.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/cmp.php';
	include_once '../inc/format_address.php';
	include_once '../inc/getFreightAccount.php';
	include_once '../inc/getTerms.php';
	include_once '../inc/getWarranty.php';
	include_once '../inc/getCondition.php';
	include_once '../inc/getOrder.php';

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

	// get blank order table structure so we know what fields to use below
	$ORDER = getOrder(0,$order_type);

	$freight_carriers = array();
	$freight_accounts = array();
	$billings = array();
	$shippings = array();
	$terms = array();
	$warrantyid = 0;
	$warranties = array();
	$conditionid = 0;
	$conditions = array();

	$company_terms = getCompanyTerms($companyid,$T['account']);

	// in stages of months (3 months, 6 months, etc), try to get a minimum number of results from which
	// we can build auto-defaults based on use cases
	$ranges = array(3,6,12,36);
	$i = 0;
	foreach ($ranges as $n) {
		$past_date = format_date($today,'Y-m-d 00:00:00',array('m'=>-$n));

		$query = "SELECT ".$T['order']." order_number, ".$T['addressid']." bill_to_id, ";
		if (array_key_exists('ship_to_id',$ORDER)) { $query .= "ship_to_id, freight_carrier_id carrierid, freight_account_id, "; }
		$query .= "termsid ";
		$query .= "FROM ".$T['orders']." WHERE companyid = '".res($companyid)."' AND ".$T['datetime']." >= '".res($past_date)."' ";
		if ($bill_to_id) { $query .= "AND ".$T['addressid']." = '".res($bill_to_id)."' "; }
		if (array_key_exists('ship_to_id',$ORDER)) {
			if ($ship_to_id) { $query .= "AND ship_to_id = '".res($ship_to_id)."' "; }
			if ($carrierid) { $query .= "AND freight_carrier_id = '".res($carrierid)."' "; }
		}
		if ($termsid) { $query .= "AND termsid = '".res($termsid)."' "; }
		$query .= "ORDER BY ".$T['datetime']." DESC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (! $r['bill_to_id']) { continue; }

			if ($r['carrierid']) { $freight_carriers[$r['carrierid']][] = true; }
			if ($r['freight_account_id']) { $freight_accounts[$r['freight_account_id']][] = true; }
			$billings[$r['bill_to_id']][] = true;
			if ($r['ship_to_id']) { $shippings[$r['ship_to_id']][] = true; }
			if ($r['termsid']) {
				// confirm this termsid is allowed for this company
				if (array_key_exists($r['termsid'],$company_terms)) {
					$terms[$r['termsid']][] = true;
				}
			}

			if ($T['warranty'] OR $T['condition']) {
				$query2 = "SELECT ";
				if ($T['warranty']) { $query2 .= $T['warranty']." warrantyid "; }
				if ($T['warranty'] AND $T['condition']) { $query2 .= ", "; }
				if ($T['condition']) { $query2 .= $T['condition']." conditionid "; }
				$query2 .= "FROM ".$T['items']." WHERE ".$T['order']." = '".$r['order_number']."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				while ($r2 = mysqli_fetch_assoc($result2)) {
					if ($r2['warrantyid']) { $warranties[$r2['warrantyid']][] = true; }
					if ($r2['conditionid']) { $conditions[$r2['conditionid']][] = true; }
				}
			}

			$i++;
		}

		if ($i>0) { break; }
//		if (mysqli_num_rows($result)>0) { break; }
	}

	uasort($freight_carriers, 'cmp_rcount');
	// get first result which is sorted by most frequent first
	foreach ($freight_carriers as $id => $r) {
		$carrierid = $id;
		break;
	}

	uasort($freight_accounts, 'cmp_rcount');
	// get results and end with most often sorted last
	foreach ($freight_accounts as $id => $r) {
		$freight_account_id = $id;
	}

	uasort($shippings, 'cmp_rcount');
	// get results and end with most often sorted last
	foreach ($shippings as $id => $r) {
		$ship_to_id = $id;
	}

	uasort($billings, 'cmp_rcount');
	// get results and end with most often sorted last
	foreach ($billings as $id => $r) {
		$bill_to_id = $id;
	}

	uasort($terms, 'cmp_rcount');
	// get results and end with most often sorted last
	foreach ($terms as $id => $r) {
		$termsid = $id;
	}

	uasort($warranties, 'cmp_rcount');
	// get results and end with most often sorted last
	foreach ($warranties as $id => $r) {
		$warrantyid = $id;
	}

	uasort($conditions, 'cmp_rcount');
	// get results and end with most often sorted last
	foreach ($conditions as $id => $r) {
		$conditionid = $id;
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
		'warrantyid'=>$warrantyid,
		'warranty'=>getWarranty($warrantyid,'warranty'),
		'conditionid'=>$conditionid,
		'condition'=>getCondition($conditionid),
	);
	echo json_encode($results);
	exit;
?>
