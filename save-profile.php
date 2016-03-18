<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';

	function updateContact($fieldname,$fieldvalue,$id=0) {
$type = '';//for now
		$query = "REPLACE ".$fieldname."s (".$fieldname.", type, contactid";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".$fieldvalue."',";
		if ($type) { $query .= "'".$type."',"; } else { $query .= "NULL,"; }
		$query .= "'".$contactid."'";
		if ($id) { $query .= ",'".$id."'"; }
		$query .= "); ";
		$result = qdb($query) OR reportError(qe().' '.$query);
		if (! $id) { $id = qid(); }

		return ($id);
	}

	$companyid = 0;
	$name = '';
	$title = '';
	$notes = '';
	$emails = array();
	$phones = array();
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) {
		$companyid = $_REQUEST['companyid'];
	}
	if (isset($_REQUEST['name'])) { $name = trim($_REQUEST['name']); }
	if (isset($_REQUEST['title'])) { $title = trim($_REQUEST['title']); }
	if (isset($_REQUEST['notes'])) { $notes = trim($_REQUEST['notes']); }
	if (isset($_REQUEST['emails']) AND is_array($_REQUEST['emails'])) { $emails = $_REQUEST['emails']; }
	if (isset($_REQUEST['phones']) AND is_array($_REQUEST['phones'])) { $phones = $_REQUEST['phones']; }

	$msg = 'Success';
	if (! $companyid OR ! $name) {
		$msg = 'Missing valid input data';
	} else {
		// is this valid input?
		$query = "SELECT id FROM companies WHERE companyid = '".$companyid."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) {
			$msg = 'Contact does not exist';
		}

		$query = "INSERT INTO contacts (name, title, notes, status, companyid) ";
		$query .= "VALUES ('".res($name)."',";
		if ($title) { $query .= "'".res($title)."',"; } else { $query .= "NULL,"; }
		if ($notes) { $query .= "'".res($notes)."',"; } else { $query .= "NULL,"; }
		$query .= "'Active','".res($companyid)."'); ";
		$result = qdb($query) OR die(qe().' '.$query);
		$contactid = qid();

		foreach ($emails as $id => $email) {
			$id = updateContact('email',$email,$id);
		}
		foreach ($phones as $id => $phone) {
			$id = updateContact('phone',$phone,$id);
		}
	}

	header('Location: /profile.php?companyid='.$companyid);
	exit;
?>
