<?php
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';

	function getRepairCode($repair_code){
		$select = "SELECT description FROM repair_codes WHERE id = ".prep($repair_code).";";
		return rsrq($select);
	}
?>