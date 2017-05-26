<?php	
	header('Content-Type: application/json');

		$rootdir = $_SERVER['ROOT_DIR'];
			
			include_once $rootdir.'/inc/dbconnect.php';
			
            $record = $_POST['id'];
			$order_type = $_POST['order'];

	        //Build the insert statements
	        $delete = "DELETE FROM ";
	        $delete .= ($order_type == 'Purchase')? '`purchase_items`' : '`sales_items`';
	        $delete .= " WHERE `id` = $record;";
	        
	         qdb($delete) or die(qe());

?>

