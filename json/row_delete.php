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
			
            $record = $_POST['id'];
			$order_type = $_POST['order'];

	        //Build the insert statements
	        $delete = "DELETE FROM ";
	        $delete .= ($order_type == 'Purchase')? '`purchase_items`' : '`sales_items`';
	        $delete .= " WHERE `id` = $record;";
	        
	         qdb($delete);

?>

