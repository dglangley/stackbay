<?php

//=============================================================================
//========================= Order Form Submit Template ========================
//=============================================================================
// This script is a JSON text which will handle the left side of the orders   |
// pages on general saves and creates. It should be flexible to allow users   |
// to create new fields with minimal updates, and handle the varience between | 
// new and pre-existing forms. Primarily, this will work with the orders table|
//                                                                            | 
// Last update: Aaron Morefield - October 18th, 2016                          |
//=============================================================================
    
    //Prepare the page as a JSON type
	header('Content-Type: application/json');
	
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/pipe.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
	
//=============================== Inputs section ==============================
    //Macros
    $order_type = $_REQUEST['order_type'];
    $order_number = $_REQUEST['order_number'];
    $form_rows = $_REQUEST['table_rows'];

    
    //Form Specifics
    $companyid = is_numeric($_REQUEST['companyid'])? $_REQUEST['companyid'] : getCompany($_REQUEST['companyid'],'name','id');
    $company_name = getCompany($companyid);
    $rep = is_numeric($_REQUEST['sales-rep'])? $_REQUEST['sales-rep'] :  '0';
    $ship = is_numeric($_REQUEST['ship_to'])? $_REQUEST['ship_to'] :  '0';
    $bill = is_numeric($_REQUEST['bill_to'])? $_REQUEST['bill_to'] :  '0';
    $carrier = is_numeric($_REQUEST['carrier'])? $_REQUEST['carrier'] :  '0';
    $service = is_numeric($_REQUEST['freight'])? $_REQUEST['freight'] :  '1';
    $account = is_numeric($_REQUEST['account'])? $_REQUEST['account'] :  '0';
    $private = $_REQUEST['pri_notes'];
    $public = $_REQUEST['pub_notes'];
    $contact = is_numeric($_REQUEST['sales-rep'])? $_REQUEST['sales-rep'] :  '0';

    $andrew = "wrong";
    
    if ($order_number == "New"){
        //If this is a new entry, save the value, insert the row, and return the
        //new-fangled ID from the mega-sketch qid function
        $update = "INSERT INTO ";
        $update .= ($order_type=="Purchase") ? "`purchase_orders`" : "`sales_orders`";
        $update .= " (`created_by`, `companyid`, `sales_rep_id`, `contactid`, `bill_to_id`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES 
        ('$rep', '$companyid', '$rep', '$contact', '$bill', '$ship', '$carrier', '$service', '$account', NULL, '$public', '$private', 'Active')";
    
        //Run the update
        qdb($update);
        
        //Create a new update number
        $order_number = qid();
        $andrew = "wright";
    }
    else{
        
        //Note that the update field doesn't have all the requisite fields
        $update = "UPDATE ";
        $update .= ($order_type == "Purchase")? "`purchase_orders`" :"`sales_orders`";
        $update .= " SET 
        `companyid`= $companyid,
        `sales_rep_id`= $rep,
        `contactid`= '$contact',
        `bill_to_id`= $bill,
        `ship_to_id`= $ship,
        `public_notes`= '$public',
        `private_notes`= '$private' 
        WHERE ";
        $update .= ($order_type == "Purchase")? "`po_number`" :"`so_number`";
        $update .= "='$order_number'";
        
        //Query the database
        qdb($update);
    }
    
    $stupid = 0;
    //RIGHT HAND SUBMIT
    foreach ($form_rows as $r){
        $stupid++;
        $line_number = $r[0];
        $item_id = $r[1];
        $record = $r[2];
        $date = format_date($r[3],'y-m-d');
        $qty = $r[4];
        $unitPrice = format_price($r[5],true,'',true);
        
        if ($record == 'new'){
            //Build the insert statements
            $insert = "INSERT INTO ";
            $insert .=  ($order_type=="Purchase") ? "`purchase_items`" : "`sales_items`";
            $insert .=  " (`partid`,";
            $insert .=  ($order_type=="Purchase") ? "`po_number`, " : "`so_number`, ";
            $insert .=  "`delivery_date`, `line_number`, `qty`, `price`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`, `id`) VALUES ";
            $insert .=   "('$item_id', '$order_number' , '$date','$line_number', $qty, $unitPrice, NULL, NULL, NULL, NULL, NULL);";
            
            qdb($insert);
        }
        else{
            $update = "UPDATE ";
            $update .= ($order_type=="Purchase") ? "`purchase_items`" : "`sales_items`";
            $update .= " SET 
            `partid`= $item_id,
            `line_number`= $line_number,
            `qty`= $qty,
            `price`= $unitPrice,
            `delivery_date` = '$date'
            WHERE id = $record;";
            qdb($update);
        }
    }

    
    //Return the meta data about the information submitted, including the order
    //type, number, and the inserted statement (for debugging purposes)
    $form = array(
        'type' => $order_type,
        'order' => $order_number,
        'insert' => $update,
        'stupid' => $stupid
    );
    
    echo json_encode($form);
    exit;

?>