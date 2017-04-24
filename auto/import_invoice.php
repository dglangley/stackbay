<?php
    $start_time = microtime();
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

    //Pre-everything prep
    
    //Optional limiter to run as an update instead of a full import: will check to endure no values have been duplicated.
    $initial_invoices = "SELECT DISTINCT `invoice_no` FROM `invoices`;";
    $invoice_results = qdb($initial_invoices) or die(qe()." $initial_invoices :|:");
    $not_in = "";
    $already_imported = "";
    $already_imported = mysqli_num_rows($invoice_results);
    if ($already_imported){
        $not_in = "(";
        foreach($invoice_results as $invoice_no){
            $not_in .= "'".$invoice_no['invoice_no']."', ";
        }
        $not_in = rtrim($not_in,", ");
        $not_in .= ")";
    }
    
    //Parameter where, if true, then all the values have been properly import 
    $import_complete = false;
    $query = "SELECT id, date as date_invoiced, customer_id as companyid, memo, sent, ext_memo, amount,
    lump_id, voided, voided_by_id, voided_date, voided_reason, paid, paid_date, postfix, ref_no
    FROM inventory_invoice
    ".(($not_in)? "WHERE `id` NOT IN ".$not_in : "").";";
    
    
    $result = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
    
    $lump_builder = array();
    echo"
    <style>
        table, tr, td, th{
            border: thin black solid;
            padding:5px;
        }
        table{
            margin:auto;
            width:99%;
        }
    </style>
    <table class='table table-bordered'>
    <tr>
        <th>Pulled</th>
        <th>Prepped</th>
        <th>Payment Macro</th>
        <th>Payment Details</th>
        <th>Lump</th>
    </tr>
    ";
    $grand_failed = false;
    $count = 0;
if(mysqli_num_rows($result)){
    foreach($result as $meta){
        $failed = false;
        $debug = false;
        $failed_message = "";
        $insert_row = array();
        $payment_info = array();
        $payment_details = array();
        //Notes are assumed null, but appended with potentially multiple fields
        $insert_row['notes'] = '';
        
        
        // echo"<br><br>";
        // print_r($meta);
        
        //ID
        $insert_row['invoice_no'] = $meta['id'];
        // if(!$meta['id']){$failed=true;}
        
        //Contact
        $companyid = dbTranslate($meta['companyid']);
        $insert_row['companyid'] = $companyid;
        
        //Date
        $insert_row['date_invoiced'] = $meta['date_invoiced'];
        
        //======================= Order Type Processing =======================
        $insert_row['order_number'] = null;
        $insert_row['order_type'] = null;
        $insert_row['freight'] = null;
        
        if($meta['memo'] && $meta['memo'][0] == "S" || $meta['memo'][0] == "R"){
            //If the row is of the type SO or RO, explode and move on
            $order_info = explode("#",$meta['memo']);
            if (trim($order_info[0]) == "SO"){
                $insert_row['order_type'] = "Sale";
            } else {
                $insert_row['order_type'] = "Repair";
            }
            $insert_row['order_number'] = trim($order_info[1]);
            
            //Catch for when the memo carries an extra line-broken parameter
            if(!is_numeric($insert_row['order_number'])){
                $more_parse = explode("\n" , $insert_row['order_number']);
                $insert_row['order_number'] = trim($more_parse[0]);
            }
            if(!is_numeric($insert_row['order_number'])){
                $failed_message .= "| Not a a valid RO/SO number: ".$insert_row['order_number']." ";
            }
            
        } else if($meta['postfix'] == "IT"){
            //IT has no order number, but a relavant order type
            $insert_row['order_type'] = "IT";
            $insert_row['notes'] .= trim($meta['ref_no']);
        } else {
            //Otherwise just build notes
            $insert_row['notes'] .= $meta["memo"].($meta['ref_no']? " (".$meta['ref_no'].")" : "");
        }

        
        //========================= Status processing =========================
        if($meta['sent']){
            $insert_row['status'] = "Completed";
        } else {
            $insert_row['status'] = "Voided";
            $insert_row['notes'] .= " Voided by ".getUser(mapUser($meta['voided_by_id']))." ".$meta['voided_date']." Reason: ".$meta['voided_reason'];
        }
        
        //=============================== Notes ===============================
        $insert_row['notes'] .= $meta['ext_memo'];
        
        
        //========================== LINE PROCESSING ==========================
        $lines = "SELECT id, amount, quantity, memo, item, oqi_id, repair_id, it_id
        FROM inventory_invoiceli WHERE invoice_id = ".$meta['id'].";";
        $line_results = qdb($lines,"PIPE") or die(qe("PIPE")." ".$lines);
        
        //Prep the sum for each of the line totals to do the SUM audit
        $lines_sum = 0.00;
        if(!mysqli_num_rows($line_results)){
            // $failed_message = "| No Rows ";
        } else {
            foreach($line_results as $line){
            
            //Naive Line Pricing information to audit the amount 
            $lines_sum += $line['amount'] * $line['quantity'];
            
            //The following acts as a switch would to process different types of
            
            if($line['oqi_id']){
                
                $part_collection = "SELECT ioq.line_number, ioq.ref_no, ioq.quantity , ioq.clei_override , i.heci, i.clei, i.short_description, im.name manf, iq.warranty_period_id warr
                    FROM inventory_outgoing_quote_invoice ioqi, inventory_outgoing_quote ioq, inventory_inventory i, inventory_manufacturer im, inventory_quote iq
                    WHERE ioqi.id = ".$line['oqi_id']." AND ioqi.oq_id = ioq.id AND ioq.inventory_id = i.id AND i.manufacturer_id_id = im.id AND ioq.quote_id = iq.id;";
                $parts_results = qdb($part_collection,"PIPE") or die(qe("PIPE")." ".$part_collection);
                
                $part_row = mysqli_fetch_assoc($parts_results);
                $partid = part_process($part_row);
                
                //Prep the array line
                $p_line["invoice_no"] = $meta['id'];
                $p_line["partid"] = $partid;
                $p_line["qty"] = $line['quantity'];
                $p_line["price"] = $line['amount'];
                $p_line["line_number"] = ($part_row['line_number']? $part_row['line_number'] : "");
                $p_line["ref_1"] = $part_row['ref_no'];
                $p_line["ref_1_label"] = "";
                $p_line["ref_2"] = $part_row['clei_override'];
                $p_line["ref_2_label"] = ($part_row['clei_override']? "CLEI" : "");
                $p_line["warranty"] = $WARRANTY_MAPS[$part_row['warr']];

                //Append the line information
                $insert_row['lines'][] = $p_line;
                continue;
            } else if($line['repair_id'] /*|| substr($line['memo'], 0, 6) == "Repair"*/){
                
                
                $p_line["invoice_no"] =  $meta['id'];
                
                $part_collection = "SELECT ir.serials, ir.price_per_unit, i.heci, i.clei, i.short_description , im.name manf, ir.warranty_id warr
                    FROM `inventory_repair` ir, inventory_inventory i, inventory_manufacturer im
                    WHERE ir.ticket_number = ".$line['repair_id']." AND ir.inventory_id = i.id AND i.manufacturer_id_id = im.id;";
                $parts_results = qdb($part_collection,"PIPE") or die(qe("PIPE")." ".$part_collection);
                

                $part_row = mysqli_fetch_assoc($parts_results);
                $partid = part_process($part_row);
                $quantity = count(explode("\n",$part_row['serials']));
                
                
                $p_line["partid"] = $partid;
                $p_line["qty"] = $quantity;
                $p_line["price"] = $part_row['price_per_unit'];
                $p_line["line_number"] = null;
                $p_line["ref_1"] = "";
                $p_line["ref_1_label"] = "";
                $p_line["ref_2"] = "";
                $p_line["ref_2_label"] = "";
                $p_line["warranty"] = $WARRANTY_MAPS[$part_row['warr']];
                //Run an audit check to see if there are any invoice records where 
                $insert_row['lines'][] = $p_line;
                continue;
            } else if($line['item'] == "Freight Charge" || $line['memo'] == "Freight"){
                $insert_row['freight'] = $line['amount'];
                $insert_row['notes'] .= " (Freight)";
                continue;
            } else if ($line['it_id'] || $line['item'] == "IT Service" || $line['item'] == "IT Contract"){
                $insert_row['amount'] = 0.00;
                $insert_row['notes'] .= $line['memo'];
                continue;
            // } else if (){
            //     $insert_row['repair_lines'][] = $line;
            } else {
                $part_info = '';
                //Secondary Item Processor, outputs a similar row to the item processor, but takes extra steps to parse memo
                if (substr($line['memo'], 0, 4) == "Item"){
                    $memo_params = array();
                    $memo_params = memo_walk($line['memo']);
                    $part_search = "
                    SELECT i.heci, i.clei, i.short_description, im.name manf 
                    FROM `inventory_inventory` i, `inventory_manufacturer` im 
                    WHERE i.manufacturer_id_id = im.id ";
                    $part_search .= sFilter("part_number", $memo_params['item']);
                    // $part_search .= sFilter("short_description", $memo_params['description']);
                    $part_search .= sFilter("heci",$memo_params['heci']);
                    $part_search .= ";";
                    $part_result = qdb($part_search,"PIPE") or die(qe("PIPE")." ".$part_search);
                    if (mysqli_num_rows($part_result)){
                        $closest = mysqli_fetch_assoc($part_result);
                        if (mysqli_num_rows($part_result)>1){
                            $beginning = 0;
                            foreach($part_result as $i => $row){
                                $row_weight = desc_weight_value($memo_params['description'],$row['short_description']);
                                if ($row_weight > $beginning){
                                    $closest = $row;
                                }
                            }
                        } 
                        $partid = part_process($closest);
                        
                        $p_line["invoice_no"] = $meta['id'];
                        $p_line["partid"] = $partid;
                        $p_line["qty"] = $line['quantity'];
                        $p_line["price"] = $line['amount'];
                        $insert_row['lines'][] = $p_line;
                    } else {
                        $result = explode(" ",$memo_params['item']);
                        $part_search = "
                        SELECT i.heci, i.clei, i.short_description, im.name manf 
                        FROM `inventory_inventory` i, `inventory_manufacturer` im 
                        WHERE i.manufacturer_id_id = im.id AND `part_number` LIKE '".$result[0];
                        $part_search .= "';";
                        $part_result = qdb($part_search,"PIPE") or die(qe("PIPE")." ".$part_search);
                    
                        // $failed_message .= print_r($memo_params,true);
                        // $failed_message .= print_r($part_result,true);
                    }
                    // These will take extra care to handle beyond the OQI id
                } elseif (substr($line['memo'], 0, strlen("Freight")) == "Freight"){
                    $insert_row['freight'] = $line['amount'];
                    $insert_row['notes'] .= " (Freight)";
                } else {
                // $failed_message .= "| Unprocessable Line Type ".print_r($line,true);
                $p_line["invoice_no"] = $meta["id"];
                // $p_line["warranty"] = "";
                $p_line["memo"] = $line['memo'];
                $insert_row['lines'][] = $p_line;
                }
            }
        }
        }
        $double = intval($meta['amount']);
        $lines_sum = intval($lines_sum);
        //Ignores
        if($lines_sum != $double){
            if(!$insert_row['status'] == "Voided"){
                $failed_message .= "| Sum Mismatch: I Count ".floatval($lines_sum)." (".gettype($lines_sum)."), he says ".floatval($double)." (".gettype($double).") ";
            }
        }
        // qdb()
        
        //Payment Processing
        if($meta['paid']){
            $payment_info['companyid'] = $companyid;
            $payment_info['date'] = $meta['paid_date'];
            $payment_info['payment_type'] = "Other";
            $payment_info['number'] = "Invoice".$insert_row['invoice_no'];
            $payment_info['amount'] = $lines_sum; //This amount will equal the audited amount on the line items, assuming no discrepency
            $payment_info['notes'] = "Imported";
            $payment_details['order_number'] = $insert_row['invoice_no'];
            $payment_details['order_type'] = "Invoice";
            $payment_details['ref_number'] = "";
            $payment_details['ref_type'] = "";
            $payment_details['amount'] = $lines_sum;
        }else{
            // $failed_message .= "| Not Paid For ";
        }
        
        //LUMP Processing Prep
        if($meta['lump_id']){ //These rows don't actually fail, I just want to see my lump print
            // $failed_message .= "| LUMP ROW ";
            $lump = $meta['lump_id'];
            if(!isset($lump_builder[$lump])){
                $lump_select = "SELECT `date` FROM inventory_invoicelump WHERE id = '$lump';";
                qdb($lump_select,"PIPE") or die(qe("PIPE")." ".$lump_select);
                $lump_builder[$lump]['date'] = '';
                $lump_builder[$lump]['companyid'] = $companyid;
            }
            $lump_builder[$lump]['items'][] = $meta['id'];
        }
        if ($debug || $failed_message){
        $grand_failed = true;
        echo"
            <tr>
                <td>
                    $failed_message<br>
                    <pre>";
                        print_r($meta);
        echo"       </pre>
                </td>
                <td>
                    <pre>";
                        print_r($insert_row);
        echo"       </pre>
                </td>
                <td>
                    <pre>";
                        print_r($payment_info);
        echo"       </pre>
                </td>
                <td>
                    <pre>";
                        print_r($payment_details);
        echo"       </pre>
                </td>
                <td>
                    <pre>";
                        if($meta['lump_id']){
                            print_r($lump_builder[$meta['lump_id']]);
                        }
        echo"       </pre>
                </td>
            </tr>

        ";
        } else {
            $insert = "
            INSERT INTO `invoices`(`invoice_no`, `companyid`, `date_invoiced`, 
            `order_number`, `order_type`, `shipmentid`, `freight`, `notes`, `status`) 
            VALUES (".$insert_row['invoice_no'].",
            ".prep($insert_row['companyid']).",
            ".prep($insert_row['date_invoiced']).",
            ".prep($insert_row['order_number']).",
            ".prep($insert_row['order_type']).",
            ".prep($insert_row['shipmentid']).",
            ".prep($insert_row['freight']).",
            ".prep($insert_row['notes']).",
            ".prep($insert_row['status']).");
            ";
            qdb($insert) or die(qe()." | $insert");
            
            if (count($insert_row['lines'])){
                foreach($insert_row['lines'] as $line){
                    $line_insert = "INSERT INTO `invoice_items`(`invoice_no`, `partid`, `qty`, `price`, `line_number`, `ref_1`, 
                    `ref_1_label`, `ref_2`, `ref_2_label`, `warranty`, `memo`) VALUES (
                    ".prep($line['invoice_no']).",
                    ".prep($line['partid']).",
                    ".prep($line['qty']).",
                    ".prep($line['price']).",
                    ".prep($line['line_number']).",
                    ".prep($line['ref_1']).",
                    ".prep($line['ref_1_label']).",
                    ".prep($line['ref_2']).",
                    ".prep($line['ref_2_label']).",
                    ".prep($line['warranty']).",
                    ".prep($line['memo']).")";
                    qdb($line_insert) or die(qe()." | $line_insert");
                }
            }
            if ($meta['paid']){
                $payment_insert = "
                INSERT INTO `payments`(`companyid`, `date`, `payment_type`, `number`, `amount`, `notes`) VALUES (
                ".prep($payment_info['companyid']).",
                ".prep($payment_info['date']).",
                ".prep($payment_info['payment_type']).",
                ".prep($payment_info['number']).",
                ".prep($payment_info['amount']).",
                ".prep($payment_info['notes']).");";
                
                qdb($payment_insert) or die(qe()." | $payment_insert");
                $p_details_insert = "INSERT INTO `payment_details`(`order_number`, `order_type`, `ref_number`, `ref_type`, `amount`, `paymentid`) 
                VALUES (
                ".prep($payment_details['order_number']).",
                ".prep($payment_details['order_type']).",
                ".prep($payment_details['ref_number']).",
                ".prep($payment_details['ref_type']).",
                ".prep($payment_details['amount']).",
                ".prep(qid()).")";
                qdb($p_details_insert) or die(qe()." | $p_details_insert");
            }
        }
        
        $count++;
    }
}  else {
    $insert = array();
    $import_complete = true;
}
    //Lump Builder
    
    if(!$grand_failed){
        //INSERT STATEMENT HERE!!
        echo"<tr><td colspan='5'>".$count." rows successfully added ($already_imported Already Added)</td></tr>";
        echo (($import_complete)? "<tr><td colspan='5'><b>IMPORT COMPLETE</b></td></tr>" : "");
    }
    // foreach ($lump_builder as $lump_row) {
    //     print_r($lump_row);
    // }
    echo"
        </table>
    ";
    echo($query);
    $end_time = microtime();
    $st_a = explode(" ",$start_time);
    $en_a = explode(" ",$end_time);
    
    // echo("Start: $start_time <br> End: $end_time<br>");
    echo("Time Per Row: ".((($en_a[1] - $st_a[1])+($en_a[0]-$st_a[0]))/$count)." s");
    // echo"
    // <pre>
    // ".print_r($insert,true)."
    // </pre>
    // ";
    
?>

<!--<script>-->
    <?php 
        // if(!$grand_failed){
        //     echo("setTimeout(location.reload.bind(location),1000);");
        // } 
        // if ($import_complete){
        //     echo("alert('Import Complete)';");
        // }
    ?>
<!--</script>-->