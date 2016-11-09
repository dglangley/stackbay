<?php
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
			
            $address = $_POST['test'];
            $name = $address[0];
            $street = $address[1];
            $street .= ' '.$address[2];
            $city = $address[3];
            $state = $address[4];
            $zip = $address[5];
            
            
	        $insert = "INSERT INTO `addresses` (`name`, `street`, `city`, `state`, `postal_code`, `country`)  
	        VALUES ('$name','$street','$city','$state','$zip','US')";
	        
	        
	        qdb($insert);
            
?>