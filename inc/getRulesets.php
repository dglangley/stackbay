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