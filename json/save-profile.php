<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_phone.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$contactid = 0;
	$change_field = '';
	$change_value = '';
	$fieldid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid']) AND $_REQUEST['contactid']>0) {
		$contactid = $_REQUEST['contactid'];
	}
	if (isset($_REQUEST['change_field'])) { $change_field = trim($_REQUEST['change_field']); }
	if (isset($_REQUEST['change_value'])) { $change_value = urldecode(trim($_REQUEST['change_value'])); }
	if (isset($_REQUEST['fieldid']) AND is_numeric($_REQUEST['fieldid']) AND $_REQUEST['fieldid']>0) { $fieldid = trim($_REQUEST['fieldid']); }

	$msg = 'Success';
	if (! $contactid OR ! $change_field OR ! $change_value) {
		reportError('Missing valid input data');
	}

	// is this valid input?
	$query = "SELECT * FROM contacts WHERE id = '".$contactid."'; ";
//	$query .= "AND companyid = '".$companyid."'; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)==0) {
		$msg = 'Contact does not exist';
	}

	if ($change_field=='email' OR $change_field=='phone') {
		// validate email
		if ($change_field=='email') {
			$valid_email = filter_var($change_value, FILTER_VALIDATE_EMAIL);
			if (! $valid_email) { reportError('Email "'.$change_value.'" is invalid!'); }
		} else if ($change_field=='phone') {
			$change_value = format_phone($change_value);
		}

		// confirm entry doesn't exist already first
		if (! $fieldid) {
			$query = "SELECT * FROM ".$change_field."s WHERE ".$change_field." = '".$change_value."' ";
			$query .= "AND contactid = '".$contactid."'; ";
			$result = qdb($query) OR reportError(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				reportError("This ".$change_field." already exists for this contact!");
			}
		}
//for now
$type = '';
		$query = "REPLACE ".$change_field."s (".$change_field.", type, contactid";
		if ($fieldid) { $query .= ", id"; }
		$query .= ") VALUES ('".$change_value."',";
		if ($type) { $query .= "'".$type."',"; } else { $query .= "NULL,"; }
		$query .= "'".$contactid."'";
		if ($fieldid) { $query .= ",'".$fieldid."'"; }
		$query .= "); ";
		$result = qdb($query) OR reportError(qe().' '.$query);
		if (! $fieldid) { $fieldid = qid(); }
	} else {
		$query = "UPDATE contacts SET ".res($change_field)." = '".res($change_value)."' ";
		$query .= "WHERE id = '".$contactid."'; ";// AND companyid = '".$companyid."'; ";
		$result = qdb($query) OR reportError(qe().' '.$query);
	}

	echo json_encode(array('message'=>$msg,'data'=>$change_value,'id'=>$fieldid));
	exit;
?>
