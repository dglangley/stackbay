<?php

	function getFinancialAccounts() {
		$accounts = array();

		$query = "SELECT * FROM finance_accounts;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$accounts[] = $r;
		}

		return $accounts;
	}

	function getFinanceName($financeid) {
		$name = '';

		$query = "SELECT nickname FROM finance_accounts WHERE id = ".fres($financeid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$name = $r['nickname'];
		}

		return $name;
	}