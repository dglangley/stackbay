<?php
	$NO_CACHE = true;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	header("Content-Type: application/json", true);

	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid']) AND $_REQUEST['contactid']>0) {
		$contactid = $_REQUEST['contactid'];
	}
	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) {
		$companyid = $_REQUEST['companyid'];
	}

	$name = '';
	if (isset($_REQUEST['name']) AND trim($_REQUEST['name'])) { $name = trim($_REQUEST['name']); }
	$email = '';
	if (isset($_REQUEST['email']) AND trim($_REQUEST['email'])) { $email = trim($_REQUEST['email']); }
	$title = '';
	if (isset($_REQUEST['title']) AND trim($_REQUEST['title'])) { $title = trim($_REQUEST['title']); }
	$notes = '';
	if (isset($_REQUEST['notes']) AND trim($_REQUEST['notes'])) { $notes = trim($_REQUEST['notes']); }

	$msg = 'Success';

	// updating existing contact
	if ($contactid) {
		$query = "UPDATE contacts SET name = ".fres($name).", title = ".fres($title).", notes = ".fres($notes).", companyid = $companyid ";
		$query .= "WHERE id = '".res($contactid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		if ($email) {
			$query = "DELETE FROM emails WHERE contactid = '".res($contactid)."'; ";
			$result = qdb($query) OR jsonDie(qe().' '.$query);
		}
	} else {//creating new contact
		$query = "INSERT INTO contacts (name, title, notes, companyid) ";
		$query .= "VALUES (".fres($name).", ".fres($title).", ".fres($notes).", $companyid); ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		$contactid = qid();
	}

	if ($email) {
		$query = "INSERT INTO emails (email, type, contactid) VALUES (".fres($email).",'Work','".res($contactid)."'); ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
	}

	echo json_encode(array('message'=>$msg,'contactid'=>$contactid,'name'=>$name));
	exit;
?>
