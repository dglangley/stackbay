<?php

	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/keywords.php';
	include_once '../inc/getContact.php';
	include_once '../inc/getContacts.php';
	include_once '../inc/form_handle.php';

    $q = grab('q');

    
    //$companyid = (isset($_REQUEST['limit']))? trim($_REQUEST['limit']) : '0'; 
    $companyid = prep(grab('limit'),"'%'"); 
    $output = array();
    
    $query = "SELECT * FROM `freight_accounts` WHERE `account_no` LIKE '%$q%' AND `companyid` LIKE $companyid;";
    $results = qdb($query);
    
    if (isset($results)){
        foreach($results as $row){
            $line = array(
                'id' => $row['id'], 
                'text' => $row['account_no']
            );
            $output[] = $line;
        }
    }

    
    
    //Then append the rest of the contacts ordered by alphabetical
    $secondary = " SELECT DISTINCT * FROM `freight_accounts`
    WHERE `account_no` LIKE '%$q%' 
    ORDER BY `account_no`;";
    $second = qdb($secondary);
    // $output[] = array(
    //     'id' => 'NULL', 
    //     'text' => $secondary
    // );

    if (isset($second)){
        foreach($second as $id => $row){
            $line = array(
                'id' => $row['id'], 
                'text' => $row['name']
            );
            $output[] = $line;
        }
    }
    $output[] = array(
            'id' => "null",
            'text' => "PREPAID"
        );
    if (strlen($q) > 1){
        $output[] = array(
            'id' => "Add: $q",
            'text' => "Add: $q"
        );
    }
    
//	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));
/*    
    $items = array();
    if (strlen($q) > 1){
         $results = (hecidb($qlower));
         foreach($results as $id=> $row){
             $name = $row['part']." &nbsp; ".$row['heci'].' &nbsp; '.$row['Manf'].' '.$row['system'].' '.$row['Descr'];
             $items[] = array('id' => $id, 'text' => $name);
         }
    }
*/    


    
	echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>
