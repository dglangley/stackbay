<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/format_price.php';
	include_once '../inc/getUser.php';

	function reportError($err) {
		header("Content-Type: application/json", true);
		echo json_encode(array('message'=>$err));
		exit;
	}

	$messageid = 0;
	$partid = 0;

	if (isset($_REQUEST['messageid'])) {
		$messageid = str_replace('undefined','',$_REQUEST['messageid']);
	}

	if (isset($_REQUEST['partid'])) {
		$partid = str_replace('undefined','',$_REQUEST['partid']);
	}

	$notes = array();
	$userid = $U['id'];
	$hours_sec = 60*60;
	$days_sec = $hours_sec*24;

	/***** GET NOTIFICATIONS ONLY *****/
	// if no messageid passedin, give back all notes related to new/unread notifications
	if (! $messageid AND ! $partid) {
		$query = "SELECT messages.id as messageid, messages.datetime, messages.message as note, messages.link, contacts.name, ";
		$query .= "read_datetime, click_datetime, notifications.id, messages.ref_1, messages.ref_1_label ";
		$query .= "FROM notifications, users, contacts, messages ";
		$query .= "LEFT JOIN prices ON messageid = messages.id ";
		$query .= "WHERE notifications.userid = '".$userid."' AND notifications.messageid = messages.id ";
		$query .= "AND contacts.id = users.contactid AND users.id = messages.userid ";
		$query .= "GROUP BY messages.id ";
		$query .= "ORDER BY messages.datetime DESC LIMIT 0,20; ";

		$result = qdb($query) OR reportError(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {

			// Determine if the notification under suspicion is a partid or something else (ref_1_label)
			// This is a patch assuming not all messages have a partid associated with it
			if($r['ref_1_label'] == 'partid') {
				$query = "SELECT part, heci FROM parts WHERE id = ".res($r['ref_1']).";";
				$result2 = qdb($query) OR reportError(qe().' '.$query);

				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);

					$r['heci'] = $r2['heci'];
					$r['part'] = $r2['part'];
				}
			}

			$r['part_label'] = trim($r['part'].' '.$r['heci']);

			if ($r['heci']) {
				$r['search'] = substr($r['heci'],0,7);
			} else {
				$parts = explode(' ',$r['part']);
				$r['search'] = $parts[0];
			}
			$name = explode(' ',$r['name']);
			$r['name'] = $name[0];
			$r['datetime'] = utf8_encode($r['datetime']);
			$r['read'] = '';
			if ($r['read_datetime']) { $r['read'] = 'T'; }
			$r['viewed'] = '';
			if ($r['click_datetime']) { $r['viewed'] = 'T'; }
			$r['note'] = utf8_encode($r['note']);

			$secs_diff = strtotime($now)-strtotime($r['datetime']);
			if ($secs_diff>$days_sec) {
				$date1 = new DateTime(substr($r['datetime'],0,10));
				$date2 = new DateTime($today);
				$r['since'] = $date2->diff($date1)->format("%d");
				if ($r['since']==1) { $r['since'] .= ' day'; }
				else { $r['since'] .= ' days'; }
			} else if ($secs_diff>$hours_sec) {
				$r['since'] = floor($secs_diff/$hours_sec).' hours';
			} else {
				$r['since'] = floor($secs_diff/60).' mins';//mins
			}

			$r['type'] = '';
			//dispose of unneeded fields
			unset($r['part']);
			unset($r['heci']);
			unset($r['datetime']);
			unset($r['read_datetime']);
			unset($r['click_datetime']);
			$notes[] = $r;

			// mark this notification as read, but do not mark it as clicked
			$query2 = "UPDATE notifications SET read_datetime = '".$now."' WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		}

		header("Content-Type: application/json", true);
		echo json_encode(array('results'=>$notes));
		exit;
	}
	/***** END GET NOTIFICATIONS *****/

	// if any new notes to add, add them first
	$add_notes = '';
	if (isset($_REQUEST['add_notes'])) {
		$add_notes = iconv('UTF-8','ASCII//TRANSLIT',urldecode(trim($_REQUEST['add_notes'])));

		if (! $userid) {
			reportError('Sorry, you must be logged in before entering notes. If you are already logged in, your session may be invalid. Please notify Admin immediately!');
		}

		if ($add_notes) {
			if ($partid AND ! $messageid) {
				$link = '';

				// Query to generate the link for a sales page note
				$query = "SELECT part FROM parts WHERE id = ".res($partid).";";
				$result2 = qdb($query) OR reportError(qe().' '.$query);

				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$parts = explode(' ',$r2['part']);

					$link = '/sales.php?s='.$parts[0];
				}

				$query = "INSERT INTO messages (message, datetime, userid, link, ref_1, ref_1_label) ";
				$query .= "VALUES ('".res($add_notes)."','".$now."','".$userid."', '".res($link)."','".$partid."','partid'); ";
				qdb($query) OR reportError('Sorry, there was an error adding your note to the db. Please notify Admin immediately! ' . $query);
				$messageid = qid();

				$query = "INSERT INTO prices (messageid, price) ";
				$query .= "VALUES ('".$messageid."',NULL); ";
				$result = qdb($query) OR reportError('Sorry, there was an error adding your note to the db. Please notify Admin immediately! ' . $query);

				// after entering notes, add notifications to the other members of this user's team (for now, just add for sam and chris)
				$team_users = array(getUser('Chris Bumgarner','name','userid'),getUser('Sam Campa','name','userid'),getUser('David Langley','name','userid'),getUser('Andrew Kuan','name','userid'));
				foreach ($team_users as $each_userid) {
					if ($userid==$each_userid) { continue; }
					$query = "INSERT INTO notifications (messageid, userid, read_datetime, click_datetime) ";
					$query .= "VALUES ('".$messageid."','".$each_userid."',NULL,NULL); ";
					$result = qdb($query) OR reportError('Unfortunately, there was an error adding notifications for other users on your note. Please notify Admin immediately!');
				}
			} else {
				reportError('Sorry, your note could not be added due to a missing partid. Please notify Admin immediately.');
			}
//			$notes[] = array('user'=>'Bot','date'=>date("n/j/y g:ia"),'note'=>str_replace(chr(10),'<BR>',utf8_encode($add_notes)));
		}
	}

	// if there are any notifications related to this partid for this userid, mark as read; only do this
	// when no $add_notes are passed in
	if (! $add_notes AND $userid AND $messageid) {
		$query = "SELECT * FROM notifications n WHERE n.userid = '".res($userid)."' AND n.messageid = '".res($messageid)."' ";
		$query .= "AND (n.read_datetime IS NULL OR n.click_datetime IS NULL); ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$read = $r['read_datetime'];
			$viewed = $r['click_datetime'];
			if ($read AND $viewed) { continue; }
			if (! $read) { $read = $now; }
			if (! $viewed) { $viewed = $now; }

			$query2 = "UPDATE notifications SET read_datetime = '".$read."', click_datetime = '".$viewed."' ";
			$query2 .= "WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		}
	}

	// get pricing notes from new system
	if ($partid AND (! $messageid OR $add_notes)) {
		$query = "SELECT message as note, price, datetime, userid FROM prices p, messages m WHERE m.ref_1 = '".res($partid)."' AND m.ref_1_label = 'partid' AND m.id = p.messageid ORDER BY datetime DESC LIMIT 0,30;";
		$result = qdb($query) OR reportError('Sorry, there was an error getting notes from the db. Please see Admin immediately!');
		while ($r = mysqli_fetch_assoc($result)) {
			$note = trim($r['note']);
			if ($r['price']) { $note = 'Price: '.format_price($r['price'],true).chr(10).$note; }
			$notes[] = array('user'=>getUser($r['userid']),'date'=>format_date($r['datetime'],'n/j/y g:ia'),'note'=>str_replace(chr(10),'<BR>',utf8_encode($note)));
		}

		//echo $query; exit;
	}

	if (count($notes)==0) {
		$notes[] = array('user'=>'Brian','date'=>'8/5/16 5:07pm','note'=>'I will haunt you guys forever');
		$notes[] = array('user'=>'Chris','date'=>'4/21/16 10:07am','note'=>'You guys are bogus!!');
		$notes[] = array('user'=>'David','date'=>'3/13/16 17:35am','note'=>'This is a test');
		$notes[] = array('user'=>'Sam','date'=>'2/13/16 11:30am','note'=>'Sell this or die!');
	}

	header("Content-Type: application/json", true);
	echo json_encode(array('results'=>$notes));
	exit;
