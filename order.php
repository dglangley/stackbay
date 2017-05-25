<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	//$order = preg_replace('/^([\/])([SPR]O)?([0-9]{4,6})$/i','$3',trim($_SERVER["REQUEST_URI"]));
	$type = trim($_SERVER["REQUEST_URI"]);
	$order = '';
	$order_str = explode('|',preg_replace('/^([\/])([SPR]O)?([0-9]{4,6})?$/i','$2|$3',$type));
	if (count($order_str)==2) {
		$type = $order_str[0];
		$order = $order_str[1];
	}

	// user is searching by customer PO#?
	$search_parts = '';
	if (! $order AND $type==$_SERVER["REQUEST_URI"]) {
		$search_parts = explode('|',preg_replace('/^([\/])([SPR]O)?([[:alnum:].-]{3,25})$/i','$2|$3',trim($_SERVER["REQUEST_URI"])));
		$type = $search_parts[0];
		$search = $search_parts[1];

		if ($search) {
			if ($type=='SO') {
				$query = "SELECT so_number FROM sales_orders WHERE cust_ref = '".res($search)."' OR so_number = '".res($search)."'; ";
				$result = qdb($query) OR die(qe().'<BR>'.$search);
				if (mysqli_num_rows($result)==1) {
					$r = mysqli_fetch_assoc($result);
					header('Location: /order_form.php?on='.$r['so_number'].'&ps=s');
					exit;
				}
			} else if ($type=='PO') {
				$query = "SELECT po_number FROM purchase_orders WHERE assoc_order = '".res($search)."' OR po_number = '".res($search)."'; ";
				$result = qdb($query) OR die(qe().'<BR>'.$search);
				if (mysqli_num_rows($result)==1) {
					$r = mysqli_fetch_assoc($result);
					header('Location: /order_form.php?on='.$r['po_number'].'&ps=p');
					exit;
				}
			}
			header('Location: /operations.php?s='.$search);
			exit;
		}
	}

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

	if (in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) {
		include 'order_form.php';
	} else {
		if ($type=='PO') {
			include 'inventory_add.php';
		} else {
			include 'shipping.php';
		}
	}
?>
