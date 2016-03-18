<?php
	$COMPANIES = array();
	function getCompany($search_field,$input_field='id',$output_field='name',$add_new=false) {
		global $COMPANIES;

		$search_field = trim($search_field);
		if (! $search_field) { return (''); }

		if ($output_field=='companyid') { $output_field = 'id'; }

		if (isset($COMPANIES[$search_field]) AND isset($COMPANIES[$search_field][$input_field])) {
			return ($COMPANIES[$search_field][$input_field][$output_field]);
		}

		$num_results = 0;

		if ($add_new) {
			$search_field = trim($search_field);

			// check for existing
			$query = "SELECT id FROM companies WHERE name = '".res($search_field)."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				return ($r['id']);
			}

			$query = "INSERT INTO companies (name) VALUES ('".res($search_field)."'); ";
			$result = qdb($query);
			return (qid());
		}

		$query = "SELECT * FROM companies WHERE $input_field = '".res($search_field)."'; ";
		$result = qdb($query);
		$num_results = mysqli_num_rows($result);
		if ($num_results==0) {
			if ($input_field=='name') {
				$find_companyid = abbrevName($search_field);
				if ($find_companyid>0) {
					$query = "SELECT * FROM companies WHERE id = '$find_companyid'; ";
					$result = qdb($query);
					$num_results = mysqli_num_rows($result);
				}
			}
			if ($num_results==0) {
				$query2 = "SELECT * FROM company_aliases WHERE name = '".res($search_field)."'; ";
				$result2 = qdb($query2);
				if (mysqli_num_rows($result2)==1) {
					$r2 = mysqli_fetch_assoc($result2);

					$query = "SELECT * FROM companies WHERE id = '".res($r2['companyid'])."'; ";
					$result = qdb($query);
					$num_results = mysqli_num_rows($result);
				}
			}

			if ($num_results==0) { return (0); }
		}
		$r = mysqli_fetch_assoc($result);

		if (! $r['default_email']) {
			$query2 = "SELECT * FROM users WHERE companyid = '".res($r['id'])."' ";
			$query2 .= "AND login_email IS NOT NULL AND login_email <> '' AND rfq = 'T'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$r['default_email'] = $r2['login_email'];
			}
		}

		// add all aliases for this company
		$r['aliases'] = array();
		$query2 = "SELECT * FROM company_aliases WHERE companyid = '".res($r['id'])."'; ";
		$result2 = qdb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$r['aliases'][$r2['id']] = $r2['name'];
		}

		$COMPANIES[$search_field] = array($input_field=>$r);

		return ($r[$output_field]);
	}

	function addCompany(&$company) {
		$company = trim($company);
		if (! $company) { return 7; }

		$companyid = abbrevName($company);
		if ($companyid) { return ($companyid); }

		$query = "INSERT INTO companies (name) VALUES ('".res($company)."') ";
		if ($GLOBALS['test']) { echo "$query<BR>"; }
		else { $result = qdb($query); }
		return (qid());
	}

	function abbrevName(&$company) {
		$try_company = trim(preg_replace('/[,]?[[:space:]]?(inc|ab|bv|ltd|us|ag|limited|l[.]?l[.]?c|plc|technologies|corp|corporation|telecom|e\\.K|\\([A-Z]+\\))?[.]*[[:space:]]*$/i','',$company));

		if (strtolower($company)=='commsource telecommunication supply') {
			$company = trim(str_replace('Telecommunication Supply','',$company));
		} else if (strtolower($company)=='e & m communications') {
			$company = 'E&M Communications';
		}

		if ($try_company<> '' AND $try_company<>$company) {
			$company = $try_company;

			$query = "SELECT * FROM companies WHERE name = '".res($try_company)."' ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				return ($r['id']);
			}

//			$company = $try_company;
		}
		return false;//($company);
	}

	function setCompany($input_name='companyid') {
		$companyid = 0;
		if (isset($_REQUEST[$input_name]) AND trim($_REQUEST[$input_name])) {
			$new_company = false;
			// check that this is a legitimate company by passing in id and asking for it back; this way, even an
			// all-numeric company NAME can be created here...
			if (is_numeric($_REQUEST[$input_name])) {
				$companyid = getCompany($_REQUEST[$input_name],'id','id');
				if (! $companyid OR $companyid<>$_REQUEST[$input_name]) { $new_company = true; }
			} else {
				$new_company = true;
			}
			if ($new_company) {
				$companyid = getCompany(trim($_REQUEST[$input_name]),'name','id',true);
			}
		}

		return ($companyid);
	}
?>
