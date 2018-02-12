<?php
	function getReimbursements($userid,$start_date,$end_date,$reimbursed=1) {
		$total = 0;

		$query = "SELECT * FROM expenses ";
		$query .= "WHERE datetime >= '".res($start_date)."' AND datetime <= '".res($end_date)."' ";
		$query .= "AND reimbursement = '1' ";
		$query .= "; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$query2 = "SELECT * FROM reimbursements WHERE expense_id = '".$r['id']."'; ";
			$result2 = qedb($query2);
			if (($reimbursed AND qnum($result2)==0) OR (! $reimbursed AND qnum($result2)>0)) { continue; }

			$total += ($r['units']*$r['amount']);
		}


		return ($total);
	}
?>
