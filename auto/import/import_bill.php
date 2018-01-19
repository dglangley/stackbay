<?php
exit;    
  
    $start_time = microtime(); //Time per row check
    
//Standard includes section
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
	
    //Parameter where, if true, then all the values have been properly imported
    $import_complete = false;
    
    $query = "SELECT id, date as date_created, vendor_id as companyid, memo, sent, amount,
    voided, voided_by_id, voided_date, voided_reason, paid, due_date, postfix, ref_no
    FROM inventory_bill; ";// LIMIT 2500,500;";
    $result = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
    
    $count = 0;
    $repair_records = array();
    $inserted = "";
echo("<br>".$query);


foreach($result as $meta){
    $failed_message = "";
    $insert_row = array();
    $payment_info = array();
    $payment_details = array();
    $insert = "";

    //Notes are assumed null, but appended with potentially multiple fields
    $insert_row['notes'] = '';
    $insert_row['lines'] = array();
    //ID
    $insert_row['bill_no'] = $meta['id'];
    
    //Contact
    $companyid = dbTranslate($meta['companyid']);
    $insert_row['companyid'] = $companyid;
    
    //Date
    $insert_row['date_created'] = $meta['date_created'];
    //======================= Order Type Processing =======================
    
    //This portion of the process will probably vary drastically between the
    //value
    $insert_row['po_number'] = null;
    $insert_row['freight'] = null;
    

    $insert_row['invoice_no'] = $meta['ref_no'];
    
    //========================= Status processing =========================
    $insert_row['status'] = "Completed";
    if($meta['sent']){
        $insert_row['status'] = "Completed";
    } else if($meta['voided_by_id']) {
        $insert_row['status'] = "Voided";
        $insert_row['notes'] .= " Voided by ".$meta['voided_by_id']." ".$meta['voided_date']." Reason: ".$meta['voided_reason'];
    } else {
        $insert_row['note'] .= " (Not Sent, Not Voided)";
    }
    
    //=============================== Notes ===============================
    $insert_row['notes'] .= $meta["memo"];

    
    
    //========================== LINE PROCESSING ==========================
    $lines = "
    SELECT bli.id,  bli.memo memo, iqi_id, iiqi.iq_id, iiqi.quantity, 
        iiq.company_id, iiq.price as amount, creator_id, i.part_number,  i.heci, 
        i.clei, i.short_description, im.name manf, ipi.po_id po_number
    FROM inventory_billli bli, inventory_incoming_quote_invoice iiqi, inventory_incoming_quote iiq, inventory_inventory i, inventory_manufacturer im, inventory_purchaseinvoice ipi
    WHERE bill_id = ".prep($insert_row['bill_no'])." AND iqi_id > '' AND iqi_id = iiqi.id AND iiqi.iq_id = iiq.id and iiq.inventory_id = i.id and i.manufacturer_id_id = im.id AND iiqi.purchase_invoice_id = ipi.id;"; //http://stackoverflow.com/questions/2327029/checking-for-an-empty-field-with-mysql
echo("<br>".$lines);
    $line_results = qdb($lines,"PIPE") or die(qe("PIPE")." ".$lines);
    
    //Prep the sum for each of the line totals to do the SUM audit
    $lines_sum = 0.00;
    if(mysqli_num_rows($line_results) > 0){
        foreach($line_results as $line){
            $p_line = array();
            //Naive Line Pricing information to audit the amount 
            $lines_sum += $line['amount'] * $line['quantity'];
            //The following acts as a switch would to process different types of
            $partid = part_process($line);
            
            //Grab any associated PO_number, including the ones which he hides in his memo if there is none linked
            $insert_row['po_number'] = $line['po_number'];
            if(!$insert_row['po_number']){
                if(!mysqli_num_rows($line_results)){
                    $memo_parts = memo_walk($line['memo']);
                    if($memo_parts["po#"]){
                        $insert_row['po_number'] = $memo_parts["po#"];
                    }
                }
            } 
            //Prep the array line
            $p_line["bill_no"] = $meta['id'];
            $p_line["partid"] = $partid;
            $p_line["qty"] = $line['quantity'];
            $p_line["amount"] = $line['amount'];
            $p_line["line_number"] = null;
            $p_line["warranty"] = $WARRANTY_MAPS[$part_row['warr']];
            
            //Append the line information
            $insert_row['lines'][] = $p_line;
        }
    }
    // }
    if(!$insert_row['po_number']){
        $insert_row = array();
        continue;
    }
    // Method | Line audit
    // Since the cents audit seems to be invalid despite extensive type casting efforts,
    // Parse both values down to integers and compare them
    // $double = intval($meta['amount']);
    // $lines_sum = intval($lines_sum);
    //Ignores
    // if($lines_sum != $double && ($meta['amount'] != floatval($lines_sum))){
    //     if(!$meta['voided'] == "Voided"){
    //         // $failed_message .= "| Sum Mismatch: I Count ".floatval($lines_sum)." (".gettype($lines_sum)."), he says ".floatval($double)." (".gettype($double).") ";
    //     }
    // }



        //Payment Processing
        if($meta['paid']){
            $payment_info['companyid'] = $companyid;
            $payment_info['date'] = $meta['due_date'];
            $payment_info['payment_type'] = "Other";
            $payment_info['number'] = "Bill".$insert_row['bill_no'];
            $payment_info['amount'] = $lines_sum; //This amount will equal the audited amount on the line items, assuming no discrepency
            $payment_info['notes'] = "Imported";
            $payment_details['order_number'] = $insert_row['bill_no'];
            $payment_details['order_type'] = "Bill";
            $payment_details['ref_number'] = "";
            $payment_details['ref_type'] = "";
            $payment_details['amount'] = $lines_sum;
        }
    
        $insert = "
        INSERT INTO `bills` (`bill_no`,`invoice_no`,`date_created`,`due_date`,`po_number`,`companyid`,`notes`,`status`) VALUES (
        ".prep($insert_row['bill_no']).",
        ".prep($insert_row['invoice_no']).",
        ".prep($insert_row['date_created']." 12:00:00").",
        ".prep($meta['due_date']).",
        ".prep($insert_row['po_number']).",
        ".prep($insert_row['companyid']).",
        ".prep($insert_row['notes']).",
        ".prep($insert_row['status']).");
        ";
echo($insert."<br>");
        $inserted .= $meta['id'].", ";
        qdb($insert) or die(qe()."<br>".$count."<br>".$inserted);//"<br>".$previous_query."<br>Count".$count."<br><pre>".print_r(get_defined_vars(),true)."</pre>");

        if ($insert_row['lines']){
            foreach($insert_row['lines'] as $i_line){
echo($line_insert."<br>");
                $line_insert = "INSERT INTO `bill_items`(`bill_no`, `partid`, `qty`, `amount`, `memo`) VALUES (
                ".prep($insert_row['bill_no']).",
                ".prep($i_line['partid']).",
                ".prep($i_line['qty']).",
                ".prep($i_line['amount']).",
                ".prep($i_line['memo']).");";
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
echo($payment_insert."<br>");
            
            qdb($payment_insert) or die(qe()." | $payment_insert");
            $p_details_insert = "INSERT INTO `payment_details`(`order_number`, `order_type`, `ref_number`, `ref_type`, `amount`, `paymentid`) 
            VALUES (
            ".prep($payment_details['order_number']).",
            ".prep($payment_details['order_type']).",
            ".prep($payment_details['ref_number']).",
            ".prep($payment_details['ref_type']).",
            ".prep($payment_details['amount']).",
            ".prep(qid()).");";
echo($p_details_insert."<br>");
            qdb($p_details_insert) or die(qe()." | $p_details_insert");
        }
        $count++;
        unset($insert_row);
        unset($payment_info);
        unset($payment_details);
        unset($line_insert);
    }
    if(!$grand_failed){
        $import_complete = true;
        echo"<tr><td colspan='5'>".$count." rows successfully added ($already_imported Already Added)</td></tr>";
        echo (($import_complete)? "<tr><td colspan='5'><b>IMPORT COMPLETE</b></td></tr>" : "");
    }


    //Prints the average time to process a row (For the sake of efficiency)
    $end_time = microtime();
    $st_a = explode(" ",$start_time);
    $en_a = explode(" ",$end_time);
    echo("Time Per Row: ".((($en_a[1] - $st_a[1])+($en_a[0]-$st_a[0]))/$count)." s");
?>

