<?php

	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/keywords.php';
	include_once '../inc/getContact.php';
	include_once '../inc/getContacts.php';
	include_once '../inc/form_handle.php';

    $q = grab('q');

    
    
    $order_type = prep(grab('limit'),"Purchase"); 
    $output = array();
    $number = ($order_type == 'Purchase') ? 'po_number' : 'so_number';


	$query = "SELECT po_number, name FROM `purchase_orders`, `companies` WHERE companyid = companies.id AND (`po_number` LIKE '%$q%' OR `name` LIKE '%$q%');";
// 	$query .= " OR WHERE ;";
    
    $results = qdb($query);
    
    if (isset($results)){
        foreach($results as $row){
            $line = array(
                'id' => $row['po_number'], 
                'text' => $row['po_number']." - ".$row['name']
            );
            $output[] = $line;
        }
    }
    
    echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>