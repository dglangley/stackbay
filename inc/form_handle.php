<?php
    
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
    
    function grab($string, $default = ''){
        return((!is_null($_REQUEST[$string]) && strtolower($_REQUEST[$string]) != 'null') ? trim($_REQUEST[$string]) :  $default);
    }
    
    function prep($var, $default = "NULL"){
        $output = ($var && $var != 'undefined') ? "'".res($var)."'" : $default;
    	return $output;
    }
    
    function updateNull($field,$var,$last=false,$default="NULL"){
        $return = " `$field`= ".prep($var,$default).",";
        if($last){
            $return = rtrim($return,",");
        }
        return $return;
    }
    
?>