<?php
	// TYPE = (Repair, Sale, Purchase) array
	if(! isset($_REQUEST['order_type']) AND ! isset($types)) {
		$types =  array('Purchase', 'Sale', 'Service', 'Repair', 'Return', 'Outsourced');
	}

	if (! isset($TITLE)) { $TITLE = "Operations"; }

	$page = 'operations';

	include 'dashboard.php';
	
	exit;
