<?php
	function setFreightAccount($account_no,$carrierid=0,$companyid=0) {
		$id = 0;
		$account_no = str_replace('PREPAID','',strtoupper(trim($account_no)));

		if (! $account_no) { return ($id); }

		$query = "SELECT id FROM freight_accounts ";
		$query .= "WHERE account_no = '".res($account_no)."' AND carrierid = '".res($carrierid)."' AND companyid = '".res($companyid)."'; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$r = qrow($result);
			return ($r['id']);
		}

		$query = "REPLACE freight_accounts (account_no, carrierid, companyid) ";
		$query .= "VALUES ('".res($account_no)."','".res($carrierid)."','".res($companyid)."'); ";
		$result = qedb($query);
		$id = qid();

		return ($id);
	}
?>
