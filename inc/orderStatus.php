<?php
	$rootdir = $_SERVER['ROOT_DIR'];
    include_once $rootdir.'/inc/dbconnect.php';
    include_once $rootdir.'/inc/form_handle.php';

	function orderStatus($type,$line_item) {
		$field = '';
		$invid = 0;
		$qty = 0;
		$completed_qty = 0;
		//Determine what type of line_item_id this is
		if($type == 'Sale' || strtolower($type) == 'sales' || strtolower($type) == 'so') {
			$field = 'sales_item_id';

			//Get the amount of items expected for this item
			$query = "SELECT qty FROM sales_items WHERE id = ".prep($line_item).";";
			$result = qdb($query) OR die(qe());
			
			if (mysqli_num_rows($result)>0) {
				$result = mysqli_fetch_assoc($result);
				$qty = $result['qty'];
			}
		} else if($type == 'Purchase' || strtolower($type) == 'purchases' || strtolower($type) == 'po') {
			$field = 'purchase_item_id';

			//Get the amount of items expected for this item
			$query = "SELECT qty FROM purchase_items WHERE id = ".prep($line_item).";";
			$result = qdb($query) OR die(qe());
			
			if (mysqli_num_rows($result)>0) {
				$result = mysqli_fetch_assoc($result);
				$qty = $result['qty'];
			}
		}

		//Get the inventory id assoc with the line item
		$query = "SELECT id FROM inventory WHERE ".$field." = ".prep($line_item).";";
		$result = qdb($query) OR die(qe());
			
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$invid = $result['id'];

			//Query from the inventory history table to see how many of the product was received
			$query = "SELECT COUNT(*) as qty FROM inventory_history WHERE field_changed = ".prep($field)." AND invid = ".prep($invid).";";
			$result = qdb($query) OR die(qe());
			
			if (mysqli_num_rows($result)>0) {
				$result = mysqli_fetch_assoc($result);
				$completed_qty = $result['qty'];
			}

			return ($qty <= $completed_qty ? 'Completed Item' : 'Incomplete Item');
		}
		return 'Failed';
	}
?>
