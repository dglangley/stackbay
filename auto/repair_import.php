<?php
	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPartId.php';
	include_once $rootdir.'/inc/setPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/calcRepairCost.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/filter.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/getUser.php';

	
$pipe_select = "SELECT * FROM `inventory_repair` LIMIT 50";

$results = qdb($pipe_select, "PIPE") or die(qe("PIPE")." $pipe_select");
$meta = array();
$meta['private_notes'] = "";

//get repair location
$getLocation = "SELECT id FROM locations where place like 'repair';";
$result = qdb($getLocation) or die(qe()." | $getLocation");
$result = mysqli_fetch_assoc($result);
$locationid = $result['id'];

foreach($results as $r){
    $ro_number = $r['ticket_number'];
    $meta['ro_number'] = $ro_number;
    $companyid = dbTranslate($r['company_id']);
    $creator_id = mapUser($r['tech_id']);
    $sales_rep_id = mapUser($r['sales_rep_id']);
    $meta['private_notes'] .= $r['ext_notes'];
    $freight_service = prep($FREIGHT_MAPS[$r['carrier_id']]);
    $freight_carrier = prep(getCarrierID($freight_service));
    $terms = prep($TERMS_MAPS[$R['terms_id']]);
    $line['warranty'] = $WARRANTY_MAPS[$r['warranty_id']]; //warranty_id
    $partid = translateID($r['inventory_id']);
    
    //status_id - Connects to the 'repairshippeddtatus' table, correlates to our condition table. will need to be mapped to conditions, most likely.
    $line['ref_1'] = null;
    $line['ref_1_label'] = null;
    $line['ref_2'] = null;
    $line['ref_2_label'] = null;
    if($r['material_sap']){
        //material_sap -> some verizon label REF_LABEL1 SAP, Re11val
        $line['ref_1'] = $r['material_sap'];
        $line['ref_1_label'] = 'SAP';
    }
    if($r['verizon_ref']){
        $line['ref_2'] = $r['verizon_ref'];
        $line['ref_2_label'] = 'Verizon Ref';
    }
    if($r['status'] == "12" || $r['status'] == "11"){
        $status = 'Completed';
    } else {
        $status = 'Active';
    }
//Note the fact that we don't actually have a part yet


$order_insert = "INSERT INTO `repair_orders`(
`ro_number`, `created`, `created_by`, `sales_rep_id`, `companyid`, `cust_ref`, `ship_to_id`, 
`freight_carrier_id`, `freight_services_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES (
$ro_number, ".prep($r['created_at']).", ".prep($creator_id).", ".prep($sales_rep_id).", ".prep($companyid).", 
".prep($r['purchase_order']).", 
".prep(address_translate($r['ship_to'])).", 
$freight_carrier, $freight_service, 
$terms, ".prep($r['ship_to']).", ".prep($meta['notes']).", ".prep($status).");";

qdb($order_insert) or die(qe(). " | $order_insert");
$item_insert = "INSERT INTO `repair_items`(`partid`,`ro_number`,`line_number`,`qty`,`price`,
`due_date`,`invid`,`ref_1`,`ref_1_label`,`ref_2`,`ref_2_label`,`notes`, `warrantyid`) VALUES (
$partid,$ro_number,1,1,
".prep($r['price_per_unit']).",
".prep(format_date($r['due_date'],"Y-m-d")).",
NULL,
".prep($line['ref_1']).",
".prep($line['ref_1_label']).",
".prep($line['ref_2']).",
".prep($line['ref_2_label']).",
".prep($r['notes']).",
".prep($line['warranty'])."
);";
qdb($item_insert) or die(qe()." | $item_insert");

    $conditionid = 5;
    //This will need to be translated
    // Inventory_insert
    $inv_insert = "INSERT INTO `inventory`(`serial_no`,`qty`, `partid`, `conditionid`,
    `status`,`locationid`,`repair_item_id`,`userid`,`date_created`,`notes`) 
    VALUES (
    ".$r['serial_number'].",
    0,
    ".$partid.",
    ".$conditionid.",
    ".$status.",
    ".$locationid.",
    ".$r['serial_number'].",
    ".$r['serial_number'].",
    ".$r['serial_number'].",
    ".$r['serial_number'].")";
    
    
    
    //If the item has been re-shipped back out, all of it's line information is the same
    // $sales = $line;
    // $sales['ship_date'] = $r['date_out'];
    //date_out dateshiipped
    
    //QUOTES innfo
    if($r['tpquote_id']){
        $quote_select = "SELECT * FROM inventory_repairoffer where id = '".$r['tpquote_id']."';";
        $results = qdb($quote_select) or die(qe()." | $quote_select");
        if(mysqli_num_rows($results)){
            $offer = mysqli_fetch_assoc($results);
            $o_part = $partid;
            if($offer['inventory_id'] != $r['inventory_id']){
                $o_part = dbTranslate($offer['inventory_id']);
            }
            $userid = prep(mapUser($offer['creator_id']));
            $meta_insert = "INSERT INTO `search_meta`(`companyid`, `datetime`, `source`, `userid`) 
            VALUES (".prep($companyid).",".prep($offer['date']." 12:00:00").",'RO#$ro_number',$userid)";
            qdb($meta_insert) or die(qe()." | $meta_insert");
            $metaid = null;
            $metaid = qid();
            $metaid = prep($metaid);
            $quote_insert = "INSERT INTO `repair_quotes`(`partid`, `qty`, `price`, `metaid`, `line_number`, `notes`) VALUES
            ('$o_part','1',".prep($offer['cost']).",$metaid,'1',".prep($offer['notes']).")";
            
            qdb($quote_insert) or die(qe()." | $quote_insert");
        }
    }

}



//ACTIVITIES (repair_activities)
  //date_in datereceived
  //datetime_test_in
  //datetime_test_out
  //tech_id: potentially translated (but there is only two rows with a non-for, not null value);
  //log????????????????????????????????????????
  
//Inventory_information
  //Converted Partnumber
  //serials -> serial_no
  //shipped_sn
  //shipped_pn
  //shipped_clei
  //shipped_status_id -> condition translation
  
//Inventory_costs information
  //cost
  //created_at
  //tech_id 
  //freight_cost -> only five items with freight cost
    
//Package Information
  //tracking_no : Create a package with any , add the tracking no(one per package), add all serials to the master unless otherwise specified
  //freight_cost -> only five items with freight cost

//invoice information
  //If($invoiced){
  //invoice_number -> Insert "invoice no", company, $date_due, "Ticket_no?",'Repair',shipment
  //Grab the same associated freight from the tracking_no
  //date_due

//Unknown
  //sales_order
  
  //inventory_item_id - sold item table
  //test_price - quoted price
  //source_cost
  //type_id: Joins with inventory_repairtype, used as a label
  //tpquote_id
  //external_tracking_no
  
//JUNK
  //priority_id
  //project_id - Only used four times as not one or null
  //external_out
  //external_in
  //third_party_repair_id (TP ships everything out)
  
  //third_party_success
  //external_freight
  //purchase_order_id
  //rpo_id
  //tpbilled
  //test_status_id
  //customer_packing
  //ship_to
  //shipped_heci
  //verizon_tag
  //clei
  //lead_time
  //min_price
  //canceled_by_id
  //canceled_date
  //seen_inhouse
  //image
  //repair_group_id
  //assigned_to_id
  //inventory_id
  //assigned_in
  //assigned_out
  //tech_status_id
  //qa_assigned_to_id
  //qa_in
  //qa_out
  //qa_status_id
  //metrics
  //tp_warranty
  //repair_id
  //request_test
  //request_checkout
  //request_checkin
  //quoteli_id
  //terms_idstacvk
  //verizon_region_id


?>
