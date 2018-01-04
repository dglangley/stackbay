<?php
	function mapJob($jobid, $type) {
		$taskid = 0;

		$query = '';
		if($type == 'co') {
			$query = "SELECT * FROM maps_job_co WHERE BDB_jid = '".res($jobid)."'; ";
		} else {
			$query = "SELECT * FROM maps_job WHERE BDB_jid = '".res($jobid)."'; ";
		}
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$taskid = $r['service_item_id'];
		}

		return ($taskid);
	}

		$USER_MAP = array(
			2 => 8,/*scott*/
			5 => 26,/*rharmon*/
			6 => 0,/*sthiroux*/
			17 => 0,/*cnesby*/
			29 => 0,/*carlos*/
			48 => 24,/*alexb*/
			51 => 29,/*matth*/
			52 => 27,/*michael*/
			53 => 25,/*mursprung*/
			55 => 28,/*peter*/
			56 => 23,/*damon*/
		);
	function mapUser($userid) {
		global $USER_MAP;

		$newid = 0;
		if (! $userid) { return ($newid); }

		if (isset($USER_MAP[$userid])) { $newid = $USER_MAP[$userid]; }

		return ($newid);
	}
?>
