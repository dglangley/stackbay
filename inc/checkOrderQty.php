<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function checkOrderQty($item_id, $type = 'Purchase'){
		// Check failsafe to make sure qty is not over exceeded
		$query = '';
		if($type=="Purchase") {
			$query = "SELECT qty, qty_received as completed FROM purchase_items WHERE id = ".res($item_id).";";
		} else if($type=='Sale') {
			$query = "SELECT qty, qty_shipped as completed FROM sales_items WHERE id = ".res($item_id).";";
		}

		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);

			$remaining = $r['qty'] - $r['completed'];

			if($serial) {
				$remaining--;
			} else if($qty) {
				$remaining -= $qty;
			}

			if($remaining < 0) {
				echo 'ERROR: You are trying to over receive again!!! ' . $remaining; die();
			}
		}
	}