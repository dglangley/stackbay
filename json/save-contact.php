<?php
	$NO_CACHE = true;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	header("Content-Type: application/json", true);

	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid']) AND $_REQUEST['contactid']>0) {
		$contactid = $_REQUEST['contactid'];
	}
	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) {
		$companyid = $_REQUEST['companyid'];
	}
	$name = grab('name');
	$contact_name = prep($name);
	$contact_title = prep(grab('title'));
	$email = grab('email');
	$contact_email = prep($email);
	$contact_notes = prep(grab('notes'));

	$msg = 'Success';

	// updating existing contact
	if ($contactid) {
		$query = "UPDATE contacts SET name = $contact_name, title = $contact_title, notes = $contact_notes, companyid = $companyid ";
		$query .= "WHERE id = '".res($contactid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		if ($email) {
			$query = "DELETE FROM emails WHERE contactid = '".res($contactid)."'; ";
			$result = qdb($query) OR jsonDie(qe().' '.$query);
		}
	} else {//creating new contact
		$query = "INSERT INTO contacts (name, title, notes, companyid) ";
		$query .= "VALUES ($contact_name, $contact_title, $contact_notes, $companyid); ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		$contactid = qid();
	}

	if ($email) {
		$query = "INSERT INTO emails (email, type, contactid) VALUES ($contact_email,'Work','".res($contactid)."'); ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
	}

	echo json_encode(array('message'=>$msg,'contactid'=>$contactid,'name'=>$name));
	exit;
?>
