<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getServiceClass.php';
	//include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';

	$quote = true;
	$type = 'quote';
	$task_edit = true; 

	$order_number_details = (isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '');
	$tab = (isset($_REQUEST['tab']) ? $_REQUEST['tab'] : '');

	preg_match_all("/\d+/", $order_number_details, $order_number_split);

	$order_number_split = reset($order_number_split);

	$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	$task_number = ($order_number_split[1] ? $order_number_split[1] : '');

	$ORDER = getOrder($order_number, ucwords($type));

	if($ORDER['classid']) {
		$service_class = getServiceClass($ORDER['classid']);
	}

	function getOrder($order, $type) { 
	    $results = array(); 
	 
	   	if(strtolower($type) == 'quote') { 
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
