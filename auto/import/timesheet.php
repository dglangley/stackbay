<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	function mapJob($jobid) {
		$taskid = 0;

		$query = "SELECT * FROM maps_job WHERE job = '".res($jobid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$taskid = $r['id'];
		}

		return ($taskid);
	}
	function mapUser($userid) {
		return (0);
	}
	function setTimesheet($userid,$in,$out,$taskid,$task_label,$notes,$modifier,$modified) {
		$notes = trim($notes);

		$query = "INSERT INTO timesheets (userid, clockin, clockout, taskid, task_label, rate, notes) ";
		$query .= "VALUES (".fres($userid).", ".fres($in).", ".fres($out).", ".fres($taskid).", ";
		$query .= fres($task_label).", ".fres($notes).", ".fres($modifier).", ".fres($modified)."); ";
echo $query.'<BR>';
//		$result = qdb($query) OR die(qe().'<BR>'.$query);
	}
	function setMileage($item_id,$item_id_label,$userid,$datetime,$mileage,$mileage_rate) {
		$expense_date = substr($datetime,0,10);

		$query = "INSERT INTO expenses (item_id, item_id_label, companyid, expense_date, description, ";
		$query .= "categoryid, units, amount, file, userid, datetime) ";
		$query .= "VALUES (".fres($item_id).", ".fres($item_id_label).", NULL, ".fres($expense_date).", ";
		$query .= "'Mileage', NULL, ".fres($mileage).", ".fres($mileage_rate).", NULL, ".fres($userid).", ".fres($datetime)."); ";
echo $query.'<BR>';
	}

	$mileage_rate = 0.54;
	$query = "SELECT * FROM services_techtimesheet WHERE datetime_in >= '2017-01-01 00:00:00'; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$taskid = mapJob($r['job_id']);
		$userid = mapUser($r['user_id']);
		$modifier = mapUser($r['modified_by_id']);

		$timesheetid = setTimesheet($userid,$r['datetime_in'],$r['datetime_out'],$r['notes'],$modifier,$r['modified']);

//		setTimesheetMap($r['id'],$timesheetid);

		if ($r['mileage']>0) {
			setMileage($taskid,'service_item_id',$userid,$r['datetime_out'],$r['mileage'],$mileage_rate);
		}
	}
?>
