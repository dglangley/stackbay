<?php
	$TERMS = array();
	function getTerms($str,$input = 'terms',$return = "id"){
		global $TERMS;

		$str = strtoupper($str);
		$input = strtolower($input);

		if (isset($TERMS[$str]) AND isset($TERMS[$str][$input])) {
			return ($TERMS[$str][$input][$return]);
		}

		// get ALL terms and apply the $input type string as the input $str for each result
		$query = "SELECT * FROM terms; ";// WHERE $input LIKE '".res($str)."'; ";
		$result = qdb($query) or die(qe()."<BR>".$query);
		if (mysqli_num_rows($result)==0) { return false; }

		while ($r = mysqli_fetch_assoc($result)) {
			$TERMS[$r[$input]][$input] = $r;
		}
		return ($TERMS[$str][$input][$return]);
	}

	function getCompanyTerms($companyid,$system='AR') {
		$terms = array();//array('terms'=>'Select Terms'));

		$query = "SELECT * FROM company_terms, terms ";
		$query .= "WHERE companyid = '".res($companyid)."' AND category = '".res($system)."' ";
		$query .= "AND company_terms.termsid = terms.id ORDER BY IF(type='Credit',0,1), days; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) {
			$query = "SELECT id termsid, terms FROM terms ORDER BY IF(type='Credit',0,1), days; ";
			$result = qdb($query);
		}
		while ($r = mysqli_fetch_assoc($result)) {
			$terms[$r['termsid']] = $r;
		}

		return ($terms);
	}
?>
