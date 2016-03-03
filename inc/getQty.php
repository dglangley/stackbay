<?php
	function getQty($partid=0) {
		$qty = 0;
		if (! $partid OR ! is_numeric($partid)) { return ($qty); }

		$query = "SELECT qty FROM qtys WHERE partid = '".res($partid)."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { return ($qty); }
		$r = mysqli_fetch_assoc($result);
		$qty = $r['qty'];

		return ($qty);
	}
?>
