<?php
    $rootdir = $_SERVER['ROOT_DIR'];
    	
    include_once $rootdir.'/inc/dbconnect.php';
    include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/format_price.php';
    include_once $rootdir.'/inc/getCompany.php';
    include_once $rootdir.'/inc/getPart.php';
    include_once $rootdir.'/inc/keywords.php';
    include_once $rootdir.'/inc/getRecords.php';
    include_once $rootdir.'/inc/getRep.php';
    include_once $rootdir.'/inc/getContact.php';
    include_once $rootdir.'/inc/getFreight.php';
    include_once $rootdir.'/inc/getAddresses.php';
    include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/dropPop.php';
    include_once $rootdir.'/inc/packages.php';
	include_once $rootdir.'/inc/order_parameters.php';

    function createCredit($rma_number) {
		$scid = 0;

		$query = "SELECT *, i.id return_item_id FROM returns r, return_items i, dispositions d ";
		$query .= "WHERE r.rma_number = '".res($rma_number)."' AND r.rma_number = i.rma_number ";
		$query .= "AND i.dispositionid = d.id AND d.disposition = 'Credit'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }
		while ($r = mysqli_fetch_assoc($result)) {
			$order_number = $r['order_number'];
			$order_type = $r['order_type'];
			$qty = $r['qty'];
			$inventoryid = $r['inventoryid'];
			$companyid = $r['companyid'];
			$contactid = $r['contactid'];
			$return_item_id = $r['return_item_id'];

			switch ($order_type) {
				case 'Repair':
					$item_table = 'repair_items';
					$order_field = 'ro_number';
					$item_field = 'repair_item_id';
					break;

				case 'Sale':
				default:
					$item_table = 'sales_items';
					$order_field = 'so_number';
					$item_field = 'sales_item_id';
					break;
			}

			$query2 = "INSERT INTO credits (companyid, date_created, order_number, order_type, rma_number, repid, contactid) ";
			$query2 .= "VALUES ($companyid, '".$GLOBALS['now']."', '".$order_number."', '".$order_type."', '".$rma_number."', '".$GLOBALS['U']['id']."', ";
			if ($contactid) { $query2 .= "'".$contactid."'"; } else { $query2 .= "NULL"; }
			$query2 .= "); ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			$scid = qid();

			$query2 = "SELECT price, i.id FROM $item_table i, inventory_history h ";
			$query2 .= "WHERE i.$order_field = $order_number AND i.id = h.value AND h.field_changed = '".$item_field."' ";
			$query2 .= "AND h.invid = $inventoryid GROUP BY h.invid; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)==0) { return false; }
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$item_id = $r2['id'];
				$price = $r2['price'];

				$query3 = "INSERT INTO credit_items (cid, item_id, item_id_label, return_item_id, qty, amount) ";
				$query3 .= "VALUES ('".$scid."','".$item_id."',";
				if ($order_type=='Repair') { $query3 .= "'repair_item_id',"; } else { $query3 .= "'sales_item_id',"; } 
				$query3 .= "'".$return_item_id."','".$qty."','".$price."'); ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			}
		}

		return ($scid);
    }

    function qualifyCredit($rma_number){
        //get all units on rma for credit and check if they've been received
		$received = true;
		$credited = false;
		$query = "
            SELECT ri.id, inventoryid
            FROM return_items ri, dispositions
            WHERE rma_number = ".prep($rma_number)."
            AND dispositions.id = dispositionid
            AND disposition = 'Credit';
		";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }
		while ($r = mysqli_fetch_assoc($result)) {
			$rma_item_id = $r['id'];
			$inventoryid = $r['inventoryid'];

			$query2 = "SELECT * FROM inventory_history h WHERE h.value = $rma_item_id AND h.field_changed = 'returns_item_id' AND h.invid = $inventoryid; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)==0) {
				$received = false;
				break;
			}

			$query2 = "SELECT * FROM credit_items WHERE return_item_id = $rma_item_id; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$credited = true;
				break;
			}
		}

		if ($received===false OR $credited===true) {
			return false;
		}

		return true;
    }
    function get_assoc_credit($rma_number){
        $rma_number = prep($rma_number);
        $select = "SELECT * FROM credits WHERE rma_number = $rma_number;";
        return qdb($select);
    }
?>
