<?php
	function mapJob($jobid) {
		$taskid = 0;

		$query = "SELECT * FROM maps_job WHERE BDB_jid = '".res($jobid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$taskid = $r['service_item_id'];
		}

		return ($taskid);
	}
	$USER_MAP = array(
		5 => 21,/*rharmon*/
		6 => 22,/*sthiroux*/
		17 => 23,/*cnesby*/
		29 => 24,/*carlos*/
		48 => 25,/*alexb*/
		51 => 26,/*matth*/
		52 => 27,/*michael*/
		53 => 28,/*mursprung*/
		55 => 29,/*peter*/
		56 => 30,/*damon*/
	);
	function mapUser($userid) {
		global $USER_MAP;

		$newid = 0;
		if (! $userid) { return ($newid); }

		if (isset($USER_MAP[$userid])) { $newid = $USER_MAP[$userid]; }

		return ($newid);
	}
?>
