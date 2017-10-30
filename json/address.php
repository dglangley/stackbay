<?php
	header("Content-Type: application/json", true);
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_address.php';
	include_once '../inc/getContact.php';
	include_once '../inc/jsonDie.php';

	$addressid = 0;
	if (isset($_REQUEST['addressid'])) { $addressid = trim($_REQUEST['addressid']); }
	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }

	$address = array();
	// build default array
	$query = "SHOW COLUMNS FROM addresses;";
	$fields = qdb($query) OR jsonDie(qe().'<BR>'.$query);
	if (mysqli_num_rows($fields)==0) {
		echo json_encode($address);exit;
	}
	while ($r = mysqli_fetch_assoc($fields)) {
		$address[$r['Field']] = '';
	}
	$address['title'] = 'Add New Address';
	if (! $addressid) { echo json_encode($address); exit; }

	$query = "SELECT * FROM addresses WHERE id = ".fres($addressid)."; ";
	$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) { echo json_encode($address); exit; }
	$address = mysqli_fetch_assoc($result);
	$address['title'] = format_address($addressid,', ',false);
	$address['nickname'] = NULL;
	$address['alias'] = NULL;
	$address['contactid'] = NULL;
	$address['contact'] = NULL;
	$address['code'] = NULL;

	//check company addresses for addl info
	if ($companyid) {
		$query = "SELECT * FROM company_addresses WHERE addressid = '".res($addressid)."' AND companyid = '".res($companyid)."'; ";
		$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$address['nickname'] = $r['nickname'];
			$address['alias'] = $r['alias'];
			$address['contactid'] = $r['contactid'];
			if ($r['contactid']) { $address['contact'] = getContact($r['contactid']); }
			$address['code'] = $r['code'];
			$address['title'] = format_address($addressid,', ',false,$address['contactid']);
		}
	}

	echo json_encode($address);
	exit;
?>
