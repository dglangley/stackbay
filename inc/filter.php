<?php
	$rootdir = $_SERVER['ROOT_DIR'];
    include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/format_price.php';
    include_once $rootdir.'/inc/form_handle.php';
    
	function sFilter($field, $value, $first=false){
		if ($value){
			$value = prep($value);
			$andwhere = ($first)?" WHERE ":" AND ";
			$string = " $andwhere $field = $value ";
		}
		else{
			$string = '';
		}
		return $string;
	}
	
	function dFilter($field, $start = '', $end = '', $first = false){
		$andwhere = ($first)?" WHERE ":" AND ";
		if ($start and $end){
	   		$start = prep(format_date($start, 'Y-m-d'));
	   		$end = prep(format_date($end, 'Y-m-d'));
	   		$string = " $andwhere $field between CAST($start AS DATE) and CAST($end AS DATE) ";
		}
		else if($start){
			$start = prep(format_date($start, 'Y-m-d'));
			$string = " $andwhere CAST($field AS DATE) >= CAST($start AS DATE) ";
		}
		else if($end){
			$end = prep(format_date($end, 'Y-m-d'));
			$string = " $andwhere CAST($field AS DATE) <= CAST($end AS DATE) ";
		}
		else{
			$string = '';
		}
		return $string;
	}
?>