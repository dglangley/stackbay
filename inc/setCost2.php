<?php
	function setCost($inventoryid=0) {
		if (! $inventoryid) { return false; }

		// get all purchase records in case we've purchased it multiple times
		$query = "SELECT * FROM inventory_history WHERE invid = '".res($inventoryid)."' ";
		$query .= "AND field_changed = 'purchase_item_id' AND value IS NOT NULL; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// get purchase-related freight costs here
		}

		// discount rtv's here

		// get rma-related freight costs here

		// get repair costs here
	}
?>
