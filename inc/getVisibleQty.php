<?php
	function getVisibleQty($partid) {
		$qty = '';
		if (! $partid) { return ($qty); }

		$query = "SELECT visible_qty FROM qtys WHERE partid = '".res($partid)."'; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$r = qrow($result);
			$qty = $r['visible_qty'];
		}

		return ($qty);
	}
?>
