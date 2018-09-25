<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$conditionid = ($_REQUEST['conditionid']?:'');
	$locationdid = ($_REQUEST['locationid']?:'');

	// Function checks the validity of adding a part into a certain location based on the location and the condition of the inventory item
	function checkLocation($locationid, $conditionid) {
		$query = "SELECT passive FROM locations WHERE id = ".res($locationid).";";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);
			$passive = $r['passive'];

			if($passive AND $conditionid >= 0) {
				return 1;
			}
		}

		return 0;
	}

	$conflict = checkLocation($locationid, $conditionid);

	echo json_encode(array('conflict'=>$conflict,'message'=>''));
	exit;
?>
