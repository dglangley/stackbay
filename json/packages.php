<?php
    //On creation of a new package, this will be the piece which stores the package
    function package_save(){
        
    }
    
    //On load of a new page, this function will handle the output of the respective
    //function
    function package_output(){
        
    }
    
//Main
    //Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	header('Content-Type: application/json');

    include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/form_handle.php';

function packages(){
    $action = grab('action');
    $order_number = grab('order');
    $name = grab('name');
    
    if ($action == 'addition'){
        $order_number = prep($order_number);
        $name = prep($name);
        $insert = "INSERT INTO `packages`(`order_number`,`package_no`) VALUES ($order_number, $name);";
        qdb($insert) OR die(qe());
        
        return qid();
        
    }
    elseif($action == "update"){
        $row_id = prep(grab('id'));
        
        $update = "UPDATE packages SET ";
        $update .= updateNull("width",grab("width"));
        $update .= updateNull("height",grab("height"));
        $update .= updateNull("length",grab("length"));
        $update .= updateNull("weight",grab("weight"));
        $update .= updateNull("tracking_no",grab("tracking"));
        $update .= rtrim(updateNull("freight_amount",grab("freight")),',');
        $update .= " WHERE ";
        $update .= "id = $row_id;";
        
        qdb($update);
        return $update;
    }
    elseif($action == "change"){
        $assoc = grab('assoc');
        $new = prep(grab('package'));
        $update = "Not Updated";
        if($assoc && $new){
            $update = "UPDATE package_contents SET packageid = $new WHERE serialid = $assoc";
            qdb($update);
        }
        return $update;
        
    }
    elseif($action == "delete"){
        $assoc = grab('assoc');
        $new = prep(grab('package'));
        $update = "Not Deleted";
        if($assoc && $new){
            $update = "DELETE FROM package_contents WHERE packageid = $new AND serialid = $assoc";
            qdb($update);
        }
        return $update;
        
    }
    else{
        return "Nothing.";
    }
}
    echo json_encode(packages());
?>