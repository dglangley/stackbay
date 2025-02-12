<?php
	if (! isset($DEBUG)) { $DEBUG = 0; }

	function setItem($type='Repair',$order_number,$partid,$ln=1,$qty=1,$price=false,$due_date='',$inventoryid=0) {
		$userid = $GLOBALS['U']['id'];

		switch ($type) {
			case 'Repair':
				$query = "REPLACE repair_items (partid, ro_number, line_number, qty, price, due_date, invid) ";
				$query .= "VALUES ('".res($partid)."','".res($order_number)."','".res($ln)."','".res($qty)."',";
				if ($price) { $query .= "'".res($price)."',"; } else { $query .= "NULL,"; }
				if ($due_date) { $query .= "'".res($due_date)."',"; } else { $query .= "NULL,"; }
				if ($inventoryid) { $query .= "'".res($inventoryid)."'"; } else { $query .= "NULL"; }
				$query .= "); ";

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
			$item_id = 999999;
		} else {
			$item_id = qid();
		}

		return ($item_id);
	}
?>
