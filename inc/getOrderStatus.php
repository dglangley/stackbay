<?php
    
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/order_parameters.php';

    function getOrderStatus($type,$number){
        if ($number == 'New'){
            return('Active');
        }else{
            $o = o_params($type);
            if($o['case'] || !$type){
                die("The page you are looking for doesn't exist. Please go back and try again");
            }
            if($type != 'Repair') {
                $select = "SELECT `status` FROM ".$o['order']." WHERE ".$o['id']." = $number;";
            } else {
                $select = "SELECT rc.`description` as status FROM ".$o['order']." ro, repair_codes as rc  WHERE ".$o['id']." = $number AND ro.repair_code_id = rc.id;";
            }
            $result = qdb($select) or die(qe()." $select");
            if(mysqli_num_rows($result)){
                $result = mysqli_fetch_assoc($result);
                return($result['status']);
            } else if($type == 'Repair') {
                return('');
            } else{
                return($o['status_empty']);
            }
        }
    }

?>
