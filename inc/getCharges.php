<?php
    //Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/form_handle.php';
	
    function getCharges($order_number,$order_type){
        $o = o_params($order_type);
        $pon = prep($order_number);
        $select = "SELECT * FROM ".$o['charges']." where ".$o['id']." = $pon order by id asc;";
        $result = qdb($select) or die(qe()." $select");
        if(mysqli_num_rows($result)){ 
            return($result);
        } else {
            return null;
        }
    }
?>