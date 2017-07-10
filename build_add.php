<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function addBuild($name, $partid, $qty, $status){
		global $now;
		global $U;

		$insert = "INSERT INTO repair_orders (created, created_by, companyid, status) VALUES (
			".prep($now).",
			".prep($U['id']).",
			'25',
			".prep($status)."
		);";
		qdb($insert) or die(qe());
		$ro_number = qid();


		$insert = "INSERT INTO repair_items (partid, ro_number, qty) VALUES (
			".prep($partid).",
			".prep($ro_number).",
			".prep($qty)."
		);";
		qdb($insert) or die(qe());

		$insert = "INSERT INTO builds (name, partid, ro_number, status) VALUES (
			".prep($name).",
			".prep($partid).",
			".prep($ro_number).",
			".prep($status)."
		);";
		qdb($insert) or die(qe());
		$bo_number = qid();

		return $bo_number;
	}

	function editBuild($name, $partid, $qty, $status, $id) {
		$insert = "UPDATE builds SET name = ".prep($name).", partid = ".prep($partid).", status = ".prep($status)." WHERE id = ".prep($id).";";
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
	if (isset($_REQUEST['qty'])) { $qty = $_REQUEST['qty']; }
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
