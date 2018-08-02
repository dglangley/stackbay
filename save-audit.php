<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;
	$ALERT = '';

	function editAudits($audits, $locationid) {
		$init = true;

		$audit_id = 0;

		foreach($audits as $partid => $qoh){
			if($init) {
				$query = "INSERT INTO location_audits (locationid, datetime, userid) VALUES (".res($locationid).", ".fres($GLOBALS['now']).", ".res($GLOBALS['U']['id']).");";
				qedb($query);

				$audit_id = qid();
				$init = false;
			}

			if($audit_id) {
				// create the record into inventory_audit table
				$query = "INSERT INTO inventory_audits (auditid, partid, qty) VALUES (".res($audit_id).", ".res($partid).", ".res(($qoh?:0)).");";
				qedb($query);
			}
		}

		return $audit_id;
	}

	$audits = array();
	if(isset($_REQUEST['qoh'])) {
		$audits = $_REQUEST['qoh'];
	}

	$locationid = 0;
	if(isset($_REQUEST['locationid'])) {
		$locationid = $_REQUEST['locationid'];
	}

	$auditid = 0;
	if(! empty($audits) AND $locationid) {
		$audit_id = editAudits($audits, $locationid);
	}
	
	header('Location: /audit.php' . ($audit_id?'?auditid='.$audit_id:($locationid ? '?locationid=' . $locationid : '')) . ($ALERT?'&ALERT='.$ALERT:''));

	exit;

	if ($DEBUG) { exit; }

	?>