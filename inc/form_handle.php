<?php
    
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
    
    function grab($string){
        return (!is_null($_REQUEST[$string]) ? trim($_REQUEST[$string]) :  null);
    }
    
    function updateNull($field,$var){
        if ($var){
            $return = " `$field`= '".res($var)."',";
        }
        else{
            $return = " `$field`= NULL,";
        }
        return $return;
    }
    
    function prep($var){
        $output = ($var) ? "'".res($var)."'" : "NULL";
    	return $output;
    }
?>