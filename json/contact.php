<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	header("Content-Type: application/json", true);

	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid']) AND $_REQUEST['contactid']>0) {
		$contactid = $_REQUEST['contactid'];
	}

	if (! $contactid) {
		jsonDie('Missing contactid!');
	}

	$msg = 'Success';
	$c = getContact($contactid);
	$CONTACTS[$contactid]['id']['message'] = $msg;

	// only way I could figure to convert 'null' to empty string ''
	$contact = array();
	foreach ($CONTACTS[$contactid]['id'] as $k => $c) {
		if (! $c) { $c = (string)$c; }
		$contact[$k] = $c;
	}

	echo json_encode($contact);
	exit;
?>
