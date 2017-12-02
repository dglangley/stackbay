<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

	$debug = 0;

	function setTimesheet($userid,$in,$out,$taskid,$task_label,$rate,$notes,$modifier,$modified) {
		$notes = trim($notes);

		$query = "INSERT INTO timesheets (userid, clockin, clockout, taskid, task_label, rate, notes) ";
		$query .= "VALUES (".fres($userid).", ".fres($in).", ".fres($out).", ".fres($taskid).", ";
		$query .= fres($task_label).", ".fres($rate).", ".fres($notes)."); ";
		if ($GLOBALS['debug']) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		$timesheetid = qid();

		// do the timesheet approval here with modifier data?
		$query = " ".fres($modifier).", ".fres($modified)."); ";
//		if ($GLOBALS['debug']) { echo $query.'<BR>'; }
//		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }

		return ($timesheetid);
	}
	function setTimesheetMap($oldid,$newid) {
		$query = "INSERT INTO maps_timesheet (BDB_id, timesheets_id) VALUES ('".$oldid."', '".$newid."'); ";
		if ($GLOBALS['debug']) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
	}
	function setMileage($item_id,$item_id_label,$userid,$datetime,$mileage,$mileage_rate) {
		$expense_date = substr($datetime,0,10);

		$query = "INSERT INTO expenses (item_id, item_id_label, companyid, expense_date, description, ";
		$query .= "categoryid, units, amount, file, userid, datetime) ";
		$query .= "VALUES (".fres($item_id).", ".fres($item_id_label).", NULL, ".fres($expense_date).", ";
		$query .= "'Mileage Reimbursement', 1, ".fres($mileage).", ".fres($mileage_rate).", NULL, ".fres($userid).", ".fres($datetime)."); ";
		if ($GLOBALS['debug']) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
	}

	$mileage_rate = 0.54;
	$query = "SELECT * FROM services_techtimesheet WHERE datetime_in >= '2017-01-01 00:00:00' ";
//	$query .= "LIMIT 0,10 ";
	$query .= "; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$taskid = mapJob($r['job_id']);
		if (! $taskid) { continue; }

		// check if this is mapped already
		$query2 = "SELECT * FROM maps_timesheet WHERE BDB_id = '".$r['id']."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) { continue; }

		$userid = mapUser($r['user_id']);
		$modifier = mapUser($r['modified_by_id']);
		if (! $userid AND $modifier) { $userid = $modifier; }

		if (! $userid) { continue; }

		$timesheetid = setTimesheet($userid,$r['datetime_in'],$r['datetime_out'],$taskid,'service_item_id',$r['tech_rate'],$r['notes'],$modifier,$r['modified']);

		setTimesheetMap($r['id'],$timesheetid);

		if ($r['mileage']>0) {
			setMileage($taskid,'service_item_id',$userid,$r['datetime_out'],$r['mileage'],$mileage_rate);
		}
	}

	echo 'COMPLETE!<BR>';
?>
