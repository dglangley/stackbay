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


            $data = $_POST['test'];
            $account = prep($data[0]);
            $carrier = prep($data[1]);
            $company = prep($data[2]);
            
            
            
            
            $insert = "INSERT INTO `freight_accounts`(`account_no`, 
            `carrierid`, `companyid`, `id`) VALUES 
            ($account,$carrier,$company,NULL)";
            
	        

	        
	        qdb($insert);
            
            echo json_encode(qid());
            exit;
?>
