<?php

//=============================== Address Default ==============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';

	
	$q = '';
    $companyid = grab('company');
    $line = array();
	function address_out($address_id){
		//General function for handling the standard display of addresses
		$address = '';
		//Address Handling
		$row = getAddresses($address_id);
		$name = $row['name'];
		$street = $row['street'];
		$city = $row['city'];
		$state = $row['state'];
		$zip = $row['postal_code'];
		$country = $row['country'];
		
		//Address Output
		if($name){$address .= $name."<br>";}
		if($street){$address .= $street."<br>";}
		if($city && $state){$address .= $city.", ".$state;}
		else if ($city || $state){ ($address .= $city.$state);}
		if($zip){$address .= "  $zip";}
		
		return $address;
	}

	if($companyid){
	    //If there is a value set for the company, load their defaults to the top result always.
	    //$companyid = prep($companyid,"'25'");
	    
	    
	    
	    $d_bill = "Select count(`remit_to_id`) mode, max(`created`) recent, `remit_to_id`, a.`name`, a.`street`, a.`city`, a.`state`,a.`postal_code`
    	    FROM purchase_orders po, addresses a
    	    WHERE po.`remit_to_id` = a.`id` AND `companyid` = $companyid
    	    AND DATE_SUB(CURDATE(),INTERVAL 365 DAY) <= `created` 
    	    GROUP BY `remit_to_id` 
    	    ORDER BY mode,recent 
    	    LIMIT 15;";
	    $bill = qdb($d_bill);
		if ($bill){
			$row = mysqli_fetch_array($bill);
            $line['b_value'] = $row['remit_to_id'];
            $line['b_display'] = $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'];
		}
	
    $d_ship = "Select count(`ship_to_id`) mode, max(`created`) recent, `ship_to_id`, a.`name`, a.`street`, a.`city`, a.`state`,a.`postal_code`
	    FROM purchase_orders po, addresses a
	    WHERE po.`ship_to_id` = a.`id` AND `companyid` = $companyid
	    AND DATE_SUB(CURDATE(),INTERVAL 365 DAY) <= `created` 
	    GROUP BY `ship_to_id` 
	    ORDER BY mode,recent 
	    LIMIT 15;";
	
	$ship = qdb($d_ship);
		if ($ship){
			$row = mysqli_fetch_array($ship);
	        $line['s_value'] = $row['ship_to_id'];
	        $line['s_display'] = $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'];
	        
		}
	}
	
	echo json_encode($line);//array('results'=>$companies,'more'=>false));
	exit;
?>
