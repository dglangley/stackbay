<?php
	function shipOrder($taskid, $T) {
		$so_number = 0;
		// This function finds based on $T if the sales order has been generated
		$query = "SELECT * FROM sales_items WHERE (ref_1 = ".res($taskid)." AND ref_1_label=".fres($T['item_label']).") OR (ref_2 = ".res($taskid)." AND ref_2_label=".fres($T['item_label']).");";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);

			$so_number = $r['so_number'];
		}

		return $so_number;
	}