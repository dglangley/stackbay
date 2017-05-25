<?php

//=============================== Address Default ==============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';

	
	$q = '';
    $companyid = grab('company');
    $order_type = grab('order');
    $line = array();
	$o = o_params($order_type);
	$ps = $o['short'];
	// $ot = $o['order'];
	// $br = ($order_type == "Purchase")? 'remit_to_id' : 'bill_to_id';
	if($companyid){
	    //If there is a value set for the company, load their defaults to the top result always.
		    $d_bill = "Select count(`".$o['billing']."`) moden, max(`created`) recent, `".$o['billing']."`, a.`name`, a.`street`, a.`city`, a.`state`, a.`postal_code`
	    	    FROM ".$o['order'].", addresses a
	    	    WHERE `".$o['billing']."` = a.`id` AND `companyid` = $companyid
	    	    AND DATE_SUB(CURDATE(),INTERVAL 365 DAY) <= `created` 
	    	    GROUP BY `".$o['billing']."` 
	    	    ORDER BY moden desc,recent;";
			
			$b_lines = array();
		    $bill = qdb($d_bill) or die(qe().$d_bill);
			if (mysqli_num_rows($bill)){
				foreach ($bill as $row) {
					$b_lines[$row[$o['billing']]] = $row;
		            $b_lines[$row[$o['billing']]]['b_value'] = $row[$o['billing']];
		            // // $line['b_display'] = $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'];
		            // $line['b_name'] = $row['name'];
		            // $line['b_street'] = $row['street'];
		            // $line['b_city'] = $row['city'];
		            // $line['b_state'] = $row['state'];
		            // $line['b_postal_code'] = $row['postal_code'];
				}
			}
			
		if ($o['purchase']){
			$d_ship = "SELECT a.`name`, a.`street`, a.`city`, a.`state`, a.`postal_code`, `id` ship_to_id FROM addresses a WHERE `name` = 'Ventura Telephone';";
		}else{
			
	    $d_ship = "Select count(`ship_to_id`) moden, max(`created`) recent, `ship_to_id`, a.`name`, a.`street`, a.`city`, a.`state`, a.`postal_code`
		    FROM ".$o['order']." , addresses a
		    WHERE `ship_to_id` = a.`id` AND `companyid` = $companyid
		    AND DATE_SUB(CURDATE(),INTERVAL 365 DAY) <= `created` 
		    GROUP BY `ship_to_id` 
		    ORDER BY moden,recent;";
		}
		$s_lines;
		$ship = qdb($d_ship) or die(qe().$d_ship);
			if (mysqli_num_rows($ship)){
				foreach ($ship as $srow) {
					$s_lines[$srow['ship_to_id']] = $srow;
			        $s_lines[$srow['ship_to_id']]['s_value'] = $srow['ship_to_id'];					
				}
				// $row = mysqli_fetch_array($ship);
		  //      // $line['s_display'] = $row['name'].' <br> '.$row['street'].'<br>'.$row['city'].', '.$row['state'].' '.$row['postal_code'];
				// $line['s_name'] = $row['name'];
	   //         $line['s_street'] = $row['street'];
	   //         $line['s_city'] = $row['city'];
	   //         $line['s_state'] = $row['state'];
	   //         $line['s_postal_code'] = $row['postal_code'];
			}
	}
	// echo $d_bill.$d_ship; exit;
	$results = array(
		"bill" => $b_lines,
		"ship" => $s_lines
		);
	// echo("<pre>");
	// print_r($results);
	// echo"</pre>";
	// exit;
	echo json_encode($results);//array('results'=>$companies,'more'=>false));
	exit;
?>


