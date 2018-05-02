<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';

	if (! isset($DEBUG)) { $DEBUG = 0; }

	function setOrder($type='Repair') {
		$userid = $GLOBALS['U']['id'];

		switch ($type) {
			case 'Repair':
				$query = "REPLACE repair_orders (created, created_by, companyid, contactid, freight_carrier_id, freight_services_id, termsid, status) ";
				//$query .= "VALUES ('".$GLOBALS['now']."','".res($userid)."','25','".res(getContact($userid,'userid','id'))."','3','14','15','Active'); ";
				$query .= "VALUES ('".$GLOBALS['now']."','".res($userid)."','25',NULL,'3','14','15','Active'); ";

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

		$result = qedb($query);
		if ($GLOBALS['DEBUG']) {
			$order_number = 999999;
		} else {
			$order_number = qid();
		}

		return ($order_number);
	}
?>
