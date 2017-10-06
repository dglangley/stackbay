<?php
	function getFreightAccount($accountid) {
		if (! $accountid) { return ("PREPAID"); }

		$query = "SELECT * FROM freight_accounts WHERE id = '".res($accountid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return ("PREPAID"); }
		$r = mysqli_fetch_assoc($result);
		return ($r['account_no']);
	}
?>
