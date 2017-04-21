<?php

    $rootdir = $_SERVER["ROOT_DIR"];
    include_once $rootdir.'/inc/dbconnect.php';
    include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/format_price.php';
    include_once $rootdir.'/inc/getCompany.php';
    include_once $rootdir.'/inc/getCondition.php';
    include_once $rootdir.'/inc/getPart.php';
    include_once $rootdir.'/inc/getPartId.php';
    include_once $rootdir.'/inc/setPart.php';
    include_once $rootdir.'/inc/pipe.php';
    include_once $rootdir.'/inc/getPipeIds.php';
    include_once $rootdir.'/inc/calcRepairCost.php';
    include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/filter.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/logSearchMeta.php';

    function import_quotes($type = 'outgoing'){
    //Expects Quotes/Supply
    //Query to grab the incoming_quote
    echo("REMEMBER TO SET PRICES TO NULL BEFORE RUNNING ON THE DB");
    $query = "";
    if($type == 'outgoing'){
        $query = "SELECT oq.company_id as company, oq.date, oq.threshold, oq.creator_id, oq.inventory_id invid, 
        oq.price as quote_price,oq.quantity as quote_quantity , 
        ur.quantity as req_qty, ur.price as req_price, line_number, oq.notes, sales_rep_id
        FROM inventory_outgoing_quote oq, inventory_userrequest ur, inventory_quote q
        WHERE  oq.id = ur.oq_id AND oq.quote_id = q.id;";
    } else {
        $query = "SELECT company_id as company, date, threshold, notes, creator_id, inventory_id invid, 
        price as offer_price, quantity as offer_qty, null AS line_number, null as sales_rep_id
        FROM inventory_incoming_quote iq;";
    }
    $results = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
    
    foreach($results as $row){
        print_r($row);
        $ins = array();
        // get_coldata(); ??
        //Meta information from inventory_outgoing_quote: 
        //company_id -> companyid
        $ins['company'] = $row['company'];
        
        //date -> datetime
        // append datetime as noon
        if($row['date'] == format_date('n/j/y',$row['date'])){
            $ins['datetime'] = $row['date']." 12:00:00";
        } else {
            $ins['datetime'] = $row['date'];
        }

        //creator_id -> userid (Through the maps)
        $ins['userid'] = mapUser($row['creator_id']);
        if($row['sales_rep_id'] && ($row['sales_rep_id'] != $row['creator_id'])){
            $ins['userid'] = $row['sales_rep_id'];
        }
        
        $ins['meta_id'] = logSearchMeta($ins['company'], false, $ins['date'],'import');
        
        //Demand information from `inventory_outgoing_quote`:
        //inventory_id -> translateID() -> partid
        $ins['partid'] = translateID($row['invid']);
        //price -> quote_price
        $ins['quote_price'] = $row['quote_price'];
        //quantity -> quote_qty
        $ins['quote_qty'] = $row['quote_qty'];
        //line_number -> line_number
        $ins['line_number'] = $row['line_number'];
        
        
        //Demand information from joined `inventory_userrequest`:
        //price -> request_price
        //quantity -> request_qty
        $ins['request_qty'] = $row['req_quantity'];
        $ins['request_price'] = $row['req_price'];    
        
        $ins['offer_price'] = $row['offer_price'];
        $ins['offer_qty'] = $row['offer_qty']; 
        
            
        //========================= INSERT STATEMENT ====================
        
        if($type == 'outgoing'){
            $table = 'demand';
        } else {
            $table = 'availability';   
        }
        //INCOMING GOES TO AVAILABILITY
        
        //Prepped insert query into the `supply/availability` table
        if($ins['partid']){
            if($type == 'outgoing'){
                $insert = "INSERT INTO `demand` 
                (`partid`, `request_qty`, `request_price`, `quote_qty`, `quote_price`, `metaid`, `line_number`) 
                VALUES (
                ".prep($ins['partid']).",
                ".prep($ins['request_qty']).",
                ".prep($ins['request_price']).",
                ".prep($ins['quote_qty']).",
                ".prep($ins['quote_price']).",
                ".prep($ins['meta_id']).",
                ".prep($ins['line_number'])."
                );";
            } else {
                $insert = "INSERT INTO `availability`
                (`partid`, `avail_qty`, `avail_price`, `offer_qty`, `offer_price`, `metaid` `line_number`)
                VALUES (
                ".prep($ins['partid']).",
                ".prep($ins['avail_qty']).",
                ".prep($ins['avail_price']).",
                ".prep($ins['offer_qty']).",
                ".prep($ins['offer_price']).",
                ".prep($ins['meta_id']).",
                ".'null'.",
                ".prep($ins['line_number'])."
                );";
            }
            qdb($insert) or die(qe());
        }
        
        if($row['notes'] || intval($row['threshold']) > 0){
            $note_insert = "";
            if(intval($row['threshold']) > 0){
                $note_insert .= "Threshold: ".$row['threshold']." ";
            }
            if($row['notes']){
                $note_insert .= $row['notes'];
            }
            $note_insert = prep(trim($note_insert));
            $prices_insert = "INSERT INTO `prices`(`partid`, `price`, `datetime`, `note`, `userid`) 
            VALUES (".$ins['partid'].",'-99.99',".prep($ins['datetime']).",".$note_insert.","."null".")";
                //If threshdold is greater than zero OR notes
                //take partid, leave price null, set date_time and note: Threshold . $note, user_id, id
        }
    }
}
    import_quotes();
?>








