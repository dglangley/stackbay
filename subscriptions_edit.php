<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;
	$ALERT = '';

	function addSubscriptions($name, $subscription) {
		global $ALERT;
		// First check and see if the class name already exists
		$query = "SELECT * FROM subscriptions WHERE nickname = ".fres($name)." AND subscription = ".fres($subscription).";";
		$result = qedb($query);

		// echo $query;

		if(mysqli_num_rows($result) > 0) {
			$ALERT = urlencode("Subscription email already exists.");
			return 0;
		}

		$query = "INSERT INTO subscriptions (nickname, subscription) VALUES (".fres($name).", ".fres($subscription).");";
		qedb($query);

		return qid();
	}

	function editSubscriptions($subid, $name, $subscription, $emailids) {
		global $ALERT;

		$query = "REPLACE INTO subscriptions (nickname, subscription, id) VALUES (".fres($name).", ".fres($subscription).", ".res($subid).");";
		qedb($query);
		$subid = qid();

		// Delete all previous email ids attached to this description
		$query = "DELETE FROM subscription_emails WHERE subscriptionid = ".res($subid).";";
		qedb($query);

		foreach($emailids as $emailid) {
			$query = "INSERT INTO subscription_emails (subscriptionid, emailid) VALUES (".res($subid).", ".res($emailid).");";
			qedb($query);
		}
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

	$emailids = array();
	if (isset($_REQUEST['emailids'])) { $emailids = $_REQUEST['emailids']; }

	$subid = 0;
	if (isset($_REQUEST['subid'])) { $subid = trim($_REQUEST['subid']); }

	$name = '';
	if (isset($_REQUEST['name'])) { $name = trim($_REQUEST['name']); }

	$subscription = '';
	if (isset($_REQUEST['subscription'])) { $subscription = trim($_REQUEST['subscription']); }

	if(! $subid) {
		$subid = addSubscriptions($name, $subscription);
		// die();
	} else {
		// print_r($emailids);
		editSubscriptions($subid, $name, $subscription, $emailids);
		//die();
	}

	
	header('Location: /subscriptions.php' . ($subid ? '?subscription=' . $subid : '') . ($ALERT?'?ALERT='.$ALERT:''));

	exit;

	if ($DEBUG) { exit; }

	?>