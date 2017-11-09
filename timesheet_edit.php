<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	function editTimesheet($data) {
		if(! empty($data)) {
			foreach($data as $key => $element) {
				$query = "UPDATE timesheets SET clockin = ".fres( $element['clockin'] ? date("Y-m-d H:i:s", strtotime($element['clockin'])) : '' ).", clockout = ".fres( $element['clockout'] ? date("Y-m-d H:i:s", strtotime($element['clockout'])) : '' )." WHERE id = ".res($key).";";
				// echo $query;
				qdb($query) OR die(qe() . ' ' . $query);
			}
		}
	}

	function deleteTimesheet($id) {
		$query = "DELETE FROM timesheets WHERE id = ".res($id).";";
		qdb($query) OR die(qe() . ' ' . $query);
	}

	// print '<pre>' . print_r($_REQUEST, true). '</pre>';

	$data = array();
	$id = 0;

	if (isset($_REQUEST['data'])) { $data = $_REQUEST['data']; }
	if (isset($_REQUEST['delete'])) { $delete = $_REQUEST['delete']; }

	// A second check to make sure that the user actually has the correct credentials to edit any time items
	if(in_array("4", $USER_ROLES)) {
		if(! empty($delete)) {
			deleteTimesheet($delete);
		} else {
			editTimesheet($data);
		}
	}

	header('Location: /timesheet.php');

	exit;