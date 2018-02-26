<?php
	// TYPE = (Repair, Sale, Purchase) array
	if(! isset($_REQUEST['order_type'])) {
		$types =  array('Sale', 'Purchase', 'Repair', 'Service', 'Return', 'Outsourced');
	}

	$TITLE = "Operations";

	$page = 'operations';

	include 'dashboard.php';
	
	exit;
