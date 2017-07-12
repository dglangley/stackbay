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


$select ="select ri.id repair_item_id, i.id inventoryid from repair_items ri, inventory i
where ri.invid = i.id and i.repair_item_id is null;";
$results = qdb($select) or die(qe());

echo($select."<br><BR>");

//These are all the results where there have been components ordered for a particular repair; All of these have related purchase orders and sales_orders.
foreach($results as $r){
	print_r($r);
	echo '<br>';

	$query = "UPDATE inventory SET repair_item_id = ".prep($r['repair_item_id'])." WHERE id = ".prep($r['inventoryid']).";";
	qdb($query) or die(qe());

	echo $query;
	echo '<br><br>';
}
