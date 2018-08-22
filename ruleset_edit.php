<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$DEBUG = 0;
	$ALERT = '';

	// This is just a flat add to the rulesets if the user chooses to create a ruleset manually
	function addRuleset($name){
		$query = "INSERT INTO rulesets (name) VALUES (".fres($name).");";
		qedb($query);

		return qid();
	}

	// Integrate Edit and Insert into here and rule out addRuleset function
	function editRuleset($rulesetid, $name, $market_table, $favorites, $start_date, $end_date, $range_num, $range_period, $keyword, $min_records, $max_records, $min_price, $max_price, $min_stock, $max_stock, $min_sum, $max_sum, $companyid){
		$query = "REPLACE INTO rulesets (name, market, favorites, start_date, end_date, range_num, range_period, ";
		$query .= "keyword, min_records, max_records, min_price, max_price, min_stock, max_stock, min_sum, max_sum, companyid ";
		if($rulesetid) {$query .= ", id ";}
		$query .= ") ";
		$query .= "VALUES (".fres($name).", ".fres($market_table).", ".res(($favorites ? 1 : 0)).", ".fres(format_date($start_date,'Y-m-d')).", ".fres(format_date($end_date,'Y-m-d')).", ";
		$query .= fres($range_num).", ".fres($range_period).", ";
		$query .= fres($keyword).", ".fres($min_records).", ".fres($max_records).", ".fres($min_price).", ".fres($max_price).", ".fres($min_stock).", ".fres($max_stock).", ".fres($min_sum).", ".fres($max_sum).", ".fres($companyid);
		if($rulesetid) {$query .= ", ".res($rulesetid);}
		$query .= ");";
		qedb($query);

		return qid();
	}

	function editRulesetActions($rulesetid, $action, $days, $time, $options, $max_lines){
		// For now limit to only 1 action oer ruleset
		$query = "DELETE FROM ruleset_actions WHERE rulesetid = ".res($rulesetid).";";
		qedb($query);

		$query = "INSERT INTO ruleset_actions (rulesetid, action, days, time, options, max_lines) VALUES (
			".res($rulesetid).",
			".fres($action).",
			".fres($days).",
			".fres($time).",
			".fres($options).",
			".fres($max_lines)."
		);";

		qedb($query);
	}

	function deleteRuleset($rulesetid) {
		global $USER_ROLES, $ALERT;

		// Make sure user has the correct privilege
		if(array_intersect($USER_ROLES,array(1,4))) {
			$query = "DELETE FROM rulesets WHERE id = ".fres($rulesetid).";";
			qedb($query);

			$query = "DELETE FROM ruleset_actions WHERE rulesetid = ".fres($rulesetid).";";
			qedb($query);
		} else {
			$ALERT = "You do not have permission to delete a ruleset!";
		}
	}
	
	$name = '';
	if (isset($_REQUEST['name'])) { $name = trim($_REQUEST['name']); }
	$start_date = '';
	if (isset($_REQUEST['START_DATE'])) { $start_date = trim($_REQUEST['START_DATE']); }
	$end_date = '';
	if (isset($_REQUEST['END_DATE'])) { $end_date = trim($_REQUEST['END_DATE']); }
	$range_num = '';
	if (isset($_REQUEST['range_num'])) { $range_num = trim($_REQUEST['range_num']); }
	$range_period = '';
	if (isset($_REQUEST['range_period'])) { $range_period = trim($_REQUEST['range_period']); }
	if ($range_num AND $range_period) {
		$start_date = '';
		$end_date = '';
	}
	$keyword = '';
	if (isset($_REQUEST['keyword'])) { $keyword = trim($_REQUEST['keyword']); }
	$min_records = '';
	if (isset($_REQUEST['min_records'])) { $min_records = trim($_REQUEST['min_records']); }
	$max_records = '';
	if (isset($_REQUEST['max_records'])) { $max_records = trim($_REQUEST['max_records']); }
	$min_price = '';
	if (isset($_REQUEST['min_price'])) { $min_price = trim($_REQUEST['min_price']); }
	$max_price = '';
	if (isset($_REQUEST['max_price'])) { $max_price = trim($_REQUEST['max_price']); }
	$min_stock = '';
	if (isset($_REQUEST['min_stock'])) { $min_stock = trim($_REQUEST['min_stock']); }
	$max_stock = '';
	if (isset($_REQUEST['max_stock'])) { $max_stock = trim($_REQUEST['max_stock']); }
	$min_sum = '';
	if (isset($_REQUEST['min_sum'])) { $min_sum = trim($_REQUEST['min_sum']); }
	$max_sum = '';
	if (isset($_REQUEST['max_sum'])) { $max_sum = trim($_REQUEST['max_sum']); }
	$favorites = '';
	// if (isset($_REQUEST['favorites'])) { $favorites = trim($_REQUEST['favorites']); }
	$companyid = '';
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }

	$market_table = 'Demand';
	if (isset($_REQUEST['market_table'])) { $market_table = trim($_REQUEST['market_table']); }
	
	$rulesetid = '';
	if (isset($_REQUEST['rulesetid'])) { $rulesetid = trim($_REQUEST['rulesetid']); }

	$delete = '';
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }

	$ruleset_action = false;
	if (isset($_REQUEST['ruleset_action'])) { $ruleset_action = trim($_REQUEST['ruleset_action']); }

	$action = '';
	if (isset($_REQUEST['action'])) { $action = trim($_REQUEST['action']); }
	$options = '';
	if (isset($_REQUEST['options'])) { $options = trim($_REQUEST['options']); }
	$days = '';
	if (isset($_REQUEST['days'])) { $days = trim($_REQUEST['days']); }
	$time = '';
	if (isset($_REQUEST['timestamp'])) { $time = trim($_REQUEST['timestamp']); }
	$max_lines = '';
	if (isset($_REQUEST['max_lines'])) { $max_lines = trim($_REQUEST['max_lines']); }

	// $rulesetid = addRuleset($name);

	if($delete) {
		deleteRuleset($delete);
		header('Location: /ruleset_actions.php' . ($ALERT?'?ALERT='.$ALERT:''));

		exit;
	}

	if($ruleset_action) {
		editRulesetActions($rulesetid, $action, $days, $time, $options, $max_lines);
		if ($DEBUG) { exit; }

		header('Location: /ruleset_actions.php' . ($rulesetid ? '?rulesetid=' . $rulesetid : '') . ($ALERT?'?ALERT='.$ALERT:''));
		exit;
	} else {
		$rulesetid = editRuleset($rulesetid, $name, $market_table , $favorites, $start_date, $end_date, $range_num, $range_period, $keyword, $min_records, $max_records, $min_price, $max_price, $min_stock, $max_stock, $min_sum, $max_sum, $companyid);
		if ($DEBUG) { exit; }

		// Edit ruleset happens on the miner page so make the redirect accordingly
		header('Location: /ruleset_actions.php?rulesetid='.$rulesetid.($ALERT?'&ALERT='.$ALERT:''));
		exit;
	}
?>
