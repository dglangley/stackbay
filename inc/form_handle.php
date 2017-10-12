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
    function rsrq($query){
    	//Return a single result query;
    	$result = qdb($query) or die(qe()." $query");
    	$return = false;
    	if (mysqli_num_rows($result) == 1 && mysqli_num_fields($result) == 1){
    		$fetched = mysqli_fetch_assoc($result);
    		$return = current($fetched);
    	} else if(mysqli_num_fields($result) > 1) {
    	    die("RSRQ: too many collumns returned, be more specific | $query");
    	} else if (mysqli_num_rows($result) > 1){
    	    die("RSRQ: too many rows returned: try using a key to limit results | $query");
    	}
    	return($return);
    }
  function prexit($array){
      // Alias for pretty printing a record for debug purposes;
      echo("<pre>");
      print_r($array);
      echo("</pre>");
      exit;
  }
?>
