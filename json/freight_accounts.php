<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/jsonDie.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$carrierid = 0;
	if (isset($_REQUEST['carrierid'])) { $carrierid = $_REQUEST['carrierid']; }

	$accounts = array(
		0 => array('id'=>0,'text'=>'PREPAID'),
	);
	$query = "SELECT id, account_no text FROM freight_accounts ";
	$query .= "WHERE companyid = '".res($companyid)."' AND carrierid = '".res($carrierid)."'; ";
	$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$accounts[] = $r;
	}

	header("Content-Type: application/json", true);
	echo json_encode($accounts);
	exit;
?>
