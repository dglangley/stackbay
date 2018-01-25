<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	function addBuild($name, $partid, $qty, $status){
		$insert = "INSERT INTO repair_orders (created, created_by, companyid, status) VALUES (
			'".res($GLOBALS['now'])."',
			'".res($GLOBALS['U']['id'])."',
			'25',
			".fres($status)."
		);";
		qdb($insert) or die(qe());
		$ro_number = qid();


		$insert = "INSERT INTO repair_items (partid, ro_number, qty) VALUES (
			'".res($partid)."',
			'".res($ro_number)."',
			".fres($qty)."
		);";
		qdb($insert) or die(qe());

		$insert = "INSERT INTO builds (name, partid, ro_number, status, qty) VALUES (
			".fres($name).",
			'".res($partid)."',
			".fres($ro_number).",
			".fres($status).",
			".fres($qty)."
		);";
		qdb($insert) or die(qe());
		$bo_number = qid();

		return $bo_number;
	}

	function editBuild($name, $partid, $qty, $status, $id) {
		$insert = "UPDATE builds SET name = '".res($name)."', partid = '".res($partid)."', status = '".res($status)."' WHERE id = '".res($id)."';";
		qdb($insert) or die(qe());
	}
	
	//Declare variables
	$partid;
	$qty = 1;
	$name; 
	$status; 
	$id;
	$bo_number;
	
	if (isset($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }
	if (isset($_REQUEST['qty_variable'])) { $qty = $_REQUEST['qty_variable']; }
	if (isset($_REQUEST['name'])) { $name = $_REQUEST['name']; }
	if (isset($_REQUEST['status'])) { $status = $_REQUEST['status']; }
	if (isset($_REQUEST['build_id'])) { $id = $_REQUEST['build_id']; }


	//echo $name;
	if($id) {
		editBuild($name, $partid, $qty, $status, $id);
		$bo_number = $id;
	} else {
		$bo_number = addBuild($name, $partid, $qty, $status);
	}

	header('Location: /builds_management.php?on=' . $bo_number);
	exit;
