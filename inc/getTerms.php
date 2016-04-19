<?php
	function getTerms($companyid,$system='AR') {
		$terms = array(array('terms'=>'Select Terms'));
		$query = "SELECT * FROM company_terms, terms ";
		$query .= "WHERE companyid = '".res($companyid)."' AND category = '".res($system)."' ";
		$query .= "AND company_terms.termsid = terms.id ORDER BY type, terms; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) {
			$query = "SELECT id termsid, terms FROM terms ORDER BY type, terms; ";
			$result = qdb($query);
		}
		while ($r = mysqli_fetch_assoc($result)) {
			$terms[$r['termsid']] = $r;
		}

		return ($terms);
	}
?>
