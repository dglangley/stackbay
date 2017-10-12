<?php
	include '../inc/dbconnect.php';
	include '../inc/getContacts.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$companyid = '';
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }

	$contacts = getContacts($companyid);

	$c = array();
	foreach ($contacts as $id => $r) {
		if (! $id) { continue; }

		$c[] = array('id'=>$id,'text'=>$r['name']);
	}

	echo json_encode($c);
	exit;
?>
