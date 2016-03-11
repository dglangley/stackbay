<?php
	function setCompany($company_name) {
		$name = trim($C['name']);
		if (! $name) { return false; }

		if (substr($name,0,4)=='Add ') { $name = str_replace('...','',substr($name,4)); }

		// check for existing
		$query = "SELECT * FROM companies WHERE name = '".res($name)."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			return ($r['id']);
		}

		$query = "INSERT INTO companies (name) VALUES ('".res($name)."'); ";
		$result = qdb($query);
		return (qid());
	}
?>
