<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCommRate.php';

	$DEBUG = 0;

	$userid = 0;
	if (isset($_REQUEST['userid']) AND is_numeric($_REQUEST['userid']) AND $_REQUEST['userid']>0) {
		$userid = $_REQUEST['userid'];
	}

	if (! $userid) {
		$ALERT = 'Invalid/missing user! Are you trying to confuse me here??';
		header('Location: user_management.php?ALERT='.urlencode($ALERT));
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

	$ALERTS = '';
	$companies = '';
	if (isset($_REQUEST['companies']) AND is_array($_REQUEST['companies'])) {
		foreach ($_REQUEST['companies'] as $companyid) {
			if (! $companyid) { continue; }

			// build for REPLACE query below
			$companies .= ','.$companyid;


			// run a check against every companyid to be sure we're not conflicting with another commission rate rule
			$rates = getCommRate($companyid,$userid,$start_date,$end_date,$commid);
/*
			$query = "SELECT * FROM commission_rates WHERE rep_id = '".res($userid)."' ";
			$query .= "AND ((";
				if ($start_date) { $query .= "end_date >= '".res($start_date)."' OR "; }
				$query .= "end_date IS NULL) OR (";
				if ($end_date) { $query .= "start_date <= '".res($end_date)."' OR "; }
				$query .= "start_date IS NULL";
			$query .= ")) ";
			$query .= "AND (companies IS NULL OR companies = '0' OR companies RLIKE ',".res($companyid).",') ";
			if ($commid) { $query .= "AND id <> '".res($commid)."' "; }
			$query .= "AND status = 'Active' ";
			$query .= "; ";
			$result = qedb($query);
			if (qnum($result)>0) {
*/
			if (count($rates)>0) {
				$ALERTS .= 'ALERTS[]=- Rate plan conflicts with another existing plan!';
			}
		}
	}

	if ($ALERTS) {
		if ($DEBUG) { die($ALERTS); }

		header('Location: comm_rates.php?userid='.$userid.'&commid=0&'.$ALERTS);
		exit;
	}

	$status = 'Active';
	if (isset($_REQUEST['status'])) { $status = trim($_REQUEST['status']); }

	// append with comma because all companyid's above are prepended with comma so that every companyid
	// is enclosed between commas
	if ($companies) { $companies .= ','; }

	$query = "REPLACE commission_rates (rep_id, rate, start_date, end_date, subtract_base, subtract_team, companies, status";
	if ($commid) { $query .= ", id"; }
	$query .= ") VALUES ('".res($userid)."', ".fres($rate).", ".fres($start_date).", ".fres($end_date).", ";
	$query .= fres($subtract_base).", ".fres($subtract_team).", ".fres($companies).", '".res($status)."'";
	if ($commid) { $query .= ", '".res($commid)."'"; }
	$query .= "); ";
	$result = qedb($query);

	if ($DEBUG) { exit; }

	header('Location: comm_rates.php?userid='.$userid);
	exit;
?>
