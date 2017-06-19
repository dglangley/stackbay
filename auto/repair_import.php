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
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/filter.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/getUser.php';
	
qdb("TRUNCATE `purchase_requests`;") or die(qe());
qdb("TRUNCATE `repair_activities`;") or die(qe());
qdb("TRUNCATE `repair_items`;") or die(qe());
qdb("TRUNCATE `repair_orders`;") or die(qe());
qdb("TRUNCATE `repair_quotes`;") or die(qe());
qdb("TRUNCATE `repair_sources`;") or die(qe());
// qdb('DELETE FROM `inventory` WHERE notes like "%REPAIR IMPORT%";') or die(qe());

$pipe_select = "
SELECT * FROM `inventory_repair` 
WHERE purchase_order not like 'RMA%' 
AND ticket_number IS NOT NULL 
AND inventory_id is not null
AND created_at > '2014-12-31'
AND serials NOT IN ('02CYHL6Q','NNTM40108508','MC00AU006P','ALCLAAU37346', 'XTV2.44','X501','17-H60542', '92MV07272904', 'FE4107002009', 'be0905011264', 'BE4904001250', 'FE4606004036','g29444','i21775','05VR17000001','G51565','AJP1600', 'TBD', '000', 'NA', 'n/a')
AND company_id NOT IN ('1275')
AND ticket_number not in (
  SELECT `ticket_number`
  FROM `inventory_repair` r, `inventory_rmaticket` rt, inventory_solditem isi
  WHERE r.ticket_number = rt.repair_id
  and rt.item_id = isi.id 
  AND created_at > '2014-11-10 12:58:46'
  AND serials NOT IN ('02CYHL6Q','NNTM40108508','MC00AU006P','ALCLAAU37346', 'XTV2.44','X501','17-H60542')
)
order by created_at asc;";

$results = qdb($pipe_select, "PIPE") or die(qe("PIPE")." $pipe_select");
echo($pipe_select."<br>");
$meta = array();
$meta['private_notes'] = "";
//get repair location
$getLocation = "SELECT id FROM locations where place like 'repair';";
$result = qdb($getLocation) or die(qe()." | $getLocation");
echo($getLocation."<br>");
$result = mysqli_fetch_assoc($result);
$locationid = $result['id'];
$INSERTED = 0;
$already = array();
// echo("<table style = 'border:thin black solid;'>");
foreach($results as $r){
    // $inv_orig_id = "";
    // $inventory_check = "SELECT * FROM `inventory` where serial_no = ".prep($r['serials']).";";
    // $invcheck = qdb($inventory_check) or die(qe()." | $inventory_check");
    // echo($inventory_check."<br>");
    // if(mysqli_num_rows($invcheck)){
    //   // if($r['serials'] == "TBD" || $r['serials'] == "000" || $r['serials'] == "NA" || $r['serials'] == "n/a"){continue;}
    //   $inv_res = mysqli_fetch_assoc($invcheck);
    //   // echo("<tr style = 'border:thin black solid;'><td style = 'border:thin black solid;'>");
    //   // echo($inventory_check);
    //   // echo("<td style = 'border:thin black solid;'>");
    //   if ($already[$r['serials']] || $inv_res['repair_item_id']){
    //     //This is a part which has already had at least one repair on it.
    //     $inv_orig_id = $inv_res['id'];
    //     // echo("GUESS: ALREADY BEEN REPAIRED ONCE");
    //   } else if ($r["cost"] == 0 && $r['company_id'] == 415){
    //     $internal_repair_id = $inv_res['id'];
    //   } else {
    //     echo("I don't know");
    //   }
    //   echo("</td>");
    //   echo("</td><td style = 'border:thin black solid;'><pre>");
    //   print_r($inv_res);
    //   echo("</pre></td><td style = 'border:thin black solid;'><pre>");
    //   print_r($r);
    //   echo("</pre></td></tr>");
    //   continue;
    // }
    // $already[$r['serials']] = true;
    $INSERTED++;
    
    
    $ro_number = $r['ticket_number'];
    $meta['ro_number'] = $ro_number;
    $companyid = dbTranslate($r['company_id']);
    $creator_id = prep(mapUser($r['tech_id']),16);
    
    $sales_rep_id = mapUser($r['sales_rep_id']);
    $meta['private_notes'] .= $r['ext_notes'];
    $freight_service = prep($SERVICE_MAPS[$r['carrier_id']]);
    $freight_carrier = prep($CARRIER_MAPS[$r['carrier_id']]);
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
    if($r['status_id'] == "12" || $r['status_id'] == "11"){
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
$ro_number, ".prep($r['created_at']).", ".$creator_id.", ".prep($sales_rep_id).", ".prep($companyid).", 
".prep($r['purchase_order']).", 
".prep(address_translate($r['ship_to'])).", 
$freight_carrier, $freight_service, 
$terms, ".prep($r['ship_to']).", ".prep($meta['notes']).", ".prep($status).");";
qdb($order_insert) or die(qe(). " | $order_insert");
echo($order_insert."<br>");
$item_insert = "INSERT INTO `repair_items`(`partid`,`ro_number`,`line_number`,`qty`,`price`,
`due_date`,`invid`,`ref_1`,`ref_1_label`,`ref_2`,`ref_2_label`,`notes`, `warrantyid`) VALUES (
".prep($partid).",$ro_number,1,1,
".prep($r['price_per_unit']).",
".prep(format_date($r['date_due'],"Y-m-d"), "'".format_date($r['created_at'],"Y-m-d",array("d"=>30))."'").",
NULL,
".prep($line['ref_1']).",
".prep($line['ref_1_label']).",
".prep($line['ref_2']).",
".prep($line['ref_2_label']).",
".prep($r['notes']).",
".prep($line['warranty'])."
);";
qdb($item_insert) or die(qe()." | $item_insert");
echo($item_insert."<br>");

$repair_item_id = qid();
$conditionid = 5;
$pserial = strtoupper($r['serials']);

    $inv_check = "SELECT * FROM `inventory` where serial_no like '$pserial' AND partid like '$partid';";
    
    $inv_check_result = qdb($inv_check) or die(qe()." | $inv_check");
    echo($inv_check."<br>");
    if(mysqli_num_rows($inv_check_result)){
      $inv_check_result =  mysqli_fetch_assoc($inv_check_result);
      $invid = $inv_check_result['id'];
      $prev = $result['repair_item_id'];
      $inv_insert = "UPDATE inventory SET 
      `qty` = 0, 
      `conditionid` = $conditionid, 
      `status` = ".prep($item_status).",
      `date_created` = ".prep($r['created_at']).",
      `notes` = ".prep("REPAIR IMPORT ".$r['notes']).",
      `repair_item_id` = ".prep($repair_item_id)."
      WHERE id = '$invid';";
      qdb($inv_insert) or die(qe()." | $inv_insert");
      echo($inv_insert."<br>");
      //Inventory_history Correction
      $inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and  field_changed = 'repair_item_id' AND changed_from = '$prev';";
      qdb($inv_history) or die(qe());
      echo($inv_history."<br>");
    } else {
      // Inventory_insert
      $inv_insert = "INSERT INTO `inventory`(`serial_no`,`qty`, `partid`, `conditionid`,`status`,`locationid`,`userid`,`date_created`,`notes`,`repair_item_id`) VALUES (".prep(strtoupper($r['serials'])).",0,".prep($partid).",".$conditionid.",".prep($item_status).",".$locationid.",".$creator_id.",".prep($r['created_at']).",".prep("REPAIR IMPORT ".$r['notes']).",".prep($repair_item_id).")";
      qdb($inv_insert) or die(qe()." | $inv_insert");
      echo($inv_insert."<br>");
      $invid = qid();
      //Inventory_history Correction
      $inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and (field_changed = 'new' or field_changed = 'repair_item_id');";
      qdb($inv_history) or die(qe());
      echo($inv_history."<br>");
    }
    //If there is freight
    
//Invid addition to the repair items
$inv_update = "UPDATE `repair_items` SET invid = ".prep($invid)." WHERE id = '$repair_item_id';";
qdb($inv_update) or die(qe()." | $inv_update");
echo($inv_update."<br>");


$package_insert = "
INSERT INTO `packages`( `order_type`, `order_number`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
VALUES ('Repair', $ro_number, 1, ".prep($r['tracking_no']).", ".prep($r['date_out']).", ".prep($r['freight_cost']).");";
  qdb($package_insert) or die(qe()." | $package_insert");
  echo($package_insert."<br>");
  $packageid = qid();
  
  $pc_insert = "INSERT INTO package_contents (`serialid`, `packageid`) values($invid, $packageid);";
  qdb($pc_insert) or die(qe()." | $pc_insert");
  echo($pc_insert."<br>");
    

  
    //If the item has been re-shipped back out, all of it's line information is the same
    // $sales = $line;
    // $sales['ship_date'] = $r['date_out'];
    //date_out dateshiipped
    
    //QUOTES innfo

    echo("<br><br>");
}
// echo("</table>");
echo("INSERTED ".$INSERTED."/".mysqli_num_rows($results)." ROWS");


///////////////////////////////////////
echo("------------------------------------------------------<br>")  ;
echo("------------------------------------------------------<br>")  ;
echo("------------------------------------------------------<br>")  ;
echo("------------------------------------------------------<br>")  ;
echo("------------------------------------------------------<br>")  ;
echo("------------------------------------------------------<br>")  ;

  $pipe_select = "
SELECT company_id, part_number, serials, r.status_id, date_in, date_out, purchase_order, notes, price_per_unit, ticket_number, tracking_no,
  date_due, datetime_test_in, datetime_test_out, r.cost, created_at, tech_id, external_out, external_in, shipped_pn, shipped_sn, shipped_clei,
  shipped_status_id, carrier_id, material_sap, ext_notes, warranty_id, r.freight_cost, source_cost, type_id, sales_rep_id, tpquote_id, external_tracking_no, third_party_success,
  external_freight, tpbilled,  ship_to, clei, lead_time, r.min_price, canceled_by_id, canceled_date, assigned_to_id, r.inventory_id, assigned_in, assigned_out, 
  tp_warranty, terms_id, rt.*, isi.*
FROM `inventory_repair` r, `inventory_rmaticket` rt, inventory_solditem isi
WHERE r.ticket_number = rt.repair_id
and rt.item_id = isi.id 
AND created_at > '2014-11-10 12:58:46'
AND serials NOT IN ('02CYHL6Q','NNTM40108508','MC00AU006P','ALCLAAU37346', 'XTV2.44','X501','17-H60542', '92MV07272904', 'FE4107002009', 'be0905011264', 'BE4904001250', 'FE4606004036','g29444','i21775','05VR17000001','G51565','AJP1600', 'TBD', '000', 'NA', 'n/a')
order by created_at desc;
";




$results = qdb($pipe_select,"PIPE") or die(qe("PIPE")." | $pipe_select");
echo("$pipe_select '\$pipe_select'<br>");

//get repair location
$getLocation = "SELECT id FROM locations where place like 'repair';";
$result = qdb($getLocation) or die(qe()." | $getLocation");
echo("$getLocation '\$getLocation'<br>");
$result = mysqli_fetch_assoc($result);
$locationid = $result['id'];

foreach($results as $i => $r){
    $private_notes ="";
    //ALL of the above results already exist in the database, so we can merge them into records
    $inv = "SELECT * FROM `return_items` WHERE rma_number like ".prep($r['master_id']).";";
    $serial_match = qdb($inv) or die(qe()." | $inv");
    echo("$inv '\$inv'<br>");
    if(mysqli_num_rows($serial_match)){
        $inv_info = mysqli_fetch_assoc($serial_match);
        print_r($inv_info);
    } else {
        $inv = "INSERT INTO `inventory` (`partid`,`serial_no`,`qty`) VALUES (".prep(strtoupper($r['serials'])).", 0);";
        echo("$i: $inv<br>");
        continue;
        qdb($inv) or die(qe()." | $inv");
        echo("$inv '\$inv'<br>");
        $inv = "SELECT * FROM `inventory` WHERE serial_no like ".prep(strtoupper($r['serials'])).";";
    }
    //Info From Brian's system (relevant for the RO);
    $ro_number = $r['ticket_number'];
    $companyid = dbTranslate($r['company_id']);
    $creator_id = prep(16);
    $sales_rep_id = mapUser($r['sales_rep_id']);
    $private_notes .= $r['ext_notes'];
    $freight_service = prep($SERVICE_MAPS[$r['carrier_id']]);
    $freight_carrier = prep($CARRIER_MAPS($r['carrier_id']));
    $terms = prep($TERMS_MAPS[$R['terms_id']]);
    $line['warranty'] = $WARRANTY_MAPS[$r['warranty_id']]; //warranty_id
    $partid = $inv_info['partid'];
    $invid = $inv_info['inventoryid'];
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
    if($r['status_id'] == "12" || $r['status_id'] == "11"){
        $status = 'Completed';
        $item_status = "Manifest";
    } else {
        $status = 'Active';
        $item_status = "In Repair";
    }
//Note the fact that we don't actually have a part yet

    $check = "SELECT * FROM `repair_items` ro where ro.`invid` = ".prep($invid).";";
    $check_result = qdb($check) or die(qe(). " | $check");
    echo("$check '\$check'<br>");

if(mysqli_num_rows($check_result) == 0){
    $order_insert = "
        INSERT INTO `repair_orders`(
        `ro_number`, `created`, `created_by`, `sales_rep_id`, `companyid`, `cust_ref`, `ship_to_id`, 
        `freight_carrier_id`, `freight_services_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES (
        $ro_number, ".prep($r['created_at']).", $creator_id, ".prep($sales_rep_id).", ".prep($companyid).", 
        ".prep($r['purchase_order']).", 
        ".prep(address_translate($r['ship_to'])).", 
        $freight_carrier, $freight_service, $terms, ".prep($r['ship_to']).", ".prep($private_notes).", ".prep($status).");";
    
    qdb($order_insert) or die(qe(). " | $order_insert");
    echo("$order_insert '\$order_insert'<br>");

    print_r($check_result);
    echo($check);
//Adding our record to the repair items insert
    $item_insert = "INSERT INTO `repair_items`(`partid`,`ro_number`,`line_number`,`qty`,`price`,
    `due_date`,`invid`,`ref_1`,`ref_1_label`,`ref_2`,`ref_2_label`,`notes`, `warrantyid`) VALUES (
    ".prep($partid).",
    $ro_number,
    1,
    1,
    ".prep($r['price_per_unit']).",
    ".prep(format_date($r['date_due'],"Y-m-d"), "'".format_date($r['created_at'],"Y-m-d",array("d"=>30))."'").",
    ".prep($invid).",
    ".prep($line['ref_1']).",
    ".prep($line['ref_1_label']).",
    ".prep($line['ref_2']).",
    ".prep($line['ref_2_label']).",
    ".prep($r['notes']).",
    ".prep($line['warranty'])."
    );";
    // exit($item_insert);
    qdb($item_insert) or die(qe()." | $item_insert");
    echo("$item_insert '\$item_insert'<br>");
    $repair_item_id = qid();
    $conditionid = 5;
//Package_creation (with freight if applicable)
    $package_insert = "
    INSERT INTO `packages`( `order_type`, `order_number`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
    VALUES ('Repair', $ro_number, 1, ".prep($r['tracking_no']).", ".prep($r['date_out']).", ".prep($r['freight_cost']).");";
    qdb($package_insert) or die(qe()." | $package_insert");
    echo("$package_insert '\$package_insert'<br>");
    $packageid = qid();

//Package_contents (adding the received package info) 
    $pc_insert = "INSERT INTO package_contents (`serialid`, `packageid`) values($invid, $packageid);";
    qdb($pc_insert) or die(qe()." | $pc_insert");
    echo("$pc_insert '\$pc_insert'<br>");
    
//Inventory_history Correction
    $inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and (field_changed = 'new' or field_changed = 'repair_item_id');";
    qdb($inv_history) or die(qe());
    echo("$inv_history '\$inv_history'<br>");
}
else{
    $check = "SELECT * FROM `repair_items` where `ro_number` = ".prep($ro_number).";";
    $result = qdb($check) or die(qe(). " | $check");
    echo("$check '\$check'<br>");
    $exists = mysqli_fetch_assoc($result);
    $repair_item_id = $exists['id'];
}

//Inventory_update
    $inv_check = "SELECT * FROM `inventory` where id like ".prep($invid).";";
    $ic_result = qdb($inv_check) or die(qe()." | $inv_check");
    echo("$inv_check '\$inv_check'<br>");
    
    if(mysqli_num_rows($ic_result)){
      $ic_result =  mysqli_fetch_assoc($ic_result);
    //   $invid = $ic_result['id'];
        $prev = $ic_result['repair_item_id'];
        $inv_update = "
        UPDATE inventory SET
        repair_item_id = ".prep($repair_item_id)."
        WHERE id = ".prep($invid).";";
        qdb($inv_update) or die(qe()." | $inv_update");
        echo("$inv_update '\$inv_update'<br>");
        //Inventory_history Correction
        if($prev){
          $inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and  field_changed = 'repair_item_id' AND changed_from = '$prev';";
          qdb($inv_history) or die(qe());
          echo("$inv_history '\$inv_history'<br>");
        }
    } else {
      // Inventory_insert
      $inv_insert = "INSERT INTO `inventory`(`serial_no`,`qty`, `partid`, `conditionid`,
      `status`,`locationid`,`userid`,`date_created`,`notes`,`repair_item_id`) 
      VALUES (
      ".prep(strtoupper($r['serials'])).",
      0,
      ".prep($partid).",
      5,
      ".prep($item_status).",
      ".$locationid.",
      ".$creator_id.",
      ".prep($r['created_at']).",
      ".prep("REPAIR IMPORT ".$r['notes']).",
      ".prep($repair_item_id).")";
      qdb($inv_insert) or die(qe()." | $inv_insert");
      echo("$inv_insert '\$inv_insert'<br>");
      $invid = qid();
      //Inventory_history Correction
      $inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and (field_changed = 'new' or field_changed = 'repair_item_id');";
      qdb($inv_history) or die(qe());
      echo("$inv_history '\$inv_history'<br>");
    }

//Cost Log Correction
    $cost_update = "UPDATE inventory_costs_log SET 
    eventid = ".prep($repair_item_id).",
    event_type = 'repair_item_id'
    WHERE eventid = '$ro_number' AND event_type = 'repair_id';";
    qdb($cost_update) or die(qe()." | $cost_update");
    echo("$cost_update '\$cost_update'<br><br>");

}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
//Repair activities import
    $activities_select = "SELECT `ticket_number`,`date_in`,`date_out`, `assigned_in`, `created_at`, `notes` from inventory_repair WHERE `ticket_number` is not NULL AND `created_at` > '2014-12-31';";
    $results = qdb($activities_select, "PIPE") or die(qe("PIPE").$activities_select);
    foreach($results as $r){
        $ro_number = $r['ticket_number'];
        
        //Get Line Item Number
        $ln_query = "SELECT `id` FROM `repair_items` WHERE `ro_number` = '$ro_number';";
        $ln = qdb($ln_query) or die(qe()." | $ln_query");
        $ln = mysqli_fetch_assoc($ln);
        $repair_item_id = $ln['id'];
        $tech_id = 16;
        if ($r['date_in']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_in']." 12:00:00","Y-m-d G:i:s")).", $tech_id, 'Checked In');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        if ($r['date_out']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_out']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Checked Out');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        
        //THIS IS NOT A STABLE WAY TO BUILD THIS BUT WILL MAKE THE WORLD SLIGHTLY BETTER? TALK TO DAVID;
        if ($r['assigned_in']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['assigned_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        } else if($r['date_in']) {
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        } else {
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['created'],"Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        $n = $r['notes'];
        $rma = explode(" ",$r['notes']);
        $notes ='';
        if($rma[0] != "RMA" && strlen($n) > 3){
            $notes = $n;
        }
        if ($notes){
            if ($r['assigned_in']){
                $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                        VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['assigned_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, ".prep($notes).");";
                        qdb($insert) or die(qe()." | $insert");
                        echo($insert."<br>");
            } else if($r['date_in']) {
                $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                        VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, ".prep($notes).");";
                        qdb($insert) or die(qe()." | $insert");
                        echo($insert."<br>");
            } else {
                $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                        VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['created'],"Y-m-d G:i:s")).", $tech_id, ".prep($notes).");";
                        qdb($insert) or die(qe()." | $insert");
                        echo($insert."<br>");
            }
        }
        echo("<br><br>");
        
    }

?>

