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

	

// qdb("TRUNCATE `purchase_requests`;") or die(qe());
// qdb("TRUNCATE `repair_activities`;") or die(qe());
// qdb("TRUNCATE `repair_items`;") or die(qe());
// qdb("TRUNCATE `repair_orders`;") or die(qe());
// qdb("TRUNCATE `repair_quotes`;") or die(qe());
// qdb("TRUNCATE `repair_sources`;") or die(qe());
	
$pipe_select = "SELECT * FROM `inventory_repair` 
WHERE `tpquote_id` is null 
and `third_party_repair_id` is null 
AND purchase_order not like 'RMA%' 
AND ticket_number IS NOT NULL 
AND inventory_id is not null
order by created_at asc;";

$results = qdb($pipe_select, "PIPE") or die(qe("PIPE")." $pipe_select");
echo("INSERTED ".mysqli_num_rows($results)." ROWS");
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
    $creator_id = prep(mapUser($r['tech_id']),16);
    
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
        $item_status = "Manifest";
    } else {
        $status = 'Active';
        $item_status = "In Repair";
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
".prep($partid).",$ro_number,1,1,
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
$repair_item_id = qid();
$conditionid = 5;
$pserial = $r['serials'];
    $inv_check = "SELECT `id` FROM `inventory` where serial_no like '$pserial' AND partid like '$partid';";
    
    $result = qdb($inv_check) or die(qe()." | $inv_check");
    if(mysqli_num_rows($result)){
      $result =  mysqli_fetch_assoc($result);
      $invid = $result['id'];
      $inv_insert = "UPDATE inventory SET 
      `qty` = 0, 
      `conditionid` = $conditionid, 
      `status` = ".prep($item_status).",
      `date_created` = ".prep($r['created_at']).",
      `notes` = ".prep("REPAIR IMPORT ".$r['notes']).",
      `repair_item_id` = ".prep($repair_item_id)."
      WHERE id = '$invid';";
    } else {
      // Inventory_insert
      $inv_insert = "INSERT INTO `inventory`(`serial_no`,`qty`, `partid`, `conditionid`,
      `status`,`locationid`,`userid`,`date_created`,`notes`,`repair_item_id`) 
      VALUES (
      ".prep($r['serials']).",
      0,
      ".prep($partid).",
      ".$conditionid.",
      ".prep($item_status).",
      ".$locationid.",
      ".$creator_id.",
      ".prep($r['created_at']).",
      ".prep("REPAIR IMPORT ".$r['notes']).",
      ".prep($repair_item_id).")";
    }
    qdb($inv_insert) or die(qe()." | $inv_insert");
    $invid = qid();
    //If there is freight
    
//Invid addition to the repair items
$inv_update = "UPDATE `repair_items` SET invid = ".prep($invid)." WHERE id = '$repair_item_id';";
$package_insert = "
INSERT INTO `packages`( `order_type`, `order_number`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
VALUES ('Repair', $ro_number, 1, ".prep($r['tracking_no']).", ".prep($r['date_out']).", ".prep($r['freight_cost']).");";
  qdb($package_insert) or die(qe()." | $package_insert");
  $packageid = qid();
  
  $pc_insert = "INSERT INTO package_contents (`serialid`, `packageid`) values($invid, $packageid);";
  qdb($pc_insert) or die(qe()." | $pc_insert");
    
//Inventory_history Correction
  $inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and (field_changed = 'new' or field_changed = 'repair_item_id');";
  qdb($inv_history) or die(qe());
  
    //If the item has been re-shipped back out, all of it's line information is the same
    // $sales = $line;
    // $sales['ship_date'] = $r['date_out'];
    //date_out dateshiipped
    
    //QUOTES innfo
    if($r['tpquote_id'] && false){
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
            ('$o_part','1',".prep($offer['cost']).",$metaid,'1',".prep($offer['notes']).");";
            
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
