<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;
	$ALERT = '';

	function editSubscriptions($subid, $name, $subsciption, $emails) {
		global $ALERT;
		// First check and see if the class name already exists
		$query = "SELECT * FROM subscriptions WHERE nickname = ".fres($name)." AND subscription = ".fres($subscription).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$ALERT = urlencode("Subscription email already exists.");
			return 0;
		}

		$query = "INSERT INTO service_classes (class_name) VALUES (".fres($class_name).");";
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

	$emailids = '';
	if (isset($_REQUEST['emailids'])) { $emailids = trim($_REQUEST['emailids']); }

	$subid = 0;
	if (isset($_REQUEST['subid'])) { $subid = trim($_REQUEST['subid']); }

	$name = 0;
	if (isset($_REQUEST['name'])) { $name = trim($_REQUEST['name']); }

	$subsciption = 0;
	if (isset($_REQUEST['subsciption'])) { $subsciption = trim($_REQUEST['subsciption']); }

	editSubscriptions($subid, $name, $subsciption, $emails);

	
	header('Location: /subscriptions.php' . ($ALERT?'?ALERT='.$ALERT:''));

	exit;

	if ($DEBUG) { exit; }

	?>