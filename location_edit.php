<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;
	$ALERT = '';

	function addLocation($place, $instance) {
		global $ALERT;
		// First check and see if the class name already exists
		$query = "SELECT * FROM locations WHERE place = ".fres($place)." AND instance = ".fres($instance).";";
		$result = qedb($query);

		// echo $query;

		if(mysqli_num_rows($result) > 0) {
			$ALERT = urlencode("Location already exists.");
			return 0;
		}

		$query = "INSERT INTO locations (place, instance) VALUES (".fres($place).", ".fres($instance).");";
		qedb($query);

		return qid();
	}

	function editLocation($locationid, $place, $instance) {
		global $ALERT;

		$query = "REPLACE INTO locations (place, instance, id) VALUES (".fres($place).", ".fres($instance).", ".res($locationid).");";
		qedb($query);
	}

	function checkLocationUsage($locationid) {
		$used = true;

		$query = "SELECT * FROM inventory WHERE locationid = ".$locationid.";";
		$result = qedb($query);
		
		if(! qnum($result)) {
			$used = false;
		}

		return $used;
	}

	function deleteLocation($locationid) {
		global $ALERT;
		$notUsed = checkLocationUsage($classid);

		// notUsed true means not being used
		if($notUsed) {
			$query = "DELETE FROM locations WHERE id = ".fres($locationid).";";
			qedb($query);
		} else {
			$ALERT = urlencode("Please do not try to hack the system!");
			return 0;
		}
	}

	function editPassive($passive) {
		$query = "UPDATE locations SET passive = 0;";
		qedb($query);

		foreach($passive as $locationid => $value) {
			$query = "UPDATE locations SET passive = ".fres(($value == 'on' ? 1 : 0))." WHERE id = ".res($locationid).";";

			qedb($query);
		}
	}

	$locationid = 0;
	if (isset($_REQUEST['locationid'])) { $locationid = trim($_REQUEST['locationid']); }

	$place = '';
	if (isset($_REQUEST['place'])) { $place = trim($_REQUEST['place']); }

	$instance = '';
	if (isset($_REQUEST['instance'])) { $instance = trim($_REQUEST['instance']); }

	$passive_new = '';
	if (isset($_REQUEST['passive_new'])) { $passive_new = $_REQUEST['passive_new']; }

	$passive = array();
	if (isset($_REQUEST['passive'])) { $passive = $_REQUEST['passive']; }

	$passive_edit = false;
	if (isset($_REQUEST['passive_edit'])) { $passive_edit = $_REQUEST['passive_edit']; }

	if(! $locationid AND ! $passive_edit) {
		$locationid = addLocation($place, $instance);
		// die();
	} else {
		// editLocation($locationid, $place, $instance);
		editPassive($passive);
		//die();
	}

	
	header('Location: /location_management.php' . ($locationid ? '?locationid=' . $locationid : '') . ($ALERT?'?ALERT='.$ALERT:''));

	exit;

	if ($DEBUG) { exit; }

	?>