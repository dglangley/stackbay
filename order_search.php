<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/isOrder.php';

	$type = trim($_SERVER["REQUEST_URI"]);
	$order = '';
	$order_str = explode('|',preg_replace('/^([\/])(RMA|INV|OS|[SPR]O)?([0-9]{4,6})?$/i','$2|$3',$type));
	if (count($order_str)==2) {
		$type = $order_str[0];
		$order = $order_str[1];
	}

	$O = array('search'=>'','type'=>'');
	// if there's already an assumed language (ie, "SO123456"), try to confirm the data before assuming we know what the user wants;
	// for example, they may be looking up a SO by customer PO# as in "765728", but because the navbar prepends the "SO" as the
	// *type* of order, it will confuse the data...
	if ($type AND $order) {
		$O = isOrder($order,$type);
		if ($O['search'] AND ! $O['type']) {
			header('Location: /operations.php?s='.$O['search']);
			exit;
		}
		$order = $O['search'];
		$type = $O['type'];
	}

	// user is searching by customer PO#?
	$search_parts = '';
	if (! $order AND $type==$_SERVER["REQUEST_URI"]) {
		$O = isOrder($_SERVER["REQUEST_URI"]);

		if ($O['search']) {
			header('Location: /order.php?order_number='.$O['search'].'&order_type='.$O['type']);//strtolower(substr($O['type'],0,1)));
			exit;
		} else {
			header('Location: /operations.php?s='.$O['search']);
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

		$query = "SELECT * FROM repair_orders WHERE ro_number = '".$order."' AND created >= CONCAT(DATE_SUB(CURDATE(),INTERVAL 365 DAY),' 00:00:00'); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$ro_matches = mysqli_num_rows($result);

		if ($po_matches>0 AND $so_matches==0 AND $ro_matches==0) {
			$type = 'Purchase';//'PO';
		} else if ($po_matches==0 AND $so_matches>0 AND $ro_matches==0) {
			$type = 'Sale';//'SO';
		} else if ($po_matches==0 AND $so_matches==0 AND $ro_matches>0) {
			$type = 'Repair';//'RO';
		}
/*
		$query = "SELECT * FROM repair_orders WHERE ro_number = '".$order."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$ro_matches = mysqli_num_rows($result);
*/
		$_REQUEST['on'] = $order;
	} else {
		//$type = substr($order,0,2);
		$_REQUEST['order_number'] = $O['search'];//substr($order,2);
	}

	if ($type=='SO') { $_REQUEST['order_type'] = 'Sale'; }
	else if ($type=='PO') { $_REQUEST['order_type'] = 'Purchase'; }
	else if ($type=='RO') { $_REQUEST['order_type'] = 'Repair'; }
	else if ($type) { $_REQUEST['order_type'] = $type; }

	if (in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES) || in_array("7", $USER_ROLES)) {
		if ($type=='RMA') {
			$_REQUEST['rma'] = $O['search'];
			$_REQUEST['order_number'] = '';
			include 'rma.php';
		} else if ($type=='INV') {
			$_REQUEST['invoice'] = $O['search'];
			include 'invoice.php';
		} else {
			include 'order.php';
		}
	} else {
		if ($type=='PO' OR $type=='Purchase') {
			include 'receiving.php';
		} else if ($type=='SO' OR $type=='Sale') {
			include 'shipping.php';
		} else if ($type=='OS' OR $type=='Outsourced') {
			include 'order.php';
		} else if ($type=='Service') {
			include 'service.php';
		} else {
			include 'repair.php';
		}
	}
?>
