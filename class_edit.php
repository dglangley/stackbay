<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;
	$ALERT = '';

	function saveClass($class_name,$travel_rate,$classid=0) {
		global $ALERT;

		$class_name = trim($class_name);
		if (! $class_name) {
			$ALERT = urlencode("Class name is required");
			return 0;
		}

		// First check and see if the class name already exists
		$query = "SELECT * FROM service_classes WHERE class_name = ".fres($class_name).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$ALERT = urlencode("Class already exists.");
			return 0;
		}

		$query = "REPLACE service_classes (class_name, travel_rate";
		if ($classid) { $query .= ", id"; }
		$query .= ") VALUES ('".res($class_name)."', ".fres($travel_rate);
		if ($classid) { $query .= ", '".res($classid)."'"; }
		$query .= ");";
		qedb($query);
	}

	function deleteClass($classid) {
		global $ALERT;
		$notUsed = checkClassUsage($classid);

		// notUsed true means not being used
		if($notUsed) {
			$query = "DELETE FROM service_classes WHERE id = ".fres($classid).";";
			qedb($query);
		} else {
			$ALERT = urlencode("Please do not try to hack the system!");
			return 0;
		}
	}

	function checkClassUsage($classid) {
		// Default set action row to the delete
		$notUsed = true;

		$query = "SELECT * FROM user_classes WHERE classid = ".res($classid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$notUsed = false;
		}

		$query = "SELECT * FROM service_orders WHERE classid = ".res($classid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$notUsed = false;
		}

		$query = "SELECT * FROM service_quotes WHERE classid = ".res($classid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$notUsed = false;
		}

		return $notUsed;
	}

	$class_name = '';
	if (isset($_REQUEST['class_name'])) { $class_name = trim($_REQUEST['class_name']); }

	$travel_rate = '';
	if (isset($_REQUEST['travel_rate'])) { $travel_rate = trim($_REQUEST['travel_rate']); }

	$classid = 0;
	if (isset($_REQUEST['classid'])) { $classid = trim($_REQUEST['classid']); }

	$delid = 0;
	if (isset($_REQUEST['delid'])) { $delid = trim($_REQUEST['delid']); }

	if($delid) {
		deleteClass($delid);
	}

	if($class_name) {
		saveClass($class_name,$travel_rate,$classid);
	}

	if ($DEBUG) { exit; }

	header('Location: /class_management.php' . ($ALERT?'?ALERT='.$ALERT:''));

	exit;
?>
