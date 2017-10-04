<?php
    $rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';

	function getRepairCode($repair_code){
		$desc = '';

		if(! empty($repair_code)) {
			$query = "SELECT description FROM repair_codes WHERE id = ".res($repair_code).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);
			
			if(mysqli_num_rows($result)) {
				$r = mysqli_fetch_assoc($result);
				$desc = $r['description'];
			}
		}

		return $desc;
	}
?>