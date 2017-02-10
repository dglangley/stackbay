<?php

//=============================== Address Default ==============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';

	
	$q = '';
    $companyid = grab('company');
    $order_type = grab('order');
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
	
	$ps = ($order_type == "Purchase")? 'po' : 'so' ;
	$ot = ($order_type == "Purchase")? 'purchase_orders' : 'sales_orders' ;
	$br = ($order_type == "Purchase")? 'remit_to_id' : 'bill_to_id';
	if($companyid){
	    //If there is a value set for the company, load their defaults to the top result always.
		    $d_bill = "Select count(`$br`) moden, max(`created`) recent, `$br`, a.`name`, a.`street`, a.`city`, a.`state`, a.`postal_code`
	    	    FROM $ot $ps, addresses a
	    	    WHERE $ps.`$br` = a.`id` AND `companyid` = $companyid
	    	    AND DATE_SUB(CURDATE(),INTERVAL 365 DAY) <= `created` 
	    	    GROUP BY `$br` 
	    	    ORDER BY moden,recent 
	    	    LIMIT 15;";
		    $bill = qdb($d_bill);
			if (mysqli_num_rows($bill) > 0){
				$row = mysqli_fetch_array($bill);
	            $line['b_value'] = $row[$br];
	            // $line['b_display'] = $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'];
	            $line['b_name'] = $row['name'];
	            $line['b_street'] = $row['street'];
	            $line['b_city'] = $row['city'];
	            $line['b_state'] = $row['state'];
	            $line['b_postal_code'] = $row['postal_code'];
			}
			
		if ($order_type == "Purchase"){
			$d_ship = "SELECT a.`name`, a.`street`, a.`city`, a.`state`, a.`postal_code`, `id` ship_to_id FROM addresses a WHERE `name` = 'Ventura Telephone';";
		}else{
			
	    $d_ship = "Select count(`ship_to_id`) moden, max(`created`) recent, `ship_to_id`, a.`name`, a.`street`, a.`city`, a.`state`, a.`postal_code`
		    FROM $ot $ps, addresses a
		    WHERE $ps.`ship_to_id` = a.`id` AND `companyid` = $companyid
		    AND DATE_SUB(CURDATE(),INTERVAL 365 DAY) <= `created` 
		    GROUP BY `ship_to_id` 
		    ORDER BY moden,recent 
		    LIMIT 15;";
		}
		
		$ship = qdb($d_ship);
			if (mysqli_num_rows($ship) > 0){
				$row = mysqli_fetch_array($ship);
		        $line['s_value'] = $row['ship_to_id'];
		        // $line['s_display'] = $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'];
				$line['s_name'] = $row['name'];
	            $line['s_street'] = $row['street'];
	            $line['s_city'] = $row['city'];
	            $line['s_state'] = $row['state'];
	            $line['s_postal_code'] = $row['postal_code'];
			}
	}
	// echo $d_bill.$d_ship; exit;
	echo json_encode($line);//array('results'=>$companies,'more'=>false));
	exit;
?>
