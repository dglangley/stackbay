<?php

//=============================================================================
//========================= Order Table Submit Template =======================
//=============================================================================
// The order table submit will work with the values of the individual lines of|
// the submitted pages. It will handle each of the rows, and return the       |
// success message upon its completion. This will allow the page to refresh   |
// upon completion of the page.                                               |
//                                                                            | 
// Last update: Aaron Morefield - October 18th, 2016                          |
//=============================================================================	

header('Content-Type: application/json');
    
    //The table
    $form_rows = $_REQUEST['table_rows'];
    $mess = array();
    $order_type = $_REQUEST['order_type'];
    $order_number = $_REQUEST['order_number'];

    $insert = "INSERT INTO";
    $insert .=    ($order_type=="p") ? "`purchase_items`" : "`sales_items`";
    $insert .=    "(`partid`,";
    $insert .=    ($order_type=="p") ? "`po_number`, " : "`so_number`, ";
    $insert .=    "`line_number`, `qty`, `price`, `delivery_date`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`, `id`) VALUES ";
    foreach ($form_rows as $r){
        $line_number = $r[0];
        $item_id = $r[1];
        $date = $r[2];
        $qty = $r[3];
        $unitPrice = $r[4];
        
        //Build the insert statements
        $insert.=
            "($item_id,
            $line_number,
            $qty,
            $price,
            $date,
            NULL,
            NULL,
            NULL,
            NULL,
            NULL,
            NULL),";
    }
    
    $return = array(
        'type' => '',
        'order' => ''
        );
    echo json_encode();
    exit;
?>