<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/jsonDie.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$carrierid = 0;
	if (isset($_REQUEST['carrierid'])) { $carrierid = $_REQUEST['carrierid']; }
	$order_type = 0;
	if (isset($_REQUEST['order_type'])) { $order_type = $_REQUEST['order_type']; }
	$q = '';
	if (isset($_REQUEST['q'])) { $q = strtoupper(trim($_REQUEST['q'])); }

	// purchases should always have VenTel (user company) as it's companyid
	if ($order_type=='purchase_request') { $order_type = 'Purchase'; }
	if ($order_type=='Purchase') {
		$companyid = 25;
	}

	$accounts = array();

	if (! $q) { $accounts[] = array('id'=>0,'text'=>'PREPAID'); }
	$query = "SELECT id, account_no text FROM freight_accounts ";
	$query .= "WHERE companyid = '".res($companyid)."' AND carrierid = '".res($carrierid)."' ";
	if ($q) { $query .= "AND account_no LIKE '".res($q)."%' "; }
	$query .= "; ";
	$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
	if ($q AND mysqli_num_rows($result)==0) {
		$accounts[] = array('id'=>$q,'text'=>$q);
	}
	while ($r = mysqli_fetch_assoc($result)) {
		$accounts[] = $r;
	}

	header("Content-Type: application/json", true);
	echo json_encode($accounts);
	exit;
?>
