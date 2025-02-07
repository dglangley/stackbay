<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';

	function getItemOrder($taskid, $task_label='service_item_id', $include_class=false) {
		$class = '';

		if($task_label == 'repair_item_id' OR $task_label == 'Repair') {
			$query = "SELECT ro_number, line_number FROM repair_items WHERE id = ".res($taskid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);
				$data = 'RO '.$r['ro_number'] . ($r['line_number'] ? '-'.$r['line_number'] : '-1');
			}
		} else if($task_label == 'service_quote_item_id' OR $task_label == 'service_quote_items') {
			$query = "SELECT quoteid, line_number FROM service_quote_items WHERE id = ".res($taskid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);
				$data = $r['quoteid'] . ($r['line_number'] ? '-'.$r['line_number'] : '-1');
			}
		} else if($task_label == 'outsourced_item_id') {
			$query = "SELECT os_number, line_number FROM outsourced_items WHERE id = ".res($taskid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);
				$data = $r['os_number'] . ($r['line_number'] ? '-'.$r['line_number'] : '-1');
			}
		} else {//$task_label == 'Service'
			$query = "SELECT so_number, line_number, task_name FROM service_items WHERE id = ".res($taskid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);
				$data = $r['so_number'] . ($r['line_number'] ? '-'.$r['line_number'] : '-1');

				if ($include_class) {
					if ($r['task_name']) {
						$class = $r['task_name'];
					} else {
						$query2 = "SELECT classid FROM service_orders WHERE so_number = '".$r['so_number']."'; ";
						$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
						if (mysqli_num_rows($result2)>0) {
							$r2 = mysqli_fetch_assoc($result2);
							$class = getClass($r2['classid']);
						}
					}

					if ($class) { $data = $class.' '.$data; }
				}
			}
		}

		return ($data);
	}
?>
