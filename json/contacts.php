<?php
	include '../inc/dbconnect.php';
	include '../inc/getContacts.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$companyid = '';
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }
	$noreset = false;
	if (isset($_REQUEST['noreset'])) { $noreset = trim($_REQUEST['noreset']); }

	$contacts = getContacts($companyid,$q);

	$c = array();
	if (! $q AND ! $noreset) { $c[] = array('id'=>0,'text'=>'- Reset Contacts -'); }
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
