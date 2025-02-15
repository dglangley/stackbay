<?php

//=============================== Account Default ==============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/getWarranty.php';

	$q = '';
    $companyid = prep(grab('company'));
    $order = o_params(grab('order_type'));
    
    $line = array();
	
	if($companyid && !$order['repair']){
	    //If there is a value set for the company, load their defaults to the top result always.
	    //$companyid = prep($companyid,"'25'");
	    
        $select = "SELECT warranty, COUNT(*) mode FROM ".$order['tables']." AND companyid = $companyid GROUP BY warranty ORDER BY mode DESC limit 21;";
	    
	    if ($carrier && $carrier != 'NULL'){
	    	$select .= "AND carrier = '$carrier'";
	    }
	    $select .= ";";
	    $result = qdb($select) or die(qe()." $select");
		if (mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
            $line['value'] = $row['warranty'];
            $line['warranty'] = getWarranty($row['warranty'],'warranty');
		}
		else{
	        $id = (($order['sales'])? getWarrByDays(30) : getWarrByDays(90));
        	$line['value'] = $id;
        	$line['display'] = getWarranty($id,'warranty');
		}
	} else {
		$line['value'] = "null";
		$line['display'] = "none";
	}
	echo json_encode($line);
	exit;
?>
