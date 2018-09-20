<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$DEBUG = 0;

	$userid = 0;
	if (isset($_REQUEST['userid']) AND is_numeric($_REQUEST['userid']) AND $_REQUEST['userid']>0) {
		$userid = $_REQUEST['userid'];
	}

	$commid = 0;
	if (isset($_REQUEST['id']) AND is_numeric($_REQUEST['id']) AND $_REQUEST['id']>0) {
		$commid = $_REQUEST['id'];
	}

	$rate = '';
	if (isset($_REQUEST['rate'])) { $rate = trim($_REQUEST['rate']); }

	$start_date = '';
	if (isset($_REQUEST['start_date'])) { $start_date = format_date(trim($_REQUEST['start_date']),'Y-m-d H:i:s'); }

	$end_date = '';
	if (isset($_REQUEST['end_date'])) { $end_date = format_date(trim($_REQUEST['end_date']),'Y-m-d H:i:s'); }

	$subtract_base = '';
	if (isset($_REQUEST['subtract_base'])) { $subtract_base = trim($_REQUEST['subtract_base']); }

	$subtract_team = '';
	if (isset($_REQUEST['subtract_team'])) { $subtract_team = trim($_REQUEST['subtract_team']); }

	$companies = '';
	if (isset($_REQUEST['companies']) AND is_array($_REQUEST['companies'])) {
		foreach ($_REQUEST['companies'] as $companyid) {
			if ($companies) { $companies .= ','; }
			$companies .= $companyid;
		}
	}

	$status = 'Active';
	if (isset($_REQUEST['status'])) { $status = trim($_REQUEST['status']); }

	$query = "REPLACE commission_rates (rep_id, rate, start_date, end_date, subtract_base, subtract_team, companies, status";
	if ($commid) { $query .= ", id"; }
	$query .= ") VALUES ('".res($userid)."', ".fres($rate).", ".fres($start_date).", ".fres($end_date).", ";
	$query .= fres($subtract_base).", ".fres($subtract_team).", ".fres($companies).", '".res($status)."'";
	if ($commid) { $query .= ", '".res($commid)."'"; }
	$query .= "); ";
	$result = qedb($query);

	if ($DEBUG) { exit; }

	header('Location: comm_rates.php?userid=1');
	exit;
?>
