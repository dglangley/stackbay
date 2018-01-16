<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';

	// Parameter for filtering the sourcing requests
	$task_filter = isset($_REQUEST['taskid']) ? $_REQUEST['taskid'] : '';
	//Query items from parts table
	$itemList = array();

	$query = "SELECT pr.*, p.part, p.classification FROM purchase_requests pr, parts p WHERE p.id = pr.partid AND pr.item_id_label = 'quote_item_id' ";
	if($task_filter) {
		$query .= "AND item_id = ".fres($task_filter)." ";
	}
	$query .= "ORDER BY requested DESC LIMIT 100;";
	$result = qdb($query) OR die(qe());
		
	while ($row = $result->fetch_assoc()) {
		$itemList[$row['partid']][] = $row;
	}

	$sourcing = true;

	$title = "Sourcing Requests";

	include 'requests.php';
	exit;
