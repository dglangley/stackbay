<?php
	function getTravelRate($taskid=0,$task_label='') {
		// default
		$user_rate = '11.00';

		if ($task_label == 'service_item_id') {
			$query = "SELECT c.travel_rate FROM service_classes c, service_items i, service_orders o ";
			$query .= "WHERE i.id = '".res($taskid)."' AND i.so_number = o.so_number AND o.classid = c.id AND c.travel_rate > 0; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);
				$user_rate = $r['travel_rate'];
			}
		}

		return ($user_rate);
	}
?>
