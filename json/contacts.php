<?php
	include '../inc/dbconnect.php';
	include '../inc/getContacts.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$companyid = '';
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }

	$contacts = getContacts($companyid,$q);

	$c = array();
	foreach ($contacts as $id => $r) {
		if (! $id) { continue; }

		$c[] = array('id'=>$id,'text'=>$r['name']);
	}

	if ($q) {
		// always provide an Add option for new addresses
        $c[] = array(
            'id' => "0",
            'text' => "Add $q..."
        );
	}

	echo json_encode($c);
	exit;
?>
