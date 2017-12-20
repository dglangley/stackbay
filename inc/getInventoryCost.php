<?php
	function getInventoryCost($inventoryid) {

		// calculate inventory cost
		$cost = 0;
		$query3 = "SELECT actual FROM inventory_costs WHERE inventoryid = '".res($inventoryid)."'; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$cost = $r3['actual'];//*$row['pulled'];
		}
		return ($cost);

	}
?>
