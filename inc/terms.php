<?php
//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];

	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/order_parameters.php'; 

	function terms_calc($order_number, $order_type){
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
	
	$TERMS_INFO = array();
	function getTermsInfo($value,$input = 'terms',$return = "id"){
		global $TERMS_INFO;

		if (isset($TERMS_INFO[$value]) AND isset($TERMS_INFO[$value][$input])) {
			return ($TERMS_INFO[$value][$input][$return]);
		}
		$TERMS_INFO[$value][$input] = array($return=>false);

		$select = "SELECT * FROM `terms` where `$input` LIKE ".prep($value).";"; //Assume days
		$result = qdb($select) or die(qe()." | $select");
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$TERMS_INFO[$value][$input] = $r;
		}
		return ($TERMS_INFO[$value][$input][$return]);
	}
	function getDays($id){
		return rsrq("SELECT `days` FROM `terms` where `id` = ".prep($id).";");
	}

?>
