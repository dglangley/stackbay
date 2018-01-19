<?php
exit;
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
	//$po_number = "";
	$pr_id = 0;
	
	$ro_number = $r['repair_id'];
	if(!$r['cpo_id']){
		if(!$ro_number){
			$bo_check = "SELECT ro_number FROM `builds` WHERE `id` = ".prep($r['project_id']).";";
			$co_result  = qdb($bo_check) or die(qe()." $bo_check");
			$co_array = mysqli_fetch_assoc($co_result);
			$ro_number = $co_array['ro_number'];

		}
		//Component_order_mapping
		$partid = translateComponent($r['component_id']);
		$companyid = dbTranslate($r['supplier_id']);
		$ship_method_id = $r['shipping_method_id'];
		if (! $ship_method_id) { $ship_method_id = 1; }
		$freight_carrier_id = $CARRIER_MAPS[$ship_method_id];
		$freight_service_id = $SERVICE_MAPS[$ship_method_id];
		$freight_account = "NULL"; //For now this is null, he has a bunch of information in his freight instructions
		$public = prep($r['freight_instructions']);
		$quantity = "";
		if($r['quantity'] > 0){
			$quantity = $r['quantity'];
		} else {
			$quantity = $r['billed'];
		}

		$pr_query = "SELECT id FROM purchase_requests WHERE ro_number = ".$ro_number." AND partid = ".prep($partid)." AND qty = ".$quantity." AND notes = 'IMPORTED FROM OLD CPO TABLE' AND requested = ".prep($r['date']).";";
		$result = qdb($pr_query) or die(qe());
		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$pr_id = $result['id'];
		} 

		if($pr_id) {
			echo "<b>Purchase Request ID:</b> " .$pr_id. "<br>";
			echo "Search Script: " . $pr_query . "<br>";
			$pr_update = "
			UPDATE `purchase_requests` SET po_number = ".prep($po_number)." WHERE id = ".$pr_id.";";
			//qdb($pr_update) or die(qe()." | $pr_update");
			echo("Update Script: ".$pr_update."<br><br>");
		} else {
			echo "<b>Query Failed to find Purchase Request:</b><br>";
			echo $pr_query . "<br><br>";
		}

	} 	
	
}