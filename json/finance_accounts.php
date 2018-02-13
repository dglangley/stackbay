<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFinancialAccounts.php';

	header("Content-Type: application/json", true);

    if (isset($_REQUEST['filter'])) { $filter = $_REQUEST['filter']; }

    if($filter == "Check") {
        $filter = "Checking";
    }

    $results = array();

    $results = getFinancialAccounts($filter);
	
	echo json_encode($results);
	exit;
