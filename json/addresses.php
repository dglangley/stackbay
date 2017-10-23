<?php
	header("Content-Type: application/json", true);
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
	$fieldid = 'bill_to_id';
	if (isset($_REQUEST['fieldid'])) { $fieldid = $_REQUEST['fieldid']; }
	if ($order_type=='Purchase' AND $fieldid=='bill_to_id') { $fieldid = 'remit_to_id'; }

	$addresses = array();

	if (! $companyid AND ! $q) { return ($addresses); }

	$T = order_type($order_type);

	$ids = array();
	$strs = array();
	$query = "SELECT *, addresses.id addyid FROM ".$T['orders']." o, addresses WHERE ";
	if ($companyid) { $query .= "o.companyid = '".res($companyid)."' "; }
	if ($companyid AND $q) { $query .= "AND "; }
	if ($q) { $query .= "(name RLIKE '".res($q)."' OR street RLIKE '".res($q)."' OR addr2 RLIKE '".res($q)."' OR city RLIKE '".res($q)."' OR notes RLIKE '".res($q)."') "; }
	$query .= "AND o.".res($fieldid)." = addresses.id ";//AND o.".res($fieldid)." IS NOT NULL ";
	$query .= "GROUP BY addresses.id ORDER BY ".$T['datetime']." DESC; ";
	$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$ids[$r['addyid']] = true;
		$str = format_address($r['addyid'],', ',false);
		if (isset($strs[$str])) { continue; }
		$strs[$str] = true;

		$addresses[] = array('id'=>$r['addyid'],'text'=>$str);
	}

	$id_str = '';
	foreach ($ids as $id => $bool) {
		if ($id_str) { $id_str .= ','; }
		$id_str .= $id;
	}

	if ($companyid) {
		$query = "SELECT * FROM company_addresses ca, addresses a ";
		$query .= "WHERE ca.companyid = '".res($companyid)."' AND ca.addressid = a.id ";
		if ($q) { $query .= "AND (name RLIKE '".res($q)."' OR street RLIKE '".res($q)."' OR addr2 RLIKE '".res($q)."' OR city RLIKE '".res($q)."' OR notes RLIKE '".res($q)."') "; }
		if ($id_str) { $query .= "AND ca.addressid NOT IN (".$id_str.") "; }
		$query .= "; ";
		$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$ids[$r['addressid']] = true;
			$str = format_address($r['addressid'],', ',false);
			if (isset($strs[$str])) { continue; }
			$strs[$str] = true;

			$addresses[] = array('id'=>$r['addressid'],'text'=>$str);
		}
	}

	$id_str = '';
	foreach ($ids as $id => $bool) {
		if ($id_str) { $id_str .= ','; }
		$id_str .= $id;
	}

	if ($q) {
		$query = "SELECT * FROM addresses WHERE ";
		$query .= "(name RLIKE '".res($q)."' OR street RLIKE '".res($q)."' OR addr2 RLIKE '".res($q)."' OR city RLIKE '".res($q)."' OR notes RLIKE '".res($q)."') ";
		if ($id_str) { $query .= "AND id NOT IN (".$id_str.") "; }
		$query .= "; ";
		$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$ids[$r['id']] = true;
			$addresses[] = array('id'=>$r['id'],'text'=>format_address($r['id'],', ',false));
		}
	}

	if ($q) {
		// always provide an Add option for new addresses
        $addresses[] = array(
            'id' => "Add $q",
            'text' => "Add $q..."
        );
	}

	echo json_encode($addresses);
	exit;
?>
