<?php

	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/order_parameters.php';
    include_once $rootdir.'/inc/getCompany.php';
    
//These are all the records not associated with any repair: we just have these in stock. 

//Steps for import:
//TABLES TOUCHED: `inventory`, `inventory_history`, `repair_components`, `purchase_requests`, 

$select ="
SELECT cs.component_id, cs.location_id as loc, cs.quantity as qty, co.date
	FROM inventory_componentstock cs
		LEFT JOIN inventory_componentorder co ON cs.order_id = co.id;";
$results = qdb($select,"PIPE") or die(qe("PIPE"));
echo($select."<br><BR>");

qdb("DELETE FROM `purchase_orders` WHERE private_notes = 'Component History Import';");
qdb("DELETE FROM `purchase_items` WHERE line_number = 999;");
qdb("DELETE FROM `packages` WHERE package_no = 999;");
qdb("TRUNCATE `purchase_requests`;");
qdb("DELETE FROM `inventory` WHERE `notes` = 'IMPORTED ON THE COMPONENTS IMPORT';");
qdb("TRUNCATE repair_components;");

//Check the componentorder table first
	$prq_select = "
		SELECT co.`id` orderid, co.`component_id`, `price`, `received`,`cr`.`filled`,`billed`, `supplier_id`, `cpo_id`, co.`repair_id`, co.`shipping_method_id`, 
		co.`freight_instructions`, co.`date`, `freight_cost`, `due_date`, `delivery_date`, `warranty_id`, `terms_id`, `rep_id`, `ref_no`
		FROM inventory_componentorder co, inventory_componentrepair cr, inventory_componentpurchaseorder cpo
		WHERE cr.repair_id is not null
			AND  co.repair_id = cr.repair_id
			AND co.component_id = cr.component_id
			AND cr.order_id = co.id
			and co.cpo_id = cpo.id;";

	$prq = qdb($prq_select, "PIPE") or die(qe("PIPE")." | $prq_select");
	echo($prq_select."<br><BR>");

//These are all the results where there have been components ordered for a particular repair; All of these have related purchase orders and sales_orders.
foreach($prq as $r){
	$partid = translateComponent($r['component_id']);
	$po_number = findNearestGap("po", $r['date']);
	$companyid = dbTranslate($r['supplier_id']);
	$rep_id = mapUser($r['rep_id']);
	$termsid = $TERMS_MAPS[$r['terms_id']];
	$freight_carrier_id = $CARRIER_MAPS[$r['shipping_method_id']];
	$freight_service_id = $SERVICE_MAPS[$r['shipping_method_id']];
	$freight_account = "NULL"; //For now this is null, he has a bunch of information in his freight instructions
	$public = prep($r['freight_instructions']);
	$warrantyid = $WARRANTY_MAPS[$r['warranty_id']];
	
	$po_insert = "
	INSERT INTO `purchase_orders`(`po_number`,`created`, `created_by`, `sales_rep_id`, `companyid`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `termsid`, `private_notes`, `public_notes`, `status`) 
	VALUES ($po_number, ".prep($r['date']).", ".prep($rep_id).", ".prep($rep_id).", ".prep($companyid).",  1, '$freight_carrier_id', '$freight_service_id', NULL, '$termsid', 'Component History Import',$public, 'Active');";
	qdb($po_insert) or die(qe()." | $po_insert");
	echo($po_insert."<br>");
	$pi_insert = "
	INSERT INTO `purchase_items`(`partid`, `po_number`, `line_number`, `qty`, `qty_received`, `price`, `receive_date`, `warranty`, `conditionid`) VALUES	
	(".prep($partid).", $po_number, 999, ".prep($r['received'],0).", ".prep($r['received'],0).", ".prep($r['price']).", ".prep(format_date($r['delivery_date'],"Y-m-d")).", $warrantyid, 1);";
	
	qdb($pi_insert) or die(qe()." $pi_insert");
	echo($pi_insert."<br>");
	$pline = qid();
	
	$package_insert = "
		INSERT INTO `packages`(`order_number`, `order_type`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
		VALUES ('$po_number','Purchase',999,$public,".$r['delivery_date'].",".$r['freight_cost'].");
	";
	qdb($package_insert) or die(qe()." | $package_insert");
	echo($package_insert."<br>");
	
	$pr_insert = "
	INSERT INTO `purchase_requests`(`techid`, `ro_number`, `repid`, `requested`, `po_number`, `partid`, `qty`, `notes`) 
	VALUES (16, ".$r['repair_id'].", ".prep($rep_id).", ".$r['date'].",".$po_number.", ".prep($partid).", ".prep($r['received']).", 'IMPORTED FROM OLD CPO TABLE');";
	qdb($pr_insert) or die(qe()." | $pr_insert");
	echo($pr_insert."<br>");
	
	$inv_qty = $r['received'] - $r['filled'];
	$rline = getLineItemIDs("repair",$r['repair_id']);
	$rline = $rline[0];
	
	$inv_insert = "	INSERT INTO `inventory`(`qty`, `partid`, `conditionid`, `status`, `userid`, `date_created`,`notes`, `purchase_item_id`,`repair_item_id`) 
		VALUES (".prep($inv_qty, "'0'").", ".prep($partid).", 5, 'manifest', 16, ".$r['date'].", 'IMPORTED ON THE COMPONENTS IMPORT',".$pline.", ".prep($rline).");"; 
	qdb($inv_insert) or die(qe()." | $inv_insert");
	echo($inv_insert."<br>");
	$invid = qid();
	
	$rc_insert = "INSERT INTO `repair_components`(`invid`, `ro_number`, `qty`) VALUES (".prep($invid).",".prep($r['repair_id']).",".prep($r['filled'], "'0'").");";
	qdb($rc_insert) or die(qe()." $rc_insert");
	echo($rc_insert."<br><br>");
	
}

//Uncomplicated COMPONENT STOCK
$cps_order_stock = "
  SELECT cs.order_id, co.supplier_id, cs.location_id, co.id, co.component_id, cs.`quantity` qty_purchased, co.project_id, 
  co.price, co.cpo_id, co.`billed` qty_received, co.freight_instructions freight, co.date del_date, co.freight_cost, co.due_date, co.shipping_method_id ship
  FROM  inventory_componentorder co, inventory_component c, inventory_componentpurchaseorder cpo, inventory_project p, inventory_componentstock cs, inventory_componentshipping compship
  where c.id = co.component_id AND co.cpo_id = cpo.id AND p.id = co.project_id AND cs.order_id = co.id AND compship.cpo_id = cpo.id and compship.component_id = co.component_id and p.closed is FALSE;
";
$stock_results =  qdb($cps_order_stock, "PIPE") or die(qe("PIPE")." | $cps_order_stock");
echo($cps_order_stock."<br>");
foreach($stock_results as $r){
	echo("<br>---------------------------------------<br>Compnenent Stock<br>---------------------------------------<br>");
	$partid = translateComponent($r['component_id']);
	
	$check_inv = "SELECT * FROM `inventory` where `partid` = ".prep($partid).";";
	$results = qdb($check_inv) or die(qe()." | $check_inv");
	echo($check_inv."<br>");
	$po_number = findNearestGap("po", $r['del_date']);
	$companyid = dbTranslate($r['supplier_id']);
	$rep_id = null;
	// $termsid = $TERMS_MAPS[$r['terms_id']];
	$freight_carrier_id = $CARRIER_MAPS[$r['ship']];
	$freight_service_id = $SERVICE_MAPS[$r['ship']];
	$freight_account = "NULL"; //For now this is null, he has a bunch of information in his freight instructions
	$public = prep($r['freight']);
	$warrantyid = $WARRANTY_MAPS[$r['warranty_id']];
	
	
	$po_insert = "
	INSERT INTO `purchase_orders`(`po_number`,`created`, `created_by`, `sales_rep_id`, `companyid`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `private_notes`, `public_notes`, `status`) 
	VALUES ($po_number, ".prep($r['del_date']).", ".prep($rep_id).", ".prep($rep_id).", ".prep($companyid).",  1, '$freight_carrier_id', '$freight_service_id', NULL, 'Component History Import', $public, 'Active');";
	qdb($po_insert) or die(qe()." | $po_insert");
	echo($po_insert."<br>");
	$pi_insert = "
	INSERT INTO `purchase_items`(`partid`, `po_number`, `line_number`, `qty`, `qty_received`, `price`, `receive_date`, `conditionid`) VALUES	
	(".prep($partid).", $po_number, 999, ".prep($r['qty_purchased'], 0).", ".prep($r['qty_received'], 0).", ".prep($r['price']).", ".prep(format_date($r['del_date'],"Y-m-d")).", 1);";
	
	qdb($pi_insert) or die(qe()." $pi_insert");
	echo($pi_insert."<br>");
	$pline = qid();
	
	$package_insert = "
		INSERT INTO `packages`(`order_number`, `order_type`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
		VALUES ('$po_number','Purchase',999,".prep($r['freight']).",".prep($r['del_date']).",".$r['freight_cost'].");
	";
	qdb($package_insert) or die(qe()." | $package_insert");
	echo($package_insert."<br>");

	
	$inv_qty = $r['qty_purchased'] - $r['filled'];
	$rline = getLineItemIDs("repair",$r['repair_id']);
	$rline = $rline[0];
	
	$inv_insert = "	INSERT INTO `inventory`(`qty`, `partid`, `conditionid`, `status`, `userid`, `date_created`,`notes`, `purchase_item_id`) 
		VALUES ($inv_qty, ".prep($partid).", 5, 'shelved', 16, ".prep($r['del_date']).", 'IMPORTED ON THE COMPONENTS IMPORT',".$pline.");"; 
		
	qdb($inv_insert) or die(qe()." | $inv_insert");
	echo($inv_insert."<br>");
	
	$invid = "";
	$invid = qid();
	if ($invid){
		$inv_history = "
		UPDATE `inventory_history` 
		SET `date_changed` = ".$r['del_date']."
		where invid = ".prep($invid)."
		;";
	}
}
//Non-repaired Stock
// $cps_select = "
// 	SELECT * FROM inventory_componentorder where repair_id is null;
// ";

// 	$partid = translateComponent($r['component_id']);
// 	"INSERT INTO `inventory`(`qty`, `partid`, `conditionid`, `status`, `locationid`, `userid`, `date_created`, `notes`) 
// 	VALUES (".($r['qty']).", ".prep($partid).", 5, shelved, ".$r['loc'].", 16, ".$r['date'].", 'IMPORTED ON THE COMPONENTS IMPORT');";
	
?>