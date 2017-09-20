<?php
	function setOrder($type='Repair') {
		$userid = $GLOBALS['U']['id'];

		switch ($type) {
			case 'Repair':
				$query = "REPLACE repair_orders (created, created_by, companyid, contactid, freight_carrier_id, freight_services_id, termsid, status) ";
				$query .= "VALUES ('".$GLOBALS['now']."','".res($userid)."','25','".res($userid)."','3','14','15','Active'); ";

				break;

			case 'Sale':

				break;

			case 'Return':

				break;

			case 'Purchase':

				break;

			default:

				break;
		}

		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$order_number = qid();

		return ($order_number);
	}
?>
