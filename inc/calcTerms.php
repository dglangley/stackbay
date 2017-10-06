<?php
//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];

	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/order_parameters.php'; 

	function calcTerms($order_number, $order_type){
		$o = o_params($order_type);
		$due_select = "
		SELECT created, days FROM ".$o['order'].", terms WHERE termsid = terms.id and ".$o['id']." = ".prep($order_number).";";
		$due_estimate_result = qdb($due_select) or die(qe()." | $due_select");
		$due_estimate_arr = mysqli_fetch_assoc($due_estimate_result);
		if($due_estimate_arr['days'] > 0){
			$due_estimate = format_date($due_estimate_arr['created'], "n/j/Y", array("d"=>$due_estimate_arr['days']));
		} else {
			$due_estimate = format_date($due_estimate_arr['created'], "n/j/Y");
		}

		return($due_estimate);
	}
	
	function getDays($id){
		return rsrq("SELECT `days` FROM `terms` where `id` = ".prep($id).";");
	}

?>
