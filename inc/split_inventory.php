<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';

    function split_inventory($invid, $qty, $flag = false){
        $now = $GLOBALS['now'];
        $new_invid = 0;

        $availQty = 0;

        $query = "SELECT * FROM inventory WHERE id = ".res($invid).";";
        $result = qdb($query) OR die(qe() . ' ' . $query);

        if (mysqli_num_rows($result)) {
            $result = mysqli_fetch_assoc($result);
            $availQty = $result['qty'];
        }

        $parsedQty = $availQty - $qty;

        // If this doesn't run then that means that the pulled item was fully pulled and does not need to have the extra components created
        if($parsedQty > 0) {
			$I = array('id'=>$invid,'qty'=>$qty);
			setInventory($I);

/*
            // If the qty is a partial pull of the current qty for the inventory id then split the 2 apart
            $query = "UPDATE inventory SET qty = ".res($qty).", status ='manifest' WHERE id = ".res($invid).";";
            qdb($query) OR die(qe() . ' ' . $query);
*/

			$I = getInventory($invid);
            unset($I['id']);
            // $I['serial_no'] = false;
			$I['qty'] = $parsedQty;
			$I['notes'] = 'SPLIT: '.$invid;
			$new_invid = setInventory($I);

            if($flag) {
                // Query the inventory cost manually of the original inventoryid
                $query = "SELECT actual, id FROM inventory_costs WHERE inventoryid = ".res($invid)." ORDER BY id DESC LIMIT 1;";
                $result = qedb($query);

                if(qnum($result)) {
                    $r = qrow($result);
                    // We are using the actual cost
                    $actual_cost = $r['actual'];
                    $old_record_id = $r['id'];

                    // QTY is the amount being pulled
                    // parsedQty is the amount being left in stock

                    // First get the per unit cost from the availQTY
                    $perUnit = $actual_cost / $availQty;

                    // Calculate the old inventory cost to be updated
                    $old_cost = $perUnit * $qty;

                    // Calculate also the new actual for the old record
                    $query = "UPDATE inventory_costs SET actual = ".fres($old_cost)." WHERE id = ".res($old_record_id).";";
                    qedb($query);

                    // Calculate the left in stock actual_amount
                    $new_cost = $perUnit * $parsedQty;

                    // Set the new cost of the new split out inventoryid that is left in stock
                    $query = "INSERT INTO inventory_costs (inventoryid, datetime, actual) VALUES (".res($new_invid).", ".fres($GLOBALS['now']).", ".fres($new_cost).");";
                    qedb($query);
                } 
            }
/*
            $query = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, bin, purchase_item_id, sales_item_id, returns_item_id, repair_item_id, userid, date_created, notes) 
                    SELECT serial_no, '".res($parsedQty)."' as qty, partid, conditionid, 'shelved' as status, locationid, bin, purchase_item_id, sales_item_id, returns_item_id, repair_item_id, userid, date_created,  'SPLIT: ".res($invid)."' as notes FROM inventory WHERE id = ".res($invid).";";
            qdb($query) OR die(qe() . ' ' . $query);

            $new_invid = qid();
*/
        } else if($parsedQty == 0) {
/*
            // If inventory is pulled in full then just update the status
            $query = "UPDATE inventory SET status ='manifest' WHERE id = ".res($invid).";";
            qdb($query) OR die(qe() . ' ' . $query);
*/
        }

        return $new_invid;
    }
