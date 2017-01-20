<?php

//=============================== Account Default ==============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';

	
	$q = '';
    $companyid = grab('company');
    $carrier = grab('carrier');
    
    $line = array();
	if($companyid && $carrier){
	    //If there is a value set for the company, load their defaults to the top result always.
	    //$companyid = prep($companyid,"'25'");
	    
	    $select = "SELECT * ";
	    $select .= "FROM freight_accounts fa ";
	    $select .= "WHERE ";
	    $select .= "companyid = '$companyid' ";
	    
	    if ($carrier){
	    	$select .= "AND carrier = '$carrier'";
	    }
	    $select .= ";";
	    echo $select;
	    
	    $result = qdb($select);
		if ($result){
			$row = mysqli_fetch_array($result);
            $line['value'] = $row['id'];
            $line['display'] = $row['account_no'];
		}
	}

	echo json_encode($line);
	exit;
?>
