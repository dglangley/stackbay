<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function getOrder($str,$type='') {
		$arr = array('search'=>$str,'type'=>$type);

		if ($type) {
			$search = $str;
		} else {
			$search_parts = explode('|',preg_replace('/^([\/])([SPR]O)?([[:alnum:].-]{3,25})$/i','$2|$3',trim($str)));
			if (count($search_parts)<>2) { return $arr; }

			$type = $search_parts[0];
			$search = $search_parts[1];
		}
		if (! $search) { return $arr; }

		$arr['search'] = $search;

		if ($type=='SO') {
			$query = "SELECT so_number FROM sales_orders WHERE cust_ref = '".res($search)."' OR so_number = '".res($search)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$search);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$arr['search'] = $r['so_number'];
				$arr['type'] = 'SO';
			}
		} else if ($type=='PO') {
			$query = "SELECT po_number FROM purchase_orders WHERE assoc_order = '".res($search)."' OR po_number = '".res($search)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$search);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$arr['search'] = $r['po_number'];
				$arr['type'] = 'PO';
			}
		}

		return ($arr);
	}

	//$order = preg_replace('/^([\/])([SPR]O)?([0-9]{4,6})$/i','$3',trim($_SERVER["REQUEST_URI"]));
	$type = trim($_SERVER["REQUEST_URI"]);
	$order = '';
	$order_str = explode('|',preg_replace('/^([\/])([SPR]O)?([0-9]{4,6})?$/i','$2|$3',$type));
	if (count($order_str)==2) {
		$type = $order_str[0];
		$order = $order_str[1];
	}

	$O = array('search'=>'','type'=>'');
	// if there's already an assumed language (ie, "SO123456"), try to confirm the data before assuming we know what the user wants;
	// for example, they may be looking up a SO by customer PO# as in "765728", but because the navbar prepends the "SO" as the
	// *type* of order, it will confuse the data...
	if ($type AND $order) {
		$O = getOrder($order,$type);
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
		$O = getOrder($_SERVER["REQUEST_URI"]);

		if ($O['search']) {
			header('Location: /order_form.php?on='.$O['search'].'&ps='.strtolower(substr($O['type'],0,1)));
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
		$_REQUEST['on'] = $O['search'];//substr($order,2);
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
