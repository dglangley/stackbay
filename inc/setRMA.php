<?php
	if (! isset($debug)) { $debug = 0; }
	function setRMA($rma,$date,$created_by,$companyid,$order_number,$order_type='Sale',$notes='',$status='Active') {
		if (! $notes) { $notes = 'NULL'; }
		else { $notes = "'".res(trim($notes))."'"; }

		$query = "REPLACE returns (rma_number, created, created_by, companyid, order_number, order_type, contactid, notes, status) ";
		$query .= "VALUES ($rma, '".res($date)."', $created_by, $companyid, '".res($order_number)."', '".res($order_type)."', NULL, $notes, '".$status."'); ";
		if (! $GLOBALS['debug']) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		return (qid());
	}

	function setRMAitem($rma,$partid,$inventoryid,$dispositionid,$reason='',$qty=1) {
		$reason = trim($reason);

		$query = "REPLACE return_items (partid, inventoryid, rma_number, line_number, reason, dispositionid, qty) ";
		$query .= "VALUES ('".res($partid)."', '".res($inventoryid)."', '".res($rma)."', NULL, ".prep(substr($reason,0,255)).", $dispositionid, $qty); ";
		if (! $inventoryid) {
			echo 'Missing inventoryid: '.$query.'<BR>';
			return false;
		}
		if (! $GLOBALS['debug']) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		return (qid());
	}
?>
