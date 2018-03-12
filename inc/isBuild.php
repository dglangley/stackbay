<?php
	function isBuild($id,$id_label='id') {
		if ($id_label=='repair_item_id') { $id_label = 'id'; }

		$BUILD = false;
		$query = "SELECT b.id FROM builds b, repair_items ri ";
		$query .= "WHERE ri.".$id_label." = '".res($id)."' ";
		$query .= "AND ri.ro_number = b.ro_number; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$r = qrow($result);
			$BUILD = $r['id'];
		}

		return ($BUILD);
	}
?>
