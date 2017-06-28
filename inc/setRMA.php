<?php
	if (! isset($debug)) { $debug = 0; }
	function setRMA($rma,$date,$created_by,$companyid,$order_number,$order_type='Sale',$notes='',$status='Active') {
		if (! $notes) { $notes = 'NULL'; }
		else { $notes = "'".res(trim($notes))."'"; }

		$query = "REPLACE returns (rma_number, created, created_by, companyid, order_number, order_type, contactid, notes, status) ";
		$query .= "VALUES ($rma, '".res($date)."', $created_by, $companyid, '".res($order_number)."', '".res($order_type)."', NULL, $notes, '".$status."'); ";
		echo $query.'<BR>';
		if (! $GLOBALS['debug']) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		return (qid());
	}

	function setRMAitem($rma,$partid,$inventoryid,$dispositionid,$reason='',$qty=1) {
		$reason = trim($reason);

		$return_item_id = 0;
		$query = "SELECT id FROM return_items WHERE partid = '".res($partid)."' ";
		$query .= "AND inventoryid = '".res($inventoryid)."' AND rma_number = '".res($rma)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$return_item_id = $r['id'];
		}

		$query = "REPLACE return_items (partid, inventoryid, rma_number, line_number, reason, dispositionid, qty";
		if ($return_item_id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($partid)."', '".res($inventoryid)."', '".res($rma)."', NULL, ".prep(substr($reason,0,255)).", $dispositionid, $qty";
		if ($return_item_id) { $query .= ", '".res($return_item_id)."'"; }
		$query .= "); ";
		echo $query.'<BR>';
		if (! $inventoryid) {
			echo 'Missing inventoryid: '.$query.'<BR>';
			return false;
		}
		if (! $GLOBALS['debug']) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		return (qid());
	}
?>
