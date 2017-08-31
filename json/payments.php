<?php
	include_once '../inc/paymentDetails.php';

	header("Content-Type: application/json", true);

	$data = array();
    $paymentid = 0;

    if (isset($_REQUEST['data'])) { $data = $_REQUEST['data']; }
    if (isset($_REQUEST['paymentid'])) { $paymentid = $_REQUEST['paymentid']; }

    $results = array();

    if(! empty($data)) {
        $results = getPaymentDetails($data);
    } else if($paymentid) {
        $results = updatePayment($paymentid);
    }
	
	echo json_encode($results);
	exit;
