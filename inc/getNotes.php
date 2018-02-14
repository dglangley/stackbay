<?php
	function getNotes($ref_1,$ref_1_label='partid') {
		$notes = array();

		$query = "SELECT * FROM messages, prices ";
		$query .= "WHERE ref_1 = '".res($ref_1)."' AND ref_1_label = '".res($ref_1_label)."' AND messages.id = prices.messageid; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$notes[] = $r;
		}

		return ($notes);
	}
?>
