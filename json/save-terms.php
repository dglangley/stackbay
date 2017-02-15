<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	header("Content-Type: application/json", true);

	$companyid = 0;
	$category = '';
	$termsids = array();
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) {
		$companyid = $_REQUEST['companyid'];
	}
	if (isset($_REQUEST['category']) AND (strtoupper($_REQUEST['category'])=='AR' OR strtoupper($_REQUEST['category'])=='AP')) {
		$category = $_REQUEST['category'];
	}
	if (isset($_REQUEST['termsids'])) { $termsids = $_REQUEST['termsids']; }

	if (! $companyid) {
		jsonDie('Missing companyid');
	} else if (! $category) {
		jsonDie('Missing category');
	} else if (count($termsids)==0) {
		jsonDie('Missing valid input data');
	}

	// is this valid input?
	$query = "DELETE FROM company_terms WHERE companyid = '".$companyid."'; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);

	$msg = 'Success';
	if (! is_array($termsids)) { $termsids = explode(',',$termsids); }
	foreach ($termsids as $termsid) {
		//validate the termsid
		$query = "SELECT * FROM terms WHERE id = '".res($termsid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		if (mysqli_num_rows($result)==0) {
			jsonDie("One or more of the terms you entered are invalid");
		}

		$query = "INSERT INTO company_terms (companyid, termsid, category) ";
		$query .= "VALUES ('".$companyid."','".$termsid."','".$category."'); ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
	}

	echo json_encode(array('message'=>$msg));
	exit;
?>
