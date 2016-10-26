<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';
	include '../inc/keywords.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
    $companyid = (isset($_REQUEST['limit']))? trim($_REQUEST['limit']) : '0'; 
    $output = array();
    
    $query = "SELECT * FROM `addresses` WHERE `companyid` = $companyid";
    $results = qdb($query);
        
    foreach($results as $id => $row){
        $line = array(
            'id' => $id, 
            'text' => $row['account_no']
        );
        $output[] = $line;
    }

    
	echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>
