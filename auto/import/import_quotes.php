<?php
exit;
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
    include_once $rootdir.'/inc/calcLegacyRepairCost.php';
    include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/filter.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/logSearchMeta.php';

    function threshold_process($row){
        if($row['notes'] || intval($row['threshold']) > 0){
            $note_insert = "";
            if(intval($row['threshold']) > 0){
                $note_insert .= "Threshold: ".$row['threshold']." ";
            }
            if($row['notes']){
                $note_insert .= $row['notes'];
            }
            $note_insert = prep(trim($note_insert));
        }
        return $note_insert;
    }

    function import_quotes($type = 'outgoing'){
        //Expects Quotes/Supply
        //Query to grab the incoming_quote
        echo("REMEMBER TO SET PRICES TO NULL BEFORE RUNNING ON THE DB<br>");
        $query = "";
        $incoming_fix = "";
        
        if($type == 'outgoing'){
            $incoming_fix = "";
            $table = "outgoing";
            $incoming_flag = "0";
            $ur_ignore = "oq_id";
            $in_table = "demand";
            $price_field = '`quote_price`';
            $qtyprice = " `request_qty`, `quote_price`";
            $vendorprice = "nothing";
        } else {
            $incoming_fix = "null AS";
            $table = "incoming";
            $incoming_flag = "1";
            $ur_ignore = "iq_id";
            $in_table = "availability";
            $price_field = '`avail_price`';
            $qtyprice = " `avail_qty`, `avail_price`";
        }
        

        $query = "
        SELECT company_id as company, date, q.threshold, creator_id as user, inventory_id invid, 
        price, quantity, $incoming_fix line_number, q.notes, i.part_number, i.heci, i.clei, i.short_description, im.name manf
        FROM inventory_".$table."_quote q, inventory_inventory i, inventory_manufacturer im 
        WHERE q.inventory_id = i.id  AND i.manufacturer_id_id = im.id
        UNION
        SELECT company_id as company, date, null AS threshold, user_id as user, inventory_id invid, 
        price, quantity, null AS line_number, ur.notes, i.part_number, i.heci, i.clei, i.short_description, im.name manf
        FROM inventory_userrequest ur, inventory_inventory i, inventory_manufacturer im 
        WHERE incoming = '$incoming_flag' AND $ur_ignore IS NULL AND ur.inventory_id = i.id  AND i.manufacturer_id_id = im.id
        ";
        if($type != "outgoing"){
            //Add in the vendor process as an id
            $query .= "
            UNION
            SELECT vendor_id as company, date, null as threshold, '0' as user, inventory_id invid, 
            price, '1' as quantity, null as line_number, null as notes, i.part_number, i.heci, i.clei, i.short_description, im.name manf
            FROM inventory_vendorprice vp, inventory_inventory i, inventory_manufacturer im 
            WHERE vp.inventory_id = i.id  AND i.manufacturer_id_id = im.id";
        }
        
        $results = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
    
        $quotes = array();
        foreach($results as $row){
			$row['date'] = trim($row['date']);
            $notes = "";
            //partid is occasionally zero, please advise
            $partid = part_process($row);
            if(!$partid) {
                echo("Partid is zero")."<br>";
                print_r($row);
                echo("<br><br>");
            }
            if(!(isset($quotes[$row['date']][$row['company']][$row['user']][$partid][$row['price']]))){
                $notes = threshold_process($row);
                $quotes[$row['date']][$row['company']][$row['user']][$partid][$row['price']] = array(
                    "qty" => $row['quantity'],
                    "ln" => ($row['line_number']? $row['line_number'] : null),
                    "notes" => $notes
                    );
            } else if($quotes[$row['date']][$row['company']][$row['user']][$partid][$row['price']]['qty'] < $row['quantity']){
                $quotes[$row['date']][$row['company']][$row['user']][$partid][$row['price']]['qty'] = $row['quantity'];
            }
        } 
            // echo("<br><pre>");
            // print_r($quotes);
            // echo("</pre><br>");
            // exit;
    
        
        
    //Part Processing
    foreach($quotes as $date => $company){
        $in = array();
//        if($date == format_date($date,'Y-m-d')){
		if (strlen($date)==10) {
                $in['datetime'] = $date." 12:00:00";
            } else {
                $in['datetime'] = $date;
            }
        foreach($company as $companyid => $user){
            $in['companyid'] = dbTranslate($companyid);
            
            foreach($user as $userid => $part){
                $in['userid'] = mapUser($userid);
                ////LOG SEARCH META DOES NOT CALL A USERID FUNCTION TO DISTINGUISH////
                $meta_id = logSearchMeta($in['companyid'], false, $in['datetime'], 'import', $in['userid']);
                foreach($part as $partid => $price_group){
                    foreach($price_group as $price => $row){
                        if ($price == 0 || $price == 0.00 || $price == 0.0000 || $price == null){
                            $in['price'] = 0.00;
                        } else {
                            $in['price'] = $price;
                        }
                        $check_unique_row = "
                        SELECT count(*) as rows FROM `$in_table`
                        WHERE `metaid` = $meta_id
                        AND $price_field = ".$in['price']."
                        AND `partid` = ".prep($partid)."
                        ;";
                        $unique = qdb($check_unique_row) or die(qe()." | $check_unique_row");
                        $count = mysqli_fetch_assoc($unique);
                        if($count['rows'] == 0){
                            $insert = "INSERT INTO `$in_table` (`partid`, $qtyprice, `metaid`, `line_number`) 
                                VALUES (
                                ".prep($partid).",
                                ".prep($row['qty']).",
                                ".prep($price).",
                                ".prep($meta_id).",
                                ".prep($row['ln'])."
                                );";
                            qdb($insert) or die(qe()." ".$insert);
						}
                                
                        if ($row['notes']){
                            $prices_insert = "INSERT INTO `prices`(`partid`, `price`, `datetime`, `note`, `userid`) 
                            VALUES (".$partid.",'-99.99',".prep($in['datetime']).",".$row['notes'].","."null".")";
                            qdb($prices_insert) or die(qe()." ".$prices_insert);
                        }
                    }
                }
            }
        }
        
    }
    exit('<br><b>'.$type.' Import Complete</b>');

}

    import_quotes("incoming");
    //import_quotes("outgoing");
?>
