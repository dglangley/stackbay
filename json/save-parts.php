<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/indexer.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$allowed_fields = array('part','heci','description','manfid','systemid');

	$field = '';
	if (isset($_REQUEST['field']) AND array_search($_REQUEST['field'],$allowed_fields)!==false) {
		$field = $_REQUEST['field'];
	}
	$partid = 0;
	if (isset($_REQUEST['partid']) AND is_numeric($_REQUEST['partid'])) {
		$partid = $_REQUEST['partid'];
	}
	if ($field=='manfid' OR $field=='systemid') {
		$new_value = 0;
		if (isset($_REQUEST['new_value']) AND is_numeric($_REQUEST['new_value']) AND $_REQUEST['new_value']>0) {
			$new_value = $_REQUEST['new_value'];
		}
	} else {
		$new_value = '';
		if (isset($_REQUEST['new_value'])) {
			$new_value = urldecode(trim($_REQUEST['new_value']));
		}
	}

	// confirm part existence
	$query = "SELECT * FROM parts WHERE id = '".$partid."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	if (mysqli_num_rows($result)<>1) {
		reportError("Could not find part in db");
	}

	$query = "UPDATE parts SET ".$field." = '".$new_value."' WHERE id = '".$partid."' LIMIT 1; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	// update keywords index for this part
	indexer($partid,'id');

	echo json_encode(array('message'=>'Success'));
	exit;
?>
