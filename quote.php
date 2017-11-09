<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getServiceClass.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';

	$order_type = 'service_quotes';
	// $task_edit = true; 
	$T = order_type($order_type);

	$quote = true;
	$EDIT = true;

	$order_number_details = (isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '');
	$tab = (isset($_REQUEST['tab']) ? $_REQUEST['tab'] : '');

	preg_match_all("/\d+/", $order_number_details, $order_number_split);

	$order_number_split = reset($order_number_split);

	$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	$task_number = ($order_number_split[1] ? $order_number_split[1] : '');

	if(! empty($order_number)) {
		$EDIT = false;
		$ORDER = getOrder($order_number, ucwords($order_type));
	}

	if($ORDER['classid']) {
		$service_class = getServiceClass($ORDER['classid']);
	}

	function getOrder($order, $type) { 
	    $results = array(); 
	 
	   	if(strtolower($type) == 'service_quotes') { 
			$query = "SELECT * FROM service_quotes WHERE id = ".res($order).";"; 
			$result = qdb($query) OR die(qe()); 
	       
	        // echo $query; 
	 
	        if (mysqli_num_rows($result)>0) { 
	        	$results = mysqli_fetch_assoc($result); 
	        } 
	    } 
	 
	    return $results; 
	} 

	include 'task_view.php';
	exit;
