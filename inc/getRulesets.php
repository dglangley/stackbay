<?php
	include_once $_SERVER["ROOT_DIR"]."/inc/dbconnect.php";

	function getRulesets() {
		$rulesets = array();

		$query = "SELECT * FROM rulesets";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$rulesets[] = $r;
		}

		return $rulesets;
	}

	function getRulesetActions($rulesetid) {
		$actions = array();

		$query = "SELECT * FROM ruleset_actions WHERE rulesetid = ".res($rulesetid)." LIMIT 1;";
		$result = qedb($query);

		// For now set it to only 1 result
		while($r = mysqli_fetch_assoc($result)) {
			$actions = $r;
		}

		return $actions;
	}

	function getRuleset($rulesetid) {
		$ruleset = array();

		$query = "SELECT * FROM rulesets WHERE id = ".res($rulesetid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$ruleset = $r;
		}

		return $ruleset;
	}