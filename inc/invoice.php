<?php
	
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/order_parameters.php';
    include_once $rootdir.'/inc/packages.php';
    include_once $rootdir.'/inc/setCommission.php';
	include_once $rootdir.'/inc/renderOrder.php';
	include_once $rootdir.'/inc/setInvoiceCOGS.php';
	include_once $rootdir.'/inc/sendInvoice.php';
    include_once $rootdir.'/dompdf/autoload.inc.php';

    function create_invoice($order_number, $shipment_datetime){
		//Variable Declarations
		$warranty = '';
		$macro = '';
		$ro_number = '';
		$invoice_id = '';
		$invoice_item_id = '';
		$package_order_number = $order_number;
		$type = 'Sale';
		$return = array(
			"invoice_no" => 0,
			"invoice" => 0,
			"error" => ''
		);
		//Repairs_check
		$query = "
		SELECT ro_number, ri.partid, datetime, ri.qty, ri.price, ri.line_number, ri.ref_1, 
			ri.ref_1_label, ri.ref_2, ri.ref_2_label, ri.warrantyid as warr, ri.id as item_id, packages.id as packid 
			FROM sales_items si, packages, repair_items ri 
			WHERE si.ref_1_label = 'repair_item_id' 
			AND order_number = so_number 
			AND order_number = ".prep($order_number)." AND order_type = 'Sale'
			AND ri.id = si.ref_1 
			AND ri.price > 0
			GROUP BY packages.id, ri.id;
		";
		$results = qedb($query);
		if(mysqli_num_rows($results) > 0 ){
			$type = 'Repair';
			$meta = mysqli_fetch_assoc($results);
			$order_number = $meta['ro_number'];
		} else {
			$query = "
				Select i.partid, packages.datetime, SUM(i.qty) qty, price, line_number, ref_1, ref_1_label, ref_2, ref_2_label, warranty as warr, it.id item_id, packages.id packid
				FROM packages, package_contents, sales_items it, inventory i 
				WHERE package_contents.packageid = packages.id
				AND package_contents.serialid = i.id
				AND `packages`.order_number = ".prep($order_number)."
				AND `packages`.order_type = '".$type."'
				AND i.sales_item_id = it.id
				AND it.so_number = `packages`.`order_number`
				AND price > 0.00
				GROUP BY package_contents.packageid, it.id;
			";
			//GROUP BY it.id;
			$results = qedb($query);
		}

		$o = o_params($type);

		//Eventually Shipment Datetime will be a shipment ID whenever we make that table

		// did we already invoice this shipment?
		$query2 = "SELECT * FROM invoices ";
		$query2 .= "WHERE order_number = '".res($order_number)."' AND order_type = '".res($o['type'])."' AND shipmentid = '".res($shipment_datetime)."'; ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)>0) {
			$return['error'] = 'Shipment has already been invoiced';
			return $return;
		}

		// shipping a non-invoiceable order, see query above
		if (mysqli_num_rows($results) == 0){
			return $return;
		}

		$sales_charge_holder = array();
		if ($type == 'Sale'){
			//Add in sales_charges rows into the invoice item
			$sales_charges = "SELECT * FROM sales_charges WHERE so_number = ".prep($order_number).";";
			$sales_result = qedb($sales_charges);

			while ($row = mysqli_fetch_assoc($sales_result)) {
				$sales_charge_holder[] = $row;
			}

		}
		$one = false;
		foreach ($results as $row) {
			if(format_date($row['datetime'],"Y-m-d H:i:s") == format_date($shipment_datetime, "Y-m-d H:i:s")){
				$one = true;
			}
		}
		if(!$one AND ! $GLOBALS['DEBUG']){
			$return['error'] = "No shipments on this date";
			return $return;
		}
		//Create an array for all the sales credit data
		$macro = "
			SELECT `companyid`, `created`, `days`, `type`
			FROM ".$o['order'].", terms
			WHERE ".$o['order'].".".$o['id']." = ".prep($order_number)." AND
			".$o['order'].".termsid = terms.id;
		";

		$invoice_macro = qedb($macro);
		$macro_row = mysqli_fetch_assoc($invoice_macro);
		
		$status = 'Completed';
		$freight = shipment_freight($package_order_number, "Sale", $shipment_datetime);

		$query = "
			INSERT INTO `invoices`( `companyid`, `order_number`, `order_type`, `date_invoiced`, `shipmentid`, `freight`, `public_notes`, `status`) 
			VALUES ( ".prep($macro_row['companyid']).", ".prep($order_number).", ".prep($o['type']).", ".prep($GLOBALS['now']).", ".prep($shipment_datetime)." , ".fres($freight).", NULL, '$status');
		";
		$result = qedb($query);
		$invoice_id =  qid();
		if ($GLOBALS['DEBUG']==1 OR $GLOBALS['DEBUG']==3) { $invoice_id = 999999; }
		$return['invoice_no'] = $invoice_id;
		$return['invoice'] = $invoice_id;

		// Select packages.id, serialid, sales_items.partid, price
		foreach ($results as $row) {
			if(format_date($row['datetime'],"Y-m-d H:i:s") != format_date($shipment_datetime, "Y-m-d H:i:s") AND ! $GLOBALS['DEBUG']){
				continue;	
			}
			$insert = "
				INSERT INTO `invoice_items`(`invoice_no`, `item_id`, `item_label`, `qty`, `amount`, `line_number`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`, `warranty`) 
				VALUES (".$invoice_id.", ".prep($row['partid']).", 'partid', ".prep($row['qty']).", ".prep($row['price']).", ".prep($row['line_number']).", 
				".prep($row['ref_1']).", ".prep($row['ref_1_label']).", ".prep($row['ref_2']).", ".prep($row['ref_2_label']).", ".prep($row['warr']).");";
			
			qedb($insert);
			$invoice_item_id = qid();
			if ($GLOBALS['DEBUG']==1 OR $GLOBALS['DEBUG']==3) { $invoice_item_id = 999999; }

			$package_insert = "INSERT INTO `invoice_shipments` (`invoice_item_id`, `packageid`) values ($invoice_item_id,".prep($row['packid']).");";
			qedb($package_insert);
			if($invoice_id && $invoice_item_id){
				setCommission($invoice_id,$invoice_item_id);
			}
		}/* end foreach */

		//Prevent breaks in the foreach loop
		if($sales_charge_holder) {
			foreach ($sales_charge_holder as $sch) {
				//first check if the sales_charge_id (unique to ref 1 exists) and labeled to sales_charge_id
				$query = "SELECT * FROM `invoice_items` WHERE ref_1 = ".prep($sch['id'])." AND ref_1_label = 'sales_charge_id';";
				$result = qedb($query);

				//If record does no exists at all
				if (mysqli_num_rows($result) == 0) {
					$insert = "INSERT INTO `invoice_items`(`invoice_no`, `item_id`,`item_label`, `memo`, `qty`, `amount`, `ref_1`, `ref_1_label`) 
					VALUES (".$invoice_id.", NULL, NULL, ".prep($sch['memo']).", ".$sch['qty'].", ".prep($sch['price']).",".prep($sch['id']).", 'sales_charge_id');
					";
					qedb($insert);
				}
			}/* end foreach */
		}
		if($invoice_id){
			if ($GLOBALS['DEBUG'] AND $invoice_id==999999) { $invoice_id = 18560; }
			setInvoiceCOGS($invoice_id,$type);

			if (! $GLOBALS['DEBUG']) {
				$send_err = sendInvoice($invoice_id);
				if ($send_err) {
					$return['error'] = $send_err;
				}
			}
		}

		return $return;
	}
    
    function get_assoc_invoices($order_number, $type ="Sale"){
        $select = "
        SELECT * FROM `invoices` 
        WHERE `order_number` = ".prep($order_number)." 
        AND `order_type` = ".prep($type)."
		AND status <> 'Void'
        ;";
        $results = qedb($select);
        if (mysqli_num_rows($results) > 0){
            return $results;
        } else {
            return null;
        }
    }

	function getInvoice($invoice_no, $field = ''){
		$result = qedb("Select * FROM `invoices` WHERE invoice_no = '".res($invoice_no)."';");
		$row = mysqli_fetch_assoc($result);
		if ($field){
			return $row[$field];
		} else {
			return $row;
		}
	}
	
	function getInvoicedInventory($in_no,$selector = '*'){
	    $in_no = prep($in_no);
	    $select = "
			SELECT $selector
			FROM  `invoice_items` ,  `invoice_shipments` ,  `package_contents` , inventory
			WHERE  `invoice_no` = $in_no
			AND  `invoice_items`.`id` =  `invoice_shipments`.invoice_item_id
			AND  `invoice_shipments`.`packageid` =  `package_contents`.`packageid` 
			AND  `inventory`.`partid` = `invoice_items`.`item_id` AND (`invoice_items`.`item_label` = 'partid' OR `invoice_items`.`item_label` IS NULL)
			AND  `package_contents`.`serialid` = `inventory`.`id` 
			AND ((task_label = 'repair_item_id' AND inventory.repair_item_id = taskid) OR (task_label = 'sales_item_id' AND inventory.sales_item_id = taskid))
	    ;";
	    $result = qedb($select);
	    return $result;
	}
	


?>


