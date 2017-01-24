<?php
// AddressSubmit handles the submission of a new address from an addresses modal


		header('Content-Type: application/json');
		$rootdir = $_SERVER['ROOT_DIR'];
			
			include_once $rootdir.'/inc/dbconnect.php';
			include_once $rootdir.'/inc/format_date.php';
			include_once $rootdir.'/inc/format_price.php';
			include_once $rootdir.'/inc/getCompany.php';
			include_once $rootdir.'/inc/getPart.php';
			include_once $rootdir.'/inc/pipe.php';
			include_once $rootdir.'/inc/keywords.php';
			include_once $rootdir.'/inc/getRecords.php';
			include_once $rootdir.'/inc/getRep.php';
			include_once $rootdir.'/inc/form_handle.php';
			
			// function prep($var){
			// 	if($var){
			// 		$ressed = res($var);
			// 		$output = "'$ressed'";
			// 	}	
			// 	else{
			// 		$output = "NULL";
			// 	}
			// 	return $output;
			// }
            $address = $_POST['address'];
			// $name = prep($address["name"]);
			// $street = prep($address["line_1"].' '.$address["line2"]);
			// $city = prep($address["city"]);
			// $state = prep($address["state"]);
			// $zip = prep($address["zip"]);
			// $id = $address["id"];
            
            $name = prep(grab('name'));
			$line_1 = grab('line_1');
			$line2 = grab('line2');
			$street = prep($line_1.' '.$line2);
			$city = prep(grab('city'));
			$state = prep(grab('state'));
			$zip = prep(grab('zip'));
			$id = grab('id');
            			
            
            
            
            
            
            

		if($id){
			$update = "UPDATE `addresses` SET
						`name` = $name,
						`street` = $street,
						`city` = $city,
						`state` = $state,
						`postal_code` = $zip,
						`country` = 'US'
						Where `id` = $id;";
			qdb($update) or die(qe());
			echo json_encode($update);
			exit;
		}	
		else{
            
	        $insert = "INSERT INTO `addresses` (`name`, `street`, `city`, `state`, `postal_code`, `country`)  
	        VALUES ($name,$street,$city,$state,$zip,'US')";
	        

	        
	        qdb($insert);
            
            echo json_encode($address);
            exit;
		}
?>