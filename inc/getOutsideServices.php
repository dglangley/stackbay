<?php
	function getOutsideServices($order_number,$order_type='Service') {
		$outsideServices = array();

/*
		$query = "SELECT * FROM services_joboutsideservice WHERE job_id = '".$order_number."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$outsideServices[] = $r;
		}
*/

		$query = "SELECT os_number FROM outsourced_orders os ";
		$query .= "WHERE order_number = '".res($order_number)."' AND order_type = '".res($order_type)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$os = $r['os_number'];

			$cost = 0;
			$query2 = "SELECT qty, price FROM outsourced_items oi WHERE oi.os_number = $os; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$cost += ($r2['qty']*$r2['price']);
			}

			$outsideServices[] = array('cost'=>$cost);
		}

		return ($outsideServices);
	}
?>
