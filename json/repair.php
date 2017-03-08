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
			include_once $rootdir.'/inc/jsonDie.php';

            $invid = prep(grab('invid'));
            $status = prep(grab('status'));
            
            $update;
			
			if(grab('status') == "in-repair") {
				$update = "UPDATE `inventory` SET `status`= 'shelved' WHERE `id` = $invid;";
			} else {
				$update = "UPDATE `inventory` SET `status`= 'in-repair' WHERE `id` = $invid;";
			}
			
			$result = qdb($update) OR jsonDie(qe().' '.$update);
		    
            echo json_encode($result);
            exit;
?>