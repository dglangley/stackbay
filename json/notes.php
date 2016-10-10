<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/pipe.php';
	include_once '../inc/format_date.php';
	include_once '../inc/format_price.php';
	include_once '../inc/getUser.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$pipe_ids = '';
	if (isset($_REQUEST['pipe_ids'])) {
		$pipe_ids = str_replace('undefined','',$_REQUEST['pipe_ids']);
	}
	$partid = 0;
	if (isset($_REQUEST['partid'])) {
		$partid = str_replace('undefined','',$_REQUEST['partid']);
	}

	$notes = array();

	if (! $pipe_ids) {
		echo json_encode(array('results'=>$notes));
		exit;
	}

	// if any new notes to add, add them first
	$add_notes = '';
	if (isset($_REQUEST['add_notes'])) {
		$add_notes = urldecode(trim($_REQUEST['add_notes']));

		$userid = $U['id'];
		if ($add_notes) {
			if ($partid) {
				$query = "INSERT INTO prices (partid, price, datetime, note, userid) ";
				$query .= "VALUES ('".$partid."',NULL,'".$now."','".res($add_notes)."','".$userid."'); ";
				$result = qdb($query) OR reportError('Sorry, there was an error adding your note to the new db. Please notify Admin immediately!');
			} else {
				reportError('Sorry, your note could not be added due to a missing partid. Please notify Admin immediately.');
			}
//			$notes[] = array('user'=>'Bot','date'=>date("n/j/y g:ia"),'note'=>str_replace(chr(10),'<BR>',utf8_encode($add_notes)));
		}
	}

	// get pricing notes from new system next
	$query = "SELECT note, price, datetime, userid FROM prices WHERE partid = '".res($partid)."' ORDER BY datetime DESC; ";
	$result = qdb($query) OR reportError('Sorry, there was an error getting notes from the new db. Please see Admin immediately!');
	while ($r = mysqli_fetch_assoc($result)) {
		$note = trim($r['note']);
		if ($r['price']) { $note = 'Price: '.format_price($r['price'],true).chr(10).$note; }
		$notes[] = array('user'=>getUser($r['userid']),'date'=>format_date($r['datetime'],'n/j/y g:ia'),'note'=>str_replace(chr(10),'<BR>',utf8_encode($note)));
	}

	$query = "SELECT CONCAT(notes,'\n',part_of) notes FROM inventory_inventory WHERE id IN (".$pipe_ids.") AND REPLACE('Bot Generated','',notes) <> ''; ";
	$result = qdb($query,'PIPE') OR reportError('Sorry, there was an error getting notes through the PIPE. Please see Admin immediately!');//qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		// strip out 'Bot Generated' and any outer white space / line feed
		$note = trim(str_replace('Bot Generated','',$r['notes']));
		if ($note) {
			$notes[] = array('user'=>'','date'=>'n/a','note'=>str_replace(chr(10),'<BR>',utf8_encode($note)));
		}
	}

	if (count($notes)==0) {
		$notes[] = array('user'=>'Brian','date'=>'8/5/16 5:07pm','note'=>'I will haunt you guys forever');
		$notes[] = array('user'=>'Chris','date'=>'4/21/16 10:07am','note'=>'You guys are bogus!!');
		$notes[] = array('user'=>'David','date'=>'3/13/16 17:35am','note'=>'This is a test');
		$notes[] = array('user'=>'Sam','date'=>'2/13/16 11:30am','note'=>'Sell this or die!');
	}

	echo json_encode(array('results'=>$notes));
	exit;
