<?php
	header("Content-Type: application/json", true);

	include_once $_SERVER['ROOT_DIR']."/inc/dbconnect.php";
	include_once $_SERVER['ROOT_DIR']."/inc/keywords.php";
	include_once $_SERVER['ROOT_DIR']."/inc/jsonDie.php";
	include_once $_SERVER['ROOT_DIR']."/inc/indexer.php";

	$DEBUG = 0;

	$partid = 0;
	if (isset($_REQUEST['partid']) AND is_numeric($_REQUEST['partid'])) { $partid = trim($_REQUEST['partid']); }
	$part = '';
	if (isset($_REQUEST['part']) AND trim($_REQUEST['part'])) { $part = trim($_REQUEST['part']); }
	$heci = '';
	if (isset($_REQUEST['heci']) AND trim($_REQUEST['heci'])) { $heci = trim($_REQUEST['heci']); }
	$classification = 'equipment';
	if (isset($_REQUEST['class']) AND trim($_REQUEST['class'])) { $classification = trim($_REQUEST['class']); }
	$description = '';
	if (isset($_REQUEST['descr']) AND trim($_REQUEST['descr'])) { $description = trim($_REQUEST['descr']); }
	$manfid = 0;
	if (isset($_REQUEST['manfid']) AND is_numeric($_REQUEST['manfid'])) { $manfid = trim($_REQUEST['manfid']); }
	$systemid = 0;
	if (isset($_REQUEST['systemid']) AND is_numeric($_REQUEST['systemid'])) { $systemid = trim($_REQUEST['systemid']); }

	if (! $partid OR ($part OR $heci)) {// saving new part data
		if ($partid) {
			$query = "UPDATE parts SET part = ".fres($part).", rel = NULL, heci = ".fres($heci).", ";
			$query .= "manfid = ".fres($manfid).", systemid = ".fres($systemid).", ";
			$query .= "description = ".fres($description).", classification = ".fres($classification)." ";
			$query .= "WHERE id = '".res($partid)."'; ";
		} else {
			$query = "REPLACE parts (part, rel, heci, manfid, systemid, description, classification ";
			if ($partid) { $query .= ", id"; }
			$query .= ") VALUES (".fres($part).", NULL, ".fres($heci).", ".fres($manfid).", ".fres($systemid).", ";
			$query .= fres($description).", ".fres($classification)." ";
			if ($partid) { $query .= ", '".res($partid)."'"; }
			$query .= "); ";
		}
		$result = qedb($query);
		if (! $partid) { $partid = qid(); }
//	} else {
//		echo json_encode(array('message'=>'The data entered appears to be invalid, cannot continue'));
//		exit;
	}

	$H = hecidb($partid,'id');
	$P = $H[$partid];

	indexer($partid,'id');

	if ($DEBUG) { exit; }

	echo json_encode(array('message'=>'','results'=>$P));
	exit;
/*
    $action = grab('action');
    $partid = grab('partid');
    $part_arr = array(
      "name" => grab('name'),
      "heci" => grab('heci'),
      "desc" => grab('desc'),
      "manf" => grab('manf'),
      "system" => grab('system'),
      "class" => grab('class')
      );
    echo json_encode(part_action($action,$partid,$part_arr));

	// update keywords index for this part
	indexer($partid,'id');
*/
?>
