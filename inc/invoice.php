<?php
	
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/packages.php';
    
    function create_invoice($order_number, $shipment_datetime, $type = 'Sale'){
		//Function to be run to create an invoice
		//Eventually Shipment Datetime will be a shipment ID whenever we make that table

		//Check to see there are actually invoice-able items on the order
		$invoice_item_select = "
			Select partid, count(DISTINCT(serialid)) qty, price, line_number, ref_1, ref_1_label, ref_2, ref_2_label, warranty, sales_items.id sales_item
			FROM packages, package_contents, inventory_history, sales_items
			WHERE package_contents.packageid = packages.id
			AND package_contents.serialid = inventory_history.invid
			AND inventory_history.field_changed = 'sales_item_id'
			AND inventory_history.value = sales_items.id
			AND order_number = $order_number AND sales_items.so_number = order_number
			AND price > 0.00
			GROUP BY sales_items.id;
		";
		//Type field accepts ['Sale','Repair' ]
		$invoice_item_prepped = qdb($invoice_item_select);
		if (mysqli_num_rows($invoice_item_prepped) == 0){
			return null;
		}

		//Create an array for all the sales credit data
		$sales_charge_holder = array();

		if ($type == 'Sale'){
			$macro = "
				SELECT `companyid`, `created`, `days`, `type`
				FROM sales_orders, terms
				WHERE sales_orders.so_number = ".prep($order_number)." AND
				sales_orders.termsid = termsid;
			";

			//Add in sales_charges rows into the invoice item
			$sales_charges = "SELECT * FROM sales_charges WHERE so_number = ".prep($order_number).";";
			$sales_result = qdb($sales_charges) OR die(qe());

			while ($row = $sales_result->fetch_assoc()) {
				$sales_charge_holder[] = $row;
			}

		}else{
			echo "We haven't built repairs yet. Double check you don't mean 'Sale' "; exit;
		}
		$invoice_macro = mysqli_fetch_assoc(qdb($macro));
		if (strtolower($invoice_macro['type']) == 'prepaid'){
			// $pay_day = $GLOBALS['today'];
			$status = 'Paid';
			//THERE WILL NEED TO BE A CHECK HERE TO ENSURE THE PRODUCT WAS ACTUALLY PAID FOR [VERIFIED BY DAVID 3/20/17]
		} else {
			// $pay_day = format_date($invoice_macro['created'],"Y-m-d",array("d"=>$invoice_macro['days']));
			$status = 'Pending';
		}
		$freight = prep(shipment_freight($order_number, "Sales", $shipment_datetime));

		$invoice_creation = "
			INSERT INTO `invoices`( `companyid`, `order_number`, `order_type`, `date_invoiced`, `shipmentid`, `freight`, `status`) 
			VALUES ( ".$invoice_macro['companyid'].", ".prep($order_number).", ".prep($type).", ".prep($GLOBALS['now']).", ".prep($shipment_datetime)." , $freight , '$status');
		";
		$result = qdb($invoice_creation) OR die(qe().": ".$invoice_creation);
		$invoice_id =  qid();

		// Select packages.id, serialid, sales_items.partid, price
		foreach ($invoice_item_prepped as $row) {
			$insert = "INSERT INTO `invoice_items`(`invoice_no`, `partid`, `qty`, `amount`, `line_number`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`, `warranty`) 
				VALUES (".$invoice_id.
					", ".$row['partid'].
					", ".$row['qty'].
					", ".$row['price'].
					", ".prep($row['line_number']).
					", ".prep($row['ref_1']).
					", ".prep($row['ref_1_label']).
					", ".prep($row['ref_2']).
					", ".prep($row['ref_2_label']).
					", ".prep($row['warranty']).");
			";
			qdb($insert) or die(qe()." ".$insert);
			$line = qid();
			$package_insert = "
				INSERT INTO `invoice_shipments` (`invoice_item_id`, `packageid`)
					SELECT $line AS line, packages.id
					FROM packages, package_contents, inventory_history, sales_items
					WHERE package_contents.packageid = packages.id
					AND package_contents.serialid = inventory_history.invid
					AND inventory_history.field_changed = 'sales_item_id'
					AND inventory_history.value = sales_items.id
					AND order_number = $order_number
					/*AND order_type = $type*/
					AND sales_items.id = ".$row['sales_item']."
					Group By packageid;
			";
			qdb($package_insert) or die(qe()." ".$package_insert);
		}/* end foreach */

		//Prevent breaks in the foreach loop
		if($sales_charge_holder) {
			foreach ($sales_charge_holder as $row) {
				//first check if the sales_charge_id (unique to ref 1 exists) and labeled to sales_charge_id
				$query = "SELECT * FROM `invoice_items` WHERE ref_1 = ".prep($row['id'])." AND ref_1_label = 'sales_charge_id';";
				$result = qdb($query) OR die(qe().": ".$query);

				//If record does no exists at all
				if (mysqli_num_rows($result) == 0) {
					$insert = "INSERT INTO `invoice_items`(`invoice_no`, `partid`, `qty`, `amount`, `ref_1`, `ref_1_label`) 
						VALUES (".$invoice_id.
							", ".$row['partid'].
							", ".$row['qty'].
							", ".$row['price'].
							", ".prep($row['id']).
							", 'sales_charge_id');
					";
					qdb($insert) or die(qe()." ".$insert);
				}
			}/* end foreach */
		}

		return $invoice_id;
	}
    
    function get_assoc_invoices($order_number, $type ="Sale"){
        $select = "
        SELECT * FROM `invoices` 
        WHERE `order_number` = ".prep($order_number)." 
        AND `order_type` = ".prep($type)."
        ;";
        $results = qdb($select);
        if (mysqli_num_rows($results) > 0){
            return $results;
        } else {
            return null;
        }
    }

	function getInvoice($in_no, $field = ''){
		$result = qdb("Select * FROM `invoices` WHERE invoice_no = ".prep($invoice_number).";");
		$row = mysqli_fetch_assoc($result);
		if ($field){
			return $row['field'];
		} else {
			return $row;
		}
	}
	
	function getInvoicedInventory($in_no){
	    $in_no = prep($in_no,$selector = '*');
	    $select = "
	    SELECT $selector
	    FROM `invoice_items`, `invoice_shipments`, `package_contents`, inventory
	    WHERE `invoice_no` = $in_no
	    AND `invoice_items`.`id` = `invoice_shipments`.invoice_item_id
	    AND `invoice_shipments`.`packageid` = `package_contents`.`packageid`
	    AND `package_contents`.`serialid` = `inventory`.`id`
	    ;";
	    $result = qdb($select) or die(qe());
	    return $result;
	}
?>
