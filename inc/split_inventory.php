<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

    function split_inventory($invid, $qty){
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
            // If the qty is a partial pull of the current qty for the inventory id then split the 2 apart
            $query = "UPDATE inventory SET qty = ".res($qty).", status ='manifest' WHERE id = ".res($invid).";";
            qdb($query) OR die(qe() . ' ' . $query);

            $query = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, bin, purchase_item_id, sales_item_id, returns_item_id, repair_item_id, userid, date_created, notes) 
                    SELECT serial_no, '".res($parsedQty)."' as qty, partid, conditionid, 'shelved' as status, locationid, bin, purchase_item_id, sales_item_id, returns_item_id, repair_item_id, userid, date_created,  'SPLIT: ".res($invid)."' as notes FROM inventory WHERE id = ".res($invid).";";
            qdb($query) OR die(qe() . ' ' . $query);

            $new_invid = qid();
        } else if($parsedQty == 0) {
            // If inventory is pulled in full then just update the status
            $query = "UPDATE inventory SET status ='manifest' WHERE id = ".res($invid).";";
            qdb($query) OR die(qe() . ' ' . $query);
        }

        return $new_invid;
    }
