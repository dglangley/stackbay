<?php
exit;
//This script will import historical repairs information including any third party request and internal activities.
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
    $bdb_tpquery = "
    SELECT `ticket_number`,r.`company_id`, `external_in`, `external_out`,r.`inventory_id`, `shipped_status_id`, `tpquote_id`, `external_tracking_no`,
    `external_freight`,`third_party_success`,`tpbilled`, r.inventory_id as ck,  ro.*
    FROM inventory_repair r
      LEFT JOIN inventory_repairoffer ro on ro.id = r.tpquote_id
    WHERE external_out is not null
    and third_party_success is TRUE
    AND r.created_at > '2014-12-31'
    ";
    $results = qdb($bdb_tpquery) or die(qe()." | $bdb_tpquery");
    foreach($results as $r){
        $ro_number = $r['ticket_number'];
        $companyid = dbTranslate($r['company_id']);
        $creator_id = mapUser($r['creator_id']);
        
        $already_in_db = "SELECT * FROM repair_orders ro, repair_items ri WHERE ri.ro_number = ro.ro_number and ro_number = '$ro_number';";
        $result = qdb($already_in_db) or die(qe()." | $already_in_db");
        $ro_info = mysqli_fetch_assoc($result);
        
        $ss_insert = "
        INSERT INTO `service_orders`(`companyid`, `datetime`, `ship_to_id`, `bill_to_id`, `order_number`, `order_type`, `userid`, `notes`) 
        VALUES (".prep($companyid).",".prep($r['external_out']).",".prep(address_translate($r['ship_to'])).", ".prep().", $ro_number, 'Repair', ".prep($creator_id)." , ".prep($r['ship_to']).");";
        
        $ssi_insert = "
        INSERT INTO `service_items`(`partid`, `ss_number`, `qty`, `price`, `notes`, `inventoryid`) 
        VALUES (".prep($ro_info['partid']).", '$ro_number', 1, ".prep($r['cost']).", ".$r['notes'].", ".$ro_info['invid'].");
        ";
        
        $package_insert = "
        INSERT INTO `packages`( `order_type`, `order_number`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
        VALUES ('Repair', $ro_number, 1, ".prep($r['external_tracking_no']).", ".prep($r['date_out']).", ".prep($r['freight_cost']).");";
        qdb($package_insert) or die(qe()." | $package_insert");
        echo($package_insert."<br>");
        $packageid = qid();
        
        $pc_insert = "INSERT INTO package_contents (`serialid`, `packageid`) values(".$ro_info['invid'].", $packageid);";
        qdb($pc_insert) or die(qe()." | $pc_insert");
        echo($pc_insert."<br>");
        
            
        $userid = prep(mapUser($r['creator_id']));
        $meta_insert = "INSERT INTO `search_meta`(`companyid`, `datetime`, `source`, `userid`) 
        VALUES (".prep($companyid).",".prep($r['date']." 12:00:00").",'RO#$ro_number',$creator_id)";
        qdb($meta_insert) or die(qe()." | $meta_insert");
        echo($meta_insert."<br>");
        
        $metaid = qid();
        $metaid = prep($metaid);
        $quote_insert = "INSERT INTO `repair_quotes`(`partid`, `qty`, `price`, `metaid`, `line_number`, `notes`) VALUES
        (".prep($partid).",'1',".prep($r['cost']).",$metaid,'1',".prep($r['notes']).");";
        
        qdb($quote_insert) or die(qe()." | $quote_insert");
        echo($quote_insert."<br>");
            
    }

    $query = "SELECT * FROM inventory_repairoffer ro
    LEFT JOIN inventory_repair ir on ir.tpquote_id = ro.id;";
    $result = qdb($query, "PIPE") or die(qe()." | $query");
    foreach($result as $r){
        $ro_number = $r['ticket_number'];
        $companyid = dbTranslate($r['company_id']);
        $creator_id = mapUser($r['creator_id']);
        
        // inventory_repairoffer -> repair_sources
        if(!$r['ticket_number']){
            $meta_insert = "INSERT INTO `search_meta`(`companyid`, `datetime`, `source`, `userid`) 
            VALUES (".prep($companyid).",".prep($r['date']." 12:00:00").",'RO#$ro_number',$creator_id)";
            qdb($meta_insert) or die(qe()." | $meta_insert");
            echo($meta_insert."<br>");
            $metaid = qid();
        } else {
            $meta_select = "
            SELECT `id` FROM `search_meta` 
            where `companyid` = '$companyid'
            AND datetime = ".prep($r['date']." 12:00:00")."
            AND source = 'RO#$ro_number'
            AND userid = '$creator_id';";
            $meta_result = qdb($meta_select) or die(qe()." | $meta_select");
            $meta_result = mysqli_fetch_assoc($meta_result);
            $metaid = $meta_result['id'];
        }
        
        $metaid = prep($metaid);
        $quote_insert = "INSERT INTO `repair_sources`(`partid`, `qty`, `price`, `metaid`, `line_number`, `notes`) VALUES
        (".prep($partid).",'1',".prep($r['cost']).",$metaid,'1',".prep($r['notes']).");";
        
        qdb($quote_insert) or die(qe()." | $quote_insert");
        echo($quote_insert."<br>");
    }
?>
