<?php
//Standard includes section
include_once $_SERVER['ROOT_DIR'] .'/inc/dbconnect.php';
include_once $_SERVER['ROOT_DIR'] .'/inc/format_date.php';
include_once $_SERVER['ROOT_DIR'] .'/inc/order_type.php';

	function calcTerm($order_number, $order_type){
		$T = order_type($order_type);
		// $o = o_params($order_type);
		// $due_select = "
		// SELECT created, days FROM ".$o['order'].", terms WHERE termsid = terms.id and ".$o['id']." = ".prep($order_number).";";
		// $due_estimate_result = qdb($due_select) or die(qe()." | $due_select");
		// $due_estimate_arr = mysqli_fetch_assoc($due_estimate_result);
		// if($due_estimate_arr['days'] > 0){
		// 	$due_estimate = format_date($due_estimate_arr['created'], "n/j/Y", array("d"=>$due_estimate_arr['days']));
		// } else {
		// 	$due_estimate = format_date($due_estimate_arr['created'], "n/j/Y");
		// }

		return($dueDate);
	}
	
	// Only Used in an import script to killing it (/auto/import/import_component_bills.php)
	// function getDays($id){
	// 	return rsrq("SELECT `days` FROM `terms` where `id` = ".prep($id).";");
	// }

?>
