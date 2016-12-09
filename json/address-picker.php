<?php

//=============================== Address Picker ===============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';

	$q = '';
	$output = array();
    $q = grab('q');
    $companyid = grab('limit');
    
	if ($q) { 
        
        $query = "SELECT * FROM `addresses`";
        $search = qdb($query);
	}
	if($companyid){
	    //If there is a value set for the company, load their defaults to the top result always.
	    $companyid = prep($companyid,"'25'");
	    
	    //
	    $d_bill = "Select count(`bill_to_id`) mode, max(`created`) recent, `bill_to_id` 
    	    FROM purchase_orders WHERE `companyid` = $companyid 
    	    AND DATE_SUB(CURDATE(),INTERVAL 365 DAY) <= `created` 
    	    GROUP BY `bill_to_id` 
    	    ORDER BY mode,recent 
    	    LIMIT 15;";
	    $default = qdb($d_bill);
	    foreach ($default as $row){
	        $line = array(
	            getAddresses($row['bill_to_id']);
                'id' => $row['bill_to_id'], 
                'text' => $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'],
                );
	        $output[] = $line;
	    }
	}
        
        foreach($results as $id => $row){
            $line = array(
                'id' => $row['id'], 
                'text' => $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'],
                );
            if (strpos(strtolower($line['text']),strtolower($q)) !== false){
                $output[] = $line;
            }
        }
        $output[] = array(
            'id' => "Add $q",
            'text' => "Add $q..."
            );

    
	echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>
