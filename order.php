<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	//$order = preg_replace('/^([\/])([SPR]O)?([0-9]{4,6})$/i','$3',trim($_SERVER["REQUEST_URI"]));
	$order_str = explode('-',preg_replace('/^([\/])([SPR]O)?([0-9]{4,6})$/i','$2-$3',trim($_SERVER["REQUEST_URI"])));
	$type = $order_str[0];
	$order = $order_str[1];

	// if no prefixed type ("PO123456") we are going to auto-determine (or try!)
	if (! $type) {
		$query = "SELECT * FROM purchase_orders WHERE po_number = '".$order."' AND created >= CONCAT(DATE_SUB(CURDATE(),INTERVAL 365 DAY),' 00:00:00'); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$po_matches = mysqli_num_rows($result);

		$query = "SELECT * FROM sales_orders WHERE so_number = '".$order."' AND created >= CONCAT(DATE_SUB(CURDATE(),INTERVAL 365 DAY),' 00:00:00'); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$so_matches = mysqli_num_rows($result);

		if ($po_matches>0 AND $so_matches==0) {
			$type = 'PO';
		} else if ($po_matches==0 AND $so_matches>0) {
			$type = 'SO';
		}
/*
		$query = "SELECT * FROM repair_orders WHERE ro_number = '".$order."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$ro_matches = mysqli_num_rows($result);
*/
		$_REQUEST['on'] = $order;
	} else {
		//$type = substr($order,0,2);
		$_REQUEST['on'] = $order;//substr($order,2);
	}

	if ($type=='SO') { $_REQUEST['ps'] = 'Sale'; }
	else if ($type=='PO') { $_REQUEST['ps'] = 'Purchase'; }
	include 'order_form.php';
?>
