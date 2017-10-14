<?php
	header("Content-Type: application/json", true);
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_address.php';
	include_once '../inc/jsonDie.php';

	$addressid = 0;
	if (isset($_REQUEST['addressid'])) { $addressid = trim($_REQUEST['addressid']); }

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

	echo json_encode($address);
	exit;
?>
