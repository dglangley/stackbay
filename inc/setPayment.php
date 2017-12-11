<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function setPayment($companyid, $date, $payment_type, $number, $amount, $notes, $userid, $datetime, $items='') {
		global $debug;

		$notes = trim($notes);

		$query = "REPLACE payments (companyid, date, payment_type, number, amount, notes, userid, datetime) ";
		$query .= "VALUES (".fres($companyid).", ".fres($date).", ".fres($payment_type).", ".fres($number).", ";
		$query .= fres($amount).", ".fres($notes).", ".fres($userid).", ".fres($datetime)."); ";
		if ($debug) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		$paymentid = qid();

		if (is_array($items)) {
			foreach ($items as $i => $row) {
				$query = "REPLACE payment_details (order_number, order_type, ref_number, ref_type, amount, paymentid) ";
				$query .= "VALUES (".fres($row['order_number']).", ".fres($row['order_type']).", ";
				$query .= fres($row['ref_number']).", ".fres($row['ref_type']).", ".fres($row['amount']).", '".$paymentid."'); ";
				if ($debug) { echo $query.'<BR>'; }
				else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
			}
		}

		return ($paymentid);
	}
?>
