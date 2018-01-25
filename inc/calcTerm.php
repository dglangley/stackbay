<?php
//Standard includes section
include_once $_SERVER['ROOT_DIR'] .'/inc/dbconnect.php';
include_once $_SERVER['ROOT_DIR'] .'/inc/format_date.php';
include_once $_SERVER['ROOT_DIR'] .'/inc/order_type.php';

	function calcTerm($order_number, $order_type){
		$dueDate = '';

		$T = order_type($order_type);

		$query = " SELECT ".$T['datetime']." as created, days FROM ".$T['orders'].", terms WHERE termsid = terms.id and ".$T['order']." = ".fres($order_number).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			if($r['days'] > 0){
				$dueDate = format_date($r['created'], "n/j/Y", array("d"=>$r['days']));
			} else {
				$dueDate = format_date($r['created'], "n/j/Y");
			}
		}

		return($dueDate);
	}
