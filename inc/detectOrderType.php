<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function detectOrderType($order) {
		$possibles = array('Sale','Repair','Service','Purchase');
		$order_type = false;

		foreach ($possibles as $type) {
			$T = order_type($type);

			$query = "SELECT ".$T['order']." FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$order_type = $type;
				break;
			}
		}

		return ($order_type);
	}
?>
