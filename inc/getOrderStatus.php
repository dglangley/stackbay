<?php
    
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/order_parameters.php';

    function getOrderStatus($type,$number){
        if ($number == 'New'){
            return('Active');
        }else{
            $o = o_params($type);
            $select = "SELECT `status` FROM ".$o['order']." WHERE ".$o['id']." = $number;";
            $result = qdb($select);
            if(mysqli_num_rows($result)>0){
                $result = mysqli_fetch_assoc($result);
                return($result['status']);
            }
            else{
                return($o['status_empty']);
            }
        }
    }

?>