<?php
	function getMaterialsBOM($taskid,$task_label='service_item_id') {
		$bom = array('materials'=>array(),'charge'=>0);

		$query = "SELECT * FROM service_bom WHERE item_id = '".res($taskid)."' AND item_id_label = '".res($task_label)."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$bom['materials'][] = $r;
			$bom['charge'] += $r['charge'];
		}

		return ($bom);
	}
?>
