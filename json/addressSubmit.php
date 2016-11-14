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
			
			function prep($var){
				if($var){
					$ressed = res($var);
					$output = "'$ressed'";
				}	
				else{
					$output = "NULL";
				}
				return $output;
			}

            $address = $_POST['test'];
            $name = prep($address[0]);
            $street = $address[1];
            $street .= ' '.$address[2];
            $street = prep($street);
            $city =  prep($address[3]);
            $state = prep($address[4]);
            $zip = prep($address[5]);
            
            
	        $insert = "INSERT INTO `addresses` (`name`, `street`, `city`, `state`, `postal_code`, `country`)  
	        VALUES ($name,$street,$city,$state,$zip,'US')";
	        

	        
	        qdb($insert);
            
?>