<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/pipe.php';
	include_once '../inc/format_date.php';
	include_once '../inc/format_price.php';
	include_once '../inc/getUser.php';
	include_once '../inc/getPart.php';
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
	$userid = $U['id'];
	$hours_sec = 60*60;
	$days_sec = $hours_sec*24;

	/***** GET NOTIFICATIONS ONLY *****/
	// if no part/pipe ids passedin, give back all notes related to new/unread notifications
	if (! $pipe_ids AND ! $partid) {
		$query = "SELECT prices.datetime, prices.note, part, heci, contacts.name, ";
		$query .= "read_datetime, click_datetime, notifications.id, prices.partid ";
		$query .= "FROM notifications, prices, parts, users, contacts ";
		$query .= "WHERE notifications.userid = '".$userid."' AND notifications.partid = prices.partid ";
		$query .= "AND parts.id = notifications.partid AND contacts.id = users.contactid AND users.id = prices.userid ";
		$query .= "ORDER BY prices.datetime DESC LIMIT 0,20; ";
		$result = qdb($query) OR reportError(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
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
		
		//Sabedra = 13
		//Hack notification for purchase request to create a PO
		if($U['id'] == '6') {
			//Code generated specifically for Notifications of Purchase Requests
			$query = "SELECT *, ri.id as repair_item_id, n.partid as part_string FROM notifications n, purchase_requests r, parts, contacts, users, repair_items ri WHERE n.userid = ".$U['id']." AND parts.id = n.partid AND n.partid = r.partid AND users.id = n.userid AND contacts.id = users.contactid AND po_number IS NULL AND ri.ro_number = r.ro_number ORDER BY requested DESC LIMIT 0,20;";
			$result = qdb($query) OR reportError(qe().' '.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$r['part_label'] = 'Part: ' . $r['part'];
				if ($r['heci']) {
					$r['search'] = $r['part_string'];
				} else {
					$parts = explode(' ',$r['part']);
					$r['search'] = $r['part_string'];
				}
				$name = explode(' ',$r['name']);
				$r['name'] = $name[0];
				$r['datetime'] = utf8_encode($r['requested']);
				$r['read'] = '';
				if ($r['read_datetime']) { $r['read'] = 'T'; }
				$r['viewed'] = '';
				if ($r['click_datetime']) { $r['viewed'] = 'T'; }
				$r['note'] = utf8_encode('requested for Repair# ' . $r['ro_number']);

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

				//$r['repair_item_id'] = $r['ro_number'];

				$r['type'] = "purchase_request";
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
		}

		//Rathna = 16
		//Hack notification to tell the tech who requested an item that the order has been ordered and received into stock
		if($U['id'] == '16') {
			//Code generated specifically for Notifications of Purchase Requests
			$query = "SELECT *, ri.id as repair_item_id, n.partid as part_string FROM notifications n, purchase_requests r, parts, contacts, users, repair_items ri WHERE n.userid = ".$U['id']." AND parts.id = n.partid AND n.partid = r.partid AND users.id = n.userid AND contacts.id = users.contactid AND ri.ro_number = r.ro_number ORDER BY requested DESC LIMIT 0,20;";
			$result = qdb($query) OR reportError(qe().' '.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$r['part_label'] = 'Component: ' . $r['part'];
				if ($r['heci']) {
					$r['search'] = $r['part_string'];
				} else {
					$parts = explode(' ',$r['part']);
					$r['search'] = $r['part_string'];
				}
				$name = explode(' ',$r['name']);
				$r['name'] = $name[0];
				$r['datetime'] = utf8_encode($r['requested']);
				$r['read'] = '';
				if ($r['read_datetime']) { $r['read'] = 'T'; }
				$r['viewed'] = '';
				if ($r['click_datetime']) { $r['viewed'] = 'T'; }
				$r['note'] = utf8_encode('received for Repair# ' . $r['ro_number']);

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

				$r['repair_item_id'] = $r['ro_number'];
				$r['type'] = "purchase_received";
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
		}

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
			if ($partid) {
				$query = "INSERT INTO prices (partid, price, datetime, note, userid) ";
				$query .= "VALUES ('".$partid."',NULL,'".$now."','".res($add_notes)."','".$userid."'); ";
				$result = qdb($query) OR reportError('Sorry, there was an error adding your note to the new db. Please notify Admin immediately!');

				// after entering notes, add notifications to the other members of this user's team (for now, just add for sam and chris)
				$team_users = array(getUser('Chris Bumgarner','name','userid'),getUser('Sam Campa','name','userid'),getUser('David Langley','name','userid'));
				foreach ($team_users as $each_userid) {
					if ($userid==$each_userid) { continue; }
					$query = "INSERT INTO notifications (partid, userid, read_datetime, click_datetime) ";
					$query .= "VALUES ('".$partid."','".$each_userid."',NULL,NULL); ";
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
	if (! $add_notes AND $userid AND $partid) {
		$query = "SELECT * FROM notifications WHERE userid = '".res($userid)."' AND partid = '".res($partid)."' ";
		$query .= "AND (read_datetime IS NULL OR click_datetime IS NULL); ";
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

	// get pricing notes from new system next
	if ($partid) {
		$query = "SELECT note, price, datetime, userid FROM prices WHERE partid = '".res($partid)."' ORDER BY datetime DESC LIMIT 0,30; ";
		$result = qdb($query) OR reportError('Sorry, there was an error getting notes from the new db. Please see Admin immediately!');
		while ($r = mysqli_fetch_assoc($result)) {
			$note = trim($r['note']);
			if ($r['price']) { $note = 'Price: '.format_price($r['price'],true).chr(10).$note; }
			$notes[] = array('user'=>getUser($r['userid']),'date'=>format_date($r['datetime'],'n/j/y g:ia'),'note'=>str_replace(chr(10),'<BR>',utf8_encode($note)));
		}
	}

	if ($pipe_ids) {
		$query = "SELECT CONCAT(notes,'\n',part_of) notes FROM inventory_inventory WHERE id IN (".$pipe_ids.") AND REPLACE('Bot Generated','',notes) <> ''; ";
		$result = qdb($query,'PIPE') OR reportError('Sorry, there was an error getting notes through the PIPE. Please see Admin immediately!');//qe('PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// strip out 'Bot Generated' and any outer white space / line feed
			$note = trim(str_replace('Bot Generated','',$r['notes']));
			if ($note) {
				$notes[] = array('user'=>'','date'=>'n/a','note'=>str_replace(chr(10),'<BR>',utf8_encode($note)));
			}
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
