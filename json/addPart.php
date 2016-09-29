<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/indexer.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$search = '';
	if (isset($_REQUEST['search'])) {
		$search = trim($_REQUEST['search']);
	}

	// strip out non-alphanumerics
	$search = preg_replace('/[^[:alnum:]-]+/','',$search);
	if (strlen($search)<=2) { reportError("Invalid search string (too short)"); }

	$query = "INSERT INTO parts (part) VALUES ('".res(strtoupper($search))."'); ";
	$result = qdb($query);

	indexer($search);

	echo json_encode(array('message'=>'Success'));
	exit;
?>
