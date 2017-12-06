<?php
	//function getExpenses($jobid) {
	function getExpenses($item_id,$item_label='service_item_id') {
		$expenses = array();

		$query = "SELECT * FROM expenses e ";
		$query .= "LEFT JOIN expense_categories c ON e.categoryid = c.id ";
		$query .= "WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($item_label)."'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if ($r['category']=='Mileage') { $r['type'] = '<i class="fa fa-car"></i>'; }
			else { $r['type'] = '<i class="fa fa-money"></i>'; }

			$expenses[] = $r;
		}

		return ($expenses);
	}
?>
