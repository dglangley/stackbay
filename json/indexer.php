<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/indexer.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$search = '';
	if (isset($_REQUEST['search'])) { $search = trim($_REQUEST['search']); }
	$search_type = '';
	if (isset($_REQUEST['search_type'])) { $search_type = trim($_REQUEST['search_type']); }

	// strip out non-alphanumerics
	$search = preg_replace('/[^[:alnum:]-]+/','',$search);
	if (strlen($search)<=2) { reportError("Invalid search string"); }

	indexer($search,$search_type);

	echo json_encode(array('message'=>'Success'));
	exit;
?>
