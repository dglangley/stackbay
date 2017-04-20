<?php
    
  
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
	include_once $rootdir.'/inc/calcRepairCost.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/filter.php';

//Function Declarations
    $INVENTORY_IDS = array();
	function translateID($inventory_id){
	    global $INVENTORY_IDS;
	    if (!isset($INVENTORY_IDS[$inventory_id])){
	        $query = "SELECT i.heci, i.clei, i.short_description, im.name manf
	        FROM inventory_inventory i, inventory_manufacturer im 
	        WHERE i.id = ".prep($inventory_id)." AND i.manufacturer_id_id = im.id;";
	        $result = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
	        $r = mysqli_fetch_assoc($result);
	        $INVENTORY_IDS[$inventory_id] = part_process($r);
	    }
	    return($INVENTORY_IDS[$inventory_id]);
	}
	function part_process($r){
		//Function takes in a row from Brian's inventory table, checks to 
		//see if it exists in our system, and sets part if it doesn't
		//Requires the inclusion of both getPartID.php and setPartId.php
		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
		else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		$partid = getPartId($r['part_number'],$r['heci']);
		if (! $partid) {
			$partid = setPart(array('part'=>$r['part_number'],'heci'=>$r['heci'],'manf'=>$r['manf'],'descr'=>$r['short_description']));
		}
		return $partid;
	}
   	function memo_walk($memo){
        //Brian has Item/Description values parsing through his memo collumn
            $result = array();
            $desc = explode(";",$memo);
            if (!$desc[1]){
                $desc = explode("\n",$memo);
            }
            foreach($desc as $label){
            	$instance = explode(":", $label);
            	if ($instance[1]){
	            	$result[trim(strtolower($instance[0]))] = trim($instance[1]);
            	} else {
            		$result['note'] = $instance[0];
            	}
            }
        return $result;
    }
    function splitDesc($data, $start, $end){
        //Function which splits apart a space separated description field
        $data = ' ' . $data;
        $initial = strpos($data, $start);
        if ($initial == 0)
            return '';                
        $initial += strlen($start);
        $length = strpos($data, $end, $initial) - $initial;
        return substr($data, $initial, $length);
    }
    function desc_weight_value($desc,$inv){
        //Function which wanders to find the most similar item to a description
        $weight = 0;
        $desc = explode(" ",$desc);
        $inv = explode(" ",$inv);
        foreach($desc as $d){
            foreach($inv as $i){
                if($i == $d){
                    $weight++;
                }
            }
        }
        return $weight;
    }

	
	$WARRANTY_MAPS = array(
		0 =>0,
		1 =>4,/*30 days*/
		2 =>1,/*AS IS*/
		3 =>2,/*5 days*/
		4 =>5,/*45 days*/
		5 =>7,/*90 days*/
		6 =>8,/*120 days*/
		7 =>10,/*1 year*/
		8 =>11,/*2 years*/
		9 =>12,/*3 years*/
		10 =>13,/*lifetime*/
		11 =>9,/*6 months*/
		12 =>6,/*60 days*/
		13 =>3,/*14 days*/
		14 =>9,/*180 days*/
	);


    
    //Parameter where, if true, then all the values have been properly imported
    $import_complete = false;
    
    $query = "SELECT id, date as date_created, vendor_id as companyid, memo, sent, amount,
    voided, voided_by_id, voided_date, voided_reason, paid, due_date, postfix, ref_no
    FROM inventory_bill;";
    $result = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
    
    $count = 0;
    $repair_records = array();
    


foreach($result as $meta){
    $p_line = array();
    $failed_message = "";
    $insert_row = array();
    $payment_info = array();
    $payment_details = array();
    //Notes are assumed null, but appended with potentially multiple fields
    $insert_row['notes'] = '';
    
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
    if($meta['sent']){
        $insert_row['status'] = "Active";
    } else if($meta['voided_by_id']) {
        $insert_row['status'] = "Void";
        $insert_row['notes'] .= " Voided by ".$meta['voided_by_id']." ".$meta['voided_date']." Reason: ".$meta['voided_reason'];
    } else {
        $insert_row['note'] .= " (Not Sent, Not Voided)";
    }
    
    //=============================== Notes ===============================
    $insert_row['notes'] .= $meta["memo"];

    
    
    //========================== LINE PROCESSING ==========================
    $lines = "SELECT bli.id,  bli.memo memo, iqi_id, iiqi.iq_id, iiqi.quantity, iiq.company_id, iiq.price as amount, creator_id,  i.heci, i.clei, i.short_description, im.name manf, ipi.po_id po_number
    FROM inventory_billli bli, inventory_incoming_quote_invoice iiqi, inventory_incoming_quote iiq, inventory_inventory i, inventory_manufacturer im, inventory_purchaseinvoice ipi
    WHERE bill_id = ".prep($insert_row['bill_no'])." AND iqi_id > '' AND iqi_id = iiqi.id AND iiqi.iq_id = iiq.id and iiq.inventory_id = i.id and i.manufacturer_id_id = im.id AND iiqi.purchase_invoice_id = ipi.id;"; //http://stackoverflow.com/questions/2327029/checking-for-an-empty-field-with-mysql
    // exit($lines);
    $line_results = qdb($lines,"PIPE") or die(qe("PIPE")." ".$lines);
    
    //Prep the sum for each of the line totals to do the SUM audit
    $lines_sum = 0.00;
    
    // if(!mysqli_num_rows($line_results)){
    //     // $failed_message = "| No Rows ";
    //     $p_line["bill_no"] = $meta["id"];
    //     $p_line["memo"] = "Not an original line: added to fix amount";
    //     $p_line["amount"] = $meta['amount'];
    //     $p_line["quantity"] = 1;
    //     if ($meta['memo']){
    //         $memo_parts = memo_walk($line['memo']);
    //         if($memo_parts["po#"]){
    //             $insert_row['po_number'] = $memo_parts["po#"];
    //         }
    //     }
    //     if(!$insert_row['po_number']){
    //         $insert_row = array();
    //         continue;
    //     }

    //     $lines_sum = $meta["amount"];
    //     $insert_row['lines'][] = $p_line;
    // } else {
        foreach($line_results as $line){
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
 
        qdb($insert) or die(qe()."<br>".$count);//"<br>".$previous_query."<br>Count".$count."<br><pre>".print_r(get_defined_vars(),true)."</pre>");
        
        
        if (count($insert_row['lines'])){
            foreach($insert_row['lines'] as $line){
                $line_insert = "INSERT INTO `bill_items`(`bill_no`, `partid`, `qty`, `amount`, `memo`) VALUES (
                ".prep($insert_row['bill_no']).",
                ".prep($line['partid']).",
                ".prep($line['qty']).",
                ".prep($line['amount']).",
                ".prep($line['memo']).");";
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
    $count++;
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

