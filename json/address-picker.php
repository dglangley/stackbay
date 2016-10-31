<?php

//=============================== Address Picker ===============================
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';
	include '../inc/keywords.php';

	$q = '';
	$output = array();
	if (isset($_REQUEST['q'])) { 
	    $q = trim($_REQUEST['q']);
        $companyid = (isset($_REQUEST['limit']))? trim($_REQUEST['limit']) : '0'; 
    
        $query = "SELECT * FROM `addresses`";
        $results = qdb($query);
        
        foreach($results as $id => $row){
            $line = array(
                'id' => $row['id'], 
                'text' => $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'],
                );
            if (strpos(strtolower($line['text']),strtolower($q)) !== false){
                $output[] = $line;
            }
        }
	}

    
	echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>
