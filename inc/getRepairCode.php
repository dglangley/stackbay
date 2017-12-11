<?php
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';

	function getRepairCode($service_code, $type){
		$desc = '';
		$quer = '';

		if(! empty($service_code) AND $type == 'repair') {
			$query = "SELECT description FROM repair_codes WHERE id = ".res($service_code).";";
		} else if(! empty($service_code) AND $type == 'service') {
			$query = "SELECT description FROM status_codes WHERE id = ".res($service_code).";";
		} else {
			return ('');
		}

		$result = qdb($query) OR die(qe() . ' ' . $query);
			
		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$desc = $r['description'];
		}

		return $desc;
	}
?>
