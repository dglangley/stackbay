<?php

	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/order_parameters.php';
    include_once $rootdir.'/inc/getCompany.php';
    include_once $rootdir."/inc/setCostsLog.php";
//These are all the records not associated with any repair: we just have these in stock. 

//Steps for import:
//TABLES TOUCHED: `inventory`, `inventory_history`, `repair_components`, `purchase_requests`, 



$select ="
SELECT cs.component_id, cs.location_id as loc, cs.quantity as qty, co.date
	FROM inventory_componentstock cs
		LEFT JOIN inventory_componentorder co ON cs.order_id = co.id;";
$results = qdb($select,"PIPE") or die(qe("PIPE"));
echo($select."<br><BR>");

//Check the componentorder table for alll the historical order records
	$prq_select = "
		SELECT co.`id` orderid, co.`component_id`, `price`, `received`,`billed`, `supplier_id`, `cpo_id`, co.`repair_id`, co.project_id, co.`shipping_method_id`, 
		co.`freight_instructions`, co.`date`, `freight_cost`, `due_date`, `co`.`quantity`
		FROM inventory_componentorder co
		WHERE (co.repair_id is not null or co.project_id is not null) 
		AND component_project_id is null
		AND co.`id` not in (8113,8114,8115);
		";
	$prq = qdb($prq_select, "PIPE") or die(qe("PIPE")." | $prq_select");
	echo($prq_select."<br><BR>");

//These are all the results where there have been components ordered for a particular repair; All of these have related purchase orders and sales_orders.
foreach($prq as $r){
	$delivery_date = "";
	$warrantyid = "";
	$termsid = "";
	$rep_id = "";
	$ro_number = $r['repair_id'];
	if(!$ro_number){
		$bo_check = "SELECT ro_number FROM `builds` WHERE `id` = ".prep($r['project_id']).";";
		$co_result  = qdb($bo_check) or die(qe()." $bo_check");
		$co_array = mysqli_fetch_assoc($co_result);
		$ro_number = $co_array['ro_number'];

	}
	//Component_order_mapping
	$partid = translateComponent($r['component_id']);
	$companyid = dbTranslate($r['supplier_id']);
	$freight_carrier_id = $CARRIER_MAPS[$r['shipping_method_id']];
	$freight_service_id = $SERVICE_MAPS[$r['shipping_method_id']];
	$freight_account = "NULL"; //For now this is null, he has a bunch of information in his freight instructions
	$public = prep($r['freight_instructions']);
	$quantity = "";
	if($r['quantity'] > 0){
		$quantity = $r['quantity'];
	} else {
		$quantity = $r['billed'];
	}


	if($r['cpo_id']){
		$po_number = $r['cpo_id'];
		$cpo_select = "SELECT `delivery_date`, `warranty_id`, `terms_id`, `rep_id` FROM inventory_componentpurchaseorder where id = ".$r['cpo_id'].";";
		$cpo_result = qdb($cpo_select,"PIPE") or die(qe("PIPE")." | $cpo_select");
		$cpo_result = mysqli_fetch_assoc($cpo_result);
		$warrantyid = $WARRANTY_MAPS[$cpo_result['warranty_id']];
		$delivery_date = $cpo_result['delivery_date'];
		$termsid = $TERMS_MAPS[$cpo_result['terms_id']];
		$rep_id = mapUser($cpo_result['rep_id']);
		$po_insert = "
		REPLACE `purchase_orders`(`po_number`,`created`, `created_by`, `sales_rep_id`, `companyid`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `termsid`, `private_notes`, `public_notes`, `status`) 
		VALUES ($po_number, ".prep($r['date']).", ".prep($rep_id).", ".prep($rep_id).", ".prep($companyid).", 1, '$freight_carrier_id', '$freight_service_id', NULL, ".prep($termsid).", 'Component History Import',$public, 'Active');";
		qdb($po_insert) or die(qe()." | $po_insert");
		echo($po_insert."<br>");
		
		$pi_insert = "
		INSERT INTO `purchase_items`(`partid`, `po_number`, `line_number`, `qty`, `qty_received`, `price`, `receive_date`, `warranty`, `conditionid`) VALUES	
		(".prep($partid).", $po_number, NULL, $quantity, ".prep($r['received'],0).", ".prep($r['price']).", ".prep($delivery_date).", ".prep($warrantyid).", 1);";
		
		qdb($pi_insert) or die(qe()." $pi_insert");
		echo($pi_insert."<br>");
		$pline = qid(); 
			
		$inv_insert = "	INSERT INTO `inventory`(`qty`, `partid`, `conditionid`, `status`, `userid`, `date_created`,`notes`, `purchase_item_id`) 
			VALUES (".prep($quantity).", ".prep($partid).", 5, 'manifest', 16, ".prep($r['date']."12:00:00").", 'IMPORTED ON COMPONENTS IMPORT',".$pline.");"; 
		qdb($inv_insert) or die(qe()." | $inv_insert");
		echo($inv_insert."<br>");
		$invid = qid();
		
		$amount = $r['price'] * $quantity;
		$cost = "INSERT INTO `inventory_costs`(`inventoryid`, `datetime`, `actual`, `average`, `notes`) 
		VALUES ($invid, NOW(), $amount, $amount, 'IMPORTED ON COMPONENTS IMPORT')";
		qdb($cost);
		setCostsLog($invid,$pline,"purchase_item_id",$amount);
		
		if($ro_number){
			$fill = "";
			$search = "SELECT * FROM inventory_componentrepair cr where cr.repair_id = $ro_number AND cr.component_id = ".$r['component_id']." AND order_id = ".$r['orderid'].";";
			$filled = qdb($search, "PIPE") or die(qe("PIPE")." $search");
			if(mysqli_num_rows($filled)){
				$a_filled = mysqli_fetch_assoc($filled);
				$fill = $a_filled['filled'];
			};
			// $inv_qty = $r['received'] - $r['filled'];
			$rc_insert = "INSERT INTO `repair_components`(`invid`, `ro_number`, `qty`) VALUES (".prep($invid).",".prep($ro_number).",".prep($fill, "'0'").");";
			qdb($rc_insert) or die(qe()." $rc_insert");
			echo($rc_insert."<br><br>");
		}
	    
		
	}

// $cs_query = "SELECT * FROM inventory_componentshipping WHERE cpo_id is not null;";
// qdb($cs_query)
// 	    $package_insert = "
// 	    INSERT INTO `packages`( `order_type`, `order_number`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
// 	    VALUES ('Purchase', $po_number, 1, NULL, ".prep($r['date']).", ".prep($r['freight_cost']).");";
// 	    qdb($package_insert) or die(qe()." | $package_insert");
// 	    echo("$package_insert '\$package_insert'<br>");
// 	    $packageid = qid();
	
// 	//Package_contents (adding the received package info) 
// 	    $pc_insert = "INSERT INTO package_contents (`serialid`, `packageid`) values($invid, $packageid);";
// 	    qdb($pc_insert) or die(qe()." | $pc_insert");
// 	    echo("$pc_insert '\$pc_insert'<br>");
	

	
	$pr_insert = "
	INSERT INTO `purchase_requests`(`techid`, `ro_number`, `repid`, `requested`, `po_number`, `partid`, `qty`, `notes`) 
	VALUES (16, ".$ro_number.", ".prep($rep_id).", ".prep($r['date']).",".prep($po_number).", ".prep($partid).", $quantity, 'IMPORTED FROM OLD CPO TABLE');";
	qdb($pr_insert) or die(qe()." | $pr_insert");
	echo($pr_insert."<br>");
	
	
	
}


$component_stock = "
SELECT c.part_number, c.cost_per_unit, cs.* 
FROM inventory_componentstock cs, inventory_component c 
WHERE cs.component_id = c.id;
";

$results = qdb($component_stock) or die(qe()." $component_stock");

foreach($results as $r){
	$po_id = "";
	$partid = translateComponent($r['component_id']);
	$event_type ="imported cost";
	if($r['order_id']){
		$cpo_q = "SELECT `cpo_id` FROM `inventory_componentorder` co WHERE id = ".prep($r['order_id']).";";
		$co = qdb($cpo_q,"PIPE") or die(qe("PIPE"));
		$ca = mysqli_fetch_assoc($cpo_q);
		$po_id = $ca['cpo_id'];
		$lstring = "";
		$lresults = getLineItemIDs("purchase",$po_id,$partid);
		if(count($lresults)){
			$event_type = "purchase_item_id";
			$lstring = $lresults[0];
		}
	}
	
	$insert = "INSERT INTO `inventory`(`qty`, `partid`, `conditionid`, `status`, `locationid`,`bin`, `userid`, `date_created`, `notes`, `purchase_item_id`) 
	VALUES (".($r['quantity']).", ".prep($partid).", 5, shelved, ".prep($r['location_id']).", ".prep($r['subloc_id']).", 16, NOW(), 'IMPORTED ON COMPONENTS IMPORT',".prep($lstring).");";
	qdb($insert);
	$invid = prep(qid());
	
	$amount = $r['cost_per_unit']*$r['quantity'];
	$cost = "INSERT INTO `inventory_costs`(`inventoryid`, `datetime`, `actual`, `average`, `notes`) 
	VALUES ($invid, NOW(), $amount, $amount, 'IMPORTED ON COMPONENTS IMPORT')";
	qdb($cost);
	setCostsLog($invid,$lstring,$event_type,$amount);
}


//Non-repaired Stock
// $cps_select = "
// 	SELECT * FROM inventory_componentorder where repair_id is null;
// ";


?>