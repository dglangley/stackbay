<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/pipe.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$notes = array();

	$query = "SELECT CONCAT(notes,'\n',part_of) notes FROM inventory_inventory WHERE id IN (".$_REQUEST['pipe_ids']."); ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$notes[] = array('user'=>'','date'=>'n/a','note'=>str_replace(chr(10),'<BR>',utf8_encode($r['notes'])));
	}

	if (count($notes)==0) {
		$notes[] = array('user'=>'Brian','date'=>'8/05/16 5:07pm','note'=>'I will haunt you guys forever');
		$notes[] = array('user'=>'Chris','date'=>'4/21/16 10:07am','note'=>'You guys are bogus!!');
		$notes[] = array('user'=>'David','date'=>'3/13/16 17:35am','note'=>'This is a test');
		$notes[] = array('user'=>'Sam','date'=>'2/13/16 11:30am','note'=>'Sell this or die!');
	}

	echo json_encode(array('results'=>$notes));
	exit;
