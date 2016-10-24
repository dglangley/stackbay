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
    
    //Form Specifics
    $companyid = $_REQUEST['companyid'];
    $company_name = getCompany($companyid);
    $rep = $_REQUEST['userid'];
    
    
    if ($order_number == "New"){
        //If this is a new entry, save the value, insert the row, and return the
        //new-fangled ID from the mega-sketch qid function
        $insert = "INSERT INTO";
        $insert .=    ($order_type=="Purchase") ? "`purchase_orders`" : "`sales_orders`";
        $insert .=    "(`partid`,";
        $insert .=    ($order_type=="Purchase") ? "`po_number`, " : "`so_number`, ";
        $insert .= "`created`,`created_by`,`companyid`,`sales_rep_id`,`contactid`,`bill_to_id`,
        `ship_to_id`,`freight_carrier_id`,`freight_services_id`,`freight_account_id`,
        `ref1`,`ref1_label`,`ref2`,`ref2_label`,`termsid`,`public_notes`,`private_notes`,
        `status`) 
        VALUES 
        (NULL,
        $rep,
        $companyid,
        $rep,
        NULL,
        [value-6],
        [value-7],
        [value-8],
        [value-9],
        [value-10],
        [value-11],
        [value-12],
        [value-13],
        [value-14],
        [value-15],
        [value-16],
        [value-17],
        [value-18],
        'Active')";
        
        qdb($insert);
        $order_number = qid();
        $form = array(
            'type' => $company_name,
            'order' => $order
        );
    echo json_encode($form);
    }
    else{
        $update = "UPDATE ";
        $update .= ($order_type == "Purchase")? "`purchase_orders`" :"`sales_orders`";
        " SET 
        `created`=[value-2],
        `created_by`=[value-3],
        `companyid`=[value-4],
        `sales_rep_id`=[value-5],
        `contactid`=[value-6],
        `bill_to_id`=[value-7],
        `ship_to_id`=[value-8],
        `freight_carrier_id`=[value-9],
        `freight_services_id`=[value-10],
        `freight_account_id`=[value-11],
        `termsid`=[value-12],
        `public_notes`=[value-13],
        `private_notes`=[value-14],
        `status`=[value-15] 
        WHERE";
        $update .= ($order_type == "Purchase")? "`po_number`" :"`so_number`";
        $update .= "='$order_number'";
    }

    exit;
?>