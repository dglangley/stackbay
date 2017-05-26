<?php
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/order_parameters.php';
	
	//Status check: see if items have been recieved for the order.
	function num_received($order_type,$item_id){
		if($item_id != 'new'){
			$o = o_params($order_type);
			$select = "SELECT count(invid) c FROM inventory_history WHERE field_changed = ".prep($o['inv_item_id'])." AND value = $item_id;";
			$results = qdb($select) or die(qe()." $select");
			$results = mysqli_fetch_assoc($results);
			return($results['c']);
		} else {
			return 0;
		}
	}
?>