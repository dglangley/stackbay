<?php
	function getCommRate($companyid,$rep_id=0,$start_date='',$end_date='',$commid=0) {
		$rates = array();

		$query = "SELECT rep_id, rate FROM commission_rates ";
		$query .= "WHERE status = 'Active' AND ((";
			if ($start_date) { $query .= "end_date >= '".res($start_date)."' OR "; }
			$query .= "end_date IS NULL) AND (";
			if ($end_date) { $query .= "start_date <= '".res($end_date)."' OR "; }
			$query .= "start_date IS NULL";
		$query .= ")) ";
/*
		$query .= "AND (companies IS NULL OR companies = '".res($companyid)."' OR companies RLIKE '^".res($companyid).",' ";
		$query .= "OR companies RLIKE ',".res($companyid)."$' OR companies RLIKE ',".res($companyid).",') ";
*/
		$query .= "AND (companies IS NULL OR companies = '0' OR companies RLIKE ',".res($companyid).",') ";
		if ($commid) { $query .= "AND id <> '".res($commid)."' "; }
		if ($rep_id) { $query .= "AND rep_id = '".res($rep_id)."' "; }
		$query .= "; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$rates[$r['rep_id']] = $r['rate'];
		}

		if ($rep_id) {
			return ($rates[$rep_id]);
		} else {
			return ($rates);
		}
	}
?>
