<?php

	function getFinancialAccounts($filter) {
		$accounts = array();

		$query = "SELECT *, fa.id as accountid FROM finance_accounts fa, finance_types ft ";
		$query .= "WHERE ft.id = fa.type_id ";
		if($filter) {
			$query .= "AND ft.type =".fres($filter)." ";
		}
		$query .= ";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$accounts[] = $r;
		}

		return $accounts;
	}

	function getFinanceName($financeid) {
		$name = '';

		$query = "SELECT * FROM finance_accounts WHERE id = ".fres($financeid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$name = $r['bank'] . ' ' . $r['nickname'] . ' ' . $r['account_number'];
		}

		return $name;
	}