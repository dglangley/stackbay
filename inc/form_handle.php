<?php
    
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
    
    function grab($string, $default = ''){
        return (!is_null($_REQUEST[$string]) ? trim($_REQUEST[$string]) :  $default);
    }
    
    function prep($var, $default = "NULL"){
        $output = ($var && $var != 'undefined') ? "'".res($var)."'" : $default;
    	return $output;
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
    
?>