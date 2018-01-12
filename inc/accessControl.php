<?php
	function accessControl($userid, $item_id, $label){
		global $quote;
		// Guilty until proven innocent
		$access = false;

		if(! $quote) {
			$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($label)." AND userid = ".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);


			if(mysqli_num_rows($result)) {
				$access = true;
			}
		}

		return $access;
	}
?>
