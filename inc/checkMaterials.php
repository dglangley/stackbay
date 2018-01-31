<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$ERROR = '';

	// This function takes in the item_id and order type and checks if the materials on the job is:
	// 1. If ordered make sure it has been followed thru
	// 2. the Pulled Qty reflects in the Requested Qty

	// Return values
	// true means no issues
	// false means something went wrong and that either the PO is not received or the qty has not yet been pulled

	function checkMaterials($taskid, $task_label) {
		global $ERROR;
		$T = order_type($order_type);

		$materials_table = 'repair_components';
		$inventory_column = 'invid';
		$item_label = 'item_id';

		if($task_label == 'service_item_id') {
			$materials_table = 'service_materials';
			$inventory_column = 'inventoryid';
			$item_label = $task_label;
		}


		$shortage = false;

        // this function checks two factors for verification:
        // 1) Have all purchased goods, with this taskid/task_label, been received?
        // 2) Have all requested goods, with this taskid/task_label, been pulled?

        $query = "SELECT * FROM purchase_items WHERE (ref_1 = '".$taskid."' AND ref_1_label = '".$task_label."') ";
        $result = qedb($query);
        while ($r = qrow($result)) {
            $qty = $r['qty'];

            $stk_qty = getQty(0,$r['id'],'purchase_item_id');

            if ($stk_qty<$qty) { $shortage = true; }
        }

        if($shortage) {
        	$ERROR = "Purchase items have not been received in full.";
        	return false;
        }

		// Check if everything has been requested has been pulled to the order
		// This query gets the overall sum qty requested for the partid
		$query = "SELECT SUM(qty) requested, partid FROM purchase_requests WHERE item_id_label = ".fres($task_label)." AND item_id = ".fres($taskid)." AND (status IS NULL OR status <> 'Void') GROUP BY partid;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$qty_pulled = 0;

			$query2 = "SELECT m.qty pulled FROM $materials_table m, inventory i WHERE m.$item_label = ".fres($taskid)." AND m.$inventory_column = i.id AND partid = ".fres($r['partid']).";";
			$result2 = qedb($query2);

			while($r2 = mysqli_fetch_assoc($result2)) {
				// This is the cumulative amount pulled for a specific partid
				$qty_pulled += $r2['pulled'];
			}

			if($r['requested'] > $qty_pulled) {
				$ERROR = "Materials have not been fulfilled for this order.";
				return false;
			}
		}

		return true;
	}

