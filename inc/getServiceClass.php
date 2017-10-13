<?php
	include_once $_SERVER['ROOT_DIR'] . '/inc/dbconnect.php';
	
	function getServiceClass($classid ,$field = '') {
        $class = '';

        $query = "SELECT class_name FROM service_classes WHERE id=".res($classid).";";
        $result = qdb($query) OR die(qe().' '.$query);

        if(mysqli_num_rows($result)) {
        	$r = mysqli_fetch_assoc($result);
        	$class = $r['class_name'];
        }

        return $class;
	}