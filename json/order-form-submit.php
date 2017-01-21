<?php

//=============================================================================
//========================= Order Form Submit Template ========================
//=============================================================================
// This script is a JSON text which will handle the left side of the orders   |
// pages on general saves and creates. It should be flexible to allow users   |
// to create new fields with minimal updates, and handle the varience between | 
// new and pre-existing forms. Primarily, this will work with the orders table|
//                                                                            | 
// Last update: Aaron Morefield - November 28th, 2016                         |
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
    $service = grab('service');
    $account = grab('account');
    $tracking = grab('tracking');
    $private = (trim($_REQUEST['pri_notes']));
    $public = (trim($_REQUEST['pub_notes']));
    $terms = grab('terms');
    $rep = grab('sales-rep');
    $assoc_order = grab('assoc');

    //Process the contact, see if a new one was added
    if (!is_numeric($contact) && !is_null($contact) && ($contact)){
        $title = '';
        if (strpos($contact,'.')){
            list($title, $contact) = explode('.',$contact,2);
        }
        $contact = prep($contact);
        $title = prep($title);

        if ($contact != "NULL" && strtolower($contact) != "'null'"){
            $new_con = "INSERT INTO `contacts`(`name`,`title`,`notes`,`status`,`companyid`,`id`) 
            VALUES ($contact,$title,NULL,'Active',$companyid,NULL)";
            
            qdb($new_con);
            $contact = qid();
        }
    }
    

    if ($order_number == "New"){
        //If this is a new entry, save the value, insert the row, and return the
        //new-fangled ID from the mega-sketch qid function
        $cid = prep($companyid);
        $rep = prep($rep);
        $contact = prep($contact);
        $carrier = prep($carrier);
        $terms = prep($terms);
        $ship = prep($ship);
        $bill = prep($bill); 
        $public = prep($public);
        $private = prep($private);
        $service = prep($service);
        $account = prep($account);
        $assoc_order = prep($assoc_order);
        $tracking = prep($tracking);
        
        $insert = "INSERT INTO ";
        $insert .= ($order_type=="Purchase") ? "`purchase_orders`" : "`sales_orders`";
        $insert .= " (`created_by`, `companyid`, `sales_rep_id`, `contactid`, `assoc_order`,";
        // if ($order_type=="Purchase"){ $insert .= " `tracking_no`, ";}
        $insert .= " `bill_to_id`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`,
        `freight_account_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES 
        ($rep, $cid, $rep, $contact, $assoc_order, $bill, $ship, $carrier, $service, $account, $terms, $public, $private, 'Active');";
        

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
        $macro .= updateNull('termsid',$terms);
        $macro .= updateNull('assoc_order',$assoc_order);
        if ($order_type == "Purchase"){
            $macro .= updateNull('tracking_no',$tracking);
        }
        $macro .= updateNull('freight_carrier_id',$carrier);
        $macro .= updateNull('bill_to_id',$bill);
        $macro .= updateNull('ship_to_id',$ship);
        $macro .= updateNull('freight_services_id',$service);
        $macro .= updateNull('freight_account_id',$account);
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
            $line_number = prep($r['line_number']);
            $item_id = prep($r['part']);
            $record = $r['id'];
            $date = prep(format_date($r['date'],'Y-m-d'));
            $warranty = prep($r['warranty']);
            $condition = prep($r['condition']);
            $qty = prep($r['qty']);
            $unitPrice = prep(format_price($r['price'],true,'',true));
            
            
            if ($record == 'new'){
                
                //Build the insert statements
                $line_insert = "INSERT INTO ";
                $line_insert .=  ($order_type=="Purchase") ? "`purchase_items`" : "`sales_items`";
                $line_insert .=  " (`partid`, ";
                $line_insert .=  ($order_type=="Purchase") ? "`po_number`, `receive_date`, " : "`so_number`, `delivery_date`, ";
                $line_insert .=  "`line_number`, `qty`, `price`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`, `warranty`, `cond`, `id`) VALUES ";
                $line_insert .=   "($item_id, '$order_number' , $date, $line_number, $qty , $unitPrice , NULL, NULL, NULL, NULL, $warranty , $condition, NULL);";
                
                qdb($line_insert);
            }
            else{
                $update = "UPDATE ";
                $update .= ($order_type=="Purchase") ? "`purchase_items`" : "`sales_items`";
                $update .= " SET 
                `partid`= $item_id,
                `line_number`= $line_number,
                `qty`= $qty,
                `price`= $unitPrice, ";
    $update .=  ($order_type == "Purchase")? "
                `receive_date` = $date, " : "
                `delivery_date` = $date, ";
    $update .= "
                `warranty` = $warranty,
                `cond` = $condition 
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
        'line_insert' => $line_insert,
        'error' => qe(),
        'stupid' => $stupid,
        'update' => $macro,
        'trek' => $tracking,
        'input' => $insert
    );
    
    echo json_encode($form);
    exit;

?>