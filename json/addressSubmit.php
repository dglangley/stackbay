<?php
// AddressSubmit handles the submission of a new address from an addresses modal


		header('Content-Type: application/json');
		$rootdir = $_SERVER['ROOT_DIR'];
			
			include_once $rootdir.'/inc/dbconnect.php';
			include_once $rootdir.'/inc/format_date.php';
			include_once $rootdir.'/inc/format_price.php';
			include_once $rootdir.'/inc/getCompany.php';
			include_once $rootdir.'/inc/getPart.php';
			include_once $rootdir.'/inc/keywords.php';
			include_once $rootdir.'/inc/getRecords.php';
			include_once $rootdir.'/inc/getRep.php';
			include_once $rootdir.'/inc/form_handle.php';
			
            $address = $_POST['address'];
            
            $name = prep(grab('name'));
			$line_1 = grab('line_1');
			$line2 = grab('line2');
			$street = prep($line_1);
			$street2 = prep($line2);
			$city = prep(grab('city'));
			$state = prep(grab('state'));
			$zip = prep(grab('zip'));
			$id = grab('id');

		if($id != 'false' && $id){
			$update = "UPDATE `addresses` SET
						`name` = $name,
						`street` = $street,
						`addr2` = $street2,
						`city` = $city,
						`state` = $state,
						`postal_code` = $zip,
						`country` = 'US'
						Where `id` = $id;";
			qdb($update) or die(qe());
			$result['query'] = $update;
			$result['display'] = $street;
			echo json_encode($result);
			exit;
		}	
		else{
            
	        $insert = "INSERT INTO `addresses` (`name`, `street`, `addr2`, `city`, `state`, `postal_code`, `country`)  
	        VALUES ($name,$street,$street2,$city,$state,$zip,'US')";
	        

	        qdb($insert);
            
            echo json_encode(qid());
            exit;
		}
?>
