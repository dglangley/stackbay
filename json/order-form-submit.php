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
		include_once $rootdir.'/inc/getAddresses.php';
		include_once $rootdir.'/inc/form_handle.php';

//=============================== Inputs section ==============================
    //Macros
    $order_type = $_REQUEST['order_type'];
    $order_number = $_REQUEST['order_number'];
    $form_rows = $_REQUEST['table_rows'];



    
    
    //Form Specifics
    $companyid = is_numeric($_REQUEST['companyid'])? trim($_REQUEST['companyid']) : trim(getCompany($_REQUEST['companyid'],'name','id'));
    $company_name = getCompany($companyid);
    $contact = grab('contact');
    $ship = grab('ship_to');
    $bill = grab('bill_to');
    $carrier = grab('carrier');
    $service = grab('freight');
    $account = grab('account');
    $private = (trim($_REQUEST['pri_notes']));
    $public = (trim($_REQUEST['pub_notes']));
    $rep = grab("sales-rep");

    $andrew = "wrong";

    if ($order_number == "New"){
        //If this is a new entry, save the value, insert the row, and return the
        //new-fangled ID from the mega-sketch qid function
        $rep = prep($rep);
        $ship = prep($ship);
        $bill = prep($bill); 
        $carrier = prep($carrier);
        $service = prep($service);
        $account = prep($account);
        $contact = prep($contact);
        
        $insert = "INSERT INTO ";
        $insert .= ($order_type=="Purchase") ? "`purchase_orders`" : "`sales_orders`";
        $insert .= " (`created_by`, `companyid`, `sales_rep_id`, `contactid`, `bill_to_id`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES 
        ($rep, $companyid, $rep, $contact, $bill, $ship, $carrier, $service, $account, NULL, $public, $private, 'Active');";
    
        //Run the update
        qdb($insert);
        
        //Create a new update number
        $order_number = qid();
    }
    else{
        
        //Note that the update field doesn't have all the requisite fields
        $macro = "UPDATE ";
        $macro .= ($order_type == "Purchase")? "`purchase_orders`" :"`sales_orders`";
        $macro .= " SET ";
        $macro .= updateNull('companyid',$companyid);
        $macro .= updateNull('sales_rep_id',$rep);
        $macro .= updateNull('contactid',$contact);
        $macro .= updateNull('bill_to_id',$bill);
        $macro .= updateNull('ship_to_id',$ship);
        $macro .= updateNull('public_notes',$public);
        $macro .= rtrim(updateNull('private_notes',$private),',');
        $macro .= " WHERE ";
        $macro .= ($order_type == "Purchase")? "`po_number`" :"`so_number`";
        $macro .= " = $order_number;";
        
        //Query the database

        qdb($macro);
    }
    

    $stupid = 0;
    
    
    //RIGHT HAND SUBMIT
    
    if(isset($form_rows)){
        foreach ($form_rows as $r){
            $stupid++;
            $line_number = prep($r[0]);
            $item_id = prep($r[1]);
            $record = $r[2];
            $date = prep(format_date($r[3],'y-m-d'));
            $warranty = prep($r[4]);
            $qty = prep($r[5]);
            $unitPrice = prep(format_price($r[6],true,'',true));
            
            if ($record == 'new'){
                
                //Build the insert statements
                $insert = "INSERT INTO ";
                $insert .=  ($order_type=="Purchase") ? "`purchase_items`" : "`sales_items`";
                $insert .=  " (`partid`, ";
                $insert .=  ($order_type=="Purchase") ? "`po_number`, " : "`so_number`, ";
                $insert .=  "`delivery_date`, `line_number`, `qty`, `price`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`,`warranty`, `id`) VALUES ";
                $insert .=   "($item_id, $order_number , $date, $line_number, $qty , $unitPrice , NULL, NULL, NULL, NULL, $warranty ,NULL);";
                
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
                `delivery_date` = $date,
                `warranty` = $warranty
                WHERE id = $record;";
                qdb($update);
            }
        }
    }

    
    //Return the meta data about the information submitted, including the order
    //type, number, and the inserted statement (for debugging purposes)

    $form = array(
        'type' => $order_type,
        'order' => $order_number,
        'insert' => qe().' '.$insert,
        'stupid' => $stupid
    );
    
    echo json_encode($form);
    exit;

?>