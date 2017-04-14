<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	//Temp array to hold Brian's data
	$creditHolder = array();
	
	//For testing purposes and compile time adding LIMIT 30
	$query = "SELECT * FROM inventory_creditmemo ORDER BY id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$creditHolder[] = $r;
	}
	
	foreach ($creditHolder as $key => $value) {
		print_r($value);
		echo '<br><br>';
	}
	
	
	function setCredits($companyid, $date_created, $order_num, $order_type, $rma, $repid, $contactid, $id = 0) {
		$companyid = (int)$companyid;
		$date_created = format_date($date_created);
		$order_num = (int)$order_num;
		
		$order_type = (string)$order_type;
		$order_type = trim($order_type);
		
		$rma = (int)$rma;
		$repid = (int)$repid;
		$contactid = (int)$contactid;

		$query = "REPLACE sales_credits (companyid, date_created, order_num, order_type, rma, repid, contactid";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($companyid)."',";
		$query .= " '".res($date_created)."'";
		$query .= ", ".res($order_num)."";
		$query .= ", '".res($order_type)."'";
		$query .= ", ".res($rma)."";
		$query .= ", ".res($repid)."";
		$query .= ", ".res($contactid)."";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		

		return ($id);
	}
	
	function setCreditItems($cid, $sales_item_id, $return_item_id, $qty, $amount, $id = 0) {
		$cid = (int)$cid;
		$sales_item_id = (int)$sales_item_id;
		$return_item_id = (int)$return_item_id;
		$qty = (int)$qty;
		$amount = (float)$amount;

		$query = "REPLACE sales_credit_items (cid, sales_item_id, return_item_id, qty, amount";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES (".res($cid).",";
		$query .= " ".res($sales_item_id)."";
		$query .= ", ".res($return_item_id)."";
		$query .= ", ".res($qty)."";
		$query .= ", '".res($amount)."'";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		

		return ($id);
	}
?>
