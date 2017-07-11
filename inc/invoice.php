<?php
	
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/order_parameters.php';
    include_once $rootdir.'/inc/packages.php';
    include_once $rootdir.'/inc/setCommission.php';

	if (! isset($debug)) { $debug = 0; }

    function create_invoice($order_number, $shipment_datetime, $type ='Sale'){
		//Variable Declarations
		$warranty = '';
		$macro = '';
		$already_invoiced = false;
		$ro_number = '';
		$o = o_params($type);
		//Function to be run to create an invoice
		//Eventually Shipment Datetime will be a shipment ID whenever we make that table
		$already_invoiced = rsrq("SELECT `invoice_no` FROM `invoices` where order_number = '$order_number' AND order_type = ".prep($o['type'])." AND `shipmentid` = ".prep($shipment_datetime).";");
		if($already_invoiced){return $already_invoiced;}
		//Check to see there are actually invoice-able items on the order
		//Assuming a closed package would make the 
		$warranty = ($o['sales'] ? "warranty" : "warrantyid");
		$invoice_item_select = "
			Select i.partid, datetime, count(DISTINCT(serialid)) qty, price, line_number, ref_1, ref_1_label, ref_2, ref_2_label, $warranty as warr, it.id item_id, packages.id packid
			FROM packages, package_contents, ".$o['item']." it, inventory i 
			WHERE package_contents.packageid = packages.id
			AND package_contents.serialid = i.id
			AND `packages`.order_number = $order_number
			AND `packages`.order_type = '".$o['ptype']."'
			AND i.".$o['inv_item_id']." = it.id
			AND it.`".$o['id']."` = `packages`.`order_number`
			AND packages.datetime = ".prep(format_date($shipment_datetime, "Y-m-d H:i:s"))."
			AND price > 0.00
			GROUP BY it.id;
		";
		
		//exit($invoice_item_select);
		//Type field accepts ['Sale','Repair' ]
		$invoice_item_prepped = qdb($invoice_item_select) or die(qe()." | $invoice_item_prepped");
		if (mysqli_num_rows($invoice_item_prepped) == 0){
			return "Nothing found in invoice_item_select: ".$invoice_item_select;
		}
		$sales_charge_holder = array();
		if ($type == 'Sale'){
			//Add in sales_charges rows into the invoice item
			$sales_charges = "SELECT * FROM sales_charges WHERE so_number = ".prep($order_number).";";
			$sales_result = qdb($sales_charges) OR die(qe());

			while ($row = $sales_result->fetch_assoc()) {
				$sales_charge_holder[] = $row;
			}

		}

		//Create an array for all the sales credit data
		$macro = "
			SELECT `companyid`, `created`, `days`, `type`
			FROM ".$o['order'].", terms
			WHERE ".$o['order'].".".$o['id']." = ".prep($order_number)." AND
			".$o['order'].".termsid = terms.id;
		";
		
		if ($GLOBALS['debug']) { echo $macro.'<BR>'; }
		$invoice_macro = mysqli_fetch_assoc(qdb($macro) or die(qe()." $macro"));
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
			VALUES ( ".prep($invoice_macro['companyid']).", ".prep($order_number).", ".prep($o['type']).", ".prep($GLOBALS['now']).", ".prep($shipment_datetime)." , $freight , '$status');
		";
		if ($GLOBALS['debug']) { echo $invoice_creation.'<BR>'; }
		else { $result = qdb($invoice_creation) OR die(qe().": ".$invoice_creation); }
		$invoice_id =  qid();

		// Select packages.id, serialid, sales_items.partid, price
		foreach ($invoice_item_prepped as $row) {
			$insert = "INSERT INTO `invoice_items`(`invoice_no`, `partid`, `qty`, `amount`, `line_number`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`, `warranty`) 
				VALUES (".$invoice_id.", ".prep($row['partid']).", ".prep($row['qty']).", ".prep($row['price']).", ".prep($row['line_number']).", 
				".prep($row['ref_1']).", ".prep($row['ref_1_label']).", ".prep($row['ref_2']).", ".prep($row['ref_2_label']).", ".prep($row['warr']).");";
			
			if ($GLOBALS['debug']) { echo $insert.'<BR>'; }
			else { qdb($insert) or die(qe()." ".$insert); }
			$invoice_item_id = qid();

			$package_insert = "INSERT INTO `invoice_shipments` (`invoice_item_id`, `packageid`) values ($invoice_item_id,".prep($row['packid']).");";
			if ($GLOBALS['debug']) { echo $package_insert.'<BR>'; }
			else { qdb($package_insert) or die(qe()." ".$package_insert); }

			setCommission($invoice_id,$invoice_item_id);
		}/* end foreach */

		//Prevent breaks in the foreach loop
		if($sales_charge_holder) {
			foreach ($sales_charge_holder as $sch) {
				//first check if the sales_charge_id (unique to ref 1 exists) and labeled to sales_charge_id
				$query = "SELECT * FROM `invoice_items` WHERE ref_1 = ".prep($sch['id'])." AND ref_1_label = 'sales_charge_id';";
				$result = qdb($query) OR die(qe().": ".$query);

				//If record does no exists at all
				if (mysqli_num_rows($result) == 0) {
					$insert = "INSERT INTO `invoice_items`(`invoice_no`, `partid`,`memo`, `qty`, `amount`, `ref_1`, `ref_1_label`) 
					VALUES (".$invoice_id.", NULL,".prep($sch['memo']).", ".$sch['qty'].", ".prep($sch['price']).",".prep($sch['id']).", 'sales_charge_id');
					";
					if ($GLOBALS['debug']) { echo $insert.'<BR>'; }
					else { qdb($insert) or die(qe()." ".$insert); }
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
        $results = qdb($select) or die(qe()." $select");
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
	
	function getInvoicedInventory($in_no,$selector = '*'){
	    $in_no = prep($in_no);
	    $select = "
			SELECT $selector
			FROM  `invoice_items` ,  `invoice_shipments` ,  `package_contents` , inventory
			WHERE  `invoice_no` = $in_no
			AND  `invoice_items`.`id` =  `invoice_shipments`.invoice_item_id
			AND  `invoice_shipments`.`packageid` =  `package_contents`.`packageid` 
			AND  `inventory`.`partid` = `invoice_items`.`partid`
			AND  `package_contents`.`serialid` = `inventory`.`id` 
	    ;";
	    $result = qdb($select) or die(qe());
	    return $result;
	}
?>

