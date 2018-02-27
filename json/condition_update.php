<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';

	$conditionid = '';
	if (isset($_REQUEST['conditionid'])) { $conditionid = trim($_REQUEST['conditionid']); }

	$condition = array();
	
	// Conditionid is required
	if($conditionid) {
		$query = "SELECT * FROM conditions WHERE id = ".$conditionid.";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);
			$condition = $r;
		}
	}

	header("Content-Type: application/json", true);
	echo json_encode($condition);
	exit;
?>
