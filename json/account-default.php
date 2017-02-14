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
    $result;
    $line = array('value'=>'NULL','display'=>'PREPAID','carrier'=>'');
    
	if($companyid){
	    //If there is a value set for the company, load their defaults to the top result always.
	    //$companyid = prep($companyid,"'25'");
	    
	    $select = "SELECT * ";
	    $select .= "FROM freight_accounts fa ";
	    $select .= "WHERE ";
	    $select .= "companyid = '$companyid' ";
	    
	    if ($carrier && $carrier != 'NULL'){
	    	$select .= "AND carrierid = '$carrier'";
	    }
	    $select .= ";";
	    $result = qdb($select);
		if (mysqli_num_rows($result)>0) {
			$row = mysqli_fetch_array($result);
			$line['value'] = $row['id'];
			$line['display'] = $row['account_no'];
			$line['carrier'] = $row['carrierid'];
		}
	}

	echo json_encode($line);
	exit;
?>
