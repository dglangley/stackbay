<?php
	function getFreightAccount($accountid,$default="PREPAID") {
		if (! $accountid) { return ($default); }

		$query = "SELECT * FROM freight_accounts WHERE id = '".res($accountid)."'; ";
		$result = qedb($query);
		if (qnum($result)==0) { return ($default); }
		$r = qrow($result);
		return ($r['account_no']);
	}
?>
